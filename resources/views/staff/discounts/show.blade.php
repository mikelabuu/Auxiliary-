@extends('layouts.admin')

@section('title', 'Admin - Discount Approval')
@section('page-title', 'Discount Approval')

@section('content')
@php
    $isExpired = $booking->status === 'expired';

    $bookingStatusMap = [
        'paid' => 'bg-clsu-50 text-clsu-700 border-clsu-200', 'active' => 'bg-clsu-50 text-clsu-700 border-clsu-200', 'completed' => 'bg-clsu-50 text-clsu-700 border-clsu-200',
        'pending_payment' => 'bg-palay-100 text-palay-800 border-palay-200', 'pending_discount' => 'bg-palay-100 text-palay-800 border-palay-200',
        'cancelled' => 'bg-ember-50 text-ember-700 border-ember-200', 'expired' => 'bg-ember-50 text-ember-700 border-ember-200', 'no_show' => 'bg-ember-50 text-ember-700 border-ember-200',
    ];
    $bookingBadge = $bookingStatusMap[$booking->status] ?? 'bg-stone-100 text-stone-600 border-stone-200';

    $fileStatusMeta = [
        'pending'  => ['badge' => 'bg-palay-100 text-palay-800 border-palay-200', 'dot' => 'bg-palay-500'],
        'approved' => ['badge' => 'bg-clsu-50 text-clsu-700 border-clsu-200',     'dot' => 'bg-clsu-500'],
        'rejected' => ['badge' => 'bg-ember-50 text-ember-700 border-ember-200',  'dot' => 'bg-ember-500'],
    ];

    $allFiles      = $booking->reservations->flatMap->discountFiles;
    $totalFiles    = $allFiles->count();
    $approvedFiles = $allFiles->where('status', 'approved')->count();
    $rejectedFiles = $allFiles->where('status', 'rejected')->count();
    $pendingFiles  = $allFiles->where('status', 'pending')->count();
    $isFinalized   = in_array($discount->status, ['approved', 'rejected']);
@endphp

<div class="space-y-6 max-w-[1680px] mx-auto">

    <x-admin.ui.page-header :subtitle="'Submitted ' . $discount->created_at->format('M d, Y · h:i A') . ' · ' . $totalFiles . ' document' . ($totalFiles === 1 ? '' : 's')">
        <x-slot:breadcrumb>
            <x-admin.ui.breadcrumb :items="[
                ['label' => 'Discount Requests', 'href' => route('staff.discounts.index')],
                ['label' => 'Request #' . $discount->id],
            ]" />
        </x-slot:breadcrumb>
        Discount Request <span class="text-clsu-700">#{{ $discount->id }}</span>
        <x-slot:actions>
            <x-admin.ui.button variant="secondary" :href="route('staff.discounts.index')">
                <x-admin.ui.icon name="chevron-left" class="w-4 h-4" stroke-width="2" />
                Back to Requests
            </x-admin.ui.button>
        </x-slot:actions>
    </x-admin.ui.page-header>

    {{-- Session success/error toasts fire from layouts/admin --}}

    @if($isExpired)
        <div class="animate-in flex items-center gap-2.5 rounded-2xl border border-ember-200 bg-ember-50 px-5 py-3 text-sm font-medium text-ember-700">
            <x-admin.ui.icon name="clock" class="w-4 h-4 shrink-0" />
            This booking has expired. Documents are shown for reference only and can no longer be actioned.
        </div>
    @endif

    <!-- Booking summary -->
    <x-admin.ui.section-card icon="user" title="Booking Summary" :subtitle="'Booking #' . $booking->id" :delay="40">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-4">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-stone-100 text-muted flex items-center justify-center shrink-0"><x-admin.ui.icon name="user" class="w-4 h-4" /></div>
                <div>
                    <p class="text-2xs font-bold text-faint tracking-widest uppercase">Guest</p>
                    <p class="text-sm font-semibold text-stone-800">{{ $booking->guest_name }}</p>
                    <p class="text-xs text-faint mt-0.5">{{ $booking->guest_phone }}</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-stone-100 text-muted flex items-center justify-center shrink-0"><x-admin.ui.icon name="calendar" class="w-4 h-4" /></div>
                <div>
                    <p class="text-2xs font-bold text-faint tracking-widest uppercase">Stay</p>
                    <p class="text-sm font-semibold text-stone-800 font-data tabnum">{{ $booking->check_in->format('M d') }} &rarr; {{ $booking->check_out->format('M d, Y') }}</p>
                    <p class="text-xs text-faint mt-0.5">Rooms {{ is_array($booking->room_numbers) ? implode(', ', $booking->room_numbers) : $booking->room_numbers }}</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-stone-100 text-muted flex items-center justify-center shrink-0"><x-admin.ui.icon name="receipt" class="w-4 h-4" /></div>
                <div>
                    <p class="text-2xs font-bold text-faint tracking-widest uppercase">Total Price</p>
                    <p class="text-sm font-semibold text-stone-800 font-data tabnum">₱{{ number_format($booking->total_price, 2) }}</p>
                    <p class="text-xs text-faint mt-0.5">{{ $booking->num_seniors }} senior{{ $booking->num_seniors === 1 ? '' : 's' }} / PWD declared</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-stone-100 text-muted flex items-center justify-center shrink-0"><x-admin.ui.icon name="tag" class="w-4 h-4" /></div>
                <div>
                    <p class="text-2xs font-bold text-faint tracking-widest uppercase">Booking Status</p>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-2xs font-bold border mt-1 {{ $bookingBadge }}">{{ ucwords(str_replace('_', ' ', $booking->status)) }}</span>
                </div>
            </div>
        </div>
    </x-admin.ui.section-card>

    <!-- Review progress -->
    <div class="animate-in grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6" style="animation-delay:80ms">
        <x-admin.ui.mini-stat icon="check-circle" label="Approved documents">{{ $approvedFiles }}</x-admin.ui.mini-stat>
        <x-admin.ui.mini-stat icon="block" color="ember" label="Rejected documents">{{ $rejectedFiles }}</x-admin.ui.mini-stat>
        <x-admin.ui.mini-stat icon="clock" color="palay" label="Awaiting review">{{ $pendingFiles }}</x-admin.ui.mini-stat>
    </div>

    <!-- Documents per room -->
    <x-admin.ui.section-card icon="clipboard" title="Verification Documents" subtitle="Approve or reject each uploaded ID. The discount is computed from approved IDs only" :delay="120">
        <div class="space-y-7">
            @forelse($booking->reservations as $reservation)
                @php $files = $reservation->discountFiles ?? collect(); @endphp
                <div>
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <span class="grid h-9 w-12 shrink-0 place-items-center rounded-lg bg-clsu-700 font-data text-xs font-bold text-white">{{ $reservation->room_number }}</span>
                        <div>
                            <p class="text-sm font-bold text-stone-800 capitalize">{{ $reservation->room_type }} Room</p>
                            <p class="text-2xs font-semibold text-faint">{{ $reservation->num_seniors }} declared senior{{ $reservation->num_seniors === 1 ? '' : 's' }} / PWD · capacity {{ $reservation->capacity }}</p>
                        </div>
                    </div>

                    @if($files->count())
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            @foreach($files as $file)
                                @php
                                    $fileExists = Storage::exists($file->file_path);
                                    $meta = $fileStatusMeta[$file->status] ?? ['badge' => 'bg-stone-100 text-stone-600 border-stone-200', 'dot' => 'bg-stone-400'];
                                @endphp
                                <div class="rounded-2xl border border-stone-200 bg-white shadow-subtle overflow-hidden">
                                    @if($fileExists)
                                        <a href="{{ route('staff.discounts.file.preview', $file->id) }}" target="_blank" class="group relative block h-40 overflow-hidden bg-stone-100">
                                            <img src="{{ route('staff.discounts.file.preview', $file->id) }}" alt="ID document preview" loading="lazy"
                                                 class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                                            <span class="absolute inset-0 flex items-center justify-center bg-clsu-950/0 opacity-0 transition-[opacity,background-color] duration-200 group-hover:bg-clsu-950/40 group-hover:opacity-100">
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3.5 py-1.5 text-2xs font-bold text-clsu-800">
                                                    <x-admin.ui.icon name="eye" class="w-3.5 h-3.5" /> Open full size
                                                </span>
                                            </span>
                                        </a>
                                    @else
                                        <div class="flex h-40 flex-col items-center justify-center gap-1.5 bg-stone-50 text-faint">
                                            <x-admin.ui.icon name="clipboard" class="w-6 h-6" />
                                            <span class="text-2xs font-semibold">File already reviewed &amp; archived</span>
                                        </div>
                                    @endif

                                    <div class="p-3.5 space-y-3">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-2xs font-bold border {{ $meta['badge'] }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                                                {{ ucfirst($file->status) }}
                                            </span>
                                            <span class="text-2xs text-faint font-data">#{{ $file->id }}</span>
                                        </div>

                                        @if($file->status === 'pending' && !$isExpired)
                                            <div class="grid grid-cols-2 gap-2">
                                                {{-- Per-ID review is a judgement call the reviewer is
                                                     already making with the document open, so these two
                                                     stay un-confirmed — but they still get the in-flight
                                                     state and the double-submit guard. --}}
                                                <form method="POST" action="{{ route('staff.discounts.file.approve', [$discount->id, $file->id]) }}" data-busy-form>
                                                    @csrf
                                                    <button type="submit" data-busy-btn class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-clsu-700 px-3 py-2 text-2xs font-bold text-white shadow-card transition-[color,background-color,transform] duration-200 ease-out hover:bg-clsu-800 active:scale-[0.98] cursor-pointer">
                                                        <x-admin.ui.icon name="check" class="w-3.5 h-3.5" stroke-width="2.5" /> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('staff.discounts.file.reject', [$discount->id, $file->id]) }}" data-busy-form>
                                                    @csrf
                                                    <button type="submit" data-busy-btn class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-ember-200 bg-ember-50 px-3 py-2 text-2xs font-bold text-ember-700 transition-colors hover:bg-ember-100 active:scale-[0.98] cursor-pointer">
                                                        <x-admin.ui.icon name="x" class="w-3.5 h-3.5" stroke-width="2.5" /> Reject
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <p class="text-2xs text-faint leading-relaxed">
                                                Reviewed by <span class="font-semibold text-muted">{{ $file->reviewer?->name ?? '—' }}</span>
                                                @if($file->reviewed_at) · {{ $file->reviewed_at->format('M d, Y · h:i A') }} @endif
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="rounded-xl border border-dashed border-stone-200 bg-stone-50/60 px-4 py-4 text-xs font-medium text-faint">No documents uploaded for this room.</p>
                    @endif
                </div>
            @empty
                <x-admin.ui.empty-state icon="clipboard" title="No reservations found for this booking." />
            @endforelse
        </div>
    </x-admin.ui.section-card>

    <!-- Finalize -->
    @if($isFinalized)
        <x-admin.ui.section-card icon="check-circle" title="Request Finalized" :delay="160">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border {{ $fileStatusMeta[$discount->status]['badge'] ?? 'bg-stone-100 text-stone-600 border-stone-200' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $fileStatusMeta[$discount->status]['dot'] ?? 'bg-stone-400' }}"></span>
                        Discount {{ ucfirst($discount->status) }}
                    </span>
                    <p class="text-xs text-faint">{{ $approvedFiles }} approved · {{ $rejectedFiles }} rejected</p>
                </div>
                @if($discount->status === 'approved')
                    <p class="text-sm text-muted">Discount applied: <span class="font-bold text-clsu-800 font-data tabnum">₱{{ number_format($booking->discount, 2) }}</span> · payable now <span class="font-bold text-clsu-800 font-data tabnum">₱{{ number_format($booking->payable_amount, 2) }}</span></p>
                @endif
            </div>
        </x-admin.ui.section-card>
    @else
        <x-admin.ui.section-card icon="tag" title="Finalize Discount Request" subtitle="The 20% discount is computed from approved documents only" :delay="160">
            @if($totalFiles && $pendingFiles === 0 && !$isExpired)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <p class="text-sm text-muted leading-relaxed">
                        All documents reviewed: <strong class="text-clsu-700">{{ $approvedFiles }} approved</strong>, <strong class="text-ember-600">{{ $rejectedFiles }} rejected</strong>.
                        Finalizing will recalculate the guest's bill.
                    </p>
                    <div class="flex gap-2.5 shrink-0">
                        <form method="POST" action="{{ route('staff.discounts.reject', $discount->id) }}"
                              data-busy-form
                              data-confirm-title="Reject this discount request?"
                              data-confirm="The guest keeps the original price."
                              data-confirm-action="Yes, reject"
                              data-confirm-tone="danger">
                            @csrf
                            <button type="submit" data-busy-btn class="inline-flex items-center gap-1.5 rounded-xl border border-ember-200 bg-ember-50 px-5 py-2.5 text-sm font-bold text-ember-700 transition-colors hover:bg-ember-100 active:scale-[0.98] cursor-pointer">
                                <x-admin.ui.icon name="x" class="w-4 h-4" stroke-width="2.5" /> Reject Request
                            </button>
                        </form>
                        <form method="POST" action="{{ route('staff.discounts.approve', $discount->id) }}"
                              data-busy-form
                              data-confirm-title="Approve this discount?"
                              data-confirm="The 20% per approved ID will be applied to the booking total."
                              data-confirm-action="Yes, approve">
                            @csrf
                            <button type="submit" data-busy-btn class="inline-flex items-center gap-1.5 rounded-xl bg-clsu-700 px-5 py-2.5 text-sm font-bold text-white shadow-card transition-[color,background-color,transform] duration-200 ease-out hover:bg-clsu-800 active:scale-[0.98] cursor-pointer">
                                <x-admin.ui.icon name="check-circle" class="w-4 h-4" /> Approve Discount
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($pendingFiles > 0)
                <div class="flex items-center gap-2.5 rounded-xl border border-palay-200 bg-palay-50 px-4 py-3 text-sm font-medium text-palay-800">
                    <x-admin.ui.icon name="clock" class="w-4 h-4 shrink-0" />
                    Review the {{ $pendingFiles }} remaining document{{ $pendingFiles === 1 ? '' : 's' }} above before finalizing this request.
                </div>
            @else
                <p class="text-sm text-faint">No discount documents were uploaded for this booking.</p>
            @endif
        </x-admin.ui.section-card>
    @endif
</div>
@endsection

{{-- The confirm dialog for the two finalize actions above now comes from the
     shared [data-confirm] contract in resources/js/staff-actions.js. The copy
     that used to live here bound a static NodeList (so it missed anything
     Livewire re-rendered) and finished with form.submit(), which fires no
     submit event and therefore never armed the double-submit guard. --}}
