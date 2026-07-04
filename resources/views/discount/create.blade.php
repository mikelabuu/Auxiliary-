@extends('layouts.guest')
@section('title', 'Request Discount')
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <x-booking.page-header title="Request Senior / PWD Discount" subtitle="Please upload verification documents for each room reservation to apply the 20% discount."></x-booking.page-header>

    @if ($booking->num_seniors > 0)
    <form action="{{ route('discount.store', $booking->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @foreach ($booking->reservations as $reservation)
            @if($reservation->num_seniors > 0)
                <x-booking.card title="Room #{{ $reservation->room_number }} Allocation Details" icon="badge">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-550 border-b border-slate-50 pb-3">
                            <span>Room Type: <strong class="text-slate-800">{{ ucfirst($reservation->room_type) }}</strong></span>
                            <span>Declared Seniors / PWD: <strong class="text-slate-800">{{ $reservation->num_seniors }}</strong></span>
                        </div>

                        <div>
                            <label for="discount_files_{{ $reservation->id }}" class="block text-xs font-bold text-slate-700 tracking-wider uppercase mb-1.5">
                                Upload Verification IDs (Maximum {{ $reservation->num_seniors }})
                            </label>
                            
                            <input 
                                type="file" 
                                name="discount_files[{{ $reservation->id }}][]" 
                                id="discount_files_{{ $reservation->id }}" 
                                class="discount-file-input w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-brand focus:ring-1 focus:ring-brand outline-none transition-all cursor-pointer font-semibold file:mr-4 file:py-1.5 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-brand-muted file:text-brand hover:file:bg-brand hover:file:text-white file:transition-all file:cursor-pointer" 
                                data-max="{{ $reservation->num_seniors }}" 
                                multiple
                                accept="image/jpeg,image/png,image/jpg"
                                required
                            >
                            <p class="text-[10px] text-slate-400 mt-2 font-medium leading-relaxed">Accepted formats: JPG, JPEG, PNG. Max file size: 2MB per document. Ensure details are clearly legible.</p>
                        </div>
                    </div>
                </x-booking.card>
            @endif
        @endforeach

        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-end gap-3">
            <x-booking.button variant="neutral" href="{{ route('booking.show', $booking->id) }}" class="w-full sm:w-auto py-3">
                Cancel and Go Back
            </x-booking.button>
            <x-booking.button variant="primary" type="submit" class="w-full sm:w-auto py-3 font-extrabold shadow-lg">
                Submit Discount Documents
            </x-booking.button>
        </div>
    </form>
    @else
        <x-booking.empty-state 
            title="No Seniors Declared" 
            description="You did not declare any senior citizens or PWDs for this room reservation booking."
            icon="sentiment_dissatisfied"
            actionText="Back to Summary"
            :actionUrl="route('booking.show', $booking->id)"
        />
    @endif
</div>

<script>
// Enforce upload file limit per room
document.querySelectorAll('.discount-file-input').forEach(input => {
    input.addEventListener('change', function() {
        const max = parseInt(this.dataset.max);
        if (this.files.length > max) {
            swal({
                title: "File Upload Limit",
                text: `You can upload a maximum of ${max} verification document(s) for this room.`,
                icon: "warning",
                button: "Okay",
            });
            this.value = ''; // reset field
        }
    });
});
</script>
@endsection
