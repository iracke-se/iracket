<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_connected')->default(false)->after('email_verified_at');
            $table->boolean('is_active_player')->default(true)->after('is_connected');
            $table->boolean('accepts_push_notifications')->default(false)->after('is_active_player');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_connected', 'is_active_player', 'accepts_push_notifications']);
        });
    }
};
