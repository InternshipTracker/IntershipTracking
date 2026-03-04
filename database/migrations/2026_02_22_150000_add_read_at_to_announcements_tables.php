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
        Schema::table('announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('announcements', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('message');
            }
        });

        Schema::table('admin_teacher_announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_teacher_announcements', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('message');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'read_at')) {
                $table->dropColumn('read_at');
            }
        });

        Schema::table('admin_teacher_announcements', function (Blueprint $table) {
            if (Schema::hasColumn('admin_teacher_announcements', 'read_at')) {
                $table->dropColumn('read_at');
            }
        });
    }
};
