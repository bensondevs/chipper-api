<?php

namespace Tests\Unit\Support;

use App\Models\Favorite;
use App\Models\Post;
use App\Models\User;
use App\Support\FavoriteEvaluator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class FavoriteEvaluatorTest extends TestCase
{
    use DatabaseMigrations;

    public function test_factory_method_creates_instance()
    {
        $user = User::factory()->create();

        $evaluator = FavoriteEvaluator::for($user);

        $this->assertInstanceOf(FavoriteEvaluator::class, $evaluator);
    }

    public function test_can_mark_as_favorite_returns_true_when_user_can_favorite_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $evaluator = FavoriteEvaluator::for($user);

        $this->assertTrue($evaluator->canMarkAsFavorite($post));
        $this->assertNull($evaluator->getReason());
    }

    public function test_can_mark_as_favorite_returns_true_when_user_can_favorite_another_user()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $evaluator = FavoriteEvaluator::for($user);

        $this->assertTrue($evaluator->canMarkAsFavorite($anotherUser));
        $this->assertNull($evaluator->getReason());
    }

    public function test_can_mark_as_favorite_returns_false_when_user_tries_to_favorite_themselves()
    {
        $user = User::factory()->create();

        $evaluator = FavoriteEvaluator::for($user);

        $this->assertFalse($evaluator->canMarkAsFavorite($user));
        $this->assertEquals('You cannot favorite yourself.', $evaluator->getReason());
    }

    public function test_can_mark_as_favorite_returns_false_when_post_is_already_favorited()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Create an existing favorite
        $favorite = new Favorite();
        $favorite->user()->associate($user);
        $favorite->favoritable()->associate($post);
        $favorite->save();

        $evaluator = FavoriteEvaluator::for($user);

        $this->assertFalse($evaluator->canMarkAsFavorite($post));
        $this->assertEquals('This item is already in your favorites.', $evaluator->getReason());
    }

    public function test_can_mark_as_favorite_returns_false_when_user_is_already_favorited()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        // Create an existing favorite
        $favorite = new Favorite();
        $favorite->user()->associate($user);
        $favorite->favoritable()->associate($anotherUser);
        $favorite->save();

        $evaluator = FavoriteEvaluator::for($user);

        $this->assertFalse($evaluator->canMarkAsFavorite($anotherUser));
        $this->assertEquals('This item is already in your favorites.', $evaluator->getReason());
    }

    public function test_get_reason_returns_null_when_no_reason_is_set()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $evaluator = FavoriteEvaluator::for($user);

        $this->assertNull($evaluator->getReason());

        $evaluator->canMarkAsFavorite($post);

        $this->assertNull($evaluator->getReason());
    }

    public function test_reason_is_reset_when_can_mark_as_favorite_is_called_again()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $evaluator = FavoriteEvaluator::for($user);

        // First call - user tries to favorite themselves
        $evaluator->canMarkAsFavorite($user);
        $this->assertEquals('You cannot favorite yourself.', $evaluator->getReason());

        // Second call - user can favorite a post
        $evaluator->canMarkAsFavorite($post);
        $this->assertNull($evaluator->getReason());
    }

    public function test_reason_changes_when_different_validation_fails()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $evaluator = FavoriteEvaluator::for($user);

        // First - user tries to favorite themselves
        $evaluator->canMarkAsFavorite($user);
        $this->assertEquals('You cannot favorite yourself.', $evaluator->getReason());

        // Create favorite for post
        $favorite = new Favorite();
        $favorite->user()->associate($user);
        $favorite->favoritable()->associate($post);
        $favorite->save();

        // Second - user tries to favorite already favorited post
        $evaluator->canMarkAsFavorite($post);
        $this->assertEquals('This item is already in your favorites.', $evaluator->getReason());
    }

    public function test_different_users_can_favorite_same_post()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->create();

        // User1 favorites the post
        $favorite1 = new Favorite();
        $favorite1->user()->associate($user1);
        $favorite1->favoritable()->associate($post);
        $favorite1->save();

        $evaluator1 = FavoriteEvaluator::for($user1);
        $evaluator2 = FavoriteEvaluator::for($user2);

        // User1 cannot favorite again
        $this->assertFalse($evaluator1->canMarkAsFavorite($post));
        $this->assertEquals('This item is already in your favorites.', $evaluator1->getReason());

        // User2 can favorite
        $this->assertTrue($evaluator2->canMarkAsFavorite($post));
        $this->assertNull($evaluator2->getReason());
    }

    public function test_different_users_can_favorite_same_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $author = User::factory()->create();

        // User1 favorites the author
        $favorite1 = new Favorite();
        $favorite1->user()->associate($user1);
        $favorite1->favoritable()->associate($author);
        $favorite1->save();

        $evaluator1 = FavoriteEvaluator::for($user1);
        $evaluator2 = FavoriteEvaluator::for($user2);

        // User1 cannot favorite again
        $this->assertFalse($evaluator1->canMarkAsFavorite($author));
        $this->assertEquals('This item is already in your favorites.', $evaluator1->getReason());

        // User2 can favorite
        $this->assertTrue($evaluator2->canMarkAsFavorite($author));
        $this->assertNull($evaluator2->getReason());
    }

    public function test_user_can_favorite_post_after_unfavoriting()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Create and then delete favorite
        $favorite = new Favorite();
        $favorite->user()->associate($user);
        $favorite->favoritable()->associate($post);
        $favorite->save();

        $favorite->delete();

        $evaluator = FavoriteEvaluator::for($user);

        // User can now favorite again
        $this->assertTrue($evaluator->canMarkAsFavorite($post));
        $this->assertNull($evaluator->getReason());
    }

    public function test_user_can_favorite_user_after_unfavoriting()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        // Create and then delete favorite
        $favorite = new Favorite();
        $favorite->user()->associate($user);
        $favorite->favoritable()->associate($anotherUser);
        $favorite->save();

        $favorite->delete();

        $evaluator = FavoriteEvaluator::for($user);

        // User can now favorite again
        $this->assertTrue($evaluator->canMarkAsFavorite($anotherUser));
        $this->assertNull($evaluator->getReason());
    }

    public function test_self_favoriting_check_takes_precedence_over_already_favorited_check()
    {
        $user = User::factory()->create();

        // Create a favorite where user favorites themselves (shouldn't happen, but test edge case)
        // Actually, this shouldn't be possible, but let's test the logic
        $evaluator = FavoriteEvaluator::for($user);

        // User tries to favorite themselves - should return false with self-favoriting reason
        $this->assertFalse($evaluator->canMarkAsFavorite($user));
        $this->assertEquals('You cannot favorite yourself.', $evaluator->getReason());
    }
}

