<?php

namespace Tests\Feature;

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

        $this->postJson(route('favorites.store', ['post' => $post]))
            ->assertForbidden();
    }

    public function test_a_user_can_favorite_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'post_id' => $post->id,
            'user_id' => $user->id,
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
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_not_remove_a_non_favorited_item()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNotFound();
    }
}
