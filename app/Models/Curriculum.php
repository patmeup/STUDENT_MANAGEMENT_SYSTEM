<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    use HasFactory;

    // Dahil 'curricula' ang plural ng curriculum sa database table name ng Laravel
    protected $table = 'curricula';

    protected $fillable = [
        'subject_id',
        'course',
        'year_level',
        'semester'
    ];

    // Relasyon papunta sa Subject Model para makuha ang Code, Title, at Units
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}