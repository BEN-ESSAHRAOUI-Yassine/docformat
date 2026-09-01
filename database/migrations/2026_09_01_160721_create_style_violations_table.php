<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('style_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_analysis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('detected_element_id')->nullable()->constrained()->nullOnDelete();
            $table->string('check_type');
            $table->json('expected_value')->nullable();
            $table->json('actual_value')->nullable();
            $table->enum('severity', ['error', 'warning', 'info'])->default('warning');
            $table->string('category');
            $table->text('description');
            $table->text('recommendation')->nullable();
            $table->timestamps();

            $table->index(['document_analysis_id', 'severity']);
            $table->index(['document_analysis_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('style_violations');
    }
};
