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
        if (! Schema::hasTable('books')) {
            Schema::create('books', function (Blueprint $table) {
                $table->id();
                $table->string('name', 500);
                $table->string('writer_name', 500)->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('books', function (Blueprint $table) {
            if (! Schema::hasColumn('books', 'name')) {
                $table->string('name', 500);
            }

            if (! Schema::hasColumn('books', 'writer_name')) {
                $table->string('writer_name', 500)->nullable();
            }

            if (! Schema::hasColumn('books', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
