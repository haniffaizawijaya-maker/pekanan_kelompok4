<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tes Fase 5: CRUD produk admin lewat Blade biasa.
 */
class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class, CategorySeeder::class, ProductSeeder::class]);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@minicom.test')->firstOrFail();
    }

    private function customer(): User
    {
        return User::where('email', 'customer@minicom.test')->firstOrFail();
    }

    private function category(): Category
    {
        return Category::query()->orderBy('id')->firstOrFail();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Produk Uji Baru',
            'category_id' => $this->category()->id,
            'description' => 'Deskripsi produk uji.',
            'price' => 45000,
            'stock' => 10,
            'image_url' => null,
            'is_active' => '1',
        ], $overrides);
    }

    public function test_non_admin_cannot_manage_products(): void
    {
        $this->actingAs($this->customer());

        $this->get('/admin/produk/buat')->assertForbidden();
        $this->post('/admin/produk', $this->validPayload())->assertForbidden();

        $product = Product::query()->firstOrFail();
        $this->delete(route('admin.products.destroy', $product))->assertForbidden();

        $this->get('/admin/produk/buat')->assertForbidden();
        $this->assertDatabaseMissing('products', ['name' => 'Produk Uji Baru']);
    }

    public function test_guest_redirected_to_login_for_product_create(): void
    {
        $this->get('/admin/produk/buat')->assertRedirect('/login');
    }

    public function test_admin_can_create_product_with_auto_slug(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.products.store'), $this->validPayload())
            ->assertRedirect();

        $product = Product::query()->where('slug', 'produk-uji-baru')->firstOrFail();
        $this->assertSame(45000, $product->price);
        $this->assertTrue($product->is_active);

        $this->get(route('admin.products.index'))->assertOk()->assertSee('Produk Uji Baru');
    }

    public function test_validation_errors_use_indonesian_messages(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.products.store'), $this->validPayload([
            'name' => '',
            'category_id' => 999999,
            'price' => -1,
            'stock' => -2,
            'image_url' => 'bukan-url',
        ]))->assertInvalid([
            'name' => 'Kolom nama produk wajib diisi.',
            'category_id' => 'Kategori yang dipilih tidak ditemukan.',
            'price' => 'Kolom harga tidak boleh kurang dari 0.',
            'stock' => 'Kolom stok tidak boleh kurang dari 0.',
            'image_url' => 'Kolom URL gambar harus berupa URL yang valid atau path lokal yang valid.',
        ]);

        $this->assertDatabaseMissing('products', ['description' => 'Deskripsi produk uji.']);
    }

    public function test_slug_gets_unique_suffix_on_collision(): void
    {
        $this->actingAs($this->admin());

        $existing = Product::query()->where('slug', 'netflix-premium-1-bulan')->firstOrFail();

        $this->post(route('admin.products.store'), $this->validPayload(['name' => $existing->name]))
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'name' => $existing->name,
            'slug' => 'netflix-premium-1-bulan-2',
        ]);
    }

    public function test_update_regenerates_slug_and_keeps_slug_when_name_unchanged(): void
    {
        $this->actingAs($this->admin());

        $product = Product::query()->where('slug', 'canva-pro-1-bulan')->firstOrFail();

        // Nama tidak berubah: slug tetap.
        $this->put(route('admin.products.update', $product), $this->validPayload([
            'name' => $product->name,
            'category_id' => $product->category_id,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
            'image_url' => $product->image_url,
        ]))->assertRedirect();
        $this->assertSame('canva-pro-1-bulan', $product->fresh()->slug);

        // Nama berubah: slug dibuat ulang.
        $this->put(route('admin.products.update', $product), $this->validPayload([
            'name' => 'Canva Pro Bulanan Baru',
            'category_id' => $product->category_id,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
        ]))->assertRedirect();
        $this->assertSame('canva-pro-bulanan-baru', $product->fresh()->slug);
    }

    public function test_toggle_active_hides_product_from_catalog(): void
    {
        $this->actingAs($this->admin());

        $product = Product::query()->where('slug', 'canva-pro-1-bulan')->firstOrFail();
        $this->assertTrue($product->is_active);

        $this->patch(route('admin.products.toggle', $product))->assertRedirect();
        $this->assertFalse($product->fresh()->is_active);

        $this->get(route('products.show', $product))->assertNotFound();

        $this->patch(route('admin.products.toggle', $product))->assertRedirect();
        $this->assertTrue($product->fresh()->is_active);
        $this->get(route('products.show', $product))->assertOk();
    }

    public function test_destroy_blocked_when_product_used_in_order(): void
    {
        $user = $this->customer();
        $product = Product::query()->where('slug', 'netflix-premium-1-bulan')->firstOrFail();

        $this->assertTrue(app(CartService::class)->add($user, $product, 1)['ok']);
        $created = app(OrderService::class)->createOrder($user, $user->name, $user->email, null);
        $this->assertTrue($created['ok']);

        $this->actingAs($this->admin());

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_destroy_blocked_when_product_in_cart(): void
    {
        $user = $this->customer();
        $product = Product::query()->where('slug', 'canva-pro-1-bulan')->firstOrFail();

        $this->assertTrue(app(CartService::class)->add($user, $product, 1)['ok']);

        $this->actingAs($this->admin());

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_destroy_succeeds_when_product_unused(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.products.store'), $this->validPayload())->assertRedirect();
        $product = Product::query()->where('slug', 'produk-uji-baru')->firstOrFail();

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_index_filters(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/produk?'.http_build_query(['q' => 'Netflix']))
            ->assertOk()
            ->assertSee('Netflix Premium 1 Bulan')
            ->assertDontSee('Canva Pro 1 Bulan');

        $streaming = Category::query()->where('slug', 'streaming')->first();
        $this->assertNotNull($streaming);

        $this->get('/admin/produk?'.http_build_query(['category_id' => $streaming->id]))
            ->assertOk()
            ->assertSee('Netflix Premium 1 Bulan')
            ->assertDontSee('Canva Pro 1 Bulan');

        Product::query()->where('slug', 'canva-pro-1-bulan')->update(['is_active' => false]);

        $this->get('/admin/produk?'.http_build_query(['is_active' => '0']))
            ->assertOk()
            ->assertSee('Canva Pro 1 Bulan')
            ->assertDontSee('Netflix Premium 1 Bulan');
    }
}
