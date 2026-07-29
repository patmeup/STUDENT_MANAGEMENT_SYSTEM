<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'email', 'registration_date', 'student_type', 'course', // 👈 Idinagdag ang 'email' dito
        'year_level', 'semester', 'last_name', 'first_name', 'middle_name', 'sex', 
        'civil_status', 'date_of_birth', 'nationality', 'place_of_birth', 
        'city_address', 'zip_code', 'contact_no', 'fathers_name', 
        'fathers_occupation', 'mothers_name', 'mothers_occupation', 
        'primary_school_name', 'primary_school_address', 'primary_school_year', 
        'secondary_school_name', 'secondary_school_address', 'secondary_school_year', 
        'college_school_name', 'college_school_address', 'college_school_year', 'credentials',
        'password'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'credentials' => 'array',
        'registration_date' => 'date',
        'date_of_birth' => 'date',
        'password' => 'hashed',
    ];
}