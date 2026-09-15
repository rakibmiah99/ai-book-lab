<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the authenticated user's favourite topics.
     */
    public function index(Request $request): View
    {
        $favoriteTopics = $request->user()
            ->favoriteTopics()
            ->with('book')
            ->orderByDesc('book_topic_favorites.created_at')
            ->paginate(10);

        return view('dashboard.index', ['favoriteTopics' => $favoriteTopics]);
    }
}
