<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            try {
                $table->dropUnique('batch_unique_key');
            } catch (\Throwable $e) {
                // Legacy databases may already be missing this index.
            }

            try {
                $table->index(['teacher_id', 'status', 'batch_number'], 'batches_teacher_status_number_idx');
            } catch (\Throwable $e) {
                // Ignore if the index already exists.
            }
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            try {
                $table->dropIndex('batches_teacher_status_number_idx');
            } catch (\Throwable $e) {
                // Ignore if the index is already gone.
            }

            try {
                $table->unique(['company_name', 'department_id', 'class'], 'batch_unique_key');
            } catch (\Throwable $e) {
                // Ignore if the legacy key already exists.
            }
        });
    }
};