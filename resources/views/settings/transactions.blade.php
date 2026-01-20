@extends('layouts.settings_layout')
@section('title', 'My Payments')
<link rel="stylesheet" href="{{ asset('css/payments.settings.css') }}">
<script src="https://cdn.tailwindcss.com"></script>
@section('page-title', 'My Payments')

@section('settings-content')
<div class="bookings-container">
    <br>
    <h2 class="tab-title">My Payments</h2>

    {{-- Search + Filters --}}
    <form method="GET" action="{{ route('settings.transactions') }}" class="filter-form">
        <input type="text" name="search" placeholder="Search by ID, booking, reference, or gateway..." value="{{ request('search') }}">

        <select name="status">
            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
            <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
        </select>

        <select name="sort_by">
            <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Sort by Date</option>
            <option value="amount" {{ request('sort_by') == 'amount' ? 'selected' : '' }}>Sort by Amount</option>
            <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>Sort by Status</option>
        </select>

        <select name="sort_dir">
            <option value="desc" {{ request('sort_dir') == 'desc' ? 'selected' : '' }}>Descending</option>
            <option value="asc" {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>Ascending</option>
        </select>

        <button type="submit" class="btn btn-view">Apply</button>
        @if(request()->has('search') || request()->has('status'))
            <a href="{{ route('settings.transactions') }}" class="btn btn-cancel">Reset</a>
        @endif
    </form>

    @if($payments->count())
        <table class="bookings-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Booking ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Reference No</th>
                    <th>Gateway</th>
                    <th>Landbank Transaction ID</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>#{{ $payment->id }}</td>
                        <td>#{{ $payment->booking_id }}</td>
                        <td>₱{{ number_format($payment->amount, 2) }}</td>
                        <td>
                            <span class="status-badge status-{{ $payment->status }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                        <td>{{ $payment->reference_no }}</td>
                        <td>{{ $payment->gateway }}</td>
                        <td>
                            @if($payment->gateway === 'sandbox')
                                {{ $payment->landbank_transaction_id }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $payment->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="pagination-wrapper mt-6 flex flex-col items-center space-y-2">
            <div>
                {{ $payments->links('vendor.pagination.simple-tailwind') }}
            </div>

            <div class="text-gray-400 text-sm">
                Showing {{ $payments->firstItem() }} to {{ $payments->lastItem() }} of {{ $payments->total() }} results
            </div>
        </div>
    @else
        <p class="no-bookings">You don't have any payments yet.</p>
    @endif
</div>
@endsection
