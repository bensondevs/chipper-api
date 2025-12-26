<?php

namespace Tests\Unit\Actions\User;

use App\Actions\User\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_creates_a_user_with_correct_attributes()
    {
        $name = 'John Doe';
        $email = 'john@example.com';
        $password = 'password123';

        $action = new CreateUser();
        $user = $action($name, $email, $password);

        $this->assertEquals($name, $user->name);
        $this->assertEquals($email, $user->email);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_it_persists_the_user_to_database()
    {
        $name = 'Jane Doe';
        $email = 'jane@example.com';
        $password = 'password123';

        $action = new CreateUser();
        $user = $action($name, $email, $password);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $name,
            'email' => $email,
        ]);
    }

    public function test_it_hashes_the_password()
    {
        $password = 'plaintext-password';

        $action = new CreateUser();
        $user = $action('Test User', 'test@example.com', $password);

        $this->assertNotEquals($password, $user->password);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_it_verifies_email_when_verify_email_is_true()
    {
        $action = new CreateUser();
        $user = $action('Test User', 'test@example.com', 'password', verifyEmail: true);

        $this->assertNotNull($user->email_verified_at);
    }

    public function test_it_does_not_verify_email_when_verify_email_is_false()
    {
        $action = new CreateUser();
        $user = $action('Test User', 'test@example.com', 'password', verifyEmail: false);

        $this->assertNull($user->email_verified_at);
    }

    public function test_it_does_not_verify_email_by_default()
    {
        $action = new CreateUser();
        $user = $action('Test User', 'test@example.com', 'password');

        $this->assertNull($user->email_verified_at);
    }

    public function test_it_returns_the_created_user()
    {
        $action = new CreateUser();
        $user = $action('Test User', 'test@example.com', 'password');

        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->exists);
        $this->assertNotNull($user->id);
    }

    public function test_it_creates_multiple_users()
    {
        $action = new CreateUser();
        $user1 = $action('User One', 'user1@example.com', 'password');
        $user2 = $action('User Two', 'user2@example.com', 'password');

        $this->assertNotEquals($user1->id, $user2->id);
        $this->assertDatabaseCount('users', 2);
    }
}
