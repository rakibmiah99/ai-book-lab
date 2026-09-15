@extends('layouts.app')

@section('title', $book->name.' — Page '.$page->page_number)

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            @include('partials._topic_sidebar', ['book' => $book, 'topics' => $topics])
        </div>

        <div class="col-lg-8">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="h4 mb-0">{{ $book->name }}</h1>
                    <span class="text-muted small">Page {{ $page->page_number }} of {{ $totalPages }}</span>
                </div>

                <div class="btn-group">
                    <a class="btn btn-outline-secondary {{ $previousPage ? '' : 'disabled' }}"
                       href="{{ $previousPage ? route('books.show', ['book' => $book, 'page' => $previousPage->page_number]) : '#' }}">
                        &larr; Previous
                    </a>
                    <a class="btn btn-outline-secondary {{ $nextPage ? '' : 'disabled' }}"
                       href="{{ $nextPage ? route('books.show', ['book' => $book, 'page' => $nextPage->page_number]) : '#' }}">
                        Next &rarr;
                    </a>
                </div>
            </div>

            <div class="card">
                @if ($page->image_url)
                    <div class="card-img-top p-3 pb-0 text-center">
                        <a href="#" class="js-zoom-trigger"
                           data-image-url="{{ $page->image_url }}"
                           data-image-label="Page {{ $page->page_number }}">
                            <img src="{{ $page->image_url }}" alt="Page {{ $page->page_number }}"
                                 class="img-fluid rounded" style="max-height: 320px;">
                        </a>
                        <p class="text-muted small mt-1">Click the image to zoom in</p>
                    </div>
                @endif

                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted">Page content</h2>
                    <div class="reading-text">{{ $page->ai_ocr_content ?: $page->content }}</div>

                    @if (! $page->ai_ocr_content)
                        <p class="text-muted small mt-3 mb-0">
                            This page has not been OCR'd yet — showing the originally stored content.
                        </p>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <a class="btn btn-outline-secondary {{ $previousPage ? '' : 'disabled' }}"
                   href="{{ $previousPage ? route('books.show', ['book' => $book, 'page' => $previousPage->page_number]) : '#' }}">
                    &larr; Previous
                </a>
                <a class="btn btn-outline-secondary {{ $nextPage ? '' : 'disabled' }}"
                   href="{{ $nextPage ? route('books.show', ['book' => $book, 'page' => $nextPage->page_number]) : '#' }}">
                    Next &rarr;
                </a>
            </div>
        </div>
    </div>
@endsection
