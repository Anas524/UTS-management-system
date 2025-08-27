<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@uts.id')],
            [
                'name' => 'UTS Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'Admin@123')),
                'is_admin' => true,
            ]
        );
    }
}
