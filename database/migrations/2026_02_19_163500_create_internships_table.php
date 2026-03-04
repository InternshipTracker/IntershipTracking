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
        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade')->comment('Student who created internship');
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('type')->comment('individual or group');
            $table->string('group_name')->nullable();
            $table->integer('max_members')->nullable();
            $table->string('company_name');
            $table->text('company_address');
            $table->integer('duration_weeks');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('draft')->comment('draft, pending, verified_by_coordinator, approved_by_department, rejected, completed');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internships');
    }
};
