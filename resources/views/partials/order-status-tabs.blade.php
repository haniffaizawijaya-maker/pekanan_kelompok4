@php
    $orderTabs = [
        ['label' => 'Semua', 'value' => null],
        ['label' => 'Menunggu bayar', 'value' => 'pending'],
        ['label' => 'Lunas', 'value' => 'paid'],
        ['label' => 'Selesai', 'value' => 'completed'],
        ['label' => 'Dibatalkan', 'value' => 'cancelled'],
    ];
    $currentOrderStatus = request('order_status');
    $tabBaseQuery = \Illuminate\Support\Arr::except(request()->query(), ['order_status', 'page']);
@endphp
<nav aria-label="Filter status pesanan" class="mt-5 flex flex-wrap gap-2">
    @foreach ($orderTabs as $tab)
        @php
            $isActiveTab = $tab['value'] === null
                ? $currentOrderStatus === null || $currentOrderStatus === ''
                : $currentOrderStatus === $tab['value'];
            $tabUrl = $tab['value'] === null
                ? route($tabsRoute, $tabBaseQuery)
                : route($tabsRoute, array_merge($tabBaseQuery, ['order_status' => $tab['value']]));
            $tabCount = $statusCounts[$tab['value'] ?? 'all'] ?? 0;
        @endphp
        <a
            href="{{ $tabUrl }}"
            @if ($isActiveTab) aria-current="page" @endif
            class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink px-4 text-sm font-bold text-ink focus:outline-none focus:ring-2 focus:ring-ink {{ $isActiveTab ? 'bg-mustard' : 'bg-card hover:bg-parchment' }}"
        >
            {{ $tab['label'] }} ({{ $tabCount }})
        </a>
    @endforeach
</nav>
