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
        Schema::create('abbreviations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_analysis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('detected_element_id')->nullable()->constrained()->nullOnDelete();
            $table->string('abbreviation');
            $table->text('full_form');
            $table->unsignedInteger('definition_element_index');
            $table->unsignedInteger('usage_count')->default(0);
            $table->json('occurrences')->nullable();
            $table->boolean('is_consistent')->default(true);
            $table->json('inconsistent_forms')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abbreviations');
    }
};
