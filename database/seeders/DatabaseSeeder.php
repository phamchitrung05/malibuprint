<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tạo tài khoản quản trị mẫu để có thể đăng nhập vào Filament.
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password'],
        );

        // Gọi seeder dữ liệu nghiệp vụ sau khi các bảng cha đã sẵn sàng.
        $this->call(DemoDataSeeder::class);
    }
}
