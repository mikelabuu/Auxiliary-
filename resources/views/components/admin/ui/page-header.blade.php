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
        {{-- h1, not h2. This is the page's title on all thirteen screens that
             use it, and every one of them had no h1 at all — so a screen
             reader jumping by heading found nothing naming the page, and the
             outline started a level down from where it should. The styling is
             attached to the class, not the element, so nothing moves.

             The dashboard is the exception and already has its own h1 in
             livewire/dashboard/hero; it does not use this component, so there
             is no page with two. --}}
        <h1 class="dashboard-greeting-title">{{ $slot }}</h1>
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
