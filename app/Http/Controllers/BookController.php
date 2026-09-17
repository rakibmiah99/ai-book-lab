<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BookController extends Controller
{
    /**
     * Display a listing of the books.
     */
    public function index(): View
    {
        $books = Book::withCount(['pages', 'topics'])
            ->orderBy('name')
            ->paginate(12);

        return view('books.index', ['books' => $books]);
    }

    /**
     * Display a single page of the given book, defaulting to its first page.
     */
    public function show(Book $book, Request $request): View
    {
        $requestedPageNumber = (int) $request->query('page', 0);

        $page = $book->pages()
            ->when(
                $requestedPageNumber > 0,
                fn ($query) => $query->where('page_number', $requestedPageNumber),
            )
            ->orderBy('page_number')
            ->firstOrFail();

        $totalPages = $book->pages()->count();

        $pagination = new LengthAwarePaginator(
            items: [],
            total: $totalPages,
            perPage: 1,
            currentPage: $page->page_number,
            options: [
                'path' => route('books.show', $book),
                'pageName' => 'page',
            ],
        );

        return view('books.show', [
            'book' => $book,
            'topics' => $book->topics()->orderBy('position')->get(),
            'page' => $page,
            'totalPages' => $totalPages,
            'pagination' => $pagination,
        ]);
    }
}
