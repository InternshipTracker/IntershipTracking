<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminTeacherAnnouncement extends Model
{
    protected $fillable = [
        'superadmin_id',
        'teacher_id',
        'title',
        'message',
        'read_at',
        'sender_type',
        'sender_id',
        'parent_id',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function superadmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'superadmin_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
