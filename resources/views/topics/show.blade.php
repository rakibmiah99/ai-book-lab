@extends('layouts.app')

@section('title', $topic->name.' — '.$book->name)

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            @include('partials._topic_sidebar', ['book' => $book, 'topics' => $topics, 'activeTopic' => $topic])
        </div>

        <div class="col-lg-8">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div>
                    <span class="text-muted small">{{ $book->name }} &middot; Topic {{ $topic->position }}</span>
                    <h1 class="h4 mb-0">{{ $topic->name }}</h1>
                </div>

                @auth
                    <button type="button"
                            class="btn btn-outline-warning favorite-btn js-favorite-toggle {{ $isFavorited ? 'is-favorited' : '' }}"
                            data-url="{{ route('topics.favorite', $topic) }}">
                        <span class="js-favorite-label">{{ $isFavorited ? 'Favourited' : 'Favourite this topic' }}</span>
                    </button>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary">Login to favourite</a>
                @endauth
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    @foreach($topic->refPages as $refPage)
                        <div class="d-flex align-items-center my-4">
                            <hr class="flex-grow-1">
                            <span class="px-3 text-muted">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#refPageModal{{ $refPage->page->id }}">
                                Page {{ $refPage->page->page_number }}
                            </button>
                            </span>
                            <hr class="flex-grow-1">
                        </div>
                        <div class="reading-text">{{ $refPage?->page?->ai_ocr_content ?? "" }}</div>
                    @endforeach

                    {{--@foreach ($topic->details as $detail)
                        <div class="reading-text {{ ! $loop->last ? 'mb-4' : '' }}">{{ $detail->content }}</div>
                    @endforeach--}}
                </div>
            </div>

            @if ($topic->importantNotes->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header bg-white">Important notes</div>
                    <div class="card-body">
                        @foreach ($topic->importantNotes as $note)
                            <div class="mb-4 {{ ! $loop->last ? 'pb-4 border-bottom' : '' }}">
                                <p class="mb-2">{{ $note->note }}</p>

                                @if ($note->punch_line)
                                    <p class="punch-line mb-2">&ldquo;{{ $note->punch_line }}&rdquo;</p>
                                    <a class="btn btn-sm btn-outline-secondary"
                                       target="_blank" rel="noopener"
                                       href="https://twitter.com/intent/tweet?text={{ rawurlencode($note->punch_line) }}">
                                        Share on X
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($topic->refPages->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        Reference pages
                        <span class="text-muted small">(verify this topic against the original scanned pages)</span>
                    </div>
                    <div class="card-body d-flex flex-wrap gap-2">
                        @foreach ($topic->refPages as $refPage)
                            @continue(! $refPage->page)
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal" data-bs-target="#refPageModal{{ $refPage->page->id }}">
                                Page {{ $refPage->page->page_number }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @foreach ($topic->refPages as $refPage)
                    @continue(! $refPage->page)
                    @php $refBookPage = $refPage->page; @endphp
                    <div class="modal fade" id="refPageModal{{ $refBookPage->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Page {{ $refBookPage->page_number }} &mdash; source verification</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    @if ($refBookPage->image_url)
                                        <div class="zoom-frame mb-2 text-center">
                                            <img src="{{ $refBookPage->image_url }}" alt="Page {{ $refBookPage->page_number }}"
                                                 class="img-fluid zoomable-image">
                                        </div>
                                        <p class="text-muted small">Click the image to zoom in/out.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

            <div class="card">
                <div class="card-header bg-white">Suggest a correction or improvement</div>
                <div class="card-body">
                    @auth
                        <form id="feedbackForm" action="{{ route('topics.feedback.store', $topic) }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <textarea name="message" class="form-control" rows="3" required
                                          placeholder="Found a mistake, or think something needs improvement in this topic? Let us know."></textarea>
                            </div>
                            <button type="submit" class="btn btn-accent">Submit note</button>
                            <span id="feedbackFormStatus" class="ms-2 small" style="display:none;"></span>
                        </form>
                    @else
                        <p class="text-muted mb-0">
                            <a href="{{ route('login') }}">Login</a> to report a mistake or suggest an improvement for this topic.
                        </p>
                    @endauth

                    <hr>

                    <div id="feedbackList">
                        @forelse ($topic->feedbacks as $feedback)
                            @include('partials._feedback_item', ['feedback' => $feedback])
                        @empty
                            <p id="feedbackEmpty" class="text-muted mb-0">No notes yet for this topic.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
