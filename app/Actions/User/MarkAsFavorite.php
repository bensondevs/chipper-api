<?php

namespace App\Actions\User;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MarkAsFavorite
{
    public function __invoke(User $user, Model $favoritable): void
    {
        $favorite = new Favorite();
        $favorite->user()->associate($user);
        $favorite->favoritable()->associate($favoritable);
        $favorite->save();
    }
}
