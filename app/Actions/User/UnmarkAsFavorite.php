<?php

namespace App\Actions\User;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UnmarkAsFavorite
{
    public function __invoke(User $user, Model $favoritable): void
    {
        Favorite::query()
            ->whereBelongsTo($user, 'user')
            ->whereMorphedTo('favoritable', $favoritable)
            ->delete();
    }
}
