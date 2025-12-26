<?php

namespace Tests\Unit\Actions\Post;

use App\Actions\Post\CreatePost;
use App\Events\Post\PostCreated;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreatePostTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_creates_a_post_with_correct_attributes()
    {
        $user = User::factory()->create();
        $title = 'Test Post Title';
        $body = 'Test Post Body';

        $action = new CreatePost();
        $post = $action($user, $title, $body);

        $this->assertEquals($title, $post->title);
        $this->assertEquals($body, $post->body);
        $this->assertTrue($post->user->is($user));
    }

    public function test_it_persists_the_post_to_database()
    {
        $user = User::factory()->create();
        $title = 'Test Post Title';
        $body = 'Test Post Body';

        $action = new CreatePost();
        $post = $action($user, $title, $body);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => $title,
            'body' => $body,
            'user_id' => $user->id,
        ]);
    }

    public function test_it_dispatches_post_created_event()
    {
        Event::fake();

        $user = User::factory()->create();
        $title = 'Test Post Title';
        $body = 'Test Post Body';

        $action = new CreatePost();
        $post = $action($user, $title, $body);

        Event::assertDispatched(PostCreated::class, function ($event) use ($post) {
            return $event->post->id === $post->id;
        });
    }

    public function test_it_returns_the_created_post()
    {
        $user = User::factory()->create();
        $title = 'Test Post Title';
        $body = 'Test Post Body';

        $action = new CreatePost();
        $post = $action($user, $title, $body);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertTrue($post->exists);
        $this->assertNotNull($post->id);
    }

    public function test_it_associates_post_with_correct_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $action = new CreatePost();
        $post = $action($user1, 'Title', 'Body');

        $this->assertTrue($post->user->is($user1));
        $this->assertFalse($post->user->is($user2));
        $this->assertEquals($user1->id, $post->user_id);
    }

    public function test_it_creates_multiple_posts_for_same_user()
    {
        $user = User::factory()->create();

        $action = new CreatePost();
        $post1 = $action($user, 'First Post', 'First Body');
        $post2 = $action($user, 'Second Post', 'Second Body');

        $this->assertNotEquals($post1->id, $post2->id);
        $this->assertTrue($post1->user->is($user));
        $this->assertTrue($post2->user->is($user));
        $this->assertDatabaseCount('posts', 2);
    }
}
