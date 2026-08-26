@extends('layouts.admin')

@section('title', 'Admin - Discount Requests')
@section('page-title', 'Discount Requests')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.ui.page-header subtitle="Review Senior Citizen / PWD verification documents and approve the 20% discount.">
        Discount Requests
    </x-admin.ui.page-header>

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
