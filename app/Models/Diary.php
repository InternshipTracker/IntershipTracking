<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diary extends Model
{
    protected $fillable = [
        'internship_id',
        'student_id',
        'entry_date',
        'topic',
        'progress_description',
        'what_learned',
        'time_spent',
        'hours_studied',
        'challenges_faced',
        'skills_developed',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
