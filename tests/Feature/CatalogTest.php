<?php

namespace Tests\Feature;

use App\Livewire\ProductList;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class, CategorySeeder::class, ProductSeeder::class]);
    }

    public function test_catalog_page_shows_products_and_result_count(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Netflix Premium 1 Bulan');
        $response->assertSee('Rp69.000');
        $response->assertSee('Menampilkan 12 produk.');
        $response->assertSee('Tambah ke Keranjang');
    }

    public function test_product_detail_page_shows_description_and_stock(): void
    {
        $response = $this->get('/produk/zoom-pro-1-bulan');

        $response->assertStatus(200);
        $response->assertSee('Zoom Pro 1 Bulan');
        $response->assertSee('Rp59.000');
        $response->assertSee('Stok habis');
        $response->assertSee('Beranda');
    }

    public function test_product_list_search_filters_results(): void
    {
        Livewire::test(ProductList::class)
            ->set('search', 'CapCut')
            ->assertSee('CapCut Pro 1 Bulan')
            ->assertSee('Menampilkan 1 produk.')
            ->assertDontSee('Netflix Premium 1 Bulan');
    }

    public function test_product_list_category_filter_filters_results(): void
    {
        Livewire::test(ProductList::class)
            ->set('category', 'musik')
            ->assertSee('Menampilkan 3 produk.')
            ->assertSee('Spotify Premium 1 Bulan')
            ->assertDontSee('Netflix Premium 1 Bulan');
    }

    public function test_product_list_empty_search_shows_empty_state_and_reset(): void
    {
        Livewire::test(ProductList::class)
            ->set('search', 'zzzztidakada')
            ->assertSee('Produk tidak ditemukan')
            ->assertSee('Reset filter')
            ->call('resetFilters')
            ->assertSee('Menampilkan 12 produk.');
    }

    public function test_catalog_page_shows_local_dummy_notice(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Katalog contoh untuk latihan. Produk di halaman ini data dummy lokal, bukan akun sungguhan dan tidak dijual.');
        $response->assertSee('Cari Netflix, Canva, atau lainnya');
    }

    public function test_seeded_users_have_expected_roles(): void
    {
        $this->assertTrue(User::where('email', 'admin@minicom.test')->first()->isAdmin());
        $this->assertSame('customer', User::where('email', 'customer@minicom.test')->first()->role);
        $this->assertSame(12, Product::count());
        $this->assertSame(1, Product::where('stock', 0)->count());
    }

    public function test_pagination_renders_indonesian_labels(): void
    {
        app()->setLocale('id');

        $response = $this->get('/?page=1');

        $response->assertStatus(200);
        $plainText = preg_replace('/\s+/', ' ', strip_tags($response->getContent()));
        $this->assertStringContainsString('Menampilkan 1 sampai 9 dari 12 hasil', $plainText);
        $response->assertSee('Sebelumnya');
        $response->assertSee('Berikutnya');
        $response->assertDontSee('pagination.previous');
        $response->assertDontSee('Showing');
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = Product::where('slug', 'netflix-premium-1-bulan')->first();
        $product->update(['is_active' => false]);

        $this->get('/produk/netflix-premium-1-bulan')->assertStatus(404);
    }
}
