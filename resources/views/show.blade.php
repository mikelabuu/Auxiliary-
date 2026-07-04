@extends('layouts.guest')
@section('title', 'Booking Summary')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <!-- Status-driven Banner -->
    @php
        $statusColors = [
            'pending_payment' => 'bg-amber-50 border-amber-200 text-amber-800',
            'pending_discount' => 'bg-amber-50 border-amber-200 text-amber-800',
            'active' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
            'completed' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
            'cancelled' => 'bg-rose-50 border-rose-200 text-rose-800',
        ];
        $statusIcons = [
            'pending_payment' => 'payments',
            'pending_discount' => 'upload_file',
            'active' => 'check_circle',
            'completed' => 'task_alt',
            'cancelled' => 'cancel',
        ];
        $statusLabel = str_replace('_', ' ', ucfirst($booking->status));
        $bannerClass = $statusColors[$booking->status] ?? 'bg-slate-50 border-slate-200 text-slate-700';
        $bannerIcon = $statusIcons[$booking->status] ?? 'info';
    @endphp

    <div class="border rounded-2xl p-5 mb-8 flex items-center justify-between gap-4 {{ $bannerClass }}">
        <div class="flex items-center gap-3">
            <span class="material-icons text-[24px]">{{ $bannerIcon }}</span>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-widest opacity-80 leading-none">Booking #{{ $booking->id }} &middot; Status</span>
                <span class="block text-base font-black tracking-tight mt-0.5">{{ $statusLabel }}</span>
            </div>
        </div>
        
        <div>
            <x-booking.badge :status="$booking->status" class="!px-3 !py-1 text-xs" />
        </div>
    </div>

    <!-- 2-Column Desktop Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <!-- Left Side: Guest Info + Reservation Items Breakdown (Col-Span: 2) -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Guest Details Card -->
            <x-booking.card title="Guest Details" icon="badge">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm font-semibold text-slate-650">
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase tracking-widest mb-0.5">Primary Guest Name</span>
                        <span class="text-slate-900 text-base font-bold">{{ $booking->guest_name }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase tracking-widest mb-0.5">Contact Number</span>
                        <span class="text-slate-900 text-base font-bold">{{ $booking->guest_phone }}</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-[10px] text-slate-400 uppercase tracking-widest mb-0.5">Address</span>
                        <span class="text-slate-900 leading-relaxed font-bold">{{ $booking->guest_address }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase tracking-widest mb-0.5">Expected Guests</span>
                        <span class="text-slate-900 font-bold">{{ $booking->expected_guests }} Pax</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase tracking-widest mb-0.5">Declared Seniors / PWD</span>
                        <span class="text-slate-900 font-bold">{{ $booking->num_seniors }} Person(s)</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase tracking-widest mb-0.5">Check-in Date</span>
                        <span class="text-slate-900 font-bold">{{ $booking->check_in->format('F d, Y') }} (2:00 PM)</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase tracking-widest mb-0.5">Check-out Date</span>
                        <span class="text-slate-900 font-bold">{{ $booking->check_out->format('F d, Y') }} (12:00 NN)</span>
                    </div>
                </div>
            </x-booking.card>

            <!-- Reservation Room Breakdown -->
            <div class="space-y-4">
                <h3 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2 px-1">
                    <span class="material-icons text-brand">meeting_room</span>
                    Room Reservation Details
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach($booking->reservations as $reservation)
                        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm hover:shadow transition-shadow flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between border-b border-slate-50 pb-3 mb-4">
                                    <span class="text-sm font-black text-slate-900">Room #{{ $reservation->room_number }}</span>
                                    <x-booking.badge status="active">{{ ucfirst($reservation->room_type) }}</x-booking.badge>
                                </div>
                                
                                <div class="text-xs font-semibold text-slate-650 space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Assigned Guests:</span>
                                        <span class="text-slate-800">{{ $reservation->num_guests }} Pax</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Seniors / PWD:</span>
                                        <span class="text-slate-800">{{ $reservation->num_seniors }}</span>
                                    </div>
                                    
                                    <div class="pt-2 border-t border-slate-50">
                                        <span class="block text-[10px] text-slate-400 uppercase tracking-widest mb-1.5">Free Breakfast Selection</span>
                                        @if(!empty($reservation->meal))
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($reservation->meal as $mealName => $qty)
                                                    @if($qty > 0)
                                                        <span class="px-2.5 py-1 bg-slate-50 border border-slate-100 rounded-lg text-[10px] text-slate-700 font-bold uppercase tracking-wider">
                                                            {{ $mealName }}: {{ $qty }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-slate-400 italic">No breakfast choices selected</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 pt-3 border-t border-slate-50 flex items-center justify-between text-sm font-semibold">
                                <span class="text-slate-400">Room Price / Night:</span>
                                <span class="text-slate-900 font-black">₱{{ number_format($reservation->price, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Side: Billing Summary Card & Action CTAs (Col-Span: 1) -->
        <div class="space-y-6">
            <!-- Payment Billing card -->
            <x-booking.card title="Payment Summary" icon="receipt_long">
                <div class="space-y-4 text-sm font-semibold text-slate-650">
                    <div class="flex justify-between items-center text-slate-500">
                        <span>Subtotal Price</span>
                        <span>₱{{ number_format($booking->total_price, 2) }}</span>
                    </div>

                    @if($booking->discount > 0)
                        <div class="flex justify-between items-center text-rose-600 bg-rose-50/50 border border-rose-100/50 px-3 py-2 rounded-xl text-xs">
                            <span class="flex items-center gap-1"><span class="material-icons text-[14px]">discount</span> Discount Approved</span>
                            <span>-₱{{ number_format($booking->discount, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-slate-50 pt-3 text-slate-900">
                            <span class="text-base font-black">Payable Amount</span>
                            <span class="text-lg font-black text-brand">₱{{ number_format($booking->payable_amount, 2) }}</span>
                        </div>
                    @else
                        <div class="flex justify-between items-center border-t border-slate-50 pt-3 text-slate-900">
                            <span class="text-base font-black">Total Price</span>
                            <span class="text-lg font-black text-brand">₱{{ number_format($booking->total_price, 2) }}</span>
                        </div>
                    @endif

                    <!-- Discount Uploads Processing -->
                    @if($booking->wants_discount)
                        <div class="pt-4 border-t border-slate-100 space-y-3.5">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">Senior / PWD Verification</h4>
                            
                            @if(!$discountRequested)
                                <!-- Call-to-action button to upload docs -->
                                <p class="text-xs text-slate-400 leading-relaxed font-semibold">Please upload Senior Citizen or PWD verification documents to apply your 20% discount.</p>
                                <x-booking.button variant="secondary" href="{{ route('discount.create', $booking->id) }}" class="w-full py-3 flex items-center justify-center gap-1.5 font-extrabold text-xs">
                                    <span class="material-icons text-[16px]">upload_file</span>
                                    Request & Upload IDs
                                </x-booking.button>
                            @else
                                <!-- Request exists -->
                                @if($discount && $discount->status === 'pending')
                                    <div class="p-3 bg-amber-50/50 border border-amber-100 rounded-xl text-xs space-y-3">
                                        <div class="flex gap-2 text-amber-800">
                                            <span class="material-icons text-[16px] flex-shrink-0 mt-0.5">hourglass_empty</span>
                                            <div class="font-bold leading-relaxed">Verification Request Submitted. Please wait for staff review and approval before making payments.</div>
                                        </div>
                                        
                                        <!-- Cancel Request Form button -->
                                        <form action="{{ route('discount.cancel', $booking->id) }}" method="POST" class="w-full">
                                            @csrf
                                            <button type="submit" class="w-full py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg text-[10px] transition-all cursor-pointer">
                                                Cancel Request
                                            </button>
                                        </form>
                                    </div>
                                @elseif($discount)
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-400 text-xs">Request Status:</span>
                                        <x-booking.badge :status="$discount->status" />
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

                    <!-- Primary Payments Button -->
                    @if($booking->status === 'pending_payment')
                        <div class="pt-4 border-t border-slate-100">
                            <x-booking.button variant="primary" href="{{ route('bookings.pay', $booking->id) }}" class="w-full py-3.5 flex items-center justify-center gap-1.5 shadow-2xl font-black text-sm">
                                <span class="material-icons text-[18px]">payment</span>
                                Proceed to Payment Gateway
                            </x-booking.button>
                        </div>
                    @endif
                </div>
            </x-booking.card>

            <!-- Navigation control links -->
            <div class="flex flex-col gap-3">
                <x-booking.button variant="neutral" href="{{ route('settings.bookings') }}" class="w-full py-3 flex items-center justify-center gap-1.5 font-bold">
                    <span class="material-icons text-[18px]">arrow_back</span>
                    Return to My Bookings
                </x-booking.button>
            </div>
        </div>
    </div>
</div>
@endsection
