<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_access_requests', function (Blueprint $table) {
            $table->string('payment_status', 30)
                ->default('not_required')
                ->index()
                ->after('estimated_price');

            $table->string('payment_reference', 120)
                ->nullable()
                ->after('payment_status');

            $table->timestamp('paid_at')
                ->nullable()
                ->after('payment_reference');
        });

        DB::table('data_access_requests')
            ->where('estimated_price', '>', 0)
            ->where('status', 'approved')
            ->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

        DB::table('data_access_requests')
            ->where('estimated_price', '>', 0)
            ->where('status', '!=', 'approved')
            ->update([
                'payment_status' => 'awaiting_invoice',
            ]);
    }

    public function down(): void
    {
        Schema::table('data_access_requests', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);

            $table->dropColumn([
                'payment_status',
                'payment_reference',
                'paid_at',
            ]);
        });
    }
};  