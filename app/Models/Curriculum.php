<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    use HasFactory;
    
    protected $table = 'curricula';

    protected $fillable = [
        'subject_id',
        'course',
        'year_level',
        'semester'
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}