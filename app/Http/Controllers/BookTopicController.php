<?php

namespace App\Http\Controllers;

use App\Models\BookTopic;
use Illuminate\Contracts\View\View;

class BookTopicController extends Controller
{
    /**
     * Display the given topic for reading.
     */
    public function show(BookTopic $topic)
    {
        $topic->load([
            'book',
            'details' => fn ($query) => $query->orderBy('position'),
            'importantNotes',
            'refPages.page' => fn ($query) => $query->orderBy('page_number'),
            'feedbacks' => fn ($query) => $query->with(['user', 'resolvedBy'])->latest(),
        ]);


//        return [
//            'topic' => $topic,
//            'book' => $topic->book,
//            'topics' => $topic->book->topics()->orderBy('position')->get(),
//            'isFavorited' => $topic->isFavoritedBy(auth()->user()),
//        ];

        return view('topics.show', [
            'topic' => $topic,
            'book' => $topic->book,
            'topics' => $topic->book->topics()->orderBy('position')->get(),
            'isFavorited' => $topic->isFavoritedBy(auth()->user()),
        ]);
    }
}
