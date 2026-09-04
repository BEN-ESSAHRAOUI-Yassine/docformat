<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('queued');
            $table->float('quality_score')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_items');
    }
};
