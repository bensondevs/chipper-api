<?php

namespace Tests\Unit\Actions\User;

use App\Actions\User\UnmarkAsFavorite;
use App\Models\Favorite;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class UnmarkAsFavoriteTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_deletes_favorite_for_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $favorite = new Favorite();
        $favorite->user()->associate($user);
        $favorite->favoritable()->associate($post);
        $favorite->save();

        $action = new UnmarkAsFavorite();
        $action($user, $post);

        $this->assertFalse(
            Favorite::query()
                ->whereBelongsTo($user, 'user')
                ->whereMorphedTo('favoritable', $post)
                ->exists()
        );
    }

    public function test_it_deletes_favorite_for_user()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $favorite = new Favorite();
        $favorite->user()->associate($user);
        $favorite->favoritable()->associate($anotherUser);
        $favorite->save();

        $action = new UnmarkAsFavorite();
        $action($user, $anotherUser);

        $this->assertFalse(
            Favorite::query()
                ->whereBelongsTo($user, 'user')
                ->whereMorphedTo('favoritable', $anotherUser)
                ->exists()
        );
    }

    public function test_it_only_deletes_matching_user_and_favoritable_combination()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->create();

        $favorite1 = new Favorite();
        $favorite1->user()->associate($user1);
        $favorite1->favoritable()->associate($post);
        $favorite1->save();

        $favorite2 = new Favorite();
        $favorite2->user()->associate($user2);
        $favorite2->favoritable()->associate($post);
        $favorite2->save();

        $action = new UnmarkAsFavorite();
        $action($user1, $post);

        $this->assertFalse(
            Favorite::query()
                ->whereBelongsTo($user1, 'user')
                ->whereMorphedTo('favoritable', $post)
                ->exists()
        );

        $this->assertTrue(
            Favorite::query()
                ->whereBelongsTo($user2, 'user')
                ->whereMorphedTo('favoritable', $post)
                ->exists()
        );
    }
}
