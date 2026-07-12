@extends('layouts.admin')

@section('title', 'Admin - Completed Bookings')
@section('page-title', 'Completed Bookings')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.ui.page-header subtitle="Bookings that have been checked out.">
        Completed <span class="text-clsu-700">Bookings</span>
    </x-admin.ui.page-header>

    <x-admin.ui.section-card icon="check-circle" title="Completed Bookings" :subtitle="$bookings->total() . ' total'" :delay="40">
        <form method="GET" class="filter-toolbar">
            <div class="filter-search">
                <x-admin.ui.icon name="search" class="w-4 h-4" stroke-width="2" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Search booking ID or guest…" aria-label="Search completed bookings">
            </div>
            <select name="sort" onchange="this.form.submit()" class="filter-select" aria-label="Sort order">
                <option value="latest" @selected($sort === 'latest')>Newest first</option>
                <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
            <div class="filter-toolbar-spacer"></div>
            @if($search)
                <a href="{{ route('staff.completedbookings.index') }}" class="filter-clear" style="text-decoration:none;">
                    <x-admin.ui.icon name="x" class="w-3 h-3" stroke-width="2.5" /> Clear
                </a>
            @endif
        </form>

        @if($bookings->isEmpty())
            <x-admin.ui.empty-state icon="check-circle" title="No completed bookings found." />
        @else
            <div class="scroll-x -mx-6 -mb-6 border-t border-stone-100">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ref code</th>
                            <th>Guest</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                            @php
                                $initials = collect(explode(' ', trim($booking->guest_name)))
                                    ->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
                                $initials = strtoupper($initials ?: 'G');
                            @endphp
                            <tr>
                                <td>
                                    <div class="ref-cell">
                                        <div class="ref-cell-top">
                                            <span class="ref-code">BK-{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</span>
                                            <button type="button" class="copy-ref" data-copy="BK-{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}" title="Copy reference">
                                                <x-admin.ui.icon name="clipboard" class="w-3 h-3" /> Copy
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-name">
                                        <span class="avatar-initials">{{ $initials }}</span>
                                        <div class="cell-name-text">
                                            <p class="cell-name-primary">{{ $booking->guest_name }}</p>
                                            <p class="cell-name-secondary">#{{ $booking->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-data tabnum">{{ $booking->check_in->format('M d, Y') }}</td>
                                <td class="font-data tabnum">{{ $booking->check_out->format('M d, Y') }}</td>
                                <td><span class="status status-completed">Completed</span></td>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="password-verify btn btn-outline btn-sm cursor-pointer" data-id="{{ $booking->id }}">
                                            <x-admin.ui.icon name="eye" class="w-3.5 h-3.5" />
                                            View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $bookings->links() }}
            </div>
        @endif
    </x-admin.ui.section-card>
</div>

{{-- Filled via AJAX with staff.partials.booking-details --}}
<div id="bookingDetailsModal"></div>

@push('scripts')
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    function openModal(id) { $('#' + id).removeClass('hidden').addClass('flex'); }
    function closeModal(id) { $('#' + id).addClass('hidden').removeClass('flex'); }

    $(document).on('click', '[data-modal-close]', function () { closeModal($(this).data('modal-close')); });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') $('#bookingModal').addClass('hidden').removeClass('flex');
    });

    $(document).on('click', '.password-verify', function (e) {
        e.preventDefault();
        const bookingId = $(this).data('id');

        Swal.fire({
            title: 'Enter your password',
            input: 'password',
            inputAttributes: { placeholder: 'Password', autocapitalize: 'off' },
            showCancelButton: true,
            confirmButtonText: 'Verify',
            showLoaderOnConfirm: true,
            preConfirm: (password) => {
                return $.ajax({
                    url: "{{ route('staff.completedbookings.verify-password') }}",
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), password }
                }).then(response => {
                    if (!response.success) throw new Error(response.message);
                    return true;
                }).catch(err => Swal.showValidationMessage(err.responseJSON?.message || err.message));
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: `{{ url('staff/completed-bookings') }}/${bookingId}/details`,
                method: 'GET',
                success: function (response) {
                    if (!response.success) { Swal.fire('Error', response.message, 'error'); return; }
                    $('#bookingDetailsModal').html(response.html);
                    openModal('bookingModal');
                },
                error: function () {
                    Swal.fire('Error', 'Failed to load booking details.', 'error');
                }
            });
        });
    });
});
</script>
@endpush
@endsection
