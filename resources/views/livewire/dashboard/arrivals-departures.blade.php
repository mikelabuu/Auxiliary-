<div wire:poll.15s data-component="arrivals-departures" class="bg-white rounded-2xl border border-stone-200 shadow-card hover:shadow-card-lg transition-shadow duration-200 p-0 flex flex-col h-full overflow-hidden">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 p-5 border-b border-stone-100 bg-stone-50/50">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-clsu-100 text-clsu-700 flex items-center justify-center shrink-0">
                <svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
            </div>
            <p class="font-semibold text-stone-900 text-sm">Arrivals & Departures</p>
        </div>

        {{-- Filter Buttons --}}
        <div class="flex bg-stone-100 rounded-full p-1 text-xs font-semibold w-fit">
            <button wire:click="$set('filterType', 'all')"
                class="px-3 py-1.5 rounded-full transition-all cursor-pointer {{ $filterType === 'all' ? 'bg-white text-clsu-800 shadow-sm' : 'text-stone-400 hover:text-clsu-700' }}">
                All
            </button>
            <button wire:click="$set('filterType', 'arrival')"
                class="px-3 py-1.5 rounded-full transition-all cursor-pointer {{ $filterType === 'arrival' ? 'bg-white text-clsu-800 shadow-sm' : 'text-stone-400 hover:text-clsu-700' }}">
                Arrivals
            </button>
            <button wire:click="$set('filterType', 'departure')"
                class="px-3 py-1.5 rounded-full transition-all cursor-pointer {{ $filterType === 'departure' ? 'bg-white text-clsu-800 shadow-sm' : 'text-stone-400 hover:text-clsu-700' }}">
                Departures
            </button>
        </div>
    </div>

    <div class="overflow-x-auto flex-1 p-5">
        <div class="scroll-x rounded-xl border border-stone-200">
            @if($arrivalsDepartures->isEmpty())
                <div class="grid grid-cols-6 bg-stone-50 text-[10px] font-bold text-clsu-700 tracking-wide px-4 py-2.5 uppercase border-b border-stone-200">
                    <span>ID</span><span>Guest</span><span>Check-in</span><span>Check-out</span><span>Type</span><span>Status</span>
                </div>
                <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-clsu-50 to-clsu-100 flex items-center justify-center text-clsu-500 mb-3 ring-1 ring-clsu-100">
                    <svg class="icon w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="16" y1="3" x2="16" y2="7"/><path d="m9 14 2 2 4-4"/></svg>
                  </div>
                  <p class="text-sm font-semibold text-stone-700">No arrivals or departures today</p>
                  <p class="text-xs text-stone-400 mt-1 max-w-xs">Guest check-ins and check-outs will show up here automatically as they happen.</p>
                  <a href="{{ route('staff.manualbooking') }}" class="mt-4 text-xs font-bold text-white bg-clsu-600 rounded-lg px-3.5 py-2 hover:bg-clsu-700 active:scale-[0.98] transition-all !no-underline shadow-sm">Create manual booking</a>
                </div>
            @else
                <table class="data-table" style="min-width:640px;">
                    <thead>
                        <tr>
                            <th wire:click="sortBy('guest_name')" @class(['sortable', 'sort-asc' => $sortField === 'guest_name' && $sortDirection === 'asc', 'sort-desc' => $sortField === 'guest_name' && $sortDirection === 'desc'])>Guest</th>
                            <th wire:click="sortBy('check_in')" @class(['sortable', 'sort-asc' => $sortField === 'check_in' && $sortDirection === 'asc', 'sort-desc' => $sortField === 'check_in' && $sortDirection === 'desc'])>Check-in</th>
                            <th wire:click="sortBy('check_out')" @class(['sortable', 'sort-asc' => $sortField === 'check_out' && $sortDirection === 'asc', 'sort-desc' => $sortField === 'check_out' && $sortDirection === 'desc'])>Check-out</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($arrivalsDepartures as $item)
                            @php
                                $initials = collect(explode(' ', trim($item->guest_name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
                                $initials = strtoupper($initials ?: 'G');
                                $sClass = $item->status === 'paid' ? 'status-paid' : ($item->status === 'active' ? 'status-active' : 'status-pending');
                            @endphp
                            <tr>
                                <td>
                                    <div class="cell-name">
                                        <span class="avatar-initials">{{ $initials }}</span>
                                        <div class="cell-name-text">
                                            <p class="cell-name-primary guest-history-link cursor-pointer hover:text-clsu-700 hover:underline" data-booking-id="{{ $item->id }}" title="{{ $item->guest_name }} — view guest history">{{ $item->guest_name }}</p>
                                            <p class="cell-name-secondary">#{{ $item->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-data tabnum">{{ \Carbon\Carbon::parse($item->check_in)->format('M d, Y') }}</td>
                                <td class="font-data tabnum">{{ \Carbon\Carbon::parse($item->check_out)->format('M d, Y') }}</td>
                                <td>
                                    <span class="cell-tag" style="{{ $item->type === 'arrival' ? 'color:var(--color-g-700);background:var(--color-g-50);border-color:var(--color-g-200);' : 'color:var(--color-au-800);background:var(--color-au-100);border-color:#fedf89;' }}">{{ ucfirst($item->type) }}</span>
                                </td>
                                <td><span class="status {{ $sClass }}">{{ ucfirst($item->status) }}</span></td>
                                <td class="text-right">
                                    @if($item->status === 'paid')
                                        <div class="table-actions justify-end">
                                            <button class="password-verify-arrivals btn btn-primary btn-sm cursor-pointer" data-action="checkin" data-id="{{ $item->id }}">Check In</button>
                                            <button class="password-verify-arrivals btn btn-outline btn-sm cursor-pointer" data-action="noshow" data-id="{{ $item->id }}">No Show</button>
                                        </div>
                                    @elseif($item->status === 'active')
                                        <div class="table-actions justify-end">
                                            <button class="password-verify-arrivals btn btn-primary btn-sm cursor-pointer" data-action="checkout" data-id="{{ $item->id }}">Check Out</button>
                                        </div>
                                    @else
                                        <span class="text-faint text-xs italic">None</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Upcoming Section --}}
        @if($upcomingBookings->isNotEmpty())
            <div class="mt-6">
                <p class="text-[10px] font-bold text-stone-400 tracking-widest mb-2 uppercase">UPCOMING THIS WEEK</p>
                <div class="divide-y divide-stone-100 rounded-xl border border-stone-200 overflow-hidden">
                    @foreach($upcomingBookings as $booking)
                        <div class="flex items-center gap-3 px-4 py-3 hover:bg-clsu-50/60 transition-colors">
                            <div class="w-8 h-8 rounded-full bg-clsu-600 text-white flex items-center justify-center text-[11px] font-bold shrink-0">
                                {{ $booking['initials'] }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-stone-800 truncate">{{ $booking['guest_name'] }}</p>
                                <p class="text-xs text-stone-400">{{ $booking['details'] }}</p>
                            </div>
                            <span class="text-[10px] font-bold text-clsu-700 bg-clsu-50 rounded-full px-2.5 py-1 shrink-0 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-clsu-500"></span>
                                {{ $booking['status'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Pagination --}}
    @if ($arrivalsDepartures->hasPages())
        <div class="px-5 py-3 border-t border-stone-150 bg-white">
            {{ $arrivalsDepartures->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const handleClick = (btn) => {
        const bookingId = btn.dataset.id;
        const action = btn.dataset.action;

        Swal.fire({
            title: 'Enter your password',
            input: 'password',
            showCancelButton: true,
            confirmButtonText: 'Verify',
            showLoaderOnConfirm: true,
            preConfirm: (password) => {
                return fetch("{{ route('staff.bookings.verify-password') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ password })
                })
                .then(res => res.json())
                .then(response => {
                    if (!response.success) throw new Error(response.message);
                    return response;
                })
                .catch(err => {
                    Swal.showValidationMessage(err.message || 'Password verification failed');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value?.success) {
                // v3 requires array payload
                window.Livewire.dispatch('arrivalsPasswordConfirmed', [ { bookingId, action } ]);
            }
        });
    };

    // Delegated event listener
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.password-verify-arrivals');
        if (btn) {
            e.preventDefault();
            handleClick(btn);
        }
    });

});
</script>
@endpush
