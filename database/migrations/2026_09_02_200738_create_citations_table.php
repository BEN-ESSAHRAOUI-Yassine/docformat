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
        Schema::create('citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_analysis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('detected_element_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // author_year, numeric, bracketed, footnote
            $table->text('raw_text');
            $table->string('author')->nullable();
            $table->string('year')->nullable();
            $table->json('numbers')->nullable();
            $table->unsignedInteger('element_index');
            $table->decimal('confidence', 3, 2)->default(0.8);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('bibliography_entry_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citations');
    }
};
