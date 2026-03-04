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
        Schema::table('diaries', function (Blueprint $table) {
            $table->text('what_learned')->nullable()->after('progress_description');
            $table->decimal('hours_studied', 4, 2)->nullable()->after('time_spent');
            $table->text('challenges_faced')->nullable()->after('hours_studied');
            $table->text('skills_developed')->nullable()->after('challenges_faced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diaries', function (Blueprint $table) {
            $table->dropColumn(['what_learned', 'hours_studied', 'challenges_faced', 'skills_developed']);
        });
    }
};
