<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_guest_can_not_favorite_a_post()
    {
        $post = Post::factory()->create();

        $this->postJson(
            route('favorites.store', ['post' => $post]),
        )->assertStatus(401);
    }

    public function test_a_user_can_favorite_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $post->getKey(),
            'favoritable_type' => Post::class,
        ]);
    }

    public function test_a_user_can_remove_a_post_from_his_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $post->getKey(),
            'favoritable_type' => Post::class,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $post->getKey(),
            'favoritable_type' => Post::class,
        ]);
    }

    public function test_a_user_can_not_remove_a_non_favorited_item()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNoContent();

        // Verify no favorite was created
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $post->getKey(),
            'favoritable_type' => Post::class,
        ]);
    }

    public function test_a_guest_can_not_favorite_a_user()
    {
        $author = User::factory()->create();

        $this->postJson(route('favorites.storeUser', ['user' => $author]))
            ->assertStatus(401);
    }

    public function test_a_user_can_favorite_an_author()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $author]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $author->getKey(),
            'favoritable_type' => User::class,
        ]);
    }

    public function test_a_user_can_remove_an_author_from_his_favorites()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $author]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $author->getKey(),
            'favoritable_type' => User::class,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroyUser', ['user' => $author]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $author->getKey(),
            'favoritable_type' => User::class,
        ]);
    }

    public function test_a_user_can_not_remove_a_non_favorited_user()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroyUser', ['user' => $author]))
            ->assertNoContent();

        // Verify no favorite was created
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $author->getKey(),
            'favoritable_type' => User::class,
        ]);
    }

    public function test_a_user_can_not_favorite_himself()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $user]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user'])
            ->assertJson([
                'errors' => [
                    'user' => ['You cannot favorite yourself.'],
                ],
            ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $user->getKey(),
            'favoritable_type' => User::class,
        ]);
    }

    public function test_a_user_can_not_favorite_a_post_twice()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // First favorite
        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        // Try to favorite again
        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['post'])
            ->assertJson([
                'errors' => [
                    'post' => ['This item is already in your favorites.'],
                ],
            ]);

        // Verify only one favorite exists
        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_a_user_can_not_favorite_a_user_twice()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();

        // First favorite
        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $author]))
            ->assertCreated();

        // Try to favorite again
        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $author]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user'])
            ->assertJson([
                'errors' => [
                    'user' => ['This item is already in your favorites.'],
                ],
            ]);

        // Verify only one favorite exists
        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_a_user_can_favorite_both_posts_and_authors()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $author = User::factory()->create();

        // Favorite a post
        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        // Favorite an author
        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $author]))
            ->assertCreated();

        // Verify both favorites exist
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $post->getKey(),
            'favoritable_type' => Post::class,
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $author->getKey(),
            'favoritable_type' => User::class,
        ]);
    }

    public function test_a_user_cannot_favorite_an_already_favorited_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // First, favorite the post
        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        // Verify the favorite exists
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $post->getKey(),
            'favoritable_type' => Post::class,
        ]);

        $initialFavoriteCount = Favorite::query()
            ->whereBelongsTo($user, 'user')
            ->count();

        // Try to favorite the already favorited post
        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['post'])
            ->assertJson([
                'errors' => [
                    'post' => ['This item is already in your favorites.'],
                ],
            ]);

        // Verify no duplicate was created - count should remain the same
        $this->assertDatabaseCount('favorites', $initialFavoriteCount);

        // Verify the original favorite still exists
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $post->getKey(),
            'favoritable_type' => Post::class,
        ]);
    }

    public function test_a_user_cannot_favorite_an_already_favorited_user()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();

        // First, favorite the user
        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $author]))
            ->assertCreated();

        // Verify the favorite exists
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $author->getKey(),
            'favoritable_type' => User::class,
        ]);

        $initialFavoriteCount = Favorite::query()
            ->whereBelongsTo($user, 'user')
            ->count();

        // Try to favorite the already favorited user
        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $author]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user'])
            ->assertJson([
                'errors' => [
                    'user' => ['This item is already in your favorites.'],
                ],
            ]);

        // Verify no duplicate was created - count should remain the same
        $this->assertDatabaseCount('favorites', $initialFavoriteCount);

        // Verify the original favorite still exists
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->getKey(),
            'favoritable_id' => $author->getKey(),
            'favoritable_type' => User::class,
        ]);
    }

    public function test_a_user_can_get_their_favorites_grouped_by_type()
    {
        $user = User::factory()->create();
        $postAuthor = User::factory()->create();
        $post1 = Post::factory()->for($postAuthor, 'user')->create();
        $post2 = Post::factory()->for($postAuthor, 'user')->create();
        $favoritedUser = User::factory()->create();

        // Create favorites
        $favorite1 = new Favorite();
        $favorite1->user()->associate($user);
        $favorite1->favoritable()->associate($post1);
        $favorite1->save();

        $favorite2 = new Favorite();
        $favorite2->user()->associate($user);
        $favorite2->favoritable()->associate($post2);
        $favorite2->save();

        $favorite3 = new Favorite();
        $favorite3->user()->associate($user);
        $favorite3->favoritable()->associate($favoritedUser);
        $favorite3->save();

        $response = $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'posts' => [
                        '*' => ['id', 'title', 'body', 'user' => ['id', 'name']],
                    ],
                    'users' => [
                        '*' => ['id', 'name'],
                    ],
                ],
            ]);

        $responseData = $response->json('data');

        $this->assertCount(2, $responseData['posts']);
        $this->assertCount(1, $responseData['users']);

        $this->assertEquals($post1->id, $responseData['posts'][0]['id']);
        $this->assertEquals($post2->id, $responseData['posts'][1]['id']);
        $this->assertEquals($favoritedUser->id, $responseData['users'][0]['id']);
        $this->assertEquals($favoritedUser->name, $responseData['users'][0]['name']);

        // Verify posts have user relationship
        $this->assertArrayHasKey('user', $responseData['posts'][0]);
        $this->assertEquals($postAuthor->id, $responseData['posts'][0]['user']['id']);
        $this->assertEquals($postAuthor->name, $responseData['posts'][0]['user']['name']);
    }
}
