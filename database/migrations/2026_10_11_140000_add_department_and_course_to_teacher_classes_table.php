<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_classes', function (Blueprint $table) {
            if (! Schema::hasColumn('teacher_classes', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('teacher_id')->constrained('departments')->nullOnDelete();
            }
            if (! Schema::hasColumn('teacher_classes', 'department_course_id')) {
                $table->foreignId('department_course_id')->nullable()->after('department_id')->constrained('department_courses')->nullOnDelete();
            }
        });

        $indexNames = collect(DB::select('SHOW INDEX FROM teacher_classes'))
            ->pluck('Key_name')
            ->unique();

        if ($indexNames->contains('teacher_classes_teacher_id_class_name_unique')) {
            Schema::table('teacher_classes', function (Blueprint $table) {
                $table->dropUnique('teacher_classes_teacher_id_class_name_unique');
            });
        }

        if (! $indexNames->contains('teacher_class_assignment_unique')) {
            Schema::table('teacher_classes', function (Blueprint $table) {
                $table->unique(['teacher_id', 'department_id', 'department_course_id', 'class_name'], 'teacher_class_assignment_unique');
            });
        }

        DB::table('teacher_classes')
            ->whereNull('department_id')
            ->get(['id', 'teacher_id'])
            ->each(function (object $assignment): void {
                $departmentId = DB::table('users')->where('id', $assignment->teacher_id)->value('department_id');
                if ($departmentId) {
                    DB::table('teacher_classes')->where('id', $assignment->id)->update([
                        'department_id' => $departmentId,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('teacher_classes', function (Blueprint $table) {
            $table->dropUnique('teacher_class_assignment_unique');
            $table->dropConstrainedForeignId('department_course_id');
            $table->dropConstrainedForeignId('department_id');
            $table->unique(['teacher_id', 'class_name']);
        });
    }
};
