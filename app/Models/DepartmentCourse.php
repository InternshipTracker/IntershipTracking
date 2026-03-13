<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentCourse extends Model
{
    protected $fillable = [
        'department_id',
        'name',
        'code',
        'class_names',
    ];

    protected function casts(): array
    {
        return [
            'class_names' => 'array',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function normalizedCode(): string
    {
        return strtoupper(trim($this->code));
    }

    public function normalizedClassNames(): array
    {
        return collect($this->class_names ?? [])
            ->map(fn ($className) => strtoupper(trim((string) $className)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function supportsClass(string $className): bool
    {
        return in_array(strtoupper(trim($className)), $this->normalizedClassNames(), true);
    }

    public static function inferredNameFromCode(string $code): string
    {
        return match (strtoupper(trim($code))) {
            'BCS' => 'Bachelor of Computer Science',
            'BCA' => 'Bachelor of Computer Application',
            'BBA' => 'Bachelor of Business Administration',
            'BSCIT' => 'Bachelor of Science Information Technology',
            'MCS' => 'Master of Computer Science',
            'MCA' => 'Master of Computer Application',
            'MBA' => 'Master of Business Administration',
            'BE' => 'Bachelor of Engineering',
            'ME' => 'Master of Engineering',
            default => strtoupper(trim($code)),
        };
    }
}