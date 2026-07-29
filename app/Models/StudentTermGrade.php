<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentTermGrade extends Model
{
        protected $fillable = [
            'schedule_id',
            'student_number',
            'term',
            'class_standing',
            'major_exam',
            'equivalent',
            'is_manual',
            'is_submitted',
        ];
}
