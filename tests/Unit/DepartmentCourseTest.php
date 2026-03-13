<?php

namespace Tests\Unit;

use App\Models\DepartmentCourse;
use PHPUnit\Framework\TestCase;

class DepartmentCourseTest extends TestCase
{
    public function test_it_normalizes_course_codes_and_class_names(): void
    {
        $course = new DepartmentCourse([
            'code' => 'bcs',
            'class_names' => [' fybcs ', 'FYBCS', 'sybcs', ''],
        ]);

        $this->assertSame('BCS', $course->normalizedCode());
        $this->assertSame(['FYBCS', 'SYBCS'], $course->normalizedClassNames());
        $this->assertTrue($course->supportsClass('sybcs'));
        $this->assertFalse($course->supportsClass('TYBCA'));
    }
}