<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
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