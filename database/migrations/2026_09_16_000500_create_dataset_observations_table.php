<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dataset_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dataset_variable_id')->constrained()->cascadeOnDelete();
            $table->string('geography_code', 100)->default('national');
            $table->string('geography_name')->default('Indonesia');
            $table->string('period', 30);
            $table->decimal('value_numeric', 24, 6)->nullable();
            $table->text('value_text')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('quality_status', 30)->default('reviewed')->index();
            $table->timestamps();

            $table->unique(
                ['dataset_id', 'dataset_variable_id', 'geography_code', 'period'],
                'dataset_observation_unique'
            );
            $table->index(['dataset_id', 'period']);
            $table->index(['dataset_id', 'geography_code']);
            $table->index(['dataset_id', 'quality_status'], 'dataset_observation_quality');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dataset_observations');
    }
};
