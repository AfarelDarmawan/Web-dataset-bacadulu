<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 40)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dataset_id')->constrained()->cascadeOnDelete();
            $table->json('variable_ids');
            $table->json('geographies')->nullable();
            $table->json('periods')->nullable();
            $table->text('research_purpose');
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedBigInteger('estimated_cells')->default(0);
            $table->decimal('estimated_price', 16, 2)->default(0);
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_access_requests');
    }
};
