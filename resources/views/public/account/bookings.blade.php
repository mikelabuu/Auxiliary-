@extends('layouts.public.account')
@section('title', 'My Bookings')
@section('page-title', 'My Bookings')

@section('settings-content')
    <x-booking.ui.page-header title="My Bookings" subtitle="View and manage your room reservation history."></x-booking.ui.page-header>

    {{-- Search + Filters Form Card --}}
    <form method="GET" action="{{ route('settings.bookings') }}" class="bg-stone-50/60 border border-stone-200/70 p-5 rounded-2xl mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5">Search</label>
                <input type="text" name="search" placeholder="Search by ID, name, or room..." value="{{ request('search') }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow] cursor-pointer font-semibold">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="pending_payment" {{ request('status') == 'pending_payment' ? 'selected' : '' }}>Pending Payment</option>
                    <option value="pending_discount" {{ request('status') == 'pending_discount' ? 'selected' : '' }}>Pending Discount</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5">Sort by</label>
                <select name="sort_by" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow] cursor-pointer font-semibold">
                    <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Booking Date</option>
                    <option value="check_in" {{ request('sort_by') == 'check_in' ? 'selected' : '' }}>Check-in Date</option>
                    <option value="check_out" {{ request('sort_by') == 'check_out' ? 'selected' : '' }}>Check-out Date</option>
                    <option value="total_price" {{ request('sort_by') == 'total_price' ? 'selected' : '' }}>Total Price</option>
                    <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>Booking Status</option>
                </select>
            </div>

            <div class="flex gap-2">
                <select name="sort_dir" class="flex-grow px-3 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow] cursor-pointer font-semibold">
                    <option value="desc" {{ request('sort_dir') == 'desc' ? 'selected' : '' }}>Descending</option>
                    <option value="asc" {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>Ascending</option>
                </select>

                <x-booking.ui.button variant="primary" class="py-2.5 px-4 flex-shrink-0">Apply</x-booking.ui.button>
                @if(request()->has('search') || request()->has('status'))
                    <x-booking.ui.button variant="neutral" href="{{ route('settings.bookings') }}" class="py-2.5 px-4 flex-shrink-0">Reset</x-booking.ui.button>
                @endif
            </div>
        </div>
    </form>

    {{-- Error messages --}}
    @if ($errors->any())
        <div class="mb-6">
            <x-booking.ui.alert type="danger">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-booking.ui.alert>
        </div>
    @endif

    {{-- Booking list --}}
    @if($bookings->count())
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto rounded-2xl border border-stone-200/70 shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-stone-50 text-stone-500 text-[11px] font-bold uppercase tracking-wide border-b border-stone-100">
                        <th class="p-4">ID</th>
                        <th class="p-4">Room Type</th>
                        <th class="p-4">Room Numbers</th>
                        <th class="p-4">Check-in</th>
                        <th class="p-4">Check-out</th>
                        <th class="p-4">Total</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Discount</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 text-sm font-semibold text-stone-700">
                    @foreach($bookings as $booking)
                        <tr class="hover:bg-clsu-50/40 transition-colors">
                            <td class="p-4 font-bold text-ink">#{{ $booking->id }}</td>
                            <td class="p-4 font-bold">{{ $booking->room_type ?? '—' }}</td>
                            <td class="p-4">{{ is_array($booking->room_numbers) ? implode(', ', $booking->room_numbers) : $booking->room_numbers }}</td>
                            <td class="p-4 text-stone-500">{{ $booking->check_in->format('M d, Y') }}</td>
                            <td class="p-4 text-stone-500">{{ $booking->check_out->format('M d, Y') }}</td>
                            <td class="p-4">
                                <span class="text-clsu-800 font-black">₱{{ number_format($booking->payable_amount > 0 ? $booking->payable_amount : $booking->total_price, 2) }}</span>
                            </td>
                            <td class="p-4"><x-booking.ui.badge :status="$booking->status" /></td>
                            <td class="p-4">
                                @if($booking->wants_discount)
                                    @if($booking->discount_status === 'pending')
                                        <x-booking.ui.badge status="pending">Pending</x-booking.ui.badge>
                                    @elseif($booking->discount_status === 'approved')
                                        <x-booking.ui.badge status="approved">Approved</x-booking.ui.badge>
                                    @elseif($booking->discount_status === 'rejected')
                                        <x-booking.ui.badge status="rejected">Rejected</x-booking.ui.badge>
                                    @else
                                        <x-booking.ui.badge status="default">Not Submitted</x-booking.ui.badge>
                                    @endif
                                @else
                                    <x-booking.ui.badge status="no_request">No Request</x-booking.ui.badge>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <x-booking.ui.button variant="neutral" href="{{ route('booking.show', $booking->id) }}" class="py-1.5 px-3.5">View</x-booking.ui.button>
                                    @if($booking->status === 'pending_payment')
                                        <x-booking.ui.button variant="danger" type="button" onclick="openCancelModal({{ $booking->id }})" class="py-1.5 px-3.5">Cancel</x-booking.ui.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="grid grid-cols-1 gap-4 md:hidden">
            @foreach($bookings as $booking)
                <div class="border border-stone-200/70 rounded-2xl p-5 bg-white space-y-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-black text-ink">Booking #{{ $booking->id }}</span>
                        <x-booking.ui.badge :status="$booking->status" />
                    </div>

                    <div class="grid grid-cols-2 gap-y-2.5 gap-x-2 text-xs font-semibold text-stone-600 border-t border-b border-stone-100 py-3">
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider">Room Type</span>
                            <span class="text-stone-800">{{ $booking->room_type ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider">Rooms</span>
                            <span class="text-stone-800 truncate block">{{ is_array($booking->room_numbers) ? implode(', ', $booking->room_numbers) : $booking->room_numbers }}</span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider">Check-in</span>
                            <span class="text-stone-800">{{ $booking->check_in->format('M d, Y') }}</span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider">Check-out</span>
                            <span class="text-stone-800">{{ $booking->check_out->format('M d, Y') }}</span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider">Total Due</span>
                            <span class="text-clsu-800 font-black">₱{{ number_format($booking->payable_amount > 0 ? $booking->payable_amount : $booking->total_price, 2) }}</span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider">Discount</span>
                            <span>
                                @if($booking->wants_discount)
                                    <x-booking.ui.badge :status="$booking->discount_status ?? 'default'" />
                                @else
                                    <x-booking.ui.badge status="no_request">No Request</x-booking.ui.badge>
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-booking.ui.button variant="neutral" href="{{ route('booking.show', $booking->id) }}" class="py-2.5 flex-1 text-xs">View Details</x-booking.ui.button>
                        @if($booking->status === 'pending_payment')
                            <x-booking.ui.button variant="danger" type="button" onclick="openCancelModal({{ $booking->id }})" class="py-2.5 flex-1 text-xs">Cancel Booking</x-booking.ui.button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pagination-wrapper mt-8 flex flex-col items-center space-y-2">
            <div>{{ $bookings->links('vendor.pagination.simple-tailwind') }}</div>
            <div class="text-stone-400 font-bold text-xs">
                Showing {{ $bookings->firstItem() }} to {{ $bookings->lastItem() }} of {{ $bookings->total() }} results
            </div>
        </div>
    @else
        <x-booking.ui.empty-state
            title="No Bookings Yet"
            description="You don't have any room reservations yet. Explore our available rooms and make your first booking today!"
            icon="hotel"
            actionText="Book a Room"
            :actionUrl="route('home')"
        />
    @endif

    <!-- Cancellation Modal -->
    <x-booking.ui.modal id="cancelModal" title="Cancel Room Booking">
        <form id="cancelForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="reason" class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Reason for Cancellation</label>
                <textarea name="reason" id="reason" rows="3" required placeholder="Please provide details on why you are cancelling your booking..."
                          class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold"></textarea>
            </div>

            <div class="pt-4 border-t border-stone-100 flex justify-end gap-2.5">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-stone-100 hover:bg-stone-200 text-stone-700 transition-[color,background-color,border-color,box-shadow] cursor-pointer">Close</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-ember-600 hover:bg-ember-700 text-white shadow-sm transition-[color,background-color,border-color,box-shadow] cursor-pointer">Confirm Cancellation</button>
            </div>
        </form>
    </x-booking.ui.modal>

    <script>
        function openCancelModal(bookingId) {
            const modal = document.getElementById('cancelModal');
            const form = document.getElementById('cancelForm');
            form.action = `/booking/${bookingId}/cancel`;
            modal.classList.remove('hidden');
        }

        function closeModal() {
            window.pubModalClose('cancelModal');
        }
    </script>
@endsection
