@extends(auth('staff')->user()?->role === 'frontdesk' ? 'layouts.frontdesk' : 'layouts.admin')

@section('title', 'Discount Verification')
@section('page-title', 'Discount Requests')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.ui.page-header subtitle="Verify original Senior Citizen / PWD IDs in person before approving the 20% discount.">
        Discount Requests
    </x-admin.ui.page-header>

    <div class="flex items-start gap-2.5 rounded-2xl border border-palay-200 bg-palay-50 px-5 py-3 text-sm font-medium leading-relaxed text-palay-800">
        <x-admin.ui.icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
        Online uploads are advance references only. Keep the booking on hold until the guest presents each original ID at the front desk.
    </div>

    @livewire('staff.discounts.discount-list')
</div>
@endsection

@push('scripts')
<script>
// Real-time push: the queue reflects a new or withdrawn request the moment it
// happens, instead of up to 60s later on the wire:poll fallback. A guest who
// has just uploaded their IDs is actively waiting on this review.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Echo) return;
    window.Echo.channel('discounts').listen('.DiscountChanged', function () {
        if (window.Livewire) Livewire.dispatch('refreshDiscountList');
    });
});
</script>
@endpush

{{-- Password re-auth removed — the .review-discount links now navigate directly. --}}
