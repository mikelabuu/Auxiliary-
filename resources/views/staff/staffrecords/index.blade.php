@extends('layouts.admin')
@section('title', 'Admin - Staff Center')
@section('page-title', 'Staff Center')
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
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>
<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-100 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Staff Records</h1>
            <p class="text-gray-500 text-xs font-medium mt-0.5">Manage administrative and staff accounts and permission levels.</p>
        </div>
    </div>

    {{-- Search & Sort --}}
    <x-admin.card>
        <form method="GET" action="{{ route('staff.staffrecords.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-full md:w-72">
                <x-admin.input label="Search" name="search" :value="$search" placeholder="Search by username or email" class="!mb-0" />
            </div>

            <div class="w-full md:w-48">
                <x-admin.select label="Sort By" name="sort" class="!mb-0">
                    <option value="latest" {{ $sort == 'latest' ? 'selected' : '' }}>Newest First</option>
                    <option value="oldest" {{ $sort == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                </x-admin.select>
            </div>

            <x-admin.button variant="primary" type="submit" class="h-[42px] px-6">
                Apply Filters
            </x-admin.button>
        </form>
    </x-admin.card>

    {{-- Table --}}
    <x-admin.card title="Accounts List" icon="list">
        <div class="overflow-x-auto rounded-xl border border-gray-100 bg-white">
            <table class="min-w-full divide-y divide-gray-100 text-sm text-left">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">ID</th>
                        <th class="px-6 py-3.5">Username</th>
                        <th class="px-6 py-3.5">Email</th>
                        <th class="px-6 py-3.5">Role</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Last Login</th>
                        <th class="px-6 py-3.5">Created At</th>
                        @if (Auth::guard('staff')->user()->role === 'master_admin')
                            <th class="px-6 py-3.5 text-right">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($staffs as $staff)
                        <tr class="hover:bg-gray-50/50 transition duration-150">
                            <td class="px-6 py-4 font-bold text-gray-900">{{ $staff->id }}</td>
                            <td class="px-6 py-4 font-medium">{{ $staff->name }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $staff->email }}</td>
                            <td class="px-6 py-4">
                                @if($staff->role === 'admin')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-100">Admin</span>
                                @elseif($staff->role === 'frontdesk')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-100 font-semibold">Front Desk</span>
                                @elseif($staff->role === 'master_admin')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-900 text-white font-extrabold shadow-sm">Master Admin</span>
                                @elseif($staff->role === 'housekeeping')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">Housekeeping</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($staff->is_suspended)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">Suspended</span>
                                @elseif(!$staff->is_suspended && $staff->role === 'master_admin')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800">Master Account</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800">Active</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $staff->last_login_at ? \Carbon\Carbon::parse($staff->last_login_at)->format('M d, Y g:i A') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $staff->created_at->format('M d, Y g:i A') }}</td>
                            @if (Auth::guard('staff')->user()->role === 'master_admin')
                                <td class="px-6 py-4 text-right">
                                    @if ($staff->role !== 'master_admin')
                                        @if(!$staff->is_suspended)
                                            <x-admin.button variant="danger" class="btn-sm py-1 px-3 text-xs password-verify-btn rounded-lg" data-action="suspend" data-staff-id="{{ $staff->id }}">
                                                Suspend
                                            </x-admin.button>
                                        @else
                                            <x-admin.button variant="success" class="btn-sm py-1 px-3 text-xs password-verify-btn rounded-lg" data-action="unsuspend" data-staff-id="{{ $staff->id }}">
                                                Unsuspend
                                            </x-admin.button>
                                        @endif
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-gray-400 font-medium">No staff records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $staffs->links() }}
        </div>
    </x-admin.card>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl relative mb-4">
            <ul class="list-disc list-inside space-y-1 text-sm font-semibold">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (Auth::guard('staff')->user()->role === 'master_admin')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-admin.card title="Create Staff Account" icon="person_add">
                <form method="POST" action="{{ route('staff.create-staff') }}" class="space-y-4">
                    @csrf
                    
                    <x-admin.input label="Name" name="name" placeholder="Full Name" required />
                    <x-admin.input label="Email" name="email" type="email" placeholder="Email address" required />
                    
                    <x-admin.select label="Role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="frontdesk" {{ old('role') == 'frontdesk' ? 'selected' : '' }}>Frontdesk</option>
                        <option value="housekeeping" {{ old('role') == 'housekeeping' ? 'selected' : '' }}>Housekeeping</option>
                    </x-admin.select>
                    
                    <x-admin.input label="Password" name="password" type="password" placeholder="Enter password" required />
                    <x-admin.input label="Confirm Password" name="password_confirmation" type="password" placeholder="Confirm password" required />
                    
                    <div class="pt-2">
                        <x-admin.button variant="primary" type="submit" class="w-full rounded-xl">Create Staff</x-admin.button>
                    </div>
                </form>
            </x-admin.card>

            <x-admin.card title="Edit Staff Account" icon="manage_accounts">
                <form method="POST" action="{{ route('staff.master-update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    
                    <x-admin.select label="Select Staff" name="staff_id" required>
                        <option value="">Select Staff</option>
                        @foreach ($staffs as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->role }})</option>
                        @endforeach
                    </x-admin.select>
                    
                    <x-admin.input label="Name" name="name" placeholder="Full Name" />
                    <x-admin.input label="Email" name="email" type="email" placeholder="Email address" />
                    
                    <x-admin.select label="Role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="frontdesk">Frontdesk</option>
                        <option value="housekeeping">Housekeeping</option>
                    </x-admin.select>
                    
                    <x-admin.input label="New Password (optional)" name="password" type="password" placeholder="Enter new password" />
                    <x-admin.input label="Confirm Password" name="password_confirmation" type="password" placeholder="Confirm password" />
                    
                    <div class="pt-2">
                        <x-admin.button variant="primary" type="submit" class="w-full rounded-xl">Update Staff</x-admin.button>
                    </div>
                </form>
            </x-admin.card>
        </div>
    @else
        {{-- Logged-in staff can edit their own account --}}
        <div class="max-w-xl">
            <x-admin.card title="Edit Your Account" icon="manage_accounts">
                <form id="edit-account-form" method="POST" action="{{ route('staff.update', auth()->guard('staff')->user()->id) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    
                    <x-admin.input label="Name" name="name" :value="auth()->guard('staff')->user()->name" />
                    <x-admin.input label="Email" name="email" type="email" :value="auth()->guard('staff')->user()->email" />
                    <x-admin.input label="New Password (optional)" name="password" type="password" placeholder="Enter new password" />
                    <x-admin.input label="Confirm New Password" name="password_confirmation" type="password" placeholder="Confirm new password" />
                    
                    <hr class="border-gray-100 my-4">
                    
                    <x-admin.input label="Current Password (required to save changes)" name="current_password" type="password" placeholder="Enter current password" required />
                    
                    <div class="pt-2">
                        <x-admin.button variant="primary" type="submit" class="w-full rounded-xl">Save Changes</x-admin.button>
                    </div>
                </form>
            </x-admin.card>
        </div>
    @endif
</div>
@endsection
@push('scripts')
<script>
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

