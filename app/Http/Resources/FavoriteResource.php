<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FavoriteResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $grouped = $this->resource->groupBy('favoritable_type');

        return [
            'data' => [
                'posts' => $grouped->get(Post::class, collect())
                    ->map(fn ($favorite) => $favorite->favoritable)
                    ->map(fn (Post $post) => new PostResource($post))
                    ->all(),
                'users' => $grouped->get(User::class, collect())
                    ->map(fn ($favorite) => $favorite->favoritable)
                    ->map(fn (User $user) => [
                        'id' => $user->getKey(),
                        'name' => $user->name,
                    ])
                    ->all(),
            ],
        ];
    }
}
