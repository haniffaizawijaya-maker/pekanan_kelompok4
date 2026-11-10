<?php

namespace App\Livewire;

use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CheckoutPage extends Component
{
    public string $name = '';

    public string $email = '';

    public string $notes = '';

    public function mount(CartService $cartService): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;

        // Hanya redirect bila keranjang benar-benar tidak punya baris
        // item. Item yang sudah tidak tersedia (stok habis/nonaktif)
        // tetap dihitung di sini: validasi ulangnya adalah tugas
        // submit() lewat OrderService, yang membersihkan item tersebut
        // dan mengembalikan pesan kegagalan yang tepat.
        if ($cartService->itemsFor($user)->isEmpty()) {
            session()->flash(
                'cart_notice',
                'Keranjang masih kosong. Tambahkan produk dulu sebelum checkout.'
            );
            $this->redirect(route('cart.index'));
        }
    }

    public function submit(OrderService $orderService): void
    {
        $validated = $this->validate(
            [
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:255'],
                'notes' => ['nullable', 'string', 'max:500'],
            ],
            [
                'name.required' => 'Kolom nama lengkap wajib diisi.',
                'name.max' => 'Kolom nama lengkap tidak boleh lebih dari 100 karakter.',
                'email.required' => 'Kolom email pengiriman wajib diisi.',
                'email.email' => 'Kolom email pengiriman harus berupa alamat email yang valid.',
                'email.max' => 'Kolom email pengiriman tidak boleh lebih dari 255 karakter.',
                'notes.max' => 'Kolom catatan tidak boleh lebih dari 500 karakter.',
            ],
            [
                'name' => 'nama lengkap',
                'email' => 'email pengiriman',
                'notes' => 'catatan',
            ]
        );

        $notes = trim((string) ($validated['notes'] ?? ''));

        $result = $orderService->createOrder(
            auth()->user(),
            trim($validated['name']),
            trim($validated['email']),
            $notes === '' ? null : $notes
        );

        if (! $result['ok']) {
            session()->flash('cart_notice', $result['message']);
            $this->dispatch('cart-updated');
            $this->redirect(route('cart.index'));

            return;
        }

        // Penanda banner disimpan sebagai data sesi persisten, bukan
        // flash: flash yang ditulis di dalam request AJAX Livewire bisa
        // terkonsumsi sebelum halaman detail dimuat, sehingga banner
        // tampil tidak konsisten. Penanda ini hanya dihapus oleh rute
        // detail pesanan saat halaman itu dimuat, dan banner hanya
        // ditampilkan bila URL-nya juga membawa ?created=1.
        session()->put('order_created_banner', $result['order']->id);
        $this->dispatch('cart-updated');
        $this->redirect(route('orders.show', ['order' => $result['order'], 'created' => 1]));
    }

    public function render(OrderService $orderService): View
    {
        $preview = $orderService->preview(auth()->user());

        return view('livewire.checkout-page', [
            'lines' => $preview['lines'],
            'subtotal' => $preview['subtotal'],
            'previewNotices' => $preview['notices'],
            'totalQuantity' => array_sum(array_column($preview['lines'], 'quantity')),
        ]);
    }
}
