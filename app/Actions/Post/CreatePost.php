<?php

namespace App\Actions\Post;

use App\Events\Post\PostCreated;
use App\Models\Post;
use App\Models\User;

class CreatePost
{
    public function __invoke(User $user, string $title, string $body): Post
    {
        $post = new Post();
        $post->user()->associate($user);
        $post->title = $title;
        $post->body = $body;
        $post->save();

        event(new PostCreated($post));

        return $post;
    }
}
