<?php

namespace Database\Factories;

use App\Models\Favorite;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FavoriteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Favorite::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'favoritable_id' => Post::factory(),
            'favoritable_type' => Post::class,
        ];
    }

    /**
     * Indicate that the favorite is for a user instead of a post.
     *
     * @param  \App\Models\User|null  $user
     * @return $this
     */
    public function forUser(User $user = null): static
    {
        $user = $user ?? User::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'favoritable_id' => $user->id,
            'favoritable_type' => User::class,
        ]);
    }

    /**
     * Indicate that the favorite is for a post.
     *
     * @param  \App\Models\Post|null  $post
     * @return $this
     */
    public function forPost(Post $post = null): static
    {
        $post = $post ?? Post::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'favoritable_id' => $post->id,
            'favoritable_type' => Post::class,
        ]);
    }
}
