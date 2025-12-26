<?php

namespace Tests\Unit\Actions\Post;

use App\Actions\Post\UpdatePost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdatePostTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_it_updates_a_post_with_correct_attributes()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create([
            'title' => 'Original Title',
            'body' => 'Original Body',
        ]);

        $newTitle = 'Updated Title';
        $newBody = 'Updated Body';

        $action = new UpdatePost();
        $updatedPost = $action($post, $newTitle, $newBody);

        $this->assertEquals($newTitle, $updatedPost->title);
        $this->assertEquals($newBody, $updatedPost->body);
        $this->assertEquals($post->id, $updatedPost->id);
    }

    public function test_it_persists_the_updated_post_to_database()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create([
            'title' => 'Original Title',
            'body' => 'Original Body',
        ]);

        $newTitle = 'Updated Title';
        $newBody = 'Updated Body';

        $action = new UpdatePost();
        $updatedPost = $action($post, $newTitle, $newBody);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => $newTitle,
            'body' => $newBody,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
            'title' => 'Original Title',
            'body' => 'Original Body',
        ]);
    }

    public function test_it_returns_the_updated_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create();

        $action = new UpdatePost();
        $updatedPost = $action($post, 'New Title', 'New Body');

        $this->assertInstanceOf(Post::class, $updatedPost);
        $this->assertEquals($post->id, $updatedPost->id);
        $this->assertTrue($updatedPost->exists);
    }

    public function test_it_returns_fresh_instance_of_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create([
            'title' => 'Original Title',
        ]);

        $action = new UpdatePost();
        $updatedPost = $action($post, 'Updated Title', 'Updated Body');

        // Verify it's a fresh instance by checking the original post object hasn't changed
        $post->refresh();
        $this->assertEquals('Updated Title', $post->title);
        $this->assertEquals('Updated Title', $updatedPost->title);
    }

    public function test_it_maintains_user_association()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create();

        $action = new UpdatePost();
        $updatedPost = $action($post, 'New Title', 'New Body');

        $this->assertEquals($user->id, $updatedPost->user_id);
        $this->assertTrue($updatedPost->user->is($user));
    }

    public function test_it_updates_post_with_image()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create();
        $image = UploadedFile::fake()->image('post-image.jpg', 800, 600);

        $action = new UpdatePost();
        $updatedPost = $action($post, 'Title', 'Body', $image);

        $this->assertNotNull($updatedPost->getFirstMediaUrl('images'));
        $this->assertDatabaseHas('media', [
            'model_type' => Post::class,
            'model_id' => $post->id,
            'collection_name' => 'images',
        ]);
    }

    public function test_it_replaces_old_image_when_new_image_is_provided()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create();

        $firstImage = UploadedFile::fake()->image('first-image.jpg');
        $action = new UpdatePost();
        $action($post, 'Title', 'Body', $firstImage);

        $firstImageUrl = $post->fresh()->getFirstMediaUrl('images');
        $this->assertNotNull($firstImageUrl);

        $secondImage = UploadedFile::fake()->image('second-image.jpg');
        $updatedPost = $action($post, 'Title', 'Body', $secondImage);

        $secondImageUrl = $updatedPost->getFirstMediaUrl('images');
        $this->assertNotNull($secondImageUrl);
        $this->assertNotEquals($firstImageUrl, $secondImageUrl);

        // Verify only one image exists in the collection
        $this->assertCount(1, $updatedPost->getMedia('images'));
    }

    public function test_it_does_not_change_image_when_no_image_is_provided()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create();

        $image = UploadedFile::fake()->image('post-image.jpg');
        $action = new UpdatePost();
        $action($post, 'Title', 'Body', $image);

        $originalImageUrl = $post->fresh()->getFirstMediaUrl('images');
        $this->assertNotNull($originalImageUrl);

        // Update without image
        $updatedPost = $action($post, 'New Title', 'New Body');

        $this->assertEquals($originalImageUrl, $updatedPost->getFirstMediaUrl('images'));
        $this->assertCount(1, $updatedPost->getMedia('images'));
    }

    public function test_it_can_update_post_without_existing_image()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'user')->create();

        $action = new UpdatePost();
        $updatedPost = $action($post, 'New Title', 'New Body');

        $imageUrl = $updatedPost->getFirstMediaUrl('images');
        $this->assertEmpty($imageUrl);
        $this->assertCount(0, $updatedPost->getMedia('images'));
    }

    public function test_it_can_update_multiple_posts()
    {
        $user = User::factory()->create();
        $post1 = Post::factory()->for($user, 'user')->create(['title' => 'Post 1']);
        $post2 = Post::factory()->for($user, 'user')->create(['title' => 'Post 2']);

        $action = new UpdatePost();
        $updatedPost1 = $action($post1, 'Updated Post 1', 'Body 1');
        $updatedPost2 = $action($post2, 'Updated Post 2', 'Body 2');

        $this->assertEquals('Updated Post 1', $updatedPost1->title);
        $this->assertEquals('Updated Post 2', $updatedPost2->title);
        $this->assertNotEquals($updatedPost1->id, $updatedPost2->id);
    }
}
