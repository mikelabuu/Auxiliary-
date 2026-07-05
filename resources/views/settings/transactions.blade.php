@extends('layouts.settings_layout')
@section('title', 'My Payments')
@section('page-title', 'My Payments')

@section('settings-content')
    <x-booking.page-header title="My Payments" subtitle="View details of payment transactions processed for your bookings."></x-booking.page-header>

    {{-- Search + Filters Form Card --}}
    <form method="GET" action="{{ route('settings.transactions') }}" class="bg-stone-50/60 border border-stone-200/70 p-5 rounded-2xl mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5">Search</label>
                <input type="text" name="search" placeholder="Search by ID, booking, reference, gateway..." value="{{ request('search') }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-all font-semibold">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-all cursor-pointer font-semibold">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5">Sort by</label>
                <select name="sort_by" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-all cursor-pointer font-semibold">
                    <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Transaction Date</option>
                    <option value="amount" {{ request('sort_by') == 'amount' ? 'selected' : '' }}>Payment Amount</option>
                    <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>Payment Status</option>
                </select>
            </div>

            <div class="flex gap-2">
                <select name="sort_dir" class="flex-grow px-3 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-all cursor-pointer font-semibold">
                    <option value="desc" {{ request('sort_dir') == 'desc' ? 'selected' : '' }}>Descending</option>
                    <option value="asc" {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>Ascending</option>
                </select>

                <x-booking.button variant="primary" class="py-2.5 px-4 flex-shrink-0">Apply</x-booking.button>
                @if(request()->has('search') || request()->has('status'))
                    <x-booking.button variant="neutral" href="{{ route('settings.transactions') }}" class="py-2.5 px-4 flex-shrink-0">Reset</x-booking.button>
                @endif
            </div>
        </div>
    </form>

    {{-- Transactions Log list --}}
    @if($payments->count())
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto rounded-2xl border border-stone-200/70 shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-stone-50 text-stone-500 text-[11px] font-bold uppercase tracking-wide border-b border-stone-100">
                        <th class="p-4">ID</th>
                        <th class="p-4">Booking ID</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Reference No</th>
                        <th class="p-4">Gateway</th>
                        <th class="p-4">Landbank Ref ID</th>
                        <th class="p-4">Date processed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 text-sm font-semibold text-stone-700">
                    @foreach($payments as $payment)
                        <tr class="hover:bg-clsu-50/40 transition-colors">
                            <td class="p-4 font-bold text-ink">#{{ $payment->id }}</td>
                            <td class="p-4">
                                <a href="{{ route('booking.show', $payment->booking_id) }}" class="text-clsu-700 hover:text-clsu-900 hover:underline font-bold">#{{ $payment->booking_id }}</a>
                            </td>
                            <td class="p-4 font-extrabold text-ink">₱{{ number_format($payment->amount, 2) }}</td>
                            <td class="p-4"><x-booking.badge :status="$payment->status" /></td>
                            <td class="p-4 text-stone-500 font-mono text-xs">{{ $payment->reference_no }}</td>
                            <td class="p-4 uppercase text-xs">{{ $payment->gateway }}</td>
                            <td class="p-4 text-stone-500 font-mono text-xs">
                                @if($payment->gateway === 'sandbox')
                                    {{ $payment->landbank_transaction_id }}
                                @else
                                    <span class="text-stone-400">N/A</span>
                                @endif
                            </td>
                            <td class="p-4 text-stone-400 text-xs font-medium">{{ $payment->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile list -->
        <div class="grid grid-cols-1 gap-4 md:hidden">
            @foreach($payments as $payment)
                <div class="border border-stone-200/70 rounded-2xl p-5 bg-white space-y-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-black text-ink">Payment #{{ $payment->id }}</span>
                        <x-booking.badge :status="$payment->status" />
                    </div>

                    <div class="grid grid-cols-2 gap-y-2.5 gap-x-2 text-xs font-semibold text-stone-600 border-t border-stone-100 pt-3">
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider">Booking</span>
                            <a href="{{ route('booking.show', $payment->booking_id) }}" class="text-clsu-700 hover:underline font-bold">#{{ $payment->booking_id }}</a>
                        </div>
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider">Amount Paid</span>
                            <span class="text-ink font-extrabold">₱{{ number_format($payment->amount, 2) }}</span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider">Gateway</span>
                            <span class="text-stone-800 uppercase text-xs">{{ $payment->gateway }}</span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider">Date</span>
                            <span class="text-stone-800 text-[10px]">{{ $payment->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="col-span-2 border-t border-stone-100 pt-2.5">
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider">Reference No</span>
                            <span class="text-stone-800 font-mono text-[10px] block truncate">{{ $payment->reference_no }}</span>
                        </div>
                        @if($payment->gateway === 'sandbox' && $payment->landbank_transaction_id)
                            <div class="col-span-2">
                                <span class="block text-[9px] text-stone-400 uppercase tracking-wider">Landbank Ref ID</span>
                                <span class="text-stone-800 font-mono text-[10px] block truncate">{{ $payment->landbank_transaction_id }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pagination-wrapper mt-8 flex flex-col items-center space-y-2">
            <div>{{ $payments->links('vendor.pagination.simple-tailwind') }}</div>
            <div class="text-stone-400 font-bold text-xs">
                Showing {{ $payments->firstItem() }} to {{ $payments->lastItem() }} of {{ $payments->total() }} results
            </div>
        </div>
    @else
        <x-booking.empty-state
            title="No Payments Logged"
            description="You don't have any transaction history yet. Your payment statements will appear here after booking checkout."
            icon="payments"
        />
    @endif
@endsection
