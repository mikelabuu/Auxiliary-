@extends('layouts.booking_layout')
@section('title', 'Request Discount')
@section('page-title', 'Request Discount')

@section('content')
<div class="container py-4">
    <br><br>
    <h2>Request Discount for Booking #{{ $booking->id }}</h2>

    @if ($booking->num_seniors > 0)
    <form action="{{ route('discount.store', $booking->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        @foreach ($booking->reservations as $reservation)
            <div class="mb-4 border p-3 rounded">
                <h5>
                    Room {{ $reservation->room_number }} ({{ ucfirst($reservation->room_type) }})  
                    – Seniors: {{ $reservation->num_seniors }}
                </h5>

                @if($reservation->num_seniors > 0)
                    <label for="discount_files_{{ $reservation->id }}" class="form-label">
                        Upload up to {{ $reservation->num_seniors }} Senior/PWD ID(s)
                    </label>

                    <input 
                        type="file" 
                        name="discount_files[{{ $reservation->id }}][]" 
                        id="discount_files_{{ $reservation->id }}" 
                        class="form-control discount-file-input" 
                        data-max="{{ $reservation->num_seniors }}" 
                        multiple
                        accept="image/jpeg,image/png,image/jpg"
                    >
                @else
                    <p class="text-muted">No seniors declared for this room.</p>
                @endif
                
            </div>
        @endforeach

        <button type="submit" class="btn btn-success">Submit Discount Request</button>
        <a href="{{ route('booking.show', $booking->id) }}" class="btn btn-secondary">Back to Booking Summary</a>
    </form>
    @else
        <p class="text-muted">No seniors declared for any reservations</p>
        <a href="{{ route('booking.show', $booking->id) }}" class="btn btn-secondary">Back to Booking Summary</a>
    @endif
</div>

<script>
// Enforce per-room senior limits
document.querySelectorAll('.discount-file-input').forEach(input => {
    input.addEventListener('change', function() {
        const max = parseInt(this.dataset.max);
        if (this.files.length > max) {
            alert(`You can upload a maximum of ${max} file(s) for this room.`);
            this.value = ''; // reset input
        }
    });
});
</script>

@endsection
