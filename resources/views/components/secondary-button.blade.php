<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex min-h-[44px] items-center justify-center rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-2 text-sm font-bold text-ink transition hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
