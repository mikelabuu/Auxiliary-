@extends('layouts.public.account')

@section('settings-content')
    <x-booking.ui.page-header title="Profile Settings" subtitle="Update your personal details and account password.">
        <x-slot name="actions">
            <div class="flex items-center gap-2 text-sm font-semibold">
                <span class="text-stone-400">Account Status:</span>
                @if(auth()->user()->email_verified_at)
                    <x-booking.ui.badge status="active">Verified</x-booking.ui.badge>
                @else
                    <x-booking.ui.badge status="pending">Not Verified</x-booking.ui.badge>
                @endif
            </div>
        </x-slot>
    </x-booking.ui.page-header>

    <div class="space-y-8">
        <!-- Profile Update Section -->
        <x-booking.ui.card title="Personal Information" icon="user-gear">
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-4" data-busy-form>
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-booking.ui.input label="Username" name="username" :value="auth()->user()->username" required />
                    <x-booking.ui.input label="Email Address" name="email" type="email" :value="auth()->user()->email" required />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-booking.ui.input label="Phone Number (Optional)" name="phone" :value="auth()->user()->phone" />
                </div>

                <div class="pt-2 border-t border-stone-100 flex justify-end">
                    <x-booking.ui.button variant="primary">
                        <i class="fa-solid fa-floppy-disk text-[18px] mr-1.5"></i>
                        Save Profile
                    </x-booking.ui.button>
                </div>
            </form>
        </x-booking.ui.card>

        <!-- Password Update Section -->
        <x-booking.ui.card title="Change Password" icon="lock">
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-4" data-busy-form>
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-booking.ui.input label="Current Password" name="current_password" type="password" required />
                    <x-booking.ui.input label="New Password" name="password" type="password" required />
                    <x-booking.ui.input label="Confirm New Password" name="password_confirmation" type="password" required />
                </div>

                <div class="pt-2 border-t border-stone-100 flex justify-end">
                    <x-booking.ui.button variant="primary">
                        <i class="fa-solid fa-key text-[18px] mr-1.5"></i>
                        Update Password
                    </x-booking.ui.button>
                </div>
            </form>
        </x-booking.ui.card>
    </div>
@endsection
