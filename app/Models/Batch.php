<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Batch extends Model
{
    protected $fillable = [
        'company_name',
        'department_id',
        'class',
        'teacher_id',
        'batch_number',
        'status',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    public static function nextAvailableNumberForTeacher(int $teacherId): int
    {
        $usedNumbers = static::query()
            ->where('teacher_id', $teacherId)
            ->active()
            ->whereNotNull('batch_number')
            ->orderBy('batch_number')
            ->pluck('batch_number')
            ->map(fn ($number) => (int) $number)
            ->unique()
            ->values()
            ->all();

        return static::nextAvailableNumberFromList($usedNumbers);
    }

    public static function nextAvailableNumberFromList(array $usedNumbers): int
    {
        $nextNumber = 1;

        foreach ($usedNumbers as $usedNumber) {
            if ($usedNumber === $nextNumber) {
                $nextNumber++;
                continue;
            }

            if ($usedNumber > $nextNumber) {
                break;
            }
        }

        return $nextNumber;
    }
}
