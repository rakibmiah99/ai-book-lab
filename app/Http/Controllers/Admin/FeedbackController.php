<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookTopicFeedback;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Display all reader feedback, pending notes first.
     */
    public function index(): View
    {
        $feedbacks = BookTopicFeedback::with(['user', 'topic.book'])
            ->orderByRaw("status = 'pending' desc")
            ->latest()
            ->paginate(20);

        return view('admin.feedbacks.index', ['feedbacks' => $feedbacks]);
    }

    /**
     * Mark the given feedback as resolved with a note describing the correction made.
     */
    public function update(Request $request, BookTopicFeedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'resolution_note' => ['required', 'string', 'max:2000'],
        ]);

        $feedback->markResolved($request->user(), $validated['resolution_note']);

        return back()->with('status', 'Feedback marked as corrected.');
    }
}
