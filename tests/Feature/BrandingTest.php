<?php

namespace Tests\Feature;

use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class, CategorySeeder::class, ProductSeeder::class]);

        // Nama aplikasi diambil dari env; kunci ke Sudoy agar tes tidak
        // bergantung pada isi .env lokal mesin yang menjalankan tes.
        config()->set('app.name', 'Sudoy');
    }

    public function test_catalog_page_shows_sudoy_brand_and_title(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Sudoy');
        $response->assertSee('<title>Sudoy</title>', false);
        $response->assertSee('Langganan premium digital dari Sudoy.');
        $response->assertDontSee('MiniCommerce');
    }

    public function test_login_page_shows_sudoy_brand_and_no_minicommerce(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Sudoy');
        $response->assertSee('<title>Sudoy</title>', false);
        $response->assertDontSee('MiniCommerce');
    }
}
