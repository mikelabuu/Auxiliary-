@extends('layouts.admin')
@section('title', 'Admin - Balance')
@section('page-title', 'Balance')

@section('content')
<div class="container">
    <h2 class="mb-4">Booking Balances</h2>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Booking ID</th>
                        <th>User</th>
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($balances as $balance)
                        <tr>
                            <td>{{ $balance->id }}</td>
                            <td>#{{ $balance->booking_id }}</td>
                            <td>
                                {{ $balance->booking->user->username ?? '—' }}<br>
                                <small class="text-muted">
                                    {{ $balance->booking->user->email ?? '' }}
                                </small>
                            </td>
                            <td>₱{{ number_format($balance->total_amount, 2) }}</td>
                            <td class="text-success">
                                ₱{{ number_format($balance->paid_amount, 2) }}
                            </td>
                            <td class="text-danger">
                                ₱{{ number_format($balance->remaining_balance, 2) }}
                            </td>
                            <td>
                                <span class="badge
                                    @if($balance->status === 'fully_paid') bg-success
                                    @elseif($balance->status === 'partially_paid') bg-warning
                                    @else bg-secondary
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $balance->status)) }}
                                </span>
                            </td>
                            <td>{{ $balance->updated_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                No balance records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $balances->links() }}
            </div>
        </div>
    </div>
</div>
@endsection