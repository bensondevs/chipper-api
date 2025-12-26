<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUser
{
    public function __invoke(
        string $name,
        string $email,
        string $password,
        bool $verifyEmail = false
    ): User {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        if ($verifyEmail) {
            $user->email_verified_at = now();
            $user->save();
        }

        return $user;
    }
}
