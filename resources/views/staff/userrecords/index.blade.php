@extends('layouts.admin')
@section('title', 'Admin - User Hub')
@section('page-title', 'User Hub')
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

    <h1 class="text-2xl font-bold mb-4">User Records</h1>
    <div class="flex justify-end mb-4 space-x-2">
        <a href="{{ route('reports.users.all') }}" class="btn btn-primary">Export All Users</a>
        <a href="{{ route('reports.users.active') }}" class="btn btn-success">Export Active Users</a>
        <a href="{{ route('reports.users.suspended') }}" class="btn btn-warning">Export Suspended Users</a>
    </div>

    {{-- Search & Sort --}}
    <form method="GET" action="{{ route('staff.userrecords.index') }}" class="flex flex-wrap items-center gap-3 mb-4">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search by username, email, or phone"
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
                    <th class="px-4 py-2">Phone</th>
                    <th class="px-4 py-2">Verified</th>
                    <th class="px-4 py-2">Last Login</th>
                    <th class="px-4 py-2">Created At</th>
                    <th class="px-4 py-2">Completed Bookings</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-t {{ $user->is_suspended ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-2 font-medium">{{ $user->id }}</td>
                        <td class="px-4 py-2">{{ $user->username }}</td>
                        <td class="px-4 py-2">{{ $user->email }}</td>
                        <td class="px-4 py-2">{{ $user->phone ?? 'N/A' }}</td>
                        <td class="px-4 py-2">
                            @if($user->email_verified_at)
                                <span class="text-green-600 font-semibold">Verified</span>
                            @else
                                <span class="text-red-600 font-semibold">Not Verified</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('M d, Y g:i A') : 'N/A' }}
                        </td>
                        <td class="px-4 py-2">{{ $user->created_at->format('M d, Y g:i A') }}</td>
                        <td class="px-4 py-2">{{ $user->completed_bookings_count }}</td>
                        <td class="px-4 py-2">
                            @if($user->is_suspended)
                                <span class="text-red-600 font-semibold">Suspended</span>
                            @else
                                <span class="text-green-600 font-semibold">Active</span>
                            @endif
                        </td>   
                        <td class="px-4 py-2 space-x-2">
                            @if(!$user->is_suspended)
                                <button 
                                    class="px-3 py-1 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition password-verify-btn"
                                    data-action="suspend"
                                    data-user-id="{{ $user->id }}">
                                    Suspend
                                </button>
                            @else
                                <button 
                                    class="px-3 py-1 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition password-verify-btn"
                                    data-action="unsuspend"
                                    data-user-id="{{ $user->id }}">
                                    Unsuspend
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-4 text-center text-gray-500">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $users->links() }}
    </div>

</div>
@endsection

@push('scripts')
<script>
$(document).on('click', '.password-verify-btn', function(e) {
    e.preventDefault();
    const userId = $(this).data('user-id');
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
                url: '/staff/user-records/verify-password',
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
                url: `/staff/user-records/${userId}/${action}`,
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
