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
    $me = Auth::guard('staff')->user();
    $isMaster = $me->role === 'master_admin';
    $roleMeta = [
        'master_admin' => ['badge' => 'bg-stone-900 text-white border-stone-900',        'label' => 'Master Admin'],
        'admin'        => ['badge' => 'bg-clsu-50 text-clsu-700 border-clsu-200',        'label' => 'Admin'],
        'frontdesk'    => ['badge' => 'bg-palay-100 text-palay-800 border-palay-200',    'label' => 'Front Desk'],
        'housekeeping' => ['badge' => 'bg-sky-50 text-sky-700 border-sky-200',           'label' => 'Housekeeping'],
    ];
    $inputClasses = 'w-full text-sm bg-white border border-stone-200 rounded-xl px-4 py-2.5 text-stone-700 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors';
    $labelClasses = 'block text-[10px] font-bold uppercase tracking-widest text-stone-500 mb-1.5';

    // Which form (if any) triggered the validation errors, so old() values are
    // routed back to the right modal/card and the modal reopens on load.
    $errorForm = $errors->any() ? old('_form') : null;
@endphp

<div class="space-y-6 max-w-[1680px] mx-auto">

    <x-admin.ui.page-header subtitle="Administrative and staff accounts, their roles, and access standing.">
        Staff <span class="text-clsu-700">Center</span>
        @if ($isMaster)
            <x-slot:actions>
                <x-admin.ui.button variant="primary" type="button" id="openCreateStaffBtn">
                    <x-admin.ui.icon name="plus" class="w-4 h-4" />
                    New Staff Account
                </x-admin.ui.button>
            </x-slot:actions>
        @endif
    </x-admin.ui.page-header>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="animate-in flex items-center gap-2.5 rounded-2xl border border-clsu-200 bg-clsu-50 px-5 py-3 text-sm font-medium text-clsu-800">
            <x-admin.ui.icon name="check-circle" class="w-4 h-4 shrink-0" />
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="animate-in rounded-2xl border border-ember-200 bg-ember-50 px-5 py-3.5 text-sm text-ember-700">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li class="flex items-start gap-1.5"><x-admin.ui.icon name="block" class="w-3.5 h-3.5 shrink-0 mt-0.5" /> {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Team stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <x-admin.ui.stat-card icon="users" badge="WHOLE TEAM" label="Staff Accounts" :delay="40" dark>
            {{ number_format($stats['total']) }}
            <x-slot:footnote><p class="text-xs text-clsu-300">Across all roles</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="check-circle" badge="ENABLED" label="Active Accounts" :delay="80">
            {{ number_format($stats['active']) }}
            <x-slot:footnote><p class="text-xs text-stone-400">Able to sign in</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="settings" color="palay" badge="ELEVATED" label="Admin Accounts" :delay="120">
            {{ number_format($stats['admins']) }}
            <x-slot:footnote><p class="text-xs text-stone-400">Master admin and admins</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="block" color="ember" badge="RESTRICTED" label="Suspended" :delay="160">
            {{ number_format($stats['suspended']) }}
            <x-slot:footnote><p class="text-xs text-stone-400">Sign-in disabled</p></x-slot:footnote>
        </x-admin.ui.stat-card>
    </div>

    <x-admin.ui.section-card icon="users" title="Staff Accounts" :subtitle="$staffs->total() . ' record' . ($staffs->total() === 1 ? '' : 's') . ($search ? ' matching “' . $search . '”' : '')" :delay="200">

        {{-- Search + filters --}}
        <form method="GET" class="filter-toolbar">
            <div class="filter-search">
                <x-admin.ui.icon name="search" class="w-4 h-4" stroke-width="2" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Name or email…" aria-label="Search staff">
            </div>
            <select name="role" class="filter-select" aria-label="Filter by role">
                <option value="all" @selected($role === 'all')>All roles</option>
                <option value="master_admin" @selected($role === 'master_admin')>Master Admin</option>
                <option value="admin" @selected($role === 'admin')>Admin</option>
                <option value="frontdesk" @selected($role === 'frontdesk')>Front Desk</option>
                <option value="housekeeping" @selected($role === 'housekeeping')>Housekeeping</option>
            </select>
            <select name="sort" class="filter-select" aria-label="Sort order">
                <option value="latest" @selected($sort === 'latest')>Newest first</option>
                <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
            <div class="filter-toolbar-spacer"></div>
            @if($search || $role !== 'all' || $sort !== 'latest')
                <a href="{{ route('staff.staffrecords.index') }}" class="filter-clear" style="text-decoration:none;">
                    <x-admin.ui.icon name="x" class="w-3 h-3" stroke-width="2.5" /> Clear
                </a>
            @endif
        </form>

        @if($staffs->isEmpty())
            <x-admin.ui.empty-state icon="users" title="No staff records match this view." />
        @else
            <div class="scroll-x -mx-6 -mb-6 border-t border-stone-100">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Role</th>
                            <th>Standing</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($staffs as $staff)
                            @php $staffRole = $roleMeta[$staff->role] ?? ['badge' => 'bg-stone-100 text-stone-600 border-stone-200', 'label' => ucfirst($staff->role)]; @endphp
                            <tr>
                                <td>
                                    <div class="cell-name">
                                        <span class="avatar-initials"
                                            @if($staff->is_suspended) style="background:linear-gradient(135deg,#fee2e2,#fecaca);color:#b91c1c;border-color:#fca5a5;"
                                            @elseif($staff->role === 'master_admin') style="background:linear-gradient(135deg,#3b0764,#6b21a8);color:#fff;border-color:#7e22ce;" @endif>{{ strtoupper(mb_substr($staff->name, 0, 1)) }}</span>
                                        <div class="cell-name-text">
                                            <p class="cell-name-primary truncate">{{ $staff->name }}</p>
                                            <p class="cell-name-secondary truncate">{{ $staff->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $staffRole['badge'] }}">{{ $staffRole['label'] }}</span>
                                </td>
                                <td>
                                    @if($staff->is_suspended)
                                        <span class="status status-cancelled">Suspended</span>
                                    @elseif($staff->role === 'master_admin')
                                        <span class="status" style="color:#6b21a8;border-color:#e9d5ff;background:#faf5ff;"><span style="width:6px;height:6px;border-radius:50%;background:#a855f7;"></span>Master Account</span>
                                    @else
                                        <span class="status status-active">Active</span>
                                    @endif
                                </td>
                                <td class="font-data tabnum text-xs whitespace-nowrap text-faint">
                                    {{ $staff->last_login_at ? \Carbon\Carbon::parse($staff->last_login_at)->timezone('Asia/Manila')->format('M d, Y · h:i A') : '—' }}
                                </td>
                                <td class="font-data tabnum text-xs whitespace-nowrap text-faint">{{ $staff->created_at->timezone('Asia/Manila')->format('M d, Y') }}</td>
                                <td class="text-right">
                                    <div class="table-actions justify-end">
                                        <button class="activity-btn btn btn-ghost btn-sm btn-icon cursor-pointer"
                                                data-staff-id="{{ $staff->id }}"
                                                data-name="{{ $staff->name }}"
                                                title="View activity" aria-label="View activity">
                                            <x-admin.ui.icon name="clock" class="w-4 h-4" />
                                        </button>
                                        @if ($isMaster && $staff->role !== 'master_admin')
                                            <button class="edit-staff-btn btn btn-ghost btn-sm btn-icon cursor-pointer"
                                                    data-staff-id="{{ $staff->id }}"
                                                    data-name="{{ $staff->name }}"
                                                    data-email="{{ $staff->email }}"
                                                    data-role="{{ $staff->role }}"
                                                    title="Edit account" aria-label="Edit account">
                                                <x-admin.ui.icon name="edit" class="w-4 h-4" />
                                            </button>
                                            @if(!$staff->is_suspended)
                                                <button class="password-verify-btn btn btn-danger btn-sm cursor-pointer"
                                                        data-action="suspend"
                                                        data-staff-id="{{ $staff->id }}">
                                                    Suspend
                                                </button>
                                            @else
                                                <button class="password-verify-btn btn btn-outline btn-sm cursor-pointer"
                                                        data-action="unsuspend"
                                                        data-staff-id="{{ $staff->id }}">
                                                    Unsuspend
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $staffs->links() }}
            </div>
        @endif
    </x-admin.ui.section-card>

    {{-- Own account (every staff role can edit their own details) --}}
    <div class="max-w-xl">
        <x-admin.ui.section-card icon="user" title="Your Account" subtitle="Update your own details or password" :delay="240">
            <form method="POST" action="{{ route('staff.update', $me->id) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="self-update">

                <div>
                    <label class="{{ $labelClasses }}">Name</label>
                    <input type="text" name="name" value="{{ $errorForm === 'self-update' ? old('name') : $me->name }}" class="{{ $inputClasses }}" required>
                </div>
                <div>
                    <label class="{{ $labelClasses }}">Email</label>
                    <input type="email" name="email" value="{{ $errorForm === 'self-update' ? old('email') : $me->email }}" class="{{ $inputClasses }}" required>
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
                    <button type="submit" class="w-full flex items-center justify-center gap-2 text-sm font-semibold text-white bg-clsu-600 border border-clsu-700 rounded-xl px-4 py-2.5 hover:bg-clsu-700 active:scale-[0.99] transition-all shadow-card cursor-pointer">
                        <x-admin.ui.icon name="check" class="w-4 h-4" />
                        Save Changes
                    </button>
                </div>
            </form>
        </x-admin.ui.section-card>
    </div>

    @if ($isMaster)
        {{-- Create staff modal --}}
        <x-admin.ui.modal id="createStaffModal" icon="plus" title="New Staff Account" max-width="lg" scroll-body>
            <form method="POST" action="{{ route('staff.create-staff') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_form" value="create-staff">

                <div>
                    <label class="{{ $labelClasses }}">Name</label>
                    <input type="text" name="name" value="{{ $errorForm === 'create-staff' ? old('name') : '' }}" placeholder="Full name" class="{{ $inputClasses }}" required>
                </div>
                <div>
                    <label class="{{ $labelClasses }}">Email</label>
                    <input type="email" name="email" value="{{ $errorForm === 'create-staff' ? old('email') : '' }}" placeholder="Email address" class="{{ $inputClasses }}" required>
                </div>
                <div>
                    <label class="{{ $labelClasses }}">Role</label>
                    <select name="role" class="{{ $inputClasses }} cursor-pointer" required>
                        <option value="">Select role</option>
                        <option value="admin" @selected($errorForm === 'create-staff' && old('role') === 'admin')>Admin</option>
                        <option value="frontdesk" @selected($errorForm === 'create-staff' && old('role') === 'frontdesk')>Front Desk</option>
                        <option value="housekeeping" @selected($errorForm === 'create-staff' && old('role') === 'housekeeping')>Housekeeping</option>
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

                <div class="flex gap-2.5 justify-end pt-2">
                    <x-admin.ui.modal-footer close-target="createStaffModal" submit-label="Create Staff" />
                </div>
            </form>
        </x-admin.ui.modal>

        {{-- Edit staff modal --}}
        <x-admin.ui.modal id="editStaffModal" icon="edit" title="Edit Staff Account" max-width="lg" scroll-body>
            <form method="POST" action="{{ route('staff.master-update') }}" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit-staff">
                <input type="hidden" name="staff_id" id="esStaffId" value="{{ $errorForm === 'edit-staff' ? old('staff_id') : '' }}">

                <div class="flex items-center gap-2.5 rounded-xl border border-stone-200 bg-stone-50/70 px-4 py-2.5 text-sm">
                    <x-admin.ui.icon name="user" class="w-4 h-4 text-stone-400 shrink-0" />
                    <span class="text-stone-500">Editing:</span>
                    <span id="esTarget" class="font-semibold text-stone-800 truncate">{{ $errorForm === 'edit-staff' ? (old('name') ?: 'Staff #' . old('staff_id')) : '' }}</span>
                </div>

                <div>
                    <label class="{{ $labelClasses }}">Name</label>
                    <input type="text" name="name" id="esName" value="{{ $errorForm === 'edit-staff' ? old('name') : '' }}" placeholder="Full name" class="{{ $inputClasses }}" required>
                </div>
                <div>
                    <label class="{{ $labelClasses }}">Email</label>
                    <input type="email" name="email" id="esEmail" value="{{ $errorForm === 'edit-staff' ? old('email') : '' }}" placeholder="Email address" class="{{ $inputClasses }}" required>
                </div>
                <div>
                    <label class="{{ $labelClasses }}">Role</label>
                    <select name="role" id="esRole" class="{{ $inputClasses }} cursor-pointer" required>
                        <option value="">Select role</option>
                        <option value="admin" @selected($errorForm === 'edit-staff' && old('role') === 'admin')>Admin</option>
                        <option value="frontdesk" @selected($errorForm === 'edit-staff' && old('role') === 'frontdesk')>Front Desk</option>
                        <option value="housekeeping" @selected($errorForm === 'edit-staff' && old('role') === 'housekeeping')>Housekeeping</option>
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

                <div class="flex gap-2.5 justify-end pt-2">
                    <x-admin.ui.modal-footer close-target="editStaffModal" submit-label="Save Changes" />
                </div>
            </form>
        </x-admin.ui.modal>
    @endif

    {{-- Activity modal (populated via AJAX) --}}
    <x-admin.ui.modal id="staffActivityModal" icon="clock" title="Recent Activity" max-width="lg" scroll-body>
        <div class="p-6">
            <div id="saLoading" class="py-8 text-center text-sm text-stone-400">Loading activity…</div>
            <div id="saBody" class="hidden space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p id="saName" class="font-semibold text-stone-800"></p>
                    <p id="saLastLogin" class="text-xs text-stone-400 font-data tabnum"></p>
                </div>
                <div id="saLogs" class="divide-y divide-stone-100 rounded-xl border border-stone-200 overflow-hidden"></div>
                <p id="saEmpty" class="hidden text-sm text-stone-400 text-center py-4">No recorded activity yet.</p>
                <p class="text-[11px] text-stone-400">Showing the 10 most recent entries. Full history is in the <a href="{{ route('staff.audit.index') }}" class="text-clsu-700 font-semibold hover:underline">Audit Logs</a>.</p>
            </div>
        </div>
    </x-admin.ui.modal>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// ── Modal helpers (same pattern as the rooms page) ──────────────────────────
function openModal(id) {
    $('#' + id).removeClass('hidden').addClass('flex');
}
function closeModal(id) {
    $('#' + id).removeClass('flex').addClass('hidden');
}
$(document).on('click', '[data-modal-close]', function () {
    closeModal($(this).data('modal-close'));
});
$(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
        ['createStaffModal', 'editStaffModal', 'staffActivityModal'].forEach(closeModal);
    }
});

$('#openCreateStaffBtn').on('click', () => openModal('createStaffModal'));

// ── Edit staff modal (prefilled from the row's data attributes) ─────────────
$(document).on('click', '.edit-staff-btn', function () {
    const $btn = $(this);
    $('#esStaffId').val($btn.data('staff-id'));
    $('#esTarget').text($btn.data('name'));
    $('#esName').val($btn.data('name'));
    $('#esEmail').val($btn.data('email'));
    $('#esRole').val(String($btn.data('role')));
    openModal('editStaffModal');
});

// ── Activity modal ───────────────────────────────────────────────────────────
$(document).on('click', '.activity-btn', function () {
    const staffId = $(this).data('staff-id');
    $('#saLoading').removeClass('hidden');
    $('#saBody').addClass('hidden');
    openModal('staffActivityModal');

    $.get(`/staff/staff-records/${staffId}/activity`)
        .done(function (data) {
            $('#saName').text(data.name);
            $('#saLastLogin').text(data.last_login ? 'Last login: ' + data.last_login : 'Never logged in');

            const $logs = $('#saLogs').empty();
            $('#saEmpty').toggleClass('hidden', data.logs.length > 0);
            $logs.toggleClass('hidden', data.logs.length === 0);

            data.logs.forEach(log => {
                const $row = $('<div class="px-4 py-3 bg-white"></div>');
                const $top = $('<div class="flex items-center justify-between gap-3"></div>');
                $top.append($('<span class="text-xs font-bold text-stone-800"></span>').text(log.action + (log.target ? ' · ' + log.target : '')));
                $top.append($('<span class="text-[11px] text-stone-400 font-data tabnum whitespace-nowrap shrink-0"></span>').text(log.when));
                $row.append($top);
                if (log.description) {
                    $row.append($('<p class="text-xs text-stone-500 mt-1"></p>').text(log.description));
                }
                $logs.append($row);
            });

            $('#saLoading').addClass('hidden');
            $('#saBody').removeClass('hidden');
        })
        .fail(function () {
            closeModal('staffActivityModal');
            Swal.fire('Error', 'Could not load activity.', 'error');
        });
});

// Reopen the modal that failed validation so old input + errors are visible
@if($errorForm === 'create-staff')
    openModal('createStaffModal');
@elseif($errorForm === 'edit-staff')
    openModal('editStaffModal');
@endif

// ── Suspend / unsuspend (password-verified) ──────────────────────────────────
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
