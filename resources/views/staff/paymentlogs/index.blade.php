@extends('layouts.admin')
@section('title', 'Admin - Payment Hub')
@section('page-title', 'Payment Hub')
@section('content')
<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.ui.page-header subtitle="Every peso that moved through the system, including bookings settled at the front desk.">
        Payment Hub
        <x-slot:actions>
            <x-admin.ui.button variant="secondary" :href="route('reports.payments.all')">
                <x-admin.ui.icon name="download" class="w-4 h-4" />
                All
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" :href="route('reports.payments.cash')">
                <x-admin.ui.icon name="download" class="w-4 h-4" />
                Cash
            </x-admin.ui.button>
        </x-slot:actions>
    </x-admin.ui.page-header>

    @livewire('staff.payment-logs')
</div>
@endsection
