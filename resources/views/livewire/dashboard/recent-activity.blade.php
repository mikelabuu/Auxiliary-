{{-- Markup moved verbatim from staff/dashboard/index.blade.php; poll is the
     fallback when the socket push isn't available. --}}
<div wire:poll.30s class="space-y-5">
    @foreach($recentActivities as $activity)
        <div class="timeline-item flex gap-3.5">
            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 ring-4 ring-white z-10 {{ $activity['color_class'] }}">
                <span class="material-icons text-sm">{{ $activity['icon'] }}</span>
            </div>
            <div class="pb-1">
                {{-- Escaped: descriptions are plain text and can embed
                     user-supplied names — never render them as HTML. --}}
                <p class="text-sm text-stone-700">{{ $activity['description'] }}</p>
                <p class="text-xs text-stone-400 mt-0.5">{{ $activity['created_at'] }}</p>
            </div>
        </div>
    @endforeach
</div>
