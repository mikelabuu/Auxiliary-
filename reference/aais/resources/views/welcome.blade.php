@php
    $title = 'Welcome';
    $topbarSub = 'Legacy welcome view';
@endphp

@extends('layouts.client')

@section('content')
    <div class="card card-body" style="max-width:720px;margin:0 auto;">
        <h1 class="section-title" style="font-size:22px;">Welcome to AAIS</h1>
        <p class="text-muted" style="margin-top:10px;line-height:1.7;">
            This legacy view has been aligned with the current AAIS interface.
            Use the main portal entry points below.
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:18px;">
            <a href="{{ route('aais.home') }}" class="btn btn-primary">Open Overview</a>
            <a href="{{ route('aais.client.kiosk') }}" class="btn btn-outline">Client Kiosk</a>
            <a href="{{ route('aais.admin.dashboard') }}" class="btn btn-outline">Admin Dashboard</a>
        </div>
    </div>
@endsection
