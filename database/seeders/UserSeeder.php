<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo admin mặc định
        User::factory()->admin()->create([
            'storage_limit' => 1024 * 1024 * 1024 * 50, // 50GB
        ]);

        // Tạo 5 user test
        User::factory()->count(5)->create();
    }
}
