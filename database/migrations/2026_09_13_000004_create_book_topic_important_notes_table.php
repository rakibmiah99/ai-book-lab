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
        Schema::create('book_topic_important_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_topic_id')->constrained('book_topics')->cascadeOnDelete();
            $table->text('note');
            $table->text('punch_line')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_topic_important_notes');
    }
};
