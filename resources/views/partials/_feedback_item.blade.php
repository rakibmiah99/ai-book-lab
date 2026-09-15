<div class="border rounded p-3 mb-3 {{ $feedback->isResolved() ? 'feedback-resolved' : 'feedback-pending' }}">
    <div class="d-flex justify-content-between align-items-start">
        <strong>{{ $feedback->user->name }}</strong>
        <span class="badge {{ $feedback->isResolved() ? 'text-bg-success' : 'text-bg-warning' }}">
            {{ $feedback->isResolved() ? 'Corrected' : 'Pending review' }}
        </span>
    </div>
    <p class="mb-2 mt-2">{{ $feedback->message }}</p>
    <p class="text-muted small mb-0">{{ $feedback->created_at->diffForHumans() }}</p>

    @if ($feedback->isResolved())
        <div class="mt-2 pt-2 border-top">
            <p class="small text-success mb-1"><strong>What we changed:</strong></p>
            <p class="small mb-0">{{ $feedback->resolution_note }}</p>
        </div>
    @endif
</div>
