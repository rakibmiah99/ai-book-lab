<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['book_topic_id', 'note', 'punch_line'])]
class BookTopicImportantNote extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<BookTopic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(BookTopic::class, 'book_topic_id');
    }
}
