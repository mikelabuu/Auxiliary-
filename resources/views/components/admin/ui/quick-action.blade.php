@props([
    'icon' => 'grid',
    'title' => null,
    'subtitle' => null,
    'href' => '#',
])

{{--
    Icon+label link tile for the dashboard's Quick Actions row.

    <x-admin.ui.quick-action icon="log-in" title="Check-in Guest" subtitle="Mark an arrival" :href="route('staff.manualbooking')" />
--}}

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'quick-action !no-underline']) }}>
    <div class="stat-icon stat-icon-green" style="width:38px;height:38px;">
        <x-admin.ui.icon :name="$icon" class="w-[18px] h-[18px]" />
    </div>
    <div class="min-w-0">
        <p class="quick-action-title truncate">{{ $title }}</p>
        <p class="quick-action-sub truncate">{{ $subtitle }}</p>
    </div>
</a>
