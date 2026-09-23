<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_extraction_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_extraction_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dataset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matched_variable_id')->nullable()->constrained('dataset_variables')->nullOnDelete();
            $table->foreignId('applied_observation_id')->nullable()->constrained('dataset_observations')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('row_index');
            $table->string('variable_code', 100);
            $table->string('variable_name');
            $table->text('variable_definition')->nullable();
            $table->string('unit', 80)->nullable();
            $table->string('data_type', 30)->default('numeric');
            $table->string('geography_code', 100)->default('national');
            $table->string('geography_name')->default('Indonesia');
            $table->string('period', 30);
            $table->decimal('value_numeric', 24, 6)->nullable();
            $table->text('value_text')->nullable();
            $table->string('source_locator', 255);
            $table->text('source_excerpt')->nullable();
            $table->decimal('confidence', 5, 4)->default(0);
            $table->string('status', 30)->default('proposed')->index();
            $table->string('validation_status', 30)->default('valid')->index();
            $table->json('validation_issues')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['ai_extraction_job_id', 'row_index'], 'ai_extraction_job_row_unique');
            $table->index(['ai_extraction_job_id', 'status'], 'ai_extraction_row_status');
            $table->index(['dataset_id', 'variable_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_extraction_rows');
    }
};
