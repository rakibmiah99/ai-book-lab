@extends('layouts.app')

@section('title', 'My Favourites — AI Book Lab')

@section('content')
    <h1 class="h3 mb-4">My favourite topics</h1>

    @if ($favoriteTopics->isEmpty())
        <div class="alert alert-light border">
            You haven't favourited any topics yet. Open a topic while reading and click
            <strong>Favourite this topic</strong> to save it here.
        </div>
    @else
        <div class="list-group">
            @foreach ($favoriteTopics as $topic)
                <a href="{{ route('topics.show', $topic) }}"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span>
                        <strong>{{ $topic->name }}</strong>
                        <span class="text-muted small d-block">{{ $topic->book->name }}</span>
                    </span>
                    <span class="badge text-bg-light border">Topic {{ $topic->position }}</span>
                </a>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $favoriteTopics->links() }}
        </div>
    @endif
@endsection
