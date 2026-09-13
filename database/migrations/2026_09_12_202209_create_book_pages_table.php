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
        if (! Schema::hasTable('book_pages')) {
            Schema::create('book_pages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
                $table->integer('page_number');
                $table->longText('content');
                $table->string('image_path', 1000)->nullable();
                $table->string('image_url', 1000)->nullable();
                $table->timestamps();

                $table->unique(['book_id', 'page_number'], 'unique_book_page');
            });

            return;
        }

        Schema::table('book_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('book_pages', 'book_id')) {
                $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('book_pages', 'page_number')) {
                $table->integer('page_number');
            }

            if (! Schema::hasColumn('book_pages', 'content')) {
                $table->longText('content');
            }

            if (! Schema::hasColumn('book_pages', 'image_path')) {
                $table->string('image_path', 1000)->nullable();
            }

            if (! Schema::hasColumn('book_pages', 'image_url')) {
                $table->string('image_url', 1000)->nullable();
            }

            if (! Schema::hasColumn('book_pages', 'created_at')) {
                $table->timestamps();
            }

            if (! Schema::hasIndex('book_pages', 'unique_book_page')) {
                $table->unique(['book_id', 'page_number'], 'unique_book_page');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_pages');
    }
};
