{{--
    Full booking-detail modal, rendered server-side to an HTML string by
    CompletedBookingsController::showDetails() and injected via AJAX into
    #bookingDetailsModal on staff/completedbookings/index.blade.php, then
    shown via the page's standard openModal('bookingModal').

    The body content (guest info, pricing, rooms, payment) lives in the
    shared staff.partials.booking-detail-body partial, which is also used by
    resources/views/livewire/bookings-table.blade.php's Livewire-driven
    booking modal — one source of truth for what a booking's details look
    like, instead of two independently hand-maintained copies.
--}}
{{-- $modalId lets a second caller (the global guest-name handler) inject this
     modal on a page that may already have #bookingModal in its own container. --}}
<x-admin.ui.modal :id="$modalId ?? 'bookingModal'" icon="clipboard" :title="'Booking #' . $booking->id" max-width="3xl">
    @include('staff.partials.booking-detail-body', ['booking' => $booking])
</x-admin.ui.modal>
