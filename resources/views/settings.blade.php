@extends('layouts.settings_layout')

@section('settings-content')
    <x-booking.page-header title="Profile Settings" subtitle="Update your personal details and account password.">
        <x-slot name="actions">
            <div class="flex items-center gap-2 text-sm font-semibold">
                <span class="text-stone-400">Account Status:</span>
                @if(auth()->user()->email_verified_at)
                    <x-booking.badge status="active">Verified</x-booking.badge>
                @else
                    <x-booking.badge status="pending">Not Verified</x-booking.badge>
                @endif
            </div>
        </x-slot>
    </x-booking.page-header>

    <div class="space-y-8">
        <!-- Profile Update Section -->
        <x-booking.card title="Personal Information" icon="manage_accounts">
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-booking.input label="Username" name="username" :value="auth()->user()->username" required />
                    <x-booking.input label="Email Address" name="email" type="email" :value="auth()->user()->email" required />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-booking.input label="Phone Number (Optional)" name="phone" :value="auth()->user()->phone" />
                </div>

                <div class="pt-2 border-t border-stone-100 flex justify-end">
                    <x-booking.button variant="primary">
                        <span class="material-icons text-[18px] mr-1.5">save</span>
                        Save Profile
                    </x-booking.button>
                </div>
            </form>
        </x-booking.card>

        <!-- Password Update Section -->
        <x-booking.card title="Change Password" icon="lock">
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-booking.input label="Current Password" name="current_password" type="password" required />
                    <x-booking.input label="New Password" name="password" type="password" required />
                    <x-booking.input label="Confirm New Password" name="password_confirmation" type="password" required />
                </div>

                <div class="pt-2 border-t border-stone-100 flex justify-end">
                    <x-booking.button variant="primary">
                        <span class="material-icons text-[18px] mr-1.5">lock_reset</span>
                        Update Password
                    </x-booking.button>
                </div>
            </form>
        </x-booking.card>
    </div>
@endsection
