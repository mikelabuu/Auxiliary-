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
<div class="p-6 space-y-6">

    <h1 class="text-2xl font-bold mb-4">Staff Records</h1>

    {{-- Search & Sort --}}
    <form method="GET" action="{{ route('staff.staffrecords.index') }}" class="flex flex-wrap items-center gap-3 mb-4">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search by username or email"
               class="border rounded-lg px-3 py-2 w-64">

        <select name="sort" class="border rounded-lg px-3 py-2">
            <option value="latest" {{ $sort == 'latest' ? 'selected' : '' }}>Newest First</option>
            <option value="oldest" {{ $sort == 'oldest' ? 'selected' : '' }}>Oldest First</option>
        </select>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Apply</button>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Username</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Role</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Last Login</th>
                    <th class="px-4 py-2">Created At</th>
                    @if (Auth::guard('staff')->user()->role === 'master_admin')
                        <th class="px-4 py-2">Action</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($staffs as $staff)
                    <tr class="border-t">
                        <td class="px-4 py-2 font-medium">{{ $staff->id }}</td>
                        <td class="px-4 py-2">{{ $staff->name }}</td>
                        <td class="px-4 py-2">{{ $staff->email }}</td>
                        <td class="px-4 py-2">
                            @if($staff->role === 'admin')
                                <span class="text-red-600 font-semibold">Admin</span>
                            @elseif($staff->role ==='frontdesk')
                                <span class="text-yellow-600 font-semibold">Front Desk</span>
                            @elseif($staff->role === 'master_admin')
                                <span class="text-black-800 font-bold">Master Admin</span>
                            @elseif($staff->role ==='housekeeping')
                                <span class="text-blue-600 font-semibold">Housekeeping</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if($staff->is_suspended)
                                <span class="text-red-600 font-semibold">Suspended</span>
                            @elseif(!$staff->is_suspended && $staff->role === 'master_admin')
                                <span class="text-purple-600 font-semibold">Master Account</span>
                            @else
                                <span class="text-green-600 font-semibold">Not Suspended</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            {{ $staff->last_login_at ? \Carbon\Carbon::parse($staff->last_login_at)->format('M d, Y g:i A') : 'N/A' }}
                        </td>
                        <td class="px-4 py-2">{{ $staff->created_at->format('M d, Y g:i A') }}</td>
                        @if ($staff->role !== 'master_admin')
                            <td class="px-4 py-2">
                                @if(!$staff->is_suspended)
                                    <button 
                                        class="px-3 py-1 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition password-verify-btn"
                                        data-action="suspend"
                                        data-staff-id="{{ $staff->id }}">
                                        Suspend
                                    </button>
                                @else
                                    <button 
                                        class="px-3 py-1 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition password-verify-btn"
                                        data-action="unsuspend"
                                        data-staff-id="{{ $staff->id }}">
                                        Unsuspend
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-4 text-center text-gray-500">No staff records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $staffs->links() }}
    </div>

</div>
<div class="p-6 space-y-6">

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (Auth::guard('staff')->user()->role === 'master_admin')
        <div class="p-4 bg-white rounded shadow">
            <h1 class="text-2xl font-bold mb-4">Create Staff Account</h1>
            <form method="POST" action="{{ route('staff.create-staff') }}" class="space-y-4">
                @csrf

                {{-- Name --}}
                <div>
                    <label for="name" class="block font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="border rounded-lg px-3 py-2 w-full" placeholder="Full Name" required>
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                        class="border rounded-lg px-3 py-2 w-full" placeholder="Email address" required>
                </div>

                {{-- Role --}}
                <div>
                    <label for="role" class="block font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="role" class="border rounded-lg px-3 py-2 w-full" required>
                        <option value="">Select Role</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="frontdesk" {{ old('role') == 'frontdesk' ? 'selected' : '' }}>Frontdesk</option>
                        <option value="housekeeping" {{ old('role') == 'housekeeping' ? 'selected' : '' }}>Housekeeping</option>
                    </select>
                </div>
                {{-- Password --}}
                <div>
                    <label for="password" class="block font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" id="password"
                        class="border rounded-lg px-3 py-2 w-full" placeholder="Enter password" required>
                </div>
                {{-- Confirm Password --}}
                <div>
                    <label for="password_confirmation" class="block font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="border rounded-lg px-3 py-2 w-full" placeholder="Confirm password" required>
                </div>

                {{-- Submit Button --}}
                <div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Create Staff
                    </button>
                </div>
            </form>
        </div>
        <div class="p-4 bg-white rounded shadow">
            <h1 class="text-2xl font-bold mb-4">Edit Staff Account</h1>

            <form method="POST" action="{{ route('staff.master-update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Select Staff --}}
                <div>
                    <label for="staff_id" class="block font-medium text-gray-700 mb-1">Select Staff</label>
                    <select name="staff_id" id="staff_id" class="border rounded-lg px-3 py-2 w-full" required>
                        <option value="">Select Staff</option>
                        @foreach ($staffs as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->role }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Name --}}
                <div>
                    <label for="name" class="block font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="name" class="border rounded-lg px-3 py-2 w-full"
                        placeholder="Full Name">
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" class="border rounded-lg px-3 py-2 w-full"
                        placeholder="Email address">
                </div>

                {{-- Role --}}
                <div>
                    <label for="role" class="block font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="role" class="border rounded-lg px-3 py-2 w-full" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="frontdesk">Frontdesk</option>
                        <option value="housekeeping">Housekeeping</option>
                    </select>
                </div>

                {{-- Password (optional) --}}
                <div>
                    <label for="password" class="block font-medium text-gray-700 mb-1">New Password (optional)</label>
                    <input type="password" name="password" id="password"
                        class="border rounded-lg px-3 py-2 w-full" placeholder="Enter new password">
                </div>

                <div>
                    <label for="password_confirmation" class="block font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="border rounded-lg px-3 py-2 w-full" placeholder="Confirm password">
                </div>

                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Update Staff
                </button>
            </form>
        </div>
    @else
        {{-- Logged-in staff can edit their own account --}}
        <div class="p-4 bg-white rounded shadow">
            <h2 class="text-lg font-semibold mb-2">Edit Your Account</h2>
            <form id="edit-account-form" method="POST" action="{{ route('staff.update', auth()->guard('staff')->user()->id) }}">
                @csrf
                @method('PUT')

                {{-- Name --}}
                <div class="mb-4">
                    <label class="block mb-1 font-medium" for="name">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', auth()->guard('staff')->user()->name) }}" 
                        class="border rounded-lg px-3 py-2 w-full">
                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                {{-- Email --}}
                <div class="mb-4">
                    <label class="block mb-1 font-medium" for="email">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', auth()->guard('staff')->user()->email) }}" 
                        class="border rounded-lg px-3 py-2 w-full">
                    @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                {{-- New Password --}}
                <div class="mb-4">
                    <label class="block mb-1 font-medium" for="password">New Password (optional)</label>
                    <input type="password" name="password" id="password" placeholder="Enter new password" 
                        class="border rounded-lg px-3 py-2 w-full">
                    @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="mb-4">
                    <label class="block mb-1 font-medium" for="password_confirmation">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm new password" 
                        class="border rounded-lg px-3 py-2 w-full">
                </div>

                {{-- Current Password for Verification --}}
                <div class="mb-4">
                    <label class="block mb-1 font-medium" for="current_password">Current Password</label>
                    <input type="password" name="current_password" id="current_password" placeholder="Enter current password to confirm changes" 
                        class="border rounded-lg px-3 py-2 w-full" required>
                    @error('current_password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 w-full">Save Changes</button>
            </form>
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

