<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'book_topic_id', 'message'])]
class BookTopicFeedback extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_RESOLVED = 'resolved';

    protected $table = 'book_topic_feedbacks';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<BookTopic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(BookTopic::class, 'book_topic_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    /**
     * Scope a query to only pending feedback.
     *
     * @param  Builder<BookTopicFeedback>  $query
     * @return Builder<BookTopicFeedback>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Determine if the feedback has been resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Mark the feedback as resolved with a note describing the correction made.
     */
    public function markResolved(User $resolver, string $resolutionNote): void
    {
        // Set attributes directly — these are admin-only state transitions and
        // intentionally excluded from the model's #[Fillable] allowlist.
        $this->status = self::STATUS_RESOLVED;
        $this->resolution_note = $resolutionNote;
        $this->resolved_by_id = $resolver->id;
        $this->resolved_at = now();
        $this->save();
    }
}
