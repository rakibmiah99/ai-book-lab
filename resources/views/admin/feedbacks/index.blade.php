@extends('layouts.app')

@section('title', 'Feedback Inbox — AI Book Lab')

@section('content')
    <h1 class="h3 mb-4">Reader feedback</h1>

    @if ($feedbacks->isEmpty())
        <div class="alert alert-light border">No feedback has been submitted yet.</div>
    @else
        @foreach ($feedbacks as $feedback)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <a href="{{ route('topics.show', $feedback->topic) }}">{{ $feedback->topic->name }}</a>
                            <span class="text-muted small d-block">
                                {{ $feedback->topic->book->name }} &middot; reported by {{ $feedback->user->name }}
                                &middot; {{ $feedback->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <span class="badge {{ $feedback->isResolved() ? 'text-bg-success' : 'text-bg-warning' }}">
                            {{ $feedback->isResolved() ? 'Corrected' : 'Pending review' }}
                        </span>
                    </div>

                    <p class="mt-3 mb-3">{{ $feedback->message }}</p>

                    @if ($feedback->isResolved())
                        <div class="border-top pt-3">
                            <p class="small text-success mb-1"><strong>Correction implemented:</strong></p>
                            <p class="small mb-0">{{ $feedback->resolution_note }}</p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('admin.feedbacks.update', $feedback) }}" class="border-top pt-3">
                            @csrf
                            @method('PATCH')
                            <div class="mb-2">
                                <label class="form-label small text-muted">
                                    Describe the correction implemented in the book so readers can see it
                                </label>
                                <textarea name="resolution_note" class="form-control" rows="2" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-accent btn-sm">Mark as corrected</button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="mt-4">
            {{ $feedbacks->links() }}
        </div>
    @endif
@endsection
