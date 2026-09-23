<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dataset_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained()->cascadeOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->text('definition')->nullable();
            $table->string('unit', 80)->nullable();
            $table->string('data_type', 30)->default('numeric');
            $table->string('category', 100)->nullable();
            $table->string('access_tier', 30)->default('standard');
            $table->decimal('price_per_cell', 14, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['dataset_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dataset_variables');
    }
};
