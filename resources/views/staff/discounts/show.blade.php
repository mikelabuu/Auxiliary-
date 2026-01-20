@extends('layouts.admin')
<link rel="stylesheet" href="{{ asset('css/staff-discounts.css') }}">
@section('title', 'Admin - Discount Approval')
@section('page-title', 'Discount Approval')

@section('content')
<div class="discount-review-container">
    <!-- Booking Summary -->
    <div class="card booking-summary">
        <h2 class="section-title">Booking Summary</h2>
        <div class="summary-grid">
            <div><span class="label">Guest:</span> {{ $booking->guest_name }}</div>
            <div><span class="label">Contact:</span> {{ $booking->guest_phone }}</div>
            <div><span class="label">Check-in:</span> {{ $booking->check_in->format('M d, Y') }}</div>
            <div><span class="label">Check-out:</span> {{ $booking->check_out->format('M d, Y') }}</div>
            <div><span class="label">Rooms:</span> {{ is_array($booking->room_numbers) ? implode(', ', $booking->room_numbers) : $booking->room_numbers }}</div>
            <div><span class="label">Total Price:</span> ₱{{ number_format($booking->total_price, 2) }}</div>
            <div><span class="label">Seniors Requested:</span> {{ $booking->num_seniors }}</div>
            <div><span class="label">Booking Status:</span> 
                <span class="status-badge status-{{ $booking->status }}">{{ ucfirst($booking->status) }}</span>
            </div>
        </div>
    </div>
    @php
        $isExpired = $booking->status === 'expired';
    @endphp
    <!-- Discount Files grouped by reservation -->
    <div class="card discount-files">
        <h2 class="section-title">Uploaded Discount Files (Per Room)</h2>

        @forelse($booking->reservations as $reservation)
            <div class="reservation-block">
                <h3 class="reservation-title">
                    Room {{ $reservation->room_number }} 
                    ({{ $reservation->room_type }} — capacity: {{ $reservation->capacity }})
                </h3>

                @php
                    $files = $reservation->discountFiles ?? collect();
                @endphp

                @if($files->count())
                    <div class="files-grid">
                        @foreach($files as $file)
                            <div class="file-card">
                                @php
                                    $fileExists = Storage::exists($file->file_path);
                                @endphp

                                @if($fileExists)
                                    <a href="{{ route('staff.discounts.file.preview', $file->id) }}" target="_blank">
                                        <img src="{{ route('staff.discounts.file.preview', $file->id) }}" 
                                             alt="ID image preview" 
                                             class="file-thumbnail">
                                    </a>
                                @else
                                    <div class="file-thumbnail missing-file">
                                        <span>File Already Reviewed</span>
                                    </div>
                                @endif

                                <div class="file-info">
                                    <span class="file-status status-{{ $file->status }}">
                                        {{ ucfirst($file->status) }}
                                    </span>

                                    @if($file->status === 'pending' && !$isExpired)
                                        <div class="file-actions">
                                            <form method="POST" action="{{ route('staff.discounts.file.approve', [$discount->id, $file->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-approve">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('staff.discounts.file.reject', [$discount->id, $file->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-reject">Reject</button>
                                            </form>
                                        </div>
                                    @else
                                        <div class="review-meta">
                                            Reviewed by {{ $file->reviewer?->name ?? '—' }} 
                                            at {{ $file->reviewed_at?->format('M d, Y H:i') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="no-files">No discount files uploaded for this reservation.</p>
                @endif
            </div>
        @empty
            <p class="no-files">No reservations found for this booking.</p>
        @endforelse
    </div>

    <!-- Final Action Summary -->
    @if($discount->status !== 'approved' && $discount->status !== 'rejected')
        <div class="card final-actions">
            <h2 class="section-title">Finalize Discount Request</h2>

            @php
                $allFiles = $booking->reservations->flatMap->discountFiles;

                $totalFiles   = $allFiles->count();
                $approvedFiles = $allFiles->where('status', 'approved')->count();
                $rejectedFiles = $allFiles->where('status', 'rejected')->count();
                $pendingFiles  = $allFiles->where('status', 'pending')->count();
            @endphp

            @if($totalFiles && $pendingFiles === 0 && !$isExpired)
                <p>
                    Discount files reviewed: 
                    <strong>{{ $approvedFiles }} approved</strong>, 
                    <strong>{{ $rejectedFiles }} rejected</strong>.
                </p>
                <p>
                    The system will calculate the discount based on the approved files only.
                </p>

                <div class="final-buttons">
                    <form method="POST" action="{{ route('staff.discounts.approve', $discount->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-approve">
                            Approve Discount
                        </button>
                    </form>

                    <form method="POST" action="{{ route('staff.discounts.reject', $discount->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-reject">
                            Reject Discount
                        </button>
                    </form>
                </div>
            @elseif($pendingFiles > 0)
                <p class="notice">
                    Please review all uploaded files before finalizing. 
                    Pending files: <strong>{{ $pendingFiles }}</strong>
                </p>
            @else
                <p class="notice">No discount files uploaded for this booking.</p>
            @endif
        </div>
    @endif
</div>
@endsection
