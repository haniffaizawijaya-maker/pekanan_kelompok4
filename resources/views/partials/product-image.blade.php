{{-- Gambar produk dengan fallback anti-kotak-kosong.
     Bila $src kosong atau <img> gagal dimuat, yang terlihat
     adalah div latar parchment berisi mark play Sudoy.
     Variabel: $src (URL siap pakai atau null),
     $alt, $wrapperClass (aspek + lebar frame),
     $loading ('lazy' atau 'eager'). --}}
<div class="relative {{ $wrapperClass ?? 'aspect-[3/4] w-full' }} overflow-hidden bg-parchment">
    <div class="absolute inset-0 flex items-center justify-center text-ink" aria-hidden="true">
        <svg class="h-12 w-12" viewBox="0 0 48 48" fill="none" aria-hidden="true">
            <rect x="8" y="8" width="32" height="32" rx="6" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" />
            <path d="M20 17.5 33 24l-13 6.5v-13Z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" />
            <path d="M8 42h28" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
        </svg>
    </div>
    @if (! empty($src))
        <img
            src="{{ $src }}"
            alt="{{ $alt ?? '' }}"
            loading="{{ $loading ?? 'lazy' }}"
            onerror="this.style.display='none'"
            class="relative z-10 block h-full w-full object-cover"
        >
    @endif
</div>
