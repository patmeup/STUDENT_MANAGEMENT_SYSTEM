<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'email', 'registration_date', 'student_type', 'course',
        'year_level', 'semester', 'section', 'status', 'last_name', 'first_name', 'middle_name', 'sex',
        'civil_status', 'date_of_birth', 'nationality', 'place_of_birth',
        'city_address', 'zip_code', 'contact_no', 'fathers_name',
        'fathers_occupation', 'mothers_name', 'mothers_occupation',

        'elementary_school_name', 'elementary_school_address', 'elementary_school_year',
        'junior_hs_school_name', 'junior_hs_school_address', 'junior_hs_school_year',
        'senior_hs_school_name', 'senior_hs_school_address', 'senior_hs_school_year',
        'vocational_school_name', 'vocational_school_address', 'vocational_school_year',
        'college_school_name', 'college_school_address', 'college_school_year',

        'credentials', 'password'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'credentials'       => 'array',
        'registration_date' => 'date',
        'date_of_birth'     => 'date',
        'password'          => 'hashed',

        'elementary_school_name'    => 'array',
        'elementary_school_address' => 'array',
        'elementary_school_year'    => 'array',
        'junior_hs_school_name'     => 'array',
        'junior_hs_school_address'  => 'array',
        'junior_hs_school_year'     => 'array',
        'senior_hs_school_name'     => 'array',
        'senior_hs_school_address'  => 'array',
        'senior_hs_school_year'     => 'array',
        'vocational_school_name'    => 'array',
        'vocational_school_address' => 'array',
        'vocational_school_year'    => 'array',
        'college_school_name'       => 'array',
        'college_school_address'    => 'array',
        'college_school_year'       => 'array',
    ];
}
