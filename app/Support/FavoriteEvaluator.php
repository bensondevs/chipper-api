<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FavoriteEvaluator
{
    protected ?string $reason = null;

    public function __construct(protected User $user)
    {
    }

    public static function for(User $user): static
    {
        return new static($user);
    }

    public function canMarkAsFavorite(Model $favoritable): bool
    {
        $this->reason = null;

        if ($this->user->is($favoritable)) {
            $this->reason = 'You cannot favorite yourself.';

            return false;
        }

        if ($this->user->hasMarkedFavorite($favoritable)) {
            $this->reason = 'This item is already in your favorites.';

            return false;
        }

        return true;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
