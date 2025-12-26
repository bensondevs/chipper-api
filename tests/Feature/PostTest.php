<?php

namespace Tests\Feature;

use App\Events\Post\PostCreated;
use App\Listeners\User\BroadcastFavoriteUserNewPost;
use App\Models\Favorite;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Post\FavoriteUserNewPost;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PostTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_guest_can_view_posts()
    {
        $user = User::factory()->create();
        Post::factory()->for($user, 'user')->create([
            'title' => 'Public Post',
            'body' => 'This is a public post.',
        ]);

        $response = $this->getJson(route('posts.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'body', 'user' => ['id', 'name']],
                ],
            ]);
    }

    public function test_posts_are_ordered_by_created_at_descending()
    {
        $user = User::factory()->create();
        $post1 = Post::factory()->for($user, 'user')->create(['created_at' => now()->subDay()]);
        $post2 = Post::factory()->for($user, 'user')->create(['created_at' => now()]);
        $post3 = Post::factory()->for($user, 'user')->create(['created_at' => now()->subHours(2)]);

        $response = $this->getJson(route('posts.index'));

        $response->assertOk();
        $posts = $response->json('data');
        
        $this->assertCount(3, $posts);
        $this->assertEquals($post2->id, $posts[0]['id']);
        $this->assertEquals($post3->id, $posts[1]['id']);
        $this->assertEquals($post1->id, $posts[2]['id']);
    }

    public function test_posts_include_user_relationship()
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $post = Post::factory()->for($user, 'user')->create();

        $response = $this->getJson(route('posts.index'));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'id' => $post->id,
                        'title' => $post->title,
                        'body' => $post->body,
                        'user' => [
                            'id' => $user->id,
                            'name' => 'John Doe',
                        ],
                    ],
                ],
            ]);
    }

    public function test_a_user_can_view_a_single_post()
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);
        $post = Post::factory()->for($user, 'user')->create([
            'title' => 'Single Post',
            'body' => 'This is a single post.',
        ]);

        $response = $this->actingAs($user)->getJson(route('posts.show', ['post' => $post]));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'title', 'body'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $post->id,
                    'title' => 'Single Post',
                    'body' => 'This is a single post.',
                ],
            ]);
    }

    public function test_a_guest_can_not_view_a_single_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create();

        $response = $this->getJson(route('posts.show', ['post' => $post]));

        $response->assertStatus(401);
    }

    public function test_a_guest_can_not_create_a_post()
    {
        $response = $this->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertStatus(401);
    }

    public function test_a_user_can_create_a_post()
    {
        Event::fake([PostCreated::class]);
        Notification::fake();

        $user = User::factory()->create();
        $follower = User::factory()->create();

        // Create a follower so the listener will send notifications
        $favorite = new Favorite();
        $favorite->user()->associate($follower);
        $favorite->favoritable()->associate($user);
        $favorite->save();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'body',
                ],
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Post',
                    'body' => 'This is a test post.',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
            'user_id' => $user->id,
        ]);

        $postId = Arr::get($response->json(), 'data.id');
        $post = Post::find($postId);

        // Assert PostCreated event was dispatched
        Event::assertDispatched(PostCreated::class, function ($event) use ($postId) {
            return $event->post->id === $postId;
        });

        // Manually trigger the listener to verify it runs
        // (Event::fake prevents listeners from running automatically)
        $event = new PostCreated($post);
        $listener = new BroadcastFavoriteUserNewPost();
        $listener->handle($event);

        // Assert listener ran by checking if notifications were sent
        Notification::assertSentTo(
            $follower,
            FavoriteUserNewPost::class,
            function ($notification) use ($postId, $follower) {
                $notificationData = $notification->toArray($follower);
                return $notificationData['post_id'] === $postId;
            }
        );
    }

    public function test_create_post_requires_title()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'body' => 'This is a test post.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_create_post_requires_body()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    }

    public function test_create_post_title_must_not_exceed_255_characters()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => str_repeat('a', 256),
            'body' => 'This is a test post.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_a_user_can_update_a_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Original title',
            'body' => 'Original body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($user)->putJson(route('posts.update', ['post' => $id]), [
            'title' => 'Updated title',
            'body' => 'Updated body.',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => 'Updated title',
                    'body' => 'Updated body.',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Updated title',
            'body' => 'Updated body.',
            'id' => $id,
        ]);
    }

    public function test_a_user_can_not_update_a_post_by_other_user()
    {
        $john = User::factory()->create(['name' => 'John']);
        $jack = User::factory()->create(['name' => 'Jack']);

        $response = $this->actingAs($john)->postJson(route('posts.store'), [
            'title' => 'Original title',
            'body' => 'Original body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($jack)->putJson(route('posts.update', ['post' => $id]), [
            'title' => 'Updated title',
            'body' => 'Updated body.',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'title' => 'Original title',
            'body' => 'Original body.',
            'id' => $id,
        ]);
    }

    public function test_update_post_requires_title()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user)->putJson(route('posts.update', ['post' => $post]), [
            'body' => 'Updated body.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_update_post_requires_body()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user)->putJson(route('posts.update', ['post' => $post]), [
            'title' => 'Updated title',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    }

    public function test_update_post_title_must_not_exceed_255_characters()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create();

        $response = $this->actingAs($user)->putJson(route('posts.update', ['post' => $post]), [
            'title' => str_repeat('a', 256),
            'body' => 'Updated body.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_a_user_can_destroy_one_of_his_posts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'My title',
            'body' => 'My body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($user)->deleteJson(route('posts.destroy', ['post' => $id]));

        $response->assertNoContent();

        $this->assertDatabaseMissing('posts', [
            'id' => $id,
        ]);
    }

    public function test_a_user_can_not_destroy_a_post_by_other_user()
    {
        $john = User::factory()->create(['name' => 'John']);
        $jack = User::factory()->create(['name' => 'Jack']);

        $post = Post::factory()->for($john, 'user')->create([
            'title' => 'John\'s Post',
            'body' => 'This is John\'s post.',
        ]);

        $response = $this->actingAs($jack)->deleteJson(route('posts.destroy', ['post' => $post]));

        $response->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'John\'s Post',
        ]);
    }

    public function test_a_guest_can_not_destroy_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create();

        $response = $this->deleteJson(route('posts.destroy', ['post' => $post]));

        $response->assertStatus(401);
    }
}
