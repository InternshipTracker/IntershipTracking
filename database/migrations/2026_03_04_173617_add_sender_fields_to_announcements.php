<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('sender_type', 20)->default('teacher')->after('message');
            $table->unsignedBigInteger('sender_id')->nullable()->after('sender_type');
            $table->unsignedBigInteger('parent_id')->nullable()->after('sender_id');
            $table->index(['sender_type', 'sender_id']);
            $table->index('parent_id');
        });

        Schema::table('admin_teacher_announcements', function (Blueprint $table) {
            $table->string('sender_type', 20)->default('superadmin')->after('message');
            $table->unsignedBigInteger('sender_id')->nullable()->after('sender_type');
            $table->unsignedBigInteger('parent_id')->nullable()->after('sender_id');
            $table->index(['sender_type', 'sender_id']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex(['sender_type', 'sender_id']);
            $table->dropIndex(['parent_id']);
            $table->dropColumn(['sender_type', 'sender_id', 'parent_id']);
        });

        Schema::table('admin_teacher_announcements', function (Blueprint $table) {
            $table->dropIndex(['sender_type', 'sender_id']);
            $table->dropIndex(['parent_id']);
            $table->dropColumn(['sender_type', 'sender_id', 'parent_id']);
        });
    }
};
