@props(['booking'])

{{--
    Vertical event timeline for one booking ("Created → Paid → Checked in →
    Checked out"), fed by App\Support\BookingTimeline from the log tables the
    app already keeps. Reuses the dashboard activity feed's .timeline-item
    connector line.
--}}

@php
    $events = \App\Support\BookingTimeline::for($booking);
@endphp

@if(count($events))
    <div>
        <p class="flex items-center gap-2 text-xs font-bold text-stone-500 uppercase tracking-widest mb-2.5">
            <x-admin.ui.icon name="clock" class="w-3.5 h-3.5" />
            Timeline
        </p>
        <div class="space-y-4 rounded-xl border border-stone-200 p-4">
            @foreach($events as $event)
                <div class="timeline-item flex gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 ring-4 ring-white z-10 {{ $event['color'] }}">
                        <x-admin.ui.icon :name="$event['icon']" class="w-3.5 h-3.5" stroke-width="2" />
                    </div>
                    <div class="pb-0.5 min-w-0">
                        <p class="text-sm font-semibold text-stone-800">{{ $event['label'] }}</p>
                        @if($event['detail'])
                            <p class="text-xs text-stone-500 mt-0.5">{{ $event['detail'] }}</p>
                        @endif
                        <p class="text-[11px] text-stone-400 mt-0.5 font-data tabnum">{{ $event['at']->timezone(config('hostel.timezone'))->format('M d, Y · g:i A') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
