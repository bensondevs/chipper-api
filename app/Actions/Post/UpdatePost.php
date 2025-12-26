<?php

namespace App\Actions\Post;

use App\Models\Post;
use Illuminate\Http\UploadedFile;

class UpdatePost
{
    public function __invoke(
        Post $post,
        string $title,
        string $body,
        UploadedFile $image = null,
    ): Post {
        $post->title = $title;
        $post->body = $body;
        $post->save();

        if ($image instanceof UploadedFile) {
            $post->clearMediaCollection('images');
            $post->addMedia($image)->toMediaCollection('images');
        }

        return $post->fresh();
    }
}
