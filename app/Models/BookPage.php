<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['book_id', 'page_number', 'content', 'image_path', 'image_url', 'ai_ocr_content'])]
class BookPage extends Model
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
     * @return HasMany<BookTopicRefPage, $this>
     */
    public function topicRefPages(): HasMany
    {
        return $this->hasMany(BookTopicRefPage::class);
    }
}
