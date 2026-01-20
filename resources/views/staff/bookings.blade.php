@extends('layouts.admin')

@section('title', 'Bookings Hub')
@section('page-title', 'Bookings Hub')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>
<div class="p-6">

    {{-- Import Livewire component --}}
    <livewire:dashboard.arrivals-departures />
</div>
<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Active</h1>

    {{-- Import Livewire component --}}
    <livewire:active-bookings />
</div>
<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">All Bookings</h1>
    <div class="flex justify-end mb-4 space-x-2">
        <a href="{{ route('reports.bookings.full') }}" class="btn btn-primary">Export All Bookings</a>
        <a href="{{ route('reports.bookings.paid') }}" class="btn btn-success">Export Paid Bookings</a>
        <a href="{{ route('reports.bookings.completed') }}" class="btn btn-warning">Export Completed Bookings</a>
    </div>
    {{-- Import Livewire component --}}
    <livewire:bookings-table />
</div>
@endsection

