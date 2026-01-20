<div wire:poll.60s data-component="arrivals-departures" class="space-y-4">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold mb-4">Arrivals & Departures</h1>

        {{--  Filter Buttons --}}
        <div class="space-x-2">
            <button wire:click="$set('filterType', 'all')"
                class="px-3 py-1 text-sm rounded border {{ $filterType === 'all' ? 'bg-blue-100 text-blue-700 border-blue-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                All
            </button>
            <button wire:click="$set('filterType', 'arrival')"
                class="px-3 py-1 text-sm rounded border {{ $filterType === 'arrival' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                Arrivals
            </button>
            <button wire:click="$set('filterType', 'departure')"
                class="px-3 py-1 text-sm rounded border {{ $filterType === 'departure' ? 'bg-orange-100 text-orange-700 border-orange-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                Departures
            </button>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow-md">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">ID</th>
                    <th wire:click="sortBy('guest_name')" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 cursor-pointer">
                        Guest
                        @if ($sortField === 'guest_name')
                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </th>
                    <th wire:click="sortBy('check_in')" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 cursor-pointer">
                        Check-in
                        @if ($sortField === 'check_in')
                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </th>
                    <th wire:click="sortBy('check_out')" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 cursor-pointer">
                        Check-out
                        @if ($sortField === 'check_out')
                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Type</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse ($arrivalsDepartures as $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-sm text-gray-800">{{ $item->id }}</td>
                        <td class="px-4 py-3 text-sm text-gray-800">{{ $item->guest_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ \Carbon\Carbon::parse($item->check_in)->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ \Carbon\Carbon::parse($item->check_out)->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex items-center rounded-full bg-blue-100 text-blue-800 px-2 py-0.5 text-xs font-medium">
                                {{ ucfirst($item->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $item->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($item->status === 'paid')
                                <button 
                                    class="password-verify-arrivals px-3 py-1 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition" 
                                    data-action="checkin" 
                                    data-id="{{ $item->id }}">
                                    Check In
                                </button>
                                <button 
                                    class="password-verify-arrivals px-3 py-1 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition" 
                                    data-action="noshow" 
                                    data-id="{{ $item->id }}">
                                    No Show
                                </button>
                            @elseif($item->status === 'active')
                                <button 
                                    class="password-verify-arrivals px-3 py-1 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition" 
                                    data-action="checkout" 
                                    data-id="{{ $item->id }}">
                                    Check Out
                                </button>
                            @else
                                <p>No actions</P>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">
                            No arrivals or departures for today.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($arrivalsDepartures->hasPages())
        <div class="flex justify-center mt-3">
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

