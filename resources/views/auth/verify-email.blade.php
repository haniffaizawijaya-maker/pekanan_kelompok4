<x-guest-layout>
    <div class="mb-4 text-sm text-muted">
        {{ __('Terima kasih sudah mendaftar. Sebelum mulai, verifikasi alamat email Anda dengan mengeklik tautan yang baru kami kirim. Jika belum menerima emailnya, kami akan mengirimkan tautan baru.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-ink">
            {{ __('Tautan verifikasi baru sudah dikirim ke alamat email yang Anda isi saat pendaftaran.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Kirim ulang email verifikasi') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-muted hover:text-ink rounded-[6px] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ink">
                {{ __('Keluar') }}
            </button>
        </form>
    </div>
</x-guest-layout>
