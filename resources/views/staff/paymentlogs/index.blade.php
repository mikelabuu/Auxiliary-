@extends('layouts.admin')
@section('title', 'Admin - Payment Hub')
@section('page-title', 'Payment Hub')
@section('content')
<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.ui.page-header subtitle="Every peso that moved through the system — online, sandbox, and manual payments.">
        Payment <span class="text-clsu-700">Hub</span>
        <x-slot:actions>
            <x-admin.ui.button variant="secondary" :href="route('reports.payments.all')">
                <x-admin.ui.icon name="download" class="w-4 h-4" />
                All
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" :href="route('reports.payments.cash')">
                <x-admin.ui.icon name="download" class="w-4 h-4" />
                Cash
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" :href="route('reports.payments.sandbox')">
                <x-admin.ui.icon name="download" class="w-4 h-4" />
                Sandbox
            </x-admin.ui.button>
        </x-slot:actions>
    </x-admin.ui.page-header>

    @livewire('staff.payment-logs')
</div>
@endsection
