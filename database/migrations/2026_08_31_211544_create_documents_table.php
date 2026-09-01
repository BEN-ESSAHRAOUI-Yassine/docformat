<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('original_filename');
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('uploaded');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->string('file_hash', 64);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('file_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
