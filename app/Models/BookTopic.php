<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['book_id', 'name', 'slug', 'position'])]
class BookTopic extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * @return HasMany<BookTopicDetail, $this>
     */
    public function details(): HasMany
    {
        return $this->hasMany(BookTopicDetail::class);
    }

    /**
     * @return HasMany<BookTopicRefPage, $this>
     */
    public function refPages(): HasMany
    {
        return $this->hasMany(BookTopicRefPage::class);
    }

    /**
     * @return HasMany<BookTopicImportantNote, $this>
     */
    public function importantNotes(): HasMany
    {
        return $this->hasMany(BookTopicImportantNote::class);
    }

    /**
     * @return HasMany<BookTopicFeedback, $this>
     */
    public function feedbacks(): HasMany
    {
        return $this->hasMany(BookTopicFeedback::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_topic_favorites', 'book_topic_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Determine if the given user has favorited this topic.
     */
    public function isFavoritedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->favoritedByUsers()->where('user_id', $user->id)->exists();
    }
}
