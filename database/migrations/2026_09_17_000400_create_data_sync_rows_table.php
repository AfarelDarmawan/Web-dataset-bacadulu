<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sync_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_sync_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dataset_variable_id')->constrained()->cascadeOnDelete();
            $table->foreignId('existing_observation_id')->nullable()->constrained('dataset_observations')->nullOnDelete();
            $table->unsignedInteger('row_index');
            $table->string('external_key', 191);
            $table->string('geography_code', 100);
            $table->string('geography_name');
            $table->string('period', 30);
            $table->decimal('value_numeric', 24, 6)->nullable();
            $table->text('value_text')->nullable();
            $table->string('source_reference', 500);
            $table->string('proposed_action', 20)->default('create')->index();
            $table->string('status', 20)->default('proposed')->index();
            $table->string('validation_status', 20)->default('valid')->index();
            $table->json('validation_issues')->nullable();
            $table->json('source_payload')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['data_sync_run_id', 'external_key'], 'sync_row_external_unique');
            $table->index(['data_sync_run_id', 'status'], 'sync_row_run_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_sync_rows');
    }
};
