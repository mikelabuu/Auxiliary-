@extends('layouts.admin')
@section('title', 'Admin - Audit Logs')
@section('page-title', 'Audit Logs')

@section('content')
<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.ui.page-header subtitle="View a chronological trail of all system actions and changes.">
        Audit <span class="text-clsu-700">Logs</span>
    </x-admin.ui.page-header>

    @livewire('staff.audit-logs')
</div>
@endsection
