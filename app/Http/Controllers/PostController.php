<?php

namespace App\Http\Controllers;

use App\Actions\Post\CreatePost;
use App\Actions\Post\UpdatePost;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\DestroyPostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;

/**
 * @group Posts
 *
 * API endpoints for managing posts
 */
class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('user')
            ->orderByDesc('created_at')
            ->get();

        return PostResource::collection($posts);
    }

    public function store(CreatePostRequest $request, CreatePost $createPost)
    {
        $post = $createPost(
            user: $request->user(),
            title: $request->input('title'),
            body: $request->input('body'),
            image: $request->file('image'),
        );

        return new PostResource($post);
    }

    public function show(Post $post)
    {
        return new PostResource($post);
    }

    public function update(UpdatePostRequest $request, Post $post, UpdatePost $updatePost)
    {
        $post = $updatePost(
            post: $post,
            title: $request->input('title'),
            body: $request->input('body'),
            image: $request->file('image'),
        );

        return new PostResource($post);
    }

    public function destroy(DestroyPostRequest $request, Post $post)
    {
        $post->delete();

        return response()->noContent();
    }
}
