<?php

namespace Database\Seeders;


use App\Models\Course;
use App\Models\Department;
use App\Models\DepartmentCourse;
use App\Models\User;
use App\Models\CoordinatorProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create departments
        $departments = [
            'Computer Science',
            'Information Technology',
            'Electronics & Communication',
            'Mechanical Engineering',
            'Civil Engineering',
        ];

        foreach ($departments as $name) {
            Department::firstOrCreate(['name' => $name]);
        }

        $computerScience = Department::where('name', 'Computer Science')->first();

        if ($computerScience) {
            DepartmentCourse::updateOrCreate(
                [
                    'department_id' => $computerScience->id,
                    'code' => 'BCS',
                ],
                [
                    'name' => 'Bachelor of Computer Science',
                    'class_names' => ['FYBCS', 'SYBCS', 'TYBCS'],
                ]
            );
        }

        // Create courses
        $courses = [
            ['name' => 'Bachelor of Computer Science',              'code' => 'BCS'],
            ['name' => 'Bachelor of Computer Application',          'code' => 'BCA'],
            ['name' => 'Bachelor of Business Administration',       'code' => 'BBA'],
            ['name' => 'Bachelor of Technology',                    'code' => 'B.Tech'],
            ['name' => 'Bachelor of Engineering',                   'code' => 'BE'],
            ['name' => 'Bachelor of Science (IT)',                  'code' => 'BSc IT'],
            ['name' => 'Master of Computer Application',            'code' => 'MCA'],
            ['name' => 'Master of Business Administration',         'code' => 'MBA'],
            ['name' => 'Master of Technology',                      'code' => 'M.Tech'],
            ['name' => 'Master of Science (IT)',                    'code' => 'MSc IT'],
        ];

        foreach ($courses as $course) {
            Course::firstOrCreate(['code' => $course['code']], $course);
        }

        // Ensure Super Admin credentials stay consistent across environments
        User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name'      => 'Super Admin',
                'password'  => Hash::make('Admin123'),
                'role'      => 'superadmin',
                'phone'     => '9999999999',
                'is_active' => true,
            ]
        );

        // Create 10 Coordinator users with profiles
        $coordinatorData = [
            ['name' => 'Dr. Rajesh Kumar',    'email' => 'rajesh.kumar@its.com',    'phone' => '9876543201', 'dept' => 'Computer Science'],
            ['name' => 'Prof. Anita Sharma',   'email' => 'anita.sharma@its.com',    'phone' => '9876543202', 'dept' => 'Computer Science'],
            ['name' => 'Dr. Vikram Singh',     'email' => 'vikram.singh@its.com',    'phone' => '9876543203', 'dept' => 'Information Technology'],
            ['name' => 'Prof. Priya Patel',    'email' => 'priya.patel@its.com',     'phone' => '9876543204', 'dept' => 'Information Technology'],
            ['name' => 'Dr. Suresh Reddy',     'email' => 'suresh.reddy@its.com',    'phone' => '9876543205', 'dept' => 'Electronics & Communication'],
            ['name' => 'Prof. Meena Gupta',    'email' => 'meena.gupta@its.com',     'phone' => '9876543206', 'dept' => 'Electronics & Communication'],
            ['name' => 'Dr. Arun Joshi',       'email' => 'arun.joshi@its.com',      'phone' => '9876543207', 'dept' => 'Mechanical Engineering'],
            ['name' => 'Prof. Kavita Desai',   'email' => 'kavita.desai@its.com',    'phone' => '9876543208', 'dept' => 'Mechanical Engineering'],
            ['name' => 'Dr. Ramesh Nair',      'email' => 'ramesh.nair@its.com',     'phone' => '9876543209', 'dept' => 'Civil Engineering'],
            ['name' => 'Prof. Sunita Verma',   'email' => 'sunita.verma@its.com',    'phone' => '9876543210', 'dept' => 'Civil Engineering'],
        ];

        foreach ($coordinatorData as $data) {
            $department = Department::where('name', $data['dept'])->first();

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'      => $data['name'],
                    'password'  => Hash::make('password'),
                    'role'      => 'coordinator',
                    'phone'     => $data['phone'],
                    'is_active' => true,
                ]
            );

            CoordinatorProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['department_id' => $department->id]
            );
        }
    }
}
