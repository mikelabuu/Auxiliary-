@props([
    'title' => 'No items found',
    'description' => 'There are no items matching this view.',
    'icon' => 'inbox',
    'actionText' => null,
    'actionUrl' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center p-8 md:p-12 border border-dashed border-stone-300 bg-stone-50/40 rounded-3xl']) }}>
    <div class="w-16 h-16 rounded-2xl bg-clsu-50 border border-clsu-100 flex items-center justify-center mb-4 text-clsu-600">
        <span class="material-icons text-[30px]">{{ $icon }}</span>
    </div>

    <h3 class="text-base font-bold text-ink tracking-tight">{{ $title }}</h3>
    <p class="text-sm font-medium text-stone-500 mt-1 max-w-sm leading-relaxed">{{ $description }}</p>

    @if($actionText && $actionUrl)
        <div class="mt-6">
            <x-booking.button variant="primary" :href="$actionUrl">
                {{ $actionText }}
            </x-booking.button>
        </div>
    @endif
</div>
