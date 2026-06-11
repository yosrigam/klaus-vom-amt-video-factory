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
            ['email' => config('klaus.local_admin.email')],
            [
                'name' => 'Klaus Admin',
                'password' => Hash::make(config('klaus.local_admin.password')),
                'email_verified_at' => now(),
            ],
        );
    }
}
