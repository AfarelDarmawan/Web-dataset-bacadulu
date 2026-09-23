<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datasets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_provider_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('code', 80)->unique();
            $table->text('summary');
            $table->longText('description')->nullable();
            $table->longText('methodology')->nullable();
            $table->string('category', 100)->index();
            $table->string('frequency', 50)->nullable();
            $table->string('geographic_level', 80)->nullable();
            $table->unsignedSmallInteger('period_start')->nullable();
            $table->unsignedSmallInteger('period_end')->nullable();
            $table->string('license', 120)->nullable();
            $table->string('source_url')->nullable();
            $table->string('access_type', 30)->default('restricted')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datasets');
    }
};
