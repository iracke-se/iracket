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
            // Rename name to first_name
            $table->renameColumn('name', 'first_name');
        });

        Schema::table('users', function (Blueprint $table) {
            // Add new columns
            $table->string('last_name')->after('first_name');
            $table->string('profile_picture')->nullable()->after('last_name');
            $table->boolean('terms_accepted')->default(false)->after('remember_token');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_accepted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_name', 'profile_picture', 'terms_accepted', 'terms_accepted_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('first_name', 'name');
        });
    }
};
