<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('disk', 40)->default('local');
            $table->string('mime_type', 120);
            $table->string('extension', 16);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->string('status', 30)->default('uploaded')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['dataset_id', 'sha256'], 'source_document_dataset_hash_unique');
            $table->index(['dataset_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_documents');
    }
};
