<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@klaus-vom-amt.local'],
            [
                'name' => 'Klaus Admin',
                'password' => Hash::make('klaus-admin-change-me'),
                'email_verified_at' => now(),
            ],
        );
    }
}
