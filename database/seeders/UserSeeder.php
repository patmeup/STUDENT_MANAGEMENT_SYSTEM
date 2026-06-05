<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Tiyaking 'User' model ang naka-import dito
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::truncate();

        User::create([
            'employee_id' => 'superadmin',
            'last_name'   => 'CHIEF',
            'first_name'  => 'IT DIRECTOR',
            'middle_name' => 'SYSTEMS',
            'sex'         => 'Male',
            'email'       => 'itdirector@arellano.edu.ph',
            'role'        => 'super_admin',
            'status'      => 'Active',
            'password'    => Hash::make('super123'), // O pwede ring 'super123' lang dahil may cast na 'hashed' sa model mo ngayon!
        ]);
    }
}