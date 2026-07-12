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
        <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
            <div class="relative flex-1 max-w-xs">
                <x-admin.ui.icon name="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400" stroke-width="2" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Search booking ID…" class="w-full text-sm bg-stone-50 border border-stone-200 rounded-lg pl-10 pr-4 py-2.5 text-stone-700 placeholder:text-stone-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors">
            </div>
            <select name="sort" onchange="this.form.submit()" class="w-full sm:w-44 px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-700 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 cursor-pointer transition-colors">
                <option value="latest" @selected($sort === 'latest')>Sort: Latest</option>
                <option value="oldest" @selected($sort === 'oldest')>Sort: Oldest</option>
            </select>
            <x-admin.ui.button variant="secondary" type="submit">Search</x-admin.ui.button>
        </form>

        @if($bookings->isEmpty())
            <x-admin.ui.empty-state icon="check-circle" title="No completed bookings found." />
        @else
            <div class="-mx-6 -mb-6 border-t border-stone-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-stone-50/70 border-b border-stone-100">
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">ID</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Guest Name</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Check-in</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Check-out</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Status</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                            <tr class="border-b border-stone-100 hover:bg-clsu-50/40 transition-colors">
                                <td class="px-6 py-3 text-stone-700 font-data tabnum">#{{ $booking->id }}</td>
                                <td class="px-6 py-3 text-stone-800 font-medium">{{ $booking->guest_name }}</td>
                                <td class="px-6 py-3 text-stone-700 font-data tabnum">{{ $booking->check_in->format('M d, Y') }}</td>
                                <td class="px-6 py-3 text-stone-700 font-data tabnum">{{ $booking->check_out->format('M d, Y') }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border bg-clsu-50 text-clsu-700 border-clsu-200">Completed</span>
                                </td>
                                <td class="px-6 py-3">
                                    <button type="button" class="password-verify flex items-center gap-1.5 text-xs font-semibold text-clsu-700 border border-clsu-200 bg-white rounded-lg px-3 py-1.5 hover:bg-clsu-50 transition-colors cursor-pointer" data-id="{{ $booking->id }}">
                                        <x-admin.ui.icon name="eye" class="w-3.5 h-3.5" />
                                        View
                                    </button>
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
