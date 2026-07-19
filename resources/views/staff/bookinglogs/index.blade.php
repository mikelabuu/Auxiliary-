@extends('layouts.admin')

@section('title', 'Admin - Booking Logs')
@section('page-title', 'Booking Logs')

@section('content')
<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.ui.page-header subtitle="A history of every check-in, check-out, no-show, expiry, and cancellation.">
        Booking Logs
    </x-admin.ui.page-header>

    @livewire('staff.booking-logs')
</div>
@endsection
