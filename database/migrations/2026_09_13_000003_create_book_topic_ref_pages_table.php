<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('book_topic_ref_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_topic_id')->constrained('book_topics')->cascadeOnDelete();
            $table->bigInteger('book_page_id');
            $table->foreign('book_page_id')->references('id')->on('book_pages')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['book_topic_id', 'book_page_id'], 'unique_book_topic_ref_page');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_topic_ref_pages');
    }
};
