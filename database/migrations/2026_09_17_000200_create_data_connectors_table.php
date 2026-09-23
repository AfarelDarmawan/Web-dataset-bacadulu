<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_connectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dataset_variable_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 160);
            $table->string('type', 30)->default('bps')->index();
            $table->string('status', 20)->default('active')->index();
            $table->string('schedule', 20)->default('manual')->index();
            $table->json('config');
            $table->timestamp('last_started_at')->nullable();
            $table->timestamp('last_succeeded_at')->nullable();
            $table->timestamp('next_sync_at')->nullable()->index();
            $table->unsignedSmallInteger('consecutive_failures')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['dataset_variable_id', 'type'], 'connector_variable_type_unique');
            $table->index(['status', 'next_sync_at'], 'connector_due_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_connectors');
    }
};
