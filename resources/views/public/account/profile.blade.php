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
                {{-- Both cards on this page post to settings.update. Say which
                     one, so the controller does not have to infer it from
                     whether a password field happened to be filled in. --}}
                <input type="hidden" name="_form" value="profile">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-booking.ui.input label="Username" name="username" :value="auth()->user()->username" required />
                    <x-booking.ui.input label="Email Address" name="email" type="email" :value="auth()->user()->email" required />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-booking.ui.input label="Phone Number (Optional)" name="phone" :value="auth()->user()->phone" />
                    <div>
                        {{-- Not `required`: the server asks for this only when
                             the email address actually changes, so a username
                             or phone edit stays a single-field change. --}}
                        <x-booking.ui.input label="Current Password" name="current_password" type="password" autocomplete="current-password" />
                        <p class="-mt-2.5 mb-4 text-xs text-stone-500">Required only when you change your email address.</p>
                    </div>
                </div>

                <div class="pt-2 border-t border-stone-100 flex justify-end">
                    <x-booking.ui.button variant="primary">
                        <x-booking.ui.icon-solid name="floppy-disk" class="text-[18px] mr-1.5" />
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
                <input type="hidden" name="_form" value="password">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-booking.ui.input label="Current Password" name="current_password" type="password" required />
                    {{-- 8/72 mirrors SettingsController::update, which is the same
                         floor signup uses. Without it the browser accepts a short
                         password and only the server says no. --}}
                    <x-booking.ui.input label="New Password" name="password" type="password" required minlength="8" maxlength="72" />
                    <x-booking.ui.input label="Confirm New Password" name="password_confirmation" type="password" required minlength="8" maxlength="72" />
                </div>

                <div class="pt-2 border-t border-stone-100 flex justify-end">
                    <x-booking.ui.button variant="primary">
                        <x-booking.ui.icon-solid name="key" class="text-[18px] mr-1.5" />
                        Update Password
                    </x-booking.ui.button>
                </div>
            </form>
        </x-booking.ui.card>
    </div>
@endsection
