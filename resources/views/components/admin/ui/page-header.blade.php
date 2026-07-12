@props(['subtitle' => null])

{{--
    Page intro row (AAIS greeting row): display-font title (default slot,
    green accent spans welcome), optional subtitle, right-aligned actions.

    <x-admin.ui.page-header subtitle="Manage availability, wings, and pricing across all rooms.">
        Room <span class="text-g-700">Management</span>
        <x-slot:actions>...buttons...</x-slot:actions>
    </x-admin.ui.page-header>
--}}

<div {{ $attributes->merge(['class' => 'dashboard-greeting-row animate-in']) }}>
    <div class="dashboard-greeting-copy">
        @isset($breadcrumb)
            <div class="dashboard-greeting-breadcrumb">{{ $breadcrumb }}</div>
        @endisset
        <h2 class="dashboard-greeting-title">{{ $slot }}</h2>
        @if($subtitle)
            <p class="dashboard-greeting-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="dashboard-recent-actions">
            {{ $actions }}
        </div>
    @endisset
</div>
