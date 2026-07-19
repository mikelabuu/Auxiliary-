@extends('layouts.admin')

@section('title', 'Admin - Completed Bookings')
@section('page-title', 'Completed Bookings')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.ui.page-header subtitle="Bookings that have been checked out.">
        Completed Bookings
    </x-admin.ui.page-header>

    {{-- Livewire handles search, filter, sort, and pagination reactively --}}
    @livewire('staff.completed-bookings')
</div>

{{-- Filled via AJAX with staff.partials.booking-details --}}
<div id="bookingDetailsModal"></div>

@push('scripts')
{{-- SweetAlert password-verify + AJAX detail modal (stays as JS, not Livewire) --}}
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // Animated modal helpers live in layouts/admin (window.openModal/closeModal)
    const openModal = (id) => window.openModal(id);
    const closeModal = (id) => window.closeModal(id);

    $(document).on('click', '[data-modal-close]', function () { closeModal($(this).data('modal-close')); });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') closeModal('bookingModal');
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
