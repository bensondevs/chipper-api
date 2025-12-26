<?php

namespace Database\Factories;

use App\Models\Favorite;
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
        $post = \App\Models\Post::factory()->create();
        
        return [
            'post_id' => $post->id, // Keep for backward compatibility
            'user_id' => \App\Models\User::factory(),
            'favoritable_id' => $post->id,
            'favoritable_type' => \App\Models\Post::class,
        ];
    }

    /**
     * Indicate that the favorite is for a user.
     *
     * @return $this
     */
    public function forUser(\App\Models\User $user = null): static
    {
        $user = $user ?? \App\Models\User::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'post_id' => null,
            'favoritable_id' => $user->id,
            'favoritable_type' => \App\Models\User::class,
        ]);
    }
}
