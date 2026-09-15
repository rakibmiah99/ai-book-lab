@php
    $activeTopicId = $activeTopic->id ?? null;
@endphp

<div class="topic-sidebar">
    <div class="card">
        <div class="card-header bg-white">
            <strong>{{ $book->name }}</strong>
            @if ($book->writer_name)
                <div class="text-muted small">{{ $book->writer_name }}</div>
            @endif
        </div>
        <div class="list-group list-group-flush">
            <a href="{{ route('books.show', $book) }}"
               class="list-group-item list-group-item-action {{ ! $activeTopicId && request()->routeIs('books.show') ? 'active' : '' }}">
                <i class="me-1">&#128214;</i> Read page-by-page
            </a>
        </div>

        @if ($topics->isNotEmpty())
            <div class="card-header bg-white border-top">
                <span class="small text-uppercase text-muted">Topics</span>
            </div>
            <div class="list-group list-group-flush" style="max-height: 60vh; overflow-y: auto;">
                @foreach ($topics as $topic)
                    <a href="{{ route('topics.show', $topic) }}"
                       class="list-group-item list-group-item-action {{ $activeTopicId === $topic->id ? 'active' : '' }}">
                        {{ $topic->position }}. {{ $topic->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
