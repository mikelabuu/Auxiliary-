@extends('layouts.admin')
@section('title', 'Admin - Staff Center')
@section('page-title', 'Staff Center')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
/* SweetAlert2 override for this page */
.swal2-container {
    position: fixed !important;
    top: 0;
    left: 0;
    height: 100vh !important;
    width: 100vw !important;
    z-index: 999999 !important; /* ensure it's above everything */
}

.swal2-popup {
    max-width: 450px !important;
}
</style>
@php
    $isMaster = Auth::guard('staff')->user()->role === 'master_admin';
    $roleMeta = [
        'master_admin' => ['badge' => 'bg-stone-900 text-white border-stone-900',        'label' => 'Master Admin'],
        'admin'        => ['badge' => 'bg-clsu-50 text-clsu-700 border-clsu-200',        'label' => 'Admin'],
        'frontdesk'    => ['badge' => 'bg-palay-100 text-palay-800 border-palay-200',    'label' => 'Front Desk'],
        'housekeeping' => ['badge' => 'bg-sky-50 text-sky-700 border-sky-200',           'label' => 'Housekeeping'],
    ];
    $inputClasses = 'w-full text-sm bg-white border border-stone-200 rounded-xl px-4 py-2.5 text-stone-700 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors';
    $labelClasses = 'block text-[10px] font-bold uppercase tracking-widest text-stone-500 mb-1.5';
@endphp

<div class="space-y-6 max-w-[1680px] mx-auto">

    <x-admin.page-header subtitle="Administrative and staff accounts, their roles, and access standing.">
        Staff <span class="font-display italic font-medium text-clsu-800">Center</span>
    </x-admin.page-header>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="animate-in flex items-center gap-2.5 rounded-2xl border border-clsu-200 bg-clsu-50 px-5 py-3 text-sm font-medium text-clsu-800">
            <x-admin.icon name="check-circle" class="w-4 h-4 shrink-0" />
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="animate-in rounded-2xl border border-ember-200 bg-ember-50 px-5 py-3.5 text-sm text-ember-700">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li class="flex items-start gap-1.5"><x-admin.icon name="block" class="w-3.5 h-3.5 shrink-0 mt-0.5" /> {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Team stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <x-admin.stat-card icon="users" badge="WHOLE TEAM" label="Staff Accounts" :delay="40" dark>
            {{ number_format($stats['total']) }}
            <x-slot:footnote><p class="text-xs text-clsu-300">Across all roles</p></x-slot:footnote>
        </x-admin.stat-card>

        <x-admin.stat-card icon="check-circle" badge="ENABLED" label="Active Accounts" :delay="80">
            {{ number_format($stats['active']) }}
            <x-slot:footnote><p class="text-xs text-stone-400">Able to sign in</p></x-slot:footnote>
        </x-admin.stat-card>

        <x-admin.stat-card icon="settings" color="palay" badge="ELEVATED" label="Admin Accounts" :delay="120">
            {{ number_format($stats['admins']) }}
            <x-slot:footnote><p class="text-xs text-stone-400">Master admin and admins</p></x-slot:footnote>
        </x-admin.stat-card>

        <x-admin.stat-card icon="block" color="ember" badge="RESTRICTED" label="Suspended" :delay="160">
            {{ number_format($stats['suspended']) }}
            <x-slot:footnote><p class="text-xs text-stone-400">Sign-in disabled</p></x-slot:footnote>
        </x-admin.stat-card>
    </div>

    <x-admin.section-card icon="users" title="Staff Accounts" :subtitle="$staffs->total() . ' record' . ($staffs->total() === 1 ? '' : 's') . ($search ? ' matching “' . $search . '”' : '')" :delay="200">

        <!-- Search + sort -->
        <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
            <div class="relative flex-1 max-w-xs">
                <x-admin.icon name="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400" stroke-width="2" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Name or email…" class="w-full text-sm bg-stone-50 border border-stone-200 rounded-full pl-10 pr-4 py-2.5 text-stone-700 placeholder:text-stone-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors">
            </div>
            <select name="sort" class="w-full sm:w-44 px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-700 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 cursor-pointer transition-colors">
                <option value="latest" @selected($sort === 'latest')>Newest first</option>
                <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
            </select>
            <button type="submit" class="text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 transition-colors cursor-pointer">Apply</button>
            @if($search || $sort !== 'latest')
                <a href="{{ route('staff.staffrecords.index') }}" class="self-center text-xs font-semibold text-stone-500 hover:text-clsu-700 px-2 transition-colors !no-underline">Clear</a>
            @endif
        </form>

        @if($staffs->isEmpty())
            <x-admin.empty-state icon="users" title="No staff records match this view." />
        @else
            <div class="-mx-6 -mb-6 border-t border-stone-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-stone-50/70 border-b border-stone-100">
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Staff Member</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Role</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Standing</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Last Login</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Created</th>
                            @if ($isMaster)
                                <th class="text-right font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($staffs as $staff)
                            @php $role = $roleMeta[$staff->role] ?? ['badge' => 'bg-stone-100 text-stone-600 border-stone-200', 'label' => ucfirst($staff->role)]; @endphp
                            <tr class="border-b border-stone-100 hover:bg-clsu-50/40 transition-colors">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full shrink-0 flex items-center justify-center text-xs font-bold {{ $staff->is_suspended ? 'bg-ember-100 text-ember-700' : ($staff->role === 'master_admin' ? 'bg-stone-900 text-white' : 'bg-clsu-100 text-clsu-700') }}">
                                            {{ strtoupper(mb_substr($staff->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-stone-800 truncate">{{ $staff->name }}</p>
                                            <p class="text-xs text-stone-400 truncate">{{ $staff->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $role['badge'] }}">{{ $role['label'] }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    @if($staff->is_suspended)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-ember-50 text-ember-700 border-ember-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-ember-500"></span>
                                            Suspended
                                        </span>
                                    @elseif($staff->role === 'master_admin')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-purple-50 text-purple-700 border-purple-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                            Master Account
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-clsu-50 text-clsu-700 border-clsu-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-clsu-500"></span>
                                            Active
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-stone-500 font-data tabnum text-xs whitespace-nowrap">
                                    {{ $staff->last_login_at ? \Carbon\Carbon::parse($staff->last_login_at)->timezone('Asia/Manila')->format('M d, Y · h:i A') : '—' }}
                                </td>
                                <td class="px-6 py-3 text-stone-500 font-data tabnum text-xs whitespace-nowrap">{{ $staff->created_at->timezone('Asia/Manila')->format('M d, Y') }}</td>
                                @if ($isMaster)
                                    <td class="px-6 py-3 text-right">
                                        @if ($staff->role !== 'master_admin')
                                            @if(!$staff->is_suspended)
                                                <button class="password-verify-btn text-xs font-semibold text-ember-700 border border-ember-200 bg-white rounded-lg px-3 py-1.5 hover:bg-ember-50 hover:border-ember-300 transition-colors cursor-pointer"
                                                        data-action="suspend"
                                                        data-staff-id="{{ $staff->id }}">
                                                    Suspend
                                                </button>
                                            @else
                                                <button class="password-verify-btn text-xs font-semibold text-clsu-700 border border-clsu-200 bg-white rounded-lg px-3 py-1.5 hover:bg-clsu-50 hover:border-clsu-300 transition-colors cursor-pointer"
                                                        data-action="unsuspend"
                                                        data-staff-id="{{ $staff->id }}">
                                                    Unsuspend
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $staffs->links() }}
            </div>
        @endif
    </x-admin.section-card>

    @if ($isMaster)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 items-start">
            <x-admin.section-card icon="plus" title="Create Staff Account" subtitle="Provision a new team member" :delay="240">
                <form method="POST" action="{{ route('staff.create-staff') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="{{ $labelClasses }}">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Full name" class="{{ $inputClasses }}" required>
                    </div>
                    <div>
                        <label class="{{ $labelClasses }}">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email address" class="{{ $inputClasses }}" required>
                    </div>
                    <div>
                        <label class="{{ $labelClasses }}">Role</label>
                        <select name="role" class="{{ $inputClasses }} cursor-pointer" required>
                            <option value="">Select role</option>
                            <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                            <option value="frontdesk" @selected(old('role') === 'frontdesk')>Front Desk</option>
                            <option value="housekeeping" @selected(old('role') === 'housekeeping')>Housekeeping</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelClasses }}">Password</label>
                            <input type="password" name="password" placeholder="Enter password" class="{{ $inputClasses }}" required>
                        </div>
                        <div>
                            <label class="{{ $labelClasses }}">Confirm Password</label>
                            <input type="password" name="password_confirmation" placeholder="Confirm password" class="{{ $inputClasses }}" required>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full flex items-center justify-center gap-2 text-sm font-semibold text-white bg-gradient-to-b from-clsu-600 to-clsu-800 border border-clsu-800 rounded-xl px-4 py-2.5 hover:from-clsu-700 hover:to-clsu-900 active:scale-[0.99] transition-all shadow-card cursor-pointer">
                            <x-admin.icon name="plus" class="w-4 h-4" />
                            Create Staff
                        </button>
                    </div>
                </form>
            </x-admin.section-card>

            <x-admin.section-card icon="edit" title="Edit Staff Account" subtitle="Update details, role, or password" :delay="280">
                <form method="POST" action="{{ route('staff.master-update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="{{ $labelClasses }}">Select Staff</label>
                        <select name="staff_id" class="{{ $inputClasses }} cursor-pointer" required>
                            <option value="">Select staff</option>
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $roleMeta[$staff->role]['label'] ?? ucfirst($staff->role) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClasses }}">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Full name" class="{{ $inputClasses }}">
                    </div>
                    <div>
                        <label class="{{ $labelClasses }}">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email address" class="{{ $inputClasses }}">
                    </div>
                    <div>
                        <label class="{{ $labelClasses }}">Role</label>
                        <select name="role" class="{{ $inputClasses }} cursor-pointer" required>
                            <option value="">Select role</option>
                            <option value="admin">Admin</option>
                            <option value="frontdesk">Front Desk</option>
                            <option value="housekeeping">Housekeeping</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelClasses }}">New Password <span class="normal-case font-medium text-stone-400">(optional)</span></label>
                            <input type="password" name="password" placeholder="Enter new password" class="{{ $inputClasses }}">
                        </div>
                        <div>
                            <label class="{{ $labelClasses }}">Confirm Password</label>
                            <input type="password" name="password_confirmation" placeholder="Confirm password" class="{{ $inputClasses }}">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full flex items-center justify-center gap-2 text-sm font-semibold text-white bg-gradient-to-b from-clsu-600 to-clsu-800 border border-clsu-800 rounded-xl px-4 py-2.5 hover:from-clsu-700 hover:to-clsu-900 active:scale-[0.99] transition-all shadow-card cursor-pointer">
                            <x-admin.icon name="check" class="w-4 h-4" />
                            Update Staff
                        </button>
                    </div>
                </form>
            </x-admin.section-card>
        </div>
    @else
        {{-- Logged-in staff can edit their own account --}}
        <div class="max-w-xl">
            <x-admin.section-card icon="user" title="Your Account" subtitle="Update your own details or password" :delay="240">
                <form id="edit-account-form" method="POST" action="{{ route('staff.update', auth()->guard('staff')->user()->id) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="{{ $labelClasses }}">Name</label>
                        <input type="text" name="name" value="{{ old('name', auth()->guard('staff')->user()->name) }}" class="{{ $inputClasses }}" required>
                    </div>
                    <div>
                        <label class="{{ $labelClasses }}">Email</label>
                        <input type="email" name="email" value="{{ old('email', auth()->guard('staff')->user()->email) }}" class="{{ $inputClasses }}" required>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelClasses }}">New Password <span class="normal-case font-medium text-stone-400">(optional)</span></label>
                            <input type="password" name="password" placeholder="Enter new password" class="{{ $inputClasses }}">
                        </div>
                        <div>
                            <label class="{{ $labelClasses }}">Confirm New Password</label>
                            <input type="password" name="password_confirmation" placeholder="Confirm new password" class="{{ $inputClasses }}">
                        </div>
                    </div>

                    <hr class="border-stone-100 my-4">

                    <div>
                        <label class="{{ $labelClasses }}">Current Password <span class="normal-case font-medium text-stone-400">(required to save changes)</span></label>
                        <input type="password" name="current_password" placeholder="Enter current password" class="{{ $inputClasses }}" required>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full flex items-center justify-center gap-2 text-sm font-semibold text-white bg-gradient-to-b from-clsu-600 to-clsu-800 border border-clsu-800 rounded-xl px-4 py-2.5 hover:from-clsu-700 hover:to-clsu-900 active:scale-[0.99] transition-all shadow-card cursor-pointer">
                            <x-admin.icon name="check" class="w-4 h-4" />
                            Save Changes
                        </button>
                    </div>
                </form>
            </x-admin.section-card>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$(document).on('click', '.password-verify-btn', function(e) {
    e.preventDefault();
    const staffId = $(this).data('staff-id');
    const action = $(this).data('action');

    Swal.fire({
        target: 'body', // <-- ensures modal is appended to <body>, not inside layout container
        title: 'Enter your staff password',
        input: 'password',
        inputAttributes: { placeholder: 'Password', autocapitalize: 'off' },
        showCancelButton: true,
        confirmButtonText: 'Verify',
        showLoaderOnConfirm: true,
        scrollbarPadding: false,
        preConfirm: (password) => {
            return $.ajax({
                url: '/staff/staff-records/verify-password',
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content'), password }
            }).then(response => {
                if (!response.success) throw new Error(response.message);
                return true;
            }).catch(err => Swal.showValidationMessage(err.responseJSON?.message || err.message));
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/staff/staff-records/${staffId}/${action}`,
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(data) {
                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                },
                error: function() {
                    Swal.fire('Error', 'Action failed. Please try again.', 'error');
                }
            });
        }
    });
});
</script>
@endpush
