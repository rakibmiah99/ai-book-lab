@extends('layouts.app')

@section('title', 'Books — AI Book Lab')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Books</h1>
    </div>

    @if ($books->isEmpty())
        <div class="alert alert-light border">No books have been added yet.</div>
    @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            @foreach ($books as $book)
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5">{{ $book->name }}</h2>
                            @if ($book->writer_name)
                                <p class="text-muted small mb-2">by {{ $book->writer_name }}</p>
                            @endif

                            <div class="mb-3">
                                <span class="badge text-bg-light border">{{ $book->pages_count }} pages</span>
                                <span class="badge text-bg-light border">{{ $book->topics_count }} topics</span>
                            </div>

                            <a href="{{ route('books.show', $book) }}" class="btn btn-accent mt-auto">
                                Start Reading
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $books->links() }}
        </div>
    @endif
@endsection
