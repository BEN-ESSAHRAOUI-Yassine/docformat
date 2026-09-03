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
        Schema::create('bibliography_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_analysis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('detected_element_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entry_type'); // article, book, chapter, conference, online, thesis, other
            $table->json('authors');
            $table->text('title');
            $table->string('year')->nullable();
            $table->string('journal')->nullable();
            $table->string('publisher')->nullable();
            $table->string('volume')->nullable();
            $table->string('issue')->nullable();
            $table->string('pages')->nullable();
            $table->string('doi')->nullable();
            $table->string('url')->nullable();
            $table->string('access_date')->nullable();
            $table->json('extra_fields')->nullable();
            $table->text('raw_text');
            $table->unsignedInteger('element_index');
            $table->boolean('is_duplicate')->default(false);
            $table->string('duplicate_group_id')->nullable();
            $table->decimal('duplicate_confidence', 3, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bibliography_entries');
    }
};
