<?php

namespace Tests\Unit\Listeners\User;

use App\Events\Post\PostCreated;
use App\Jobs\NotifyNewPostToFollowers;
use App\Listeners\User\BroadcastFavoriteUserNewPost;
use App\Models\Favorite;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Post\FavoriteUserNewPost;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BroadcastFavoriteUserNewPostTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_sends_notification_to_users_who_favorited_the_post_author()
    {
        Bus::fake();
        Notification::fake();

        $postAuthor = User::factory()->create();
        $follower1 = User::factory()->create();
        $follower2 = User::factory()->create();
        $nonFollower = User::factory()->create();

        // Follower1 favorites the post author
        $favorite1 = new Favorite();
        $favorite1->user()->associate($follower1);
        $favorite1->favoritable()->associate($postAuthor);
        $favorite1->save();

        // Follower2 favorites the post author
        $favorite2 = new Favorite();
        $favorite2->user()->associate($follower2);
        $favorite2->favoritable()->associate($postAuthor);
        $favorite2->save();

        // NonFollower does not favorite the post author

        $post = Post::factory()->for($postAuthor, 'user')->create();
        $event = new PostCreated($post);

        $listener = new BroadcastFavoriteUserNewPost();
        $listener->handle($event);

        // Assert Bus batch was dispatched
        Bus::assertBatchCount(1);
        Bus::assertBatched(function ($batch) use ($post) {
            return str_contains($batch->name, "Notify followers of post #{$post->id}");
        });

        // Manually run the jobs to verify notifications are sent
        $batches = Bus::batched(function ($batch) use ($post) {
            return str_contains($batch->name, "Notify followers of post #{$post->id}");
        });

        $this->assertCount(1, $batches);
        $batch = $batches->first();

        // Verify job was created with postId (not Post model)
        $this->assertCount(1, $batch->jobs);
        $job = $batch->jobs[0];
        $this->assertInstanceOf(NotifyNewPostToFollowers::class, $job);
        $this->assertEquals($post->id, $job->postId);
        $this->assertIsInt($job->postId);
        $this->assertContains($follower1->id, $job->userIds);
        $this->assertContains($follower2->id, $job->userIds);

        foreach ($batch->jobs as $job) {
            if ($job instanceof NotifyNewPostToFollowers) {
                $job->handle();
            }
        }

        // Assert notifications were sent to followers
        Notification::assertSentTo(
            [$follower1, $follower2],
            FavoriteUserNewPost::class,
            function ($notification) use ($post, $follower1) {
                $notificationData = $notification->toArray($follower1);
                return $notificationData['post_id'] === $post->id;
            }
        );

        // Assert notification was NOT sent to non-follower
        Notification::assertNotSentTo(
            $nonFollower,
            FavoriteUserNewPost::class
        );
    }

    public function test_it_does_not_send_notification_if_no_users_favorited_the_author()
    {
        Bus::fake();
        Notification::fake();

        $postAuthor = User::factory()->create();
        $otherUser = User::factory()->create();

        $post = Post::factory()->for($postAuthor, 'user')->create();
        $event = new PostCreated($post);

        $listener = new BroadcastFavoriteUserNewPost();
        $listener->handle($event);

        // Assert no batch was dispatched since there are no followers
        Bus::assertBatchCount(0);
        // Assert no notifications were sent
        Notification::assertNothingSent();
    }

    public function test_it_only_notifies_users_who_favorited_the_author_not_other_users()
    {
        Bus::fake();
        Notification::fake();

        $postAuthor = User::factory()->create();
        $follower = User::factory()->create();
        $anotherAuthor = User::factory()->create();
        $followerOfAnotherAuthor = User::factory()->create();

        // Follower favorites the post author
        $favorite1 = new Favorite();
        $favorite1->user()->associate($follower);
        $favorite1->favoritable()->associate($postAuthor);
        $favorite1->save();

        // FollowerOfAnotherAuthor favorites a different author
        $favorite2 = new Favorite();
        $favorite2->user()->associate($followerOfAnotherAuthor);
        $favorite2->favoritable()->associate($anotherAuthor);
        $favorite2->save();

        $post = Post::factory()->for($postAuthor, 'user')->create();
        $event = new PostCreated($post);

        $listener = new BroadcastFavoriteUserNewPost();
        $listener->handle($event);

        // Assert Bus batch was dispatched
        Bus::assertBatched(function ($batch) use ($post) {
            return str_contains($batch->name, "Notify followers of post #{$post->id}");
        });

        // Manually run the jobs
        $batches = Bus::batched(function ($batch) use ($post) {
            return str_contains($batch->name, "Notify followers of post #{$post->id}");
        });
        $batch = $batches->first();

        // Verify job was created with postId
        $job = $batch->jobs[0];
        $this->assertInstanceOf(NotifyNewPostToFollowers::class, $job);
        $this->assertEquals($post->id, $job->postId);
        $this->assertIsInt($job->postId);
        $this->assertContains($follower->id, $job->userIds);

        foreach ($batch->jobs as $job) {
            if ($job instanceof NotifyNewPostToFollowers) {
                $job->handle();
            }
        }

        // Assert notification was sent only to the follower of the post author
        Notification::assertSentTo(
            $follower,
            FavoriteUserNewPost::class,
            function ($notification) use ($post, $follower) {
                $notificationData = $notification->toArray($follower);
                return $notificationData['post_id'] === $post->id;
            }
        );

        // Assert notification was NOT sent to follower of another author
        Notification::assertNotSentTo(
            $followerOfAnotherAuthor,
            FavoriteUserNewPost::class
        );
    }

    public function test_it_handles_multiple_users_who_favorited_the_author()
    {
        Bus::fake();
        Notification::fake();

        $postAuthor = User::factory()->create();
        $followers = User::factory()->count(5)->create();

        // All followers favorite the post author
        foreach ($followers as $follower) {
            $favorite = new Favorite();
            $favorite->user()->associate($follower);
            $favorite->favoritable()->associate($postAuthor);
            $favorite->save();
        }

        $post = Post::factory()->for($postAuthor, 'user')->create();
        $event = new PostCreated($post);

        $listener = new BroadcastFavoriteUserNewPost();
        $listener->handle($event);

        // Assert Bus batch was dispatched
        Bus::assertBatched(function ($batch) use ($post) {
            return str_contains($batch->name, "Notify followers of post #{$post->id}");
        });

        // Manually run the jobs
        $batches = Bus::batched(function ($batch) use ($post) {
            return str_contains($batch->name, "Notify followers of post #{$post->id}");
        });
        $batch = $batches->first();

        // Verify job was created with postId
        $job = $batch->jobs[0];
        $this->assertInstanceOf(NotifyNewPostToFollowers::class, $job);
        $this->assertEquals($post->id, $job->postId);
        $this->assertIsInt($job->postId);
        $this->assertCount(5, $job->userIds);
        foreach ($followers as $follower) {
            $this->assertContains($follower->id, $job->userIds);
        }

        foreach ($batch->jobs as $job) {
            if ($job instanceof NotifyNewPostToFollowers) {
                $job->handle();
            }
        }

        // Assert notifications were sent to all followers
        Notification::assertSentTo(
            $followers,
            FavoriteUserNewPost::class,
            function ($notification) use ($post, $followers) {
                $notificationData = $notification->toArray($followers->first());
                return $notificationData['post_id'] === $post->id;
            }
        );

        // Assert exactly 5 notifications were sent
        Notification::assertCount(5);
    }

    public function test_it_does_not_notify_users_who_only_favorited_posts_not_the_author()
    {
        Bus::fake();
        Notification::fake();

        $postAuthor = User::factory()->create();
        $follower = User::factory()->create();
        $anotherPost = Post::factory()->for($postAuthor, 'user')->create();

        // Follower favorites a post by the author, but not the author themselves
        $favorite = new Favorite();
        $favorite->user()->associate($follower);
        $favorite->favoritable()->associate($anotherPost);
        $favorite->save();

        $post = Post::factory()->for($postAuthor, 'user')->create();
        $event = new PostCreated($post);

        $listener = new BroadcastFavoriteUserNewPost();
        $listener->handle($event);

        // Assert no batch was dispatched since no users favorited the author
        Bus::assertBatchCount(0);
        // Assert no notifications were sent since follower didn't favorite the author
        Notification::assertNothingSent();
    }

    public function test_it_passes_the_correct_post_to_the_notification()
    {
        Bus::fake();
        Notification::fake();

        $postAuthor = User::factory()->create();
        $follower = User::factory()->create();

        // Follower favorites the post author
        $favorite = new Favorite();
        $favorite->user()->associate($follower);
        $favorite->favoritable()->associate($postAuthor);
        $favorite->save();

        $post = Post::factory()->for($postAuthor, 'user')->create([
            'title' => 'Test Post Title',
            'body' => 'Test Post Body',
        ]);
        $event = new PostCreated($post);

        $listener = new BroadcastFavoriteUserNewPost();
        $listener->handle($event);

        // Assert Bus batch was dispatched
        Bus::assertBatched(function ($batch) use ($post) {
            return str_contains($batch->name, "Notify followers of post #{$post->id}");
        });

        // Manually run the jobs
        $batches = Bus::batched(function ($batch) use ($post) {
            return str_contains($batch->name, "Notify followers of post #{$post->id}");
        });
        $batch = $batches->first();

        // Verify job was created with postId
        $job = $batch->jobs[0];
        $this->assertInstanceOf(NotifyNewPostToFollowers::class, $job);
        $this->assertEquals($post->id, $job->postId);
        $this->assertIsInt($job->postId);
        $this->assertContains($follower->id, $job->userIds);

        foreach ($batch->jobs as $job) {
            if ($job instanceof NotifyNewPostToFollowers) {
                $job->handle();
            }
        }

        // Assert the notification contains the correct post
        Notification::assertSentTo(
            $follower,
            FavoriteUserNewPost::class,
            function ($notification) use ($post, $follower) {
                $notificationData = $notification->toArray($follower);
                return $notificationData['post_id'] === $post->id
                    && $notificationData['post_title'] === 'Test Post Title'
                    && $notificationData['author_id'] === $post->user->id
                    && $notificationData['author_name'] === $post->user->name;
            }
        );
    }
}
