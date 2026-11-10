<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // M4: akun seed memakai kata sandi yang diketahui publik,
        // jadi dilarang keras dibuat di production. Gate ini
        // menggagalkan seeding dengan pesan yang jelas, bukan
        // diam-diam melewatkannya.
        if (app()->environment('production')) {
            throw new \RuntimeException('Seeder akun dev tidak boleh dijalankan di production. Buat akun admin lewat proses yang aman, bukan seeder ini.');
        }

        User::updateOrCreate(
            ['email' => 'admin@minicom.test'],
            [
                'name' => 'Admin Sudoy',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@minicom.test'],
            [
                'name' => 'Pelanggan Sudoy',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'email_verified_at' => now(),
            ]
        );
    }
}
