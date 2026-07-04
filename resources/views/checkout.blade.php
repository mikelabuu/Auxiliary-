@extends('layouts.guest')
@section('title', 'Checkout | Farmers Hostel')
@section('content')

    <style>
        .d-none { display: none !important; }
        .room-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 8px;
            margin-top: 12px;
        }
        .room-tile {
            padding: 10px 6px;
            text-align: center;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            user-select: none;
        }
        .room-tile:hover {
            border-color: #cbd5e1;
            background-color: #f1f5f9;
        }
        .room-tile.available {
            border-color: #bbf7d0;
            background-color: #f0fdf4;
            color: #15803d;
        }
        .room-tile.available:hover {
            background-color: #dcfce7;
            border-color: #86efac;
        }
        .room-tile.selected {
            background-color: var(--color-nautical-teal) !important;
            color: #ffffff !important;
            border-color: var(--color-nautical-teal) !important;
            box-shadow: 0 4px 12px rgba(8, 78, 114, 0.3);
        }
        .room-tile.booked {
            background-color: #fee2e2;
            color: #b91c1c;
            border-color: #fecaca;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .room-tile.cleaning {
            background-color: #fef3c7;
            color: #b45309;
            border-color: #fde68a;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .room-tile.maintenance {
            background-color: #f1f5f9;
            color: #64748b;
            border-color: #e2e8f0;
            cursor: not-allowed;
            opacity: 0.6;
            text-decoration: line-through;
        }
        .flatpickr-calendar {
            box-shadow: var(--shadow-md) !important;
            border: 1px solid var(--color-slate-100) !important;
            border-radius: 1rem !important;
        }
    </style>

    <div class="min-h-screen bg-slate-50 pt-28 pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Checkout</h1>
                    <p class="text-sm font-semibold text-slate-500 mt-1">Complete your booking details to secure your reservation.</p>
                </div>
                <a href="{{ route('home') }}" class="text-sm font-bold text-nautical-teal hover:underline flex items-center gap-1">
                    <span class="material-icons text-[16px]">arrow_back</span> Back to Rooms
                </a>
            </div>

            <!-- Error feedback display -->
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 text-red-800 border border-red-200/60 rounded-xl text-sm font-semibold">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div id="bookingFormAlert" class="mb-6 p-4 bg-red-50 text-red-800 border border-red-200/60 rounded-xl text-sm font-semibold d-none"></div>

            <form id="bookingForm" method="POST" action="{{ route('booking.store') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                @csrf
                
                <!-- Hidden aggregate values needed for backend forms -->
                <input type="hidden" name="room_numbers" id="selected_room_number">
                <input type="hidden" name="num_seniors" id="num_seniors" value="0">
                <!-- Check-in and Check-out are used globally by JS, we will store them here -->
                <input type="hidden" name="check_in" id="check_in_hidden">
                <input type="hidden" name="check_out" id="check_out_hidden">

                <!-- Left Column: Guest Details & Config -->
                <div class="lg:col-span-8 space-y-6">
                    
                    <!-- DATES -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/60">
                        <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-2">
                            <span class="w-1 h-4 rounded-full bg-nautical-teal"></span>
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">Stay Dates</h4>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 tracking-wider uppercase mb-1.5">Check-in</label>
                                <input type="text" id="check_in" class="flatpickr-date w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-nautical-teal focus:ring-2 focus:ring-nautical-teal/20 outline-none font-semibold cursor-pointer" placeholder="Select Date" value="{{ $checkIn ?? '' }}">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 tracking-wider uppercase mb-1.5">Check-out</label>
                                <input type="text" id="check_out" class="flatpickr-date w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-nautical-teal focus:ring-2 focus:ring-nautical-teal/20 outline-none font-semibold cursor-pointer" placeholder="Select Date" value="{{ $checkOut ?? '' }}">
                            </div>
                        </div>
                    </div>

                    <!-- GUEST INFO (Imported from Partial) -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/60">
                        @include('booking.partials.step-guest')
                    </div>

                    <!-- ROOM SELECTION -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/60">
                        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-1 h-4 rounded-full bg-nautical-teal"></span>
                                <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">Room Allocation</h4>
                            </div>
                            <button type="button" onclick="window.addReservationBlock()" class="text-xs font-bold text-nautical-teal bg-sky-wash px-3 py-1.5 rounded-lg border border-nautical-teal/20 hover:bg-nautical-teal hover:text-white transition-colors">
                                + Add Room Type
                            </button>
                        </div>
                        <p class="text-sm text-slate-500 font-medium mb-4">Please configure the rooms you want to book. You must select specific room numbers for each type.</p>
                        
                        <div id="reservationBlocks" class="space-y-4">
                            <!-- JS will inject blocks here -->
                        </div>
                    </div>

                </div>

                <!-- Right Column: Sticky Summary -->
                <div class="lg:col-span-4">
                    <div class="bg-white rounded-3xl p-6 shadow-xl shadow-slate-200/50 border border-slate-200/80 sticky top-28">
                        <h3 class="text-lg font-black text-slate-900 border-b border-slate-100 pb-4 mb-4">Booking Summary</h3>
                        
                        <!-- Summary Invoice will be rendered here by JS -->
                        <div id="summaryInvoice" class="space-y-4 mb-6 text-sm font-medium text-slate-600">
                            <div class="text-center py-8">
                                <span class="material-icons text-slate-300 text-4xl mb-2 block">receipt_long</span>
                                <p>Select dates and rooms to view summary.</p>
                            </div>
                        </div>

                        <button type="submit" id="btnSubmitBooking" class="w-full py-4 rounded-xl text-sm font-black bg-gradient-to-r from-nautical-teal to-cobalt-pop text-white shadow-[0_4px_14px_rgba(8,78,114,0.3)] hover:shadow-[0_6px_20px_rgba(8,78,114,0.4)] hover:-translate-y-0.5 transition-all cursor-pointer">
                            Confirm Booking
                        </button>
                        <p class="text-center mt-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            Payment collected later
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Template for Room Blocks -->
    <template id="reservationBlockTemplate">
        @include('booking.partials.reservation-block-template')
    </template>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    // Make PHP variables available to JS
    window.INITIAL_ROOM_TYPE = "{{ $selectedRoomType ? $selectedRoomType['id'] : '' }}";
    window.INITIAL_GUESTS = "{{ $guests ?? 1 }}";
    window.ROOM_TYPES_CONFIG = @json($roomTypes);
</script>
<script src="{{ asset('js/booking.js') }}"></script>
@endpush
