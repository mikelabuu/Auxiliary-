@extends('layouts.settings_layout')

@section('settings-content')
    <h2 class="tab-title">Profile Settings</h2>
    <p>Account Status: 
        @if(auth()->user()->email_verified_at)
            <span class="text-green-600">Verified</span>
        @else
            <span class="text-red-600">Not Verified</span>
        @endif
    </p>

    {{-- Profile Update --}}
    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" value="{{ auth()->user()->username }}" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ auth()->user()->email }}" required>
        </div>

        <div class="form-group">
            <label>Phone (optional)</label>
            <input type="text" name="phone" value="{{ auth()->user()->phone }}">
        </div>

        <button type="submit" class="btn-save">Save Profile</button>
    </form>

    <hr>

    {{-- Password Update --}}
    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm New Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>

        <button type="submit" class="btn-save">Update Password</button>
    </form>
@endsection
