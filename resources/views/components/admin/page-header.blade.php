@props(['subtitle' => null])

{{--
    Top intro row used at the head of every admin page: a title line
    (default slot — put the italic accent span directly in it), an optional
    subtitle, and a right-aligned actions slot for buttons/links.

    <x-admin.page-header subtitle="Manage availability, wings, and pricing across all rooms.">
        Room <span class="font-display italic font-medium text-clsu-800">Management</span>
        <x-slot:actions>
            <button ...>Add Room</button>
        </x-slot:actions>
    </x-admin.page-header>
--}}

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-end justify-between gap-4 animate-in']) }}>
    <div>
        <p class="text-xl sm:text-2xl text-stone-900 tracking-tight">{{ $slot }}</p>
        @if($subtitle)
            <p class="text-sm text-stone-500 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2.5">
            {{ $actions }}
        </div>
    @endisset
</div>
