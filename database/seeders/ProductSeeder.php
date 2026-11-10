<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Foto produk disimpan LOKAL di public/images/products/.
        // 10 foto diunduh dari Unsplash (ID sumber dicantumkan di
        // komentar di bawah, parameter unduhan yang sama:
        // auto=format, fit=crop, w=800, q=80) dan diperiksa
        // satu per satu: foto generik tanpa logo merek yang
        // terbaca. 2 foto diganti artwork SVG buatan sendiri
        // karena foto aslinya memuat logo/teks merek yang
        // terbaca (logo Apple di foto Canva Pro 1 Bulan, teks
        // "GRADO LABS" di foto Spotify Premium 3 Bulan).
        // Sumber Unsplash:
        // 1489599849927-2ee91cede3ba, 1517604931442-7e0c8ed2963c,
        // 1518676590629-3dcbd9c5a5c9, 1561070791-2526d30994b5 (diganti),
        // 1581291518857-4e27b48ff24e, 1558655146-9f40138edfeb,
        // 1505740420928-5e560c06d30e, 1483412033650-1015ddeb83d1 (diganti),
        // 1470225620780-dba8ba36b745, 1498050108023-c5249f4df085,
        // 1486312338219-ce68d2c6f44d, 1519389950473-47ba0277781c.
        $local = fn (string $slug, string $ext = 'jpg'): string => 'images/products/' . $slug . '.' . $ext;

        // Dummy lokal saja: nama merek hanya contoh, bukan akun sungguhan
        // dan tidak dijual. Tidak ada akun, kredensial, atau logo merek di sini.
        $products = [
            [
                'category' => 'streaming',
                'name' => 'Netflix Premium 1 Bulan',
                'slug' => 'netflix-premium-1-bulan',
                'description' => 'Langganan contoh untuk menonton film dan serial selama 1 bulan. Data contoh untuk latihan. Bukan akun aktif dan tidak dijual.',
                'price' => 69000,
                'stock' => 24,
                'image_url' => $local('netflix-premium-1-bulan'),
            ],
            [
                'category' => 'streaming',
                'name' => 'Netflix Premium 3 Bulan',
                'slug' => 'netflix-premium-3-bulan',
                'description' => 'Langganan contoh untuk menonton film dan serial selama 3 bulan. Data contoh untuk latihan. Bukan akun aktif dan tidak dijual.',
                'price' => 189000,
                'stock' => 18,
                'image_url' => $local('netflix-premium-3-bulan'),
            ],
            [
                'category' => 'streaming',
                'name' => 'Disney+ Hotstar Premium 1 Bulan',
                'slug' => 'disney-hotstar-premium-1-bulan',
                'description' => 'Langganan contoh untuk menonton film dan serial selama 1 bulan. Data contoh untuk latihan. Bukan akun aktif dan tidak dijual.',
                'price' => 49000,
                'stock' => 30,
                'image_url' => $local('disney-hotstar-premium-1-bulan'),
            ],
            [
                'category' => 'desain',
                'name' => 'Canva Pro 1 Bulan',
                'slug' => 'canva-pro-1-bulan',
                'description' => 'Langganan contoh untuk membuat desain selama 1 bulan. Data contoh untuk latihan. Bukan akun aktif dan tidak dijual.',
                'price' => 25000,
                'stock' => 20,
                'image_url' => $local('canva-pro-1-bulan', 'svg'),
            ],
            [
                'category' => 'desain',
                'name' => 'Canva Pro 1 Tahun',
                'slug' => 'canva-pro-1-tahun',
                'description' => 'Langganan contoh untuk membuat desain selama 1 tahun. Data contoh untuk latihan. Bukan akun aktif dan tidak dijual.',
                'price' => 240000,
                'stock' => 15,
                'image_url' => $local('canva-pro-1-tahun'),
            ],
            [
                'category' => 'desain',
                'name' => 'CapCut Pro 1 Bulan',
                'slug' => 'capcut-pro-1-bulan',
                'description' => 'Langganan contoh untuk mengedit video selama 1 bulan. Data contoh untuk latihan. Bukan akun aktif dan tidak dijual.',
                'price' => 35000,
                'stock' => 25,
                'image_url' => $local('capcut-pro-1-bulan'),
            ],
            [
                'category' => 'musik',
                'name' => 'Spotify Premium 1 Bulan',
                'slug' => 'spotify-premium-1-bulan',
                'description' => 'Langganan contoh untuk mendengarkan musik selama 1 bulan. Data contoh untuk latihan. Bukan akun aktif dan tidak dijual.',
                'price' => 29000,
                'stock' => 12,
                'image_url' => $local('spotify-premium-1-bulan'),
            ],
            [
                'category' => 'musik',
                'name' => 'Spotify Premium 3 Bulan',
                'slug' => 'spotify-premium-3-bulan',
                'description' => 'Langganan contoh untuk mendengarkan musik selama 3 bulan. Data contoh untuk latihan. Bukan akun aktif dan tidak dijual.',
                'price' => 79000,
                'stock' => 10,
                'image_url' => $local('spotify-premium-3-bulan', 'svg'),
            ],
            [
                'category' => 'musik',
                'name' => 'YouTube Premium 1 Bulan',
                'slug' => 'youtube-premium-1-bulan',
                'description' => 'Langganan contoh untuk menonton video dan mendengarkan musik selama 1 bulan. Data contoh untuk latihan. Bukan akun aktif dan tidak dijual.',
                'price' => 32000,
                'stock' => 14,
                'image_url' => $local('youtube-premium-1-bulan'),
            ],
            [
                'category' => 'produktivitas',
                'name' => 'ChatGPT Plus 1 Bulan',
                'slug' => 'chatgpt-plus-1-bulan',
                'description' => 'Langganan contoh untuk menulis dan menjawab pertanyaan selama 1 bulan. Data contoh untuk latihan. Bukan akun aktif dan tidak dijual.',
                'price' => 89000,
                'stock' => 22,
                'image_url' => $local('chatgpt-plus-1-bulan'),
            ],
            [
                'category' => 'produktivitas',
                'name' => 'Microsoft 365 Personal 1 Bulan',
                'slug' => 'microsoft-365-personal-1-bulan',
                'description' => 'Langganan contoh untuk bekerja dengan dokumen selama 1 bulan. Data contoh untuk latihan. Bukan akun aktif dan tidak dijual.',
                'price' => 45000,
                'stock' => 8,
                'image_url' => $local('microsoft-365-personal-1-bulan'),
            ],
            [
                'category' => 'produktivitas',
                'name' => 'Zoom Pro 1 Bulan',
                'slug' => 'zoom-pro-1-bulan',
                'description' => 'Langganan contoh untuk rapat daring selama 1 bulan. Data contoh untuk latihan. Bukan akun aktif dan tidak dijual.',
                'price' => 59000,
                'stock' => 0,
                'image_url' => $local('zoom-pro-1-bulan'),
            ],
        ];

        foreach ($products as $data) {
            $category = Category::where('slug', $data['category'])->firstOrFail();
            unset($data['category']);
            $data['category_id'] = $category->id;
            $data['is_active'] = true;

            Product::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
