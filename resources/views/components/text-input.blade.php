@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-[6px] border-[1.5px] border-ink bg-card text-ink placeholder:text-muted focus:border-ink focus:ring-2 focus:ring-ink']) }}>
