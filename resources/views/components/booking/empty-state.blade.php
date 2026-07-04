@props([
    'title' => 'No items found',
    'description' => 'There are no items matching this view.',
    'icon' => 'inbox',
    'actionText' => null,
    'actionUrl' => null
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center p-8 md:p-12 border border-dashed border-slate-200 bg-slate-50/20 rounded-2xl']) }}>
    <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-4 text-slate-400">
        <span class="material-icons text-[28px]">{{ $icon }}</span>
    </div>
    
    <h3 class="text-base font-bold text-slate-800 tracking-tight">{{ $title }}</h3>
    <p class="text-sm font-medium text-slate-500 mt-1 max-w-sm leading-relaxed">{{ $description }}</p>
    
    @if($actionText && $actionUrl)
        <div class="mt-6">
            <x-booking.button variant="primary" :href="$actionUrl">
                {{ $actionText }}
            </x-booking.button>
        </div>
    @endif
</div>
