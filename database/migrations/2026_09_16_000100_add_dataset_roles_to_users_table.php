<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('researcher')->after('password')->index();
            $table->string('status', 20)->default('active')->after('role')->index();
            $table->string('institution')->nullable()->after('status');
            $table->string('avatar')->nullable()->after('institution');
            $table->timestamp('last_login_at')->nullable()->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'institution', 'avatar', 'last_login_at']);
        });
    }
};
