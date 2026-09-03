<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_analysis_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('quality_score')->nullable();
            $table->json('sections')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_reports');
    }
};
