<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'writer_name', 'topics_organized_through_page'])]
class Book extends Model
{
    use HasFactory;

    /**
     * @return HasMany<BookPage, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(BookPage::class);
    }

    /**
     * @return HasMany<BookTopic, $this>
     */
    public function topics(): HasMany
    {
        return $this->hasMany(BookTopic::class);
    }
}
