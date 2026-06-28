<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_code',
        'subject_title',
        'lecture_units',
        'lab_units',
        'total_units',
        'professor_id',
    ];

    public function professor()
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class, 'subject_id');
    }
}