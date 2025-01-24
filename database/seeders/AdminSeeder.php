<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('Tolumba123!'),
            'role' => User::ROLE_SUPER_ADMIN
        ]);

        User::create([
            'name' => 'Admin Backup',
            'email' => 'pepi.georgiev@yahoo.com',
            'password' => Hash::make('TOLUMBA1!'),
            'role' => User::ROLE_ADMIN
        ]);
    }
}