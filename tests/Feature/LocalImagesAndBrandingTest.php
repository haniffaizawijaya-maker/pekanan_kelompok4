<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tes gambar produk lokal + branding Sudoy: file gambar ada di
 * disk, seeder memakai path lokal, helper displayImageUrl
 * menyelesaikan path lokal dan meneruskan URL jarak jauh,
 * katalog memakai gambar lokal dengan fallback onerror, dan
 * nama Sudoy terlihat di halaman layout app maupun guest.
 */
class LocalImagesAndBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class, CategorySeeder::class, ProductSeeder::class]);

        // Sama seperti BrandingTest: nama aplikasi diambil dari env,
        // kunci ke Sudoy agar tes tidak bergantung pada isi .env
        // lokal mesin yang menjalankan tes.
        config()->set('app.name', 'Sudoy');
    }

    private function customer(): User
    {
        return User::where('email', 'customer@minicom.test')->firstOrFail();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@minicom.test')->firstOrFail();
    }

    public function test_all_seeded_product_image_files_exist_on_disk(): void
    {
        $expected = [
            'netflix-premium-1-bulan.jpg',
            'netflix-premium-3-bulan.jpg',
            'disney-hotstar-premium-1-bulan.jpg',
            'canva-pro-1-bulan.svg',
            'canva-pro-1-tahun.jpg',
            'capcut-pro-1-bulan.jpg',
            'spotify-premium-1-bulan.jpg',
            'spotify-premium-3-bulan.svg',
            'youtube-premium-1-bulan.jpg',
            'chatgpt-plus-1-bulan.jpg',
            'microsoft-365-personal-1-bulan.jpg',
            'zoom-pro-1-bulan.jpg',
        ];

        foreach ($expected as $file) {
            $this->assertFileExists(public_path('images/products/' . $file));
        }

        $this->assertSame(12, Product::query()->count());

        foreach (Product::query()->get() as $product) {
            $this->assertStringStartsWith('images/products/', (string) $product->image_url);
            $this->assertFileExists(public_path((string) $product->image_url));
        }
    }

    public function test_display_image_url_resolves_local_paths_and_passes_remote_urls_through(): void
    {
        $local = new Product(['image_url' => 'images/products/contoh.jpg']);
        $this->assertStringEndsWith('/images/products/contoh.jpg', (string) $local->displayImageUrl());
        $this->assertStringNotContainsString('//images/', (string) $local->displayImageUrl());

        $leadingSlash = new Product(['image_url' => '/images/products/contoh.jpg']);
        $this->assertStringEndsWith('/images/products/contoh.jpg', (string) $leadingSlash->displayImageUrl());

        $remote = new Product(['image_url' => 'https://example.com/foto.jpg']);
        $this->assertSame('https://example.com/foto.jpg', $remote->displayImageUrl());

        $this->assertNull((new Product(['image_url' => null]))->displayImageUrl());
        $this->assertNull((new Product(['image_url' => '  ']))->displayImageUrl());
    }

    public function test_catalog_uses_local_images_with_onerror_fallback(): void
    {
        $response = $this->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee('images/products/netflix-premium-1-bulan.jpg', false);
        $response->assertSee('images/products/canva-pro-1-bulan.svg', false);
        $response->assertSee('onerror', false);
        $response->assertDontSee('images.unsplash.com', false);
    }

    public function test_admin_accepts_local_image_path_and_rejects_bare_word(): void
    {
        $this->actingAs($this->admin());

        $categoryId = Product::query()->firstOrFail()->category_id;

        $this->post(route('admin.products.store'), [
            'name' => 'Produk Path Lokal',
            'category_id' => $categoryId,
            'description' => 'Deskripsi produk uji.',
            'price' => 10000,
            'stock' => 5,
            'image_url' => 'images/products/baru.jpg',
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['slug' => 'produk-path-lokal', 'image_url' => 'images/products/baru.jpg']);

        $this->post(route('admin.products.store'), [
            'name' => 'Produk Gambar Salah',
            'category_id' => $categoryId,
            'description' => 'Deskripsi produk uji.',
            'price' => 10000,
            'stock' => 5,
            'image_url' => 'bukan-url',
        ])->assertInvalid([
            'image_url' => 'Kolom URL gambar harus berupa URL yang valid atau path lokal yang valid.',
        ]);
    }

    public function test_sudoy_brand_is_visible_on_app_and_guest_pages(): void
    {
        $this->get(route('catalog.index'))->assertOk()->assertSee('Sudoy');
        $this->get(route('login'))->assertOk()->assertSee('Sudoy');

        $this->actingAs($this->customer());
        $this->get(route('dashboard'))->assertOk()->assertSee('Sudoy');
        $this->get(route('profile.edit'))->assertOk()->assertSee('Sudoy');
    }
}
