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
        Schema::create('book_topics', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('book_id');
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
            $table->string('name', 500);
            $table->string('slug', 500);
            $table->integer('position')->default(1);
            $table->timestamps();

            $table->unique(['book_id', 'slug'], 'unique_book_topic_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_topics');
    }
};
