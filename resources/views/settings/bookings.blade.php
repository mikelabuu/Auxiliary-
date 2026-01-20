@extends('layouts.settings_layout')
@section('title', 'My Bookings')
<link rel="stylesheet" href="{{ asset('css/booking-settings.css') }}">
<script src="https://cdn.tailwindcss.com"></script>
@section('page-title', 'My Bookings')

@section('settings-content')
<div class="bookings-container">
    <br>
    <h2 class="tab-title">My Bookings</h2>

    {{-- Search + Filters --}}
    <form method="GET" action="{{ route('settings.bookings') }}" class="filter-form">
        <input type="text" name="search" placeholder="Search by ID, name, or room..." value="{{ request('search') }}">

        <select name="status">
            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
            <option value="pending_payment" {{ request('status') == 'pending_payment' ? 'selected' : '' }}>Pending Payment</option>
            <option value="pending_discount" {{ request('status') == 'pending_discount' ? 'selected' : '' }}>Pending Discount</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
        </select>

        <select name="sort_by">
            <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Sort by Date</option>
            <option value="check_in" {{ request('sort_by') == 'check_in' ? 'selected' : '' }}>Sort by Check-in</option>
            <option value="check_out" {{ request('sort_by') == 'check_out' ? 'selected' : '' }}>Sort by Check-out</option>
            <option value="total_price" {{ request('sort_by') == 'total_price' ? 'selected' : '' }}>Sort by Total</option>
            <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>Sort by Status</option>
        </select>

        <select name="sort_dir">
            <option value="desc" {{ request('sort_dir') == 'desc' ? 'selected' : '' }}>Descending</option>
            <option value="asc" {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>Ascending</option>
        </select>

        <button type="submit" class="btn btn-view">Apply</button>
        @if(request()->has('search') || request()->has('status'))
            <a href="{{ route('settings.bookings') }}" class="btn btn-cancel">Reset</a>
        @endif
    </form>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($bookings->count())
        <table class="bookings-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Room Type</th>
                    <th>Room Numbers</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Discount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                    <tr>
                        <td>#{{ $booking->id }}</td>
                        <td>{{ $booking->room_type ?? '—' }}</td>
                        <td>{{ is_array($booking->room_numbers) ? implode(', ', $booking->room_numbers) : $booking->room_numbers }}</td>
                        <td>{{ $booking->check_in->format('M d, Y') }}</td>
                        <td>{{ $booking->check_out->format('M d, Y') }}</td>
                        @if($booking->payable_amount > 0) 
                            <td>₱{{ number_format($booking->payable_amount, 2) }}</td>
                        @else
                            <td>₱{{ number_format($booking->total_price, 2) }}</td>
                        @endif
                        <td>
                            <span class="status-badge status-{{ $booking->status ?? 'default' }}">
                                {{ str_replace('_', ' ', ucfirst($booking->status ?? 'unknown')) }}
                            </span>
                        </td>
                        <td>
                            @if($booking->wants_discount)
                                @if($booking->discount_status === 'pending')
                                    <span class="discount-badge pending">Pending</span>
                                @elseif($booking->discount_status === 'approved')
                                    <span class="discount-badge approved">Approved</span>
                                @elseif($booking->discount_status === 'rejected')
                                    <span class="discount-badge rejected">Rejected</span>
                                @else
                                    <span class="discount-badge not-submitted">Not Yet Submitted</span>
                                @endif
                            @else
                                <span class="discount-badge not-submitted">No Request</span>
                            @endif
                        </td>
                        <td class="actions-cell">
                            <a href="{{ route('booking.show', $booking->id) }}" class="btn btn-view">View</a>

                            @if($booking->status === 'pending_payment')
                                <button type="button" class="btn btn-cancel" onclick="openCancelModal({{ $booking->id }})">Cancel</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="pagination-wrapper mt-6 flex flex-col items-center space-y-2">
            <div>
                {{-- Render only pagination links (no extra text) --}}
                {{ $bookings->links('vendor.pagination.simple-tailwind') }}
            </div>

            <div class="text-gray-400 text-sm">
                Showing {{ $bookings->firstItem() }} to {{ $bookings->lastItem() }} of {{ $bookings->total() }} results
            </div>
        </div>
    @else
        <p class="no-bookings">You don't have any bookings yet.</p>
    @endif
</div>
<!-- Cancellation Modal -->
<div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded shadow w-96">
        <h3 class="text-lg font-bold mb-4">Cancel Booking</h3>
        <form id="cancelForm" method="POST">
            @csrf
            <label for="reason" class="block mb-2 font-medium">Reason for cancellation:</label>
            <textarea name="reason" id="reason" class="border p-2 w-full rounded mb-4" rows="3" required></textarea>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal()" class="btn btn-cancel">Close</button>
                <button type="submit" class="btn btn-danger">Confirm Cancel</button>
            </div>
        </form>
    </div>
</div>
<script>
    function openCancelModal(bookingId) {
        const modal = document.getElementById('cancelModal');
        const form = document.getElementById('cancelForm');
        form.action = `/booking/${bookingId}/cancel`; // dynamically set form action
        modal.classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('cancelModal').classList.add('hidden');
    }
</script>
@endsection
