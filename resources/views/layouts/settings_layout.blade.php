@extends('layouts.booking_layout')
@section('title', 'User Center')
<link rel="stylesheet" href="{{ asset('css/settings.css') }}">
@section('page-title', 'User Center')

@section('content')
<div class="user-center-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="sidebar-title">User Center</h2>
        <ul class="sidebar-menu">
            <li class="{{ request()->routeIs('settings.profile') ? 'active' : '' }}">
                <a href="{{ route('settings.profile') }}">Profile</a>
            </li>
            <li class="{{ request()->routeIs('settings.bookings') ? 'active' : '' }}">
                <a href="{{ route('settings.bookings') }}">Bookings</a>
            </li>
            <li class="{{ request()->routeIs('settings.transactions') ? 'active' : '' }}">
                <a href="{{ route('settings.transactions') }}">Transactions</a>
            </li>
        </ul>
    </div>

    <!-- Main content -->
    <div class="main-content">
        @yield('settings-content')
    </div>
</div>
@endsection
