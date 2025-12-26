<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use App\Notifications\Post\FavoriteUserNewPost;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyNewPostToFollowers implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $postId, public array $userIds)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $post = Post::query()->with('user')->find($this->postId);

        if (! $post instanceof Post) {
            return;
        }

        User::query()->eachById(
            fn (User $user) => $user->notify(new FavoriteUserNewPost($post)),
        );
    }
}
