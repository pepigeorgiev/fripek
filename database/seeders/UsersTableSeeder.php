<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // Create Super Admin
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Pepi',
                'email' => 'admin@example.com',
                'password' => Hash::make('Tolumba123'),
                'role' => User::ROLE_SUPER_ADMIN,
            ]
        );
    }
}


