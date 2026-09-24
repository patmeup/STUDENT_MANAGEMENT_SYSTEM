<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_period_id',
        'subject_id',
        'professor_id',
        'schedule_type',
        'section_name',
        'class_days',
        'start_time',
        'end_time',
        'room_assignment',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}