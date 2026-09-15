<?php

namespace App\Http\Controllers;

use App\Models\BookTopic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookTopicFavoriteController extends Controller
{
    /**
     * Toggle the authenticated user's favorite status for the given topic.
     */
    public function store(Request $request, BookTopic $topic): JsonResponse
    {
        $user = $request->user();

        if ($topic->isFavoritedBy($user)) {
            $user->favoriteTopics()->detach($topic->id);

            return response()->json(['favorited' => false]);
        }

        $user->favoriteTopics()->attach($topic->id);

        return response()->json(['favorited' => true]);
    }
}
