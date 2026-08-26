@extends('layouts.admin')
@section('title', 'Admin - Payment Hub')
@section('page-title', 'Payment Hub')
@section('content')
<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.ui.page-header subtitle="Every peso that moved through the system, including bookings settled at the front desk.">
        Payment Hub
    </x-admin.ui.page-header>

    @livewire('staff.payment-logs')
</div>
@endsection
