<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskSubmission extends Model
{
    public function task()
    {
        return $this->belongsTo(StudentTask::class, 'student_task_id');
    }
}
