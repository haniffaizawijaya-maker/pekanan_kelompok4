<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // M4: seeder memanggil UserSeeder yang membuat akun dev
        // dengan kata sandi publik, jadi seluruh seeding
        // ditolak di production dengan pesan yang jelas.
        if (app()->environment('production')) {
            throw new \RuntimeException('DatabaseSeeder tidak boleh dijalankan di production karena memuat akun dev. Jangan jalankan db:seed atau migrate:fresh --seed di server production.');
        }

        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
