@extends('layouts.frontdesk')
@section('title', 'Front Desk · Booking Details')
@section('content')

<x-frontdesk.flash />

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <x-admin.ui.icon name="receipt" />
            Booking
            <span class="ref-code">BK-{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</span>
            <span class="status status-{{ $booking->status }}">{{ ucwords(str_replace('_', ' ', $booking->status)) }}</span>
        </h3>
        <div class="card-header-actions">
            <a href="{{ route('frontdesk.booking') }}" class="btn btn-outline btn-sm !no-underline">
                <x-admin.ui.icon name="chevron-left" class="h-3.5 w-3.5" stroke-width="2" />
                All bookings
            </a>
            @if ($booking->status == 'active')
                <form method="POST" action="{{ route('frontdesk.booking.checkout', $booking->id) }}" class="js-checkout-form">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        <x-admin.ui.icon name="log-out" class="h-3.5 w-3.5" stroke-width="2" />
                        Check out
                    </button>
                </form>
            @endif
        </div>
    </div>
    <div class="card-body">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="record-detail-panel">
                @foreach ([
                    'Guest name' => $booking->guest_name,
                    'Phone' => $booking->guest_phone,
                    'Address' => $booking->guest_address,
                    'Expected guests' => $booking->expected_guests,
                    'Seniors' => $booking->num_seniors,
                ] as $label => $value)
                    <div class="record-detail-row">
                        <span class="record-detail-label">{{ $label }}</span>
                        <span class="record-detail-value">{{ $value ?? '—' }}</span>
                    </div>
                @endforeach
            </div>
            <div class="record-detail-panel">
                @foreach ([
                    'Check-in' => \Carbon\Carbon::parse($booking->check_in)->format('M d, Y'),
                    'Check-out' => \Carbon\Carbon::parse($booking->check_out)->format('M d, Y'),
                    'Booking method' => $booking->method ? ucfirst($booking->method) : '—',
                    'Total price' => '₱' . number_format($booking->total_price, 2),
                    'Discount' => '₱' . number_format($booking->discount, 2),
                ] as $label => $value)
                    <div class="record-detail-row">
                        <span class="record-detail-label">{{ $label }}</span>
                        <span class="record-detail-value tabnum">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <h4 class="section-title mt-8">Room reservations</h4>
        <div class="scroll-x mt-3 rounded-xl border border-stone-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Type</th>
                        <th class="text-right">Guests</th>
                        <th class="text-right">Seniors</th>
                        <th class="text-right">Price / night</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($booking->reservations as $res)
                    <tr>
                        <td><span class="cell-tag">{{ $res->room_number }}</span></td>
                        <td>{{ ucfirst($res->room_type) }}</td>
                        <td class="text-right tabnum">{{ $res->num_guests }}</td>
                        <td class="text-right tabnum">{{ $res->num_seniors }}</td>
                        <td class="text-right font-semibold text-ink tabnum">₱{{ number_format($res->price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer flex items-center justify-between gap-4">
        <span class="kv-label !mb-0">Payable amount</span>
        <span class="font-display text-2xl font-bold text-g-700 tabnum">₱{{ number_format($booking->payable_amount, 2) }}</span>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    $(document).on('submit', '.js-checkout-form', function (e) {
        if ($(this).data('confirmed')) return;
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Check out this booking?',
            text: 'The rooms will be released and the booking marked completed.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Check out',
            confirmButtonColor: '#099250'
        }).then(function (res) {
            if (res.isConfirmed) {
                $(form).data('confirmed', true);
                form.submit();
            }
        });
    });
});
</script>
@endpush
