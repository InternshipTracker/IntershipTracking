<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->json('class_names')->nullable();
            $table->timestamps();

            $table->unique(['department_id', 'code']);
        });

        $this->backfillDepartmentCourses();
    }

    public function down(): void
    {
        Schema::dropIfExists('department_courses');
    }

    private function backfillDepartmentCourses(): void
    {
        $now = now();

        $departmentIds = DB::table('departments')
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [trim((string) $name) => (int) $id]);

        $aggregated = [];

        $registerClass = function (int $departmentId, string $className) use (&$aggregated): void {
            $normalizedClass = strtoupper(trim($className));

            if (! preg_match('/^(MCS|MCA|FY|SY|TY|ME)(.+)$/', $normalizedClass, $matches)) {
                return;
            }

            $code = preg_replace('/[^A-Z0-9]+/', '', strtoupper(trim($matches[2])));

            if ($code === '') {
                return;
            }

            $key = $departmentId.'|'.$code;

            if (! isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'department_id' => $departmentId,
                    'name' => match ($code) {
                        'BCS' => 'Bachelor of Computer Science',
                        'BCA' => 'Bachelor of Computer Application',
                        'BBA' => 'Bachelor of Business Administration',
                        'BSCIT' => 'Bachelor of Science Information Technology',
                        'MCS' => 'Master of Computer Science',
                        'MCA' => 'Master of Computer Application',
                        'MBA' => 'Master of Business Administration',
                        'BE' => 'Bachelor of Engineering',
                        'ME' => 'Master of Engineering',
                        default => $code,
                    },
                    'code' => $code,
                    'class_names' => [],
                ];
            }

            if (! in_array($normalizedClass, $aggregated[$key]['class_names'], true)) {
                $aggregated[$key]['class_names'][] = $normalizedClass;
            }
        };

        DB::table('users')
            ->select('department_id', 'class')
            ->where('role', 'student')
            ->whereNotNull('department_id')
            ->whereNotNull('class')
            ->orderBy('department_id')
            ->get()
            ->each(fn ($row) => $registerClass((int) $row->department_id, (string) $row->class));

        DB::table('teacher_classes')
            ->join('users', 'users.id', '=', 'teacher_classes.teacher_id')
            ->select('users.department_id', 'teacher_classes.class_name')
            ->whereNotNull('users.department_id')
            ->orderBy('users.department_id')
            ->get()
            ->each(fn ($row) => $registerClass((int) $row->department_id, (string) $row->class_name));

        if ($departmentIds->has('Computer Science')) {
            $computerScienceId = $departmentIds['Computer Science'];
            $key = $computerScienceId.'|BCS';

            $aggregated[$key] = [
                'department_id' => $computerScienceId,
                'name' => 'Bachelor of Computer Science',
                'code' => 'BCS',
                'class_names' => collect($aggregated[$key]['class_names'] ?? ['FYBCS', 'SYBCS', 'TYBCS'])
                    ->merge(['FYBCS', 'SYBCS', 'TYBCS'])
                    ->map(fn ($className) => strtoupper(trim((string) $className)))
                    ->unique()
                    ->values()
                    ->all(),
            ];
        }

        foreach ($aggregated as $record) {
            DB::table('department_courses')->updateOrInsert(
                [
                    'department_id' => $record['department_id'],
                    'code' => $record['code'],
                ],
                [
                    'name' => $record['name'],
                    'class_names' => json_encode($record['class_names']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
};