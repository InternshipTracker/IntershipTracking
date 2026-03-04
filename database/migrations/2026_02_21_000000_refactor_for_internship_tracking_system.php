<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('role')->constrained('departments')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'class')) {
                $table->enum('class', ['FYBCS', 'SYBCS', 'TYBCS'])->nullable()->after('department_id');
            }

            if (! Schema::hasColumn('users', 'is_approved')) {
                $table->boolean('is_approved')->default(true)->after('class');
            }

            if (! Schema::hasColumn('users', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved')->after('is_approved');
            }
        });

        if (! Schema::hasTable('batches')) {
            Schema::create('batches', function (Blueprint $table) {
                $table->id();
                $table->string('company_name');
                $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
                $table->enum('class', ['FYBCS', 'SYBCS', 'TYBCS']);
                $table->timestamps();

                $table->unique(['company_name', 'department_id', 'class'], 'batch_unique_key');
            });
        }

        Schema::table('internships', function (Blueprint $table) {
            if (! Schema::hasColumn('internships', 'teacher_id')) {
                $table->foreignId('teacher_id')->nullable()->after('student_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('internships', 'batch_id')) {
                $table->foreignId('batch_id')->nullable()->after('department_id')->constrained('batches')->nullOnDelete();
            }

            if (! Schema::hasColumn('internships', 'duration')) {
                $table->string('duration')->nullable()->after('company_name');
            }

            if (! Schema::hasColumn('internships', 'joining_letter_path')) {
                $table->string('joining_letter_path')->nullable()->after('end_date');
            }

            if (! Schema::hasColumn('internships', 'approval_pdf_path')) {
                $table->string('approval_pdf_path')->nullable()->after('joining_letter_path');
            }
        });

        if (! Schema::hasTable('diaries')) {
            Schema::create('diaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('internship_id')->constrained('internships')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->date('entry_date');
                $table->string('topic');
                $table->text('progress_description');
                $table->string('time_spent');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->text('message');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('announcements')) {
            Schema::dropIfExists('announcements');
        }

        if (Schema::hasTable('diaries')) {
            Schema::dropIfExists('diaries');
        }

        if (Schema::hasColumn('internships', 'approval_pdf_path')) {
            Schema::table('internships', function (Blueprint $table) {
                $table->dropColumn('approval_pdf_path');
            });
        }

        if (Schema::hasColumn('internships', 'joining_letter_path')) {
            Schema::table('internships', function (Blueprint $table) {
                $table->dropColumn('joining_letter_path');
            });
        }

        if (Schema::hasColumn('internships', 'duration')) {
            Schema::table('internships', function (Blueprint $table) {
                $table->dropColumn('duration');
            });
        }

        if (Schema::hasColumn('internships', 'batch_id')) {
            Schema::table('internships', function (Blueprint $table) {
                $table->dropConstrainedForeignId('batch_id');
            });
        }

        if (Schema::hasColumn('internships', 'teacher_id')) {
            Schema::table('internships', function (Blueprint $table) {
                $table->dropConstrainedForeignId('teacher_id');
            });
        }

        if (Schema::hasTable('batches')) {
            Schema::dropIfExists('batches');
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'approval_status')) {
                $table->dropColumn('approval_status');
            }

            if (Schema::hasColumn('users', 'is_approved')) {
                $table->dropColumn('is_approved');
            }

            if (Schema::hasColumn('users', 'class')) {
                $table->dropColumn('class');
            }

            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }

            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
        });
    }
};
