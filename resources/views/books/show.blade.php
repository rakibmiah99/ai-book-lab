@extends('layouts.app')

@section('title', $book->name.' — Page '.$page->page_number)

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            @include('partials._topic_sidebar', ['book' => $book, 'topics' => $topics])
        </div>

        <div class="col-lg-8">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h1 class="h4 mb-0">{{ $book->name }}</h1>
                    <span class="text-muted small">Page {{ $page->page_number }} of {{ $totalPages }}</span>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="btn-group" role="group">
                        @if ($pagination->onFirstPage())
                            <span class="btn btn-outline-secondary disabled">&larr; Prev</span>
                        @else
                            <a class="btn btn-outline-secondary" href="{{ $pagination->previousPageUrl() }}" rel="prev">&larr; Prev</a>
                        @endif

                        @if ($pagination->hasMorePages())
                            <a class="btn btn-outline-secondary" href="{{ $pagination->nextPageUrl() }}" rel="next">Next &rarr;</a>
                        @else
                            <span class="btn btn-outline-secondary disabled">Next &rarr;</span>
                        @endif
                    </div>

                    <form action="{{ route('books.show', $book) }}" method="GET" class="d-flex align-items-center gap-2">
                        <label for="jump-to-page" class="col-form-label small text-muted text-nowrap">Jump to page</label>
                        <input type="number" id="jump-to-page" name="page" min="1" max="{{ $totalPages }}"
                               value="{{ $page->page_number }}" class="form-control form-control-sm" style="width: 90px;"
                               required>
                        <button type="submit" class="btn btn-sm btn-primary text-nowrap">Jump</button>
                    </form>
                </div>
            </div>

            <div class="card">
                @if ($page->image_url)
                    <div class="card-img-top p-3 pb-0 text-center">
                        <a href="#" class="js-zoom-trigger" data-image-url="{{ $page->image_url }}" data-image-label="Page {{ $page->page_number }}">
                            <p class="text-muted small mt-1">এই পেজ এর ইমেজটি দেখতে এখানে ক্লিক করুন। </p>

                            {{--<img src="{{ $page->image_url }}" alt="Page {{ $page->page_number }}"
                                 class="img-fluid rounded" style="max-height: 320px;">--}}
                        </a>

                    </div>
                @endif

                <div class="card-body">
{{--                    <h2 class="h6 text-uppercase text-muted">Page content</h2>--}}
                    <div class="reading-text">{{ $page->ai_ocr_content ?: $page->content }}</div>

                    @if (! $page->ai_ocr_content)
                        <p class="text-muted small mt-3 mb-0">
                            This page has not been OCR'd yet — showing the originally stored content.
                        </p>
                    @endif
                </div>
            </div>

            <div class="mt-3">
                {{ $pagination->onEachSide(1)->links('partials._page_pagination') }}
            </div>
        </div>
    </div>
@endsection
