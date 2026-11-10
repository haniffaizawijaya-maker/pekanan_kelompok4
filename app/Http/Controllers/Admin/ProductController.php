<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Daftar produk admin. Filter GET: q (nama), kategori, dan
     * status aktif (1 aktif, 0 nonaktif, kosong semua).
     */
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('category')
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.trim((string) $request->query('q')).'%');
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->query('category_id'));
            })
            ->when($request->query('is_active') === '1' || $request->query('is_active') === '0', function ($query) use ($request) {
                $query->where('is_active', $request->query('is_active') === '1');
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product(['is_active' => true]),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $product = Product::create([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $this->generateSlug($data['name']),
            'description' => $data['description'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'image_url' => $data['image_url'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Produk berhasil dibuat.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request);

        $product->update([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $this->generateSlug($data['name'], $product->id),
            'description' => $data['description'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'image_url' => $data['image_url'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Produk berhasil diubah.');
    }

    /**
     * Hapus hanya bila produk belum pernah dipakai di
     * order_items dan tidak ada di cart_items. Bila terpakai,
     * tolak dan sarankan menonaktifkan produknya.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists() || $product->cartItems()->exists()) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Produk ini tidak bisa dihapus karena sudah dipakai di pesanan atau keranjang. Nonaktifkan produknya agar hilang dari katalog.');
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Produk berhasil dihapus.');
    }

    /**
     * Alihkan status aktif. Produk nonaktif hilang dari
     * katalog karena rute produk mengembalikan 404 untuk
     * produk nonaktif.
     */
    public function toggle(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return redirect()
            ->route('admin.products.index')
            ->with('status', $product->is_active
                ? 'Produk diaktifkan dan tampil lagi di katalog.'
                : 'Produk dinonaktifkan dan hilang dari katalog.');
    }

    /**
     * Validasi dengan pesan Bahasa Indonesia. Deskripsi wajib
     * karena kolomnya tidak nullable di migrasi products.
     *
     * @return array{category_id: int, name: string, description: string, price: int, stock: int, image_url: ?string}
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'image_url' => ['nullable', 'string', 'max:2048', function ($attribute, $value, $fail): void {
                $value = trim((string) $value);

                if ($value === '') {
                    return;
                }

                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                    if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                        $fail('Kolom URL gambar harus berupa URL yang valid atau path lokal yang valid.');
                    }

                    return;
                }

                // Path lokal, mis. "images/products/nama.jpg".
                if (str_contains($value, '..')
                    || ! preg_match('#^/?(?:images|storage)/[A-Za-z0-9/_\-.]+\\.(?:jpg|jpeg|png|webp|svg|gif)$#i', $value)
                ) {
                    $fail('Kolom URL gambar harus berupa URL yang valid atau path lokal yang valid.');
                }
            }],
        ], [
            'name.required' => 'Kolom nama produk wajib diisi.',
            'name.max' => 'Kolom nama produk tidak boleh lebih dari 255 karakter.',
            'category_id.required' => 'Kolom kategori wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'description.required' => 'Kolom deskripsi wajib diisi.',
            'description.max' => 'Kolom deskripsi tidak boleh lebih dari 5000 karakter.',
            'price.required' => 'Kolom harga wajib diisi.',
            'price.integer' => 'Kolom harga harus berupa angka bulat rupiah.',
            'price.min' => 'Kolom harga tidak boleh kurang dari 0.',
            'price.max' => 'Kolom harga tidak boleh lebih dari 100000000.',
            'stock.required' => 'Kolom stok wajib diisi.',
            'stock.integer' => 'Kolom stok harus berupa angka bulat.',
            'stock.min' => 'Kolom stok tidak boleh kurang dari 0.',
            'stock.max' => 'Kolom stok tidak boleh lebih dari 100000.',
            'image_url.max' => 'Kolom URL gambar tidak boleh lebih dari 2048 karakter.',
        ], [
            'name' => 'nama produk',
            'category_id' => 'kategori',
            'description' => 'deskripsi',
            'price' => 'harga',
            'stock' => 'stok',
            'image_url' => 'URL gambar',
        ]);
    }

    /**
     * Slug otomatis dari nama dengan suffix -2, -3, dan
     * seterusnya bila bentrok. Pada update, produk sendiri
     * dikecualikan supaya nama yang tidak berubah tidak
     * mengubah slug yang sudah ada bila masih unik.
     */
    private function generateSlug(string $name, ?int $exceptId = null): string
    {
        $base = Str::slug($name);
        $base = $base === '' ? 'produk' : $base;

        $slug = $base;
        $suffix = 2;

        while (Product::query()
            ->where('slug', $slug)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
