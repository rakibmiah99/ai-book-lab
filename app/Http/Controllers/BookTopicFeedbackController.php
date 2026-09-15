<?php

namespace App\Http\Controllers;

use App\Models\BookTopic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookTopicFeedbackController extends Controller
{
    /**
     * Store a new correction/improvement note for the given topic.
     */
    public function store(Request $request, BookTopic $topic): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $feedback = $topic->feedbacks()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        $feedback->load('user');

        return response()->json([
            'message' => 'Thanks — your note has been submitted for review.',
            'html' => view('partials._feedback_item', ['feedback' => $feedback])->render(),
        ]);
    }
}
