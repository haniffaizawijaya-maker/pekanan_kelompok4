<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-accent px-4 py-2 text-sm font-bold text-white shadow-[2px_2px_0_#191919] transition hover:bg-accentdark focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
