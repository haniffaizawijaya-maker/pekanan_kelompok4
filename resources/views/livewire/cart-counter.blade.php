{{-- Satu root <div> stabil untuk komponen ini. View Livewire tidak boleh
     diawali @if kondisional dengan dua root berbeda: Livewire 4 menaruh
     wire:snapshot di <span> penanda di dalam <a>, morph menumpuk <a> baru
     di dalam <a> lama, dan komponen hilang dari registry ("Snapshot
     missing"). Pembungkus ini tidak punya styling, jadi tampilan sama. --}}
<div>
@if ($variant === 'row')
    <a
        href="{{ route('cart.index') }}"
        aria-label="Keranjang, {{ $count }} item"
        class="flex min-h-[44px] w-full items-center justify-between px-3 py-2 text-base font-medium text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink"
    >
        <span>Keranjang</span>
        {{-- Badge selalu dirender agar bentuk DOM stabil saat morph Livewire
             (0 ke >0); disembunyikan lewat inline style saat 0 agar tidak
             mengambil ruang. Class Tailwind `hidden` tidak dipakai karena
             `inline-flex` akan mengalahkannya di cascade. --}}
        <span @if ($count === 0) style="display: none;" @endif class="inline-flex min-h-[24px] min-w-[24px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-mustard px-1.5 text-xs font-bold text-ink">{{ $count }}</span>
    </a>
@else
    <a
        href="{{ route('cart.index') }}"
        aria-label="Keranjang, {{ $count }} item"
        class="flex min-h-[56px] w-full flex-col items-center justify-center gap-1 px-1 py-2 text-center text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ink"
    >
        <span class="relative inline-flex">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 7h12l1.5 13.5a1 1 0 0 1-1 1.1H5.5a1 1 0 0 1-1-1.1L6 7Z" />
                <path d="M9 10V6a3 3 0 0 1 6 0v4" />
            </svg>
            {{-- Badge selalu dirender agar bentuk DOM stabil saat morph Livewire
                 (0 ke >0); disembunyikan lewat inline style saat 0. --}}
            <span @if ($count === 0) style="display: none;" @endif class="absolute -right-2 -top-2 inline-flex min-h-[20px] min-w-[20px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-mustard px-1 text-[11px] font-bold text-ink">{{ $count }}</span>
        </span>
        <span class="text-[11px] font-bold leading-none">Keranjang</span>
    </a>
@endif
</div>
