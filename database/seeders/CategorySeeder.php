<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Streaming',
                'slug' => 'streaming',
                'description' => 'Langganan contoh untuk menonton film dan serial. Data contoh untuk latihan, bukan akun aktif dan tidak dijual.',
            ],
            [
                'name' => 'Desain',
                'slug' => 'desain',
                'description' => 'Langganan contoh untuk membuat desain dan mengedit video. Data contoh untuk latihan, bukan akun aktif dan tidak dijual.',
            ],
            [
                'name' => 'Musik',
                'slug' => 'musik',
                'description' => 'Langganan contoh untuk mendengarkan musik dan menonton video. Data contoh untuk latihan, bukan akun aktif dan tidak dijual.',
            ],
            [
                'name' => 'Produktivitas',
                'slug' => 'produktivitas',
                'description' => 'Langganan contoh untuk bekerja, menulis, dan rapat daring. Data contoh untuk latihan, bukan akun aktif dan tidak dijual.',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
