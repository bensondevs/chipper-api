<?php

namespace App\Listeners\User;

use App\Events\Post\PostCreated;
use App\Jobs\NotifyNewPostToFollowers;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;

class BroadcastFavoriteUserNewPost implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PostCreated $event): void
    {
        $post = $event->post;
        $postId = $post->getKey();

        User::query()
            ->select('id')
            ->whereHas('favorites', fn (Builder $query) => $query->whereMorphedTo(
                'favoritable',
                $post->user,
            ))
            ->chunkById(
                10_000,
                fn (Collection $users) => Bus::batch(
                    $users->chunk(100)
                        ->map(fn (Collection $chunk) => new NotifyNewPostToFollowers(
                            $postId,
                            $chunk->pluck('id')->all(),
                        ))
                        ->all(),
                )
                    ->name("Notify followers of post #{$postId}")
                    ->allowFailures()
                    ->onQueue('notifications')
                    ->dispatch(),
            );
    }
}
