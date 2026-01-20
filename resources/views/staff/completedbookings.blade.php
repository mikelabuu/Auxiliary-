@extends('layouts.admin')
@section('title', 'Admin - Booking Hub')
@section('page-title', 'Completed Bookings')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>
<div class="p-6">
    <h1 class="text-2xl font-semibold mb-6">Completed Bookings</h1>

    {{-- Filters --}}
    <form method="GET" class="flex justify-between mb-4">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search booking ID..." class="border rounded-lg px-3 py-2 w-1/3">
        <select name="sort" class="border rounded-lg px-3 py-2" onchange="this.form.submit()">
            <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Sort: Latest</option>
            <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Sort: Oldest</option>
        </select>
    </form>

    {{-- Table --}}
    <table class="min-w-full bg-white border rounded-lg">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="p-3">ID</th>
                <th class="p-3">Guest Name</th>
                <th class="p-3">Check-in</th>
                <th class="p-3">Check-out</th>
                <th class="p-3">Status</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
                <tr class="border-t">
                    <td class="p-3">{{ $booking->id }}</td>
                    <td class="p-3">{{ $booking->guest_name }}</td>
                    <td class="p-3">{{ $booking->check_in->format('M d, Y') }}</td>
                    <td class="p-3">{{ $booking->check_out->format('M d, Y') }}</td>
                    <td class="p-3">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                            Completed
                        </span>
                    </td>
                    <td class="p-3">
                        <button 
                            class="px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition password-verify" 
                            data-id="{{ $booking->id }}">
                            View
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center p-4 text-gray-500">No completed bookings found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $bookings->links() }}
    </div>

    {{-- Modal Container (HTML inserted via AJAX) --}}
    <div id="bookingDetailsModal"></div>
</div>
@endsection

@push('scripts')
<script>
$(document).on('click', '.password-verify', function(e) {
    e.preventDefault();
    const bookingId = $(this).data('id');

    Swal.fire({
        title: 'Enter your password',
        input: 'password',
        inputAttributes: {
            placeholder: 'Password',
            autocapitalize: 'off'
        },
        showCancelButton: true,
        confirmButtonText: 'Verify',
        showLoaderOnConfirm: true,
        preConfirm: (password) => {
            return $.ajax({
                url: "{{ route('staff.completedbookings.verify-password') }}",
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    password: password
                }
            }).then(response => {
                if (!response.success) {
                    throw new Error(response.message);
                }
                return true;
            }).catch(err => {
                Swal.showValidationMessage(err.responseJSON?.message || err.message);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/staff/completed-bookings/${bookingId}/details`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        $('#bookingDetailsModal').html(response.html);
                        $('#bookingModal').removeClass('hidden');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to load booking details.', 'error');
                }
            });
        }
    });
});

$(document).on('click', '#closeBookingModal', function() {
    $('#bookingModal').addClass('hidden');
});
</script>
@endpush
