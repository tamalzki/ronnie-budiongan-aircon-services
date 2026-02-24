<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'rbaircon@admin.com'],
            [
                'name' => 'RB Aircon Admin',
                'password' => Hash::make('rbaircon2026'),
            ]
        );
    }
}