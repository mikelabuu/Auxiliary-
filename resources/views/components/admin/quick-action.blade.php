@props([
    'icon' => 'grid',
    'title' => null,
    'subtitle' => null,
    'href' => '#',
])

{{--
    Small icon+label link tile used in the dashboard's Quick Actions row.

    <x-admin.quick-action icon="log-in" title="Check-in Guest" subtitle="Mark an arrival" :href="route('staff.manualbooking')" />
--}}

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'group flex items-center gap-3 bg-white rounded-xl border border-stone-200/70 shadow-card hover:shadow-card-lg hover:border-clsu-200 transition-all p-3.5 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400 !no-underline']) }}>
    <div class="w-9 h-9 rounded-lg bg-clsu-50 text-clsu-700 flex items-center justify-center shrink-0 group-hover:bg-clsu-100 transition-colors">
        <x-admin.icon :name="$icon" class="w-[18px] h-[18px]" />
    </div>
    <div class="min-w-0">
        <p class="text-xs font-semibold text-stone-800 truncate">{{ $title }}</p>
        <p class="text-[11px] text-stone-400 truncate">{{ $subtitle }}</p>
    </div>
</a>
