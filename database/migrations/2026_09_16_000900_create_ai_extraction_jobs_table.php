<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_extraction_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dataset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('queued')->index();
            $table->string('extractor', 40);
            $table->string('model', 100)->nullable();
            $table->string('prompt_version', 40)->default('dataset-extract-v1');
            $table->decimal('overall_confidence', 5, 4)->nullable();
            $table->string('response_id')->nullable();
            $table->json('usage')->nullable();
            $table->text('summary')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['dataset_id', 'created_at']);
            $table->index(['source_document_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_extraction_jobs');
    }
};
