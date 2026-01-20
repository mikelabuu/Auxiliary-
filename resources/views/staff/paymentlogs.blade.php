@extends('layouts.admin')
@section('title', 'Admin - Payment Hub')
@section('page-title', 'Payment Hub')
@section('content')
<div class="p-6 space-y-6">
    <h1 class="text-2xl font-bold mb-4">Payment Logs</h1>

    <div class="flex justify-end mb-4 space-x-2">
        <a href="{{ route('reports.payments.all') }}" class="btn btn-primary">Export All Payments</a>
        <a href="{{ route('reports.payments.cash') }}" class="btn btn-success">Export Cash Payments</a>
        <a href="{{ route('reports.payments.sandbox') }}" class="btn btn-warning">Export Sandbox Payments</a>
    </div>

    {{-- Search + Sort --}}
    <form method="GET" action="{{ route('staff.paymentlogs.index') }}" class="flex flex-wrap items-center gap-3 mb-4">
        <input type="text" name="search" value="{{ $search }}" placeholder="ID, Booking ID, or Ref No"
               class="border rounded-lg px-3 py-2 w-64">

        <select name="sort" class="border rounded-lg px-3 py-2">
            <option value="latest" {{ $sort == 'latest' ? 'selected' : '' }}>Newest First</option>
            <option value="oldest" {{ $sort == 'oldest' ? 'selected' : '' }}>Oldest First</option>
        </select>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Apply</button>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Booking ID</th>
                    <th class="px-4 py-2">Amount</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Method</th>
                    <th class="px-4 py-2">Reference No</th>
                    <th class="px-4 py-2">Landbank Transaction ID</th>
                    <th class="px-4 py-2">Date Paid</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">#{{ $payment->id }}</td>
                        <td class="px-4 py-2">#{{ $payment->booking_id }}</td>
                        <td class="px-4 py-2">₱{{ number_format($payment->amount, 2) }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-white 
                                {{ $payment->status === 'success' ? 'bg-green-600' : 'bg-red-600' }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ $payment->gateway }}</td>
                        <td class="px-4 py-2">{{ $payment->reference_no ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $payment->landbank_transaction_id ?? '—' }}</td>
                        <td class="px-4 py-2">
                            {{ \Carbon\Carbon::parse($payment->created_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                            No payment logs found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $payments->links() }}
    </div>
</div>
@endsection
