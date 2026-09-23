<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_connector_id')->constrained()->cascadeOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trigger', 20)->default('manual');
            $table->string('status', 20)->default('queued')->index();
            $table->unsignedInteger('rows_count')->default(0);
            $table->unsignedInteger('valid_rows_count')->default(0);
            $table->unsignedInteger('warning_rows_count')->default(0);
            $table->unsignedInteger('invalid_rows_count')->default(0);
            $table->unsignedInteger('created_rows_count')->default(0);
            $table->unsignedInteger('updated_rows_count')->default(0);
            $table->unsignedInteger('unchanged_rows_count')->default(0);
            $table->char('response_checksum', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['data_connector_id', 'created_at'], 'sync_run_connector_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_sync_runs');
    }
};
