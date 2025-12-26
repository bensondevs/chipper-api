<?php

namespace App\Http\Controllers;

use App\Actions\User\MarkAsFavorite;
use App\Actions\User\UnmarkAsFavorite;
use App\Http\Requests\CreateFavoriteRequest;
use App\Http\Resources\FavoriteResource;
use App\Models\Post;
use App\Models\User;
use App\Support\FavoriteEvaluator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * @group Favorites
 *
 * API endpoints for managing favorites
 */
class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = $request->user()->favorites()
            ->with(['favoritable' => fn ($morphTo) => $morphTo->morphWith([
                Post::class => ['user'],
                User::class => [],
            ])])
            ->get();

        return new FavoriteResource($favorites);
    }

    public function store(
        CreateFavoriteRequest $request,
        Post $post,
        MarkAsFavorite $markAsFavorite,
    ) {
        $evaluator = FavoriteEvaluator::for($request->user());

        if (! $evaluator->canMarkAsFavorite($post)) {
            throw ValidationException::withMessages([
                'post' => [$evaluator->getReason()],
            ]);
        }

        $markAsFavorite(
            user: $request->user(),
            favoritable: $post,
        );

        return response()->noContent(ResponseAlias::HTTP_CREATED);
    }

    public function destroy(
        Request $request,
        UnmarkAsFavorite $unmarkAsFavorite,
        Post $post,
    ) {
        $unmarkAsFavorite(
            user: $request->user(),
            favoritable: $post,
        );

        return response()->noContent();
    }

    public function storeUser(
        CreateFavoriteRequest $request,
        MarkAsFavorite $markAsFavorite,
        User $user,
    ) {
        $evaluator = FavoriteEvaluator::for($request->user());

        if (! $evaluator->canMarkAsFavorite($user)) {
            throw ValidationException::withMessages([
                'user' => [$evaluator->getReason()],
            ]);
        }

        $markAsFavorite(
            user: $request->user(),
            favoritable: $user,
        );

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroyUser(
        Request $request,
        UnmarkAsFavorite $unmarkAsFavorite,
        User $user,
    ) {
        $unmarkAsFavorite(
            user: $request->user(),
            favoritable: $user,
        );

        return response()->noContent();
    }
}
