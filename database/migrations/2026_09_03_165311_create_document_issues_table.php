<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_analysis_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('detected_element_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source');
            $table->string('category');
            $table->string('severity')->default('warning');
            $table->text('description');
            $table->text('recommendation')->nullable();
            $table->json('location')->nullable();
            $table->string('decision')->default('pending');
            $table->text('ignored_reason')->nullable();
            $table->string('review_mode')->nullable();
            $table->boolean('probabilistic')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'decision']);
            $table->index(['document_id', 'category']);
            $table->index(['document_id', 'severity']);
            $table->index('document_analysis_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_issues');
    }
};
