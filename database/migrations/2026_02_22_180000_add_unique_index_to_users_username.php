<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('username', 'users_username_unique');
            });
        } catch (\Throwable $e) {
            try {
                DB::statement('CREATE UNIQUE INDEX users_username_unique ON users (username)');
            } catch (\Throwable $ignored) {
                // Index already exists or cannot be created in current state.
            }
        }
    }

    public function down(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_username_unique');
            });
        } catch (\Throwable $e) {
            try {
                DB::statement('DROP INDEX users_username_unique');
            } catch (\Throwable $ignored) {
                // Index does not exist.
            }
        }
    }
};
