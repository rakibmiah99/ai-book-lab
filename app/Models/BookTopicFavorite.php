<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'book_topic_id'])]
class BookTopicFavorite extends Model
{
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
}
