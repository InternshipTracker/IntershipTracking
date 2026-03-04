<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE users DROP INDEX users_username_unique');
        } catch (\Throwable $e) {
            // Index may already be removed.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE users ADD UNIQUE users_username_unique (username)');
        } catch (\Throwable $e) {
            // May fail if duplicate usernames exist.
        }
    }
};
