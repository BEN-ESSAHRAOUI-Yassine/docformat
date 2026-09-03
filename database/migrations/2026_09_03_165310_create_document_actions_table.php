<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type');
            $table->string('element_type')->nullable();
            $table->unsignedBigInteger('element_id')->nullable();
            $table->string('origin')->default('automatic');
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->json('payload')->nullable();
            $table->string('reversibility')->default('full');
            $table->string('bulk_id')->nullable();
            $table->timestamp('undone_at')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'bulk_id']);
            $table->index(['document_id', 'origin']);
            $table->index(['document_id', 'action_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_actions');
    }
};
