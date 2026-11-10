@if (session('status'))
    <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="status">
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div class="mt-5 rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-3 text-sm leading-6 text-ink" role="alert">
        {{ session('error') }}
    </div>
@endif
