<?php

namespace Tests\Unit\Actions\User;

use App\Actions\User\MarkAsFavorite;
use App\Models\Favorite;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MarkAsFavoriteTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_creates_favorite_and_associates_user_and_favoritable()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $action = new MarkAsFavorite();
        $action($user, $post);

        $favorite = Favorite::query()
            ->whereBelongsTo($user, 'user')
            ->whereMorphedTo('favoritable', $post)
            ->first();

        $this->assertInstanceOf(Favorite::class, $favorite);
        $this->assertTrue($favorite->user->is($user));
        $this->assertTrue($favorite->favoritable->is($post));
    }

    public function test_it_works_with_user_as_favoritable()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $action = new MarkAsFavorite();
        $action($user, $anotherUser);

        $favorite = Favorite::query()
            ->whereBelongsTo($user, 'user')
            ->whereMorphedTo('favoritable', $anotherUser)
            ->first();

        $this->assertInstanceOf(Favorite::class, $favorite);
        $this->assertTrue($favorite->user->is($user));
        $this->assertTrue($favorite->favoritable->is($anotherUser));
    }
}
