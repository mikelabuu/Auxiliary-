@extends('layouts.admin')

@section('title', 'Admin - Discount Requests')
@section('page-title', 'Discount Requests')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.page-header subtitle="Review Senior Citizen / PWD verification documents and approve the 20% discount.">
        Discount <span class="font-display italic font-medium text-clsu-800">Requests</span>
        <x-slot:actions>
            <a href="{{ route('reports.discounts.all') }}" class="flex items-center gap-2 text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 active:scale-[0.98] transition-all shadow-sm !no-underline">
                <x-admin.icon name="download" class="w-4 h-4" />
                All
            </a>
            <a href="{{ route('reports.discounts.pending') }}" class="flex items-center gap-2 text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 active:scale-[0.98] transition-all shadow-sm !no-underline">
                <x-admin.icon name="download" class="w-4 h-4" />
                Pending
            </a>
            <a href="{{ route('reports.discounts.approved') }}" class="flex items-center gap-2 text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 active:scale-[0.98] transition-all shadow-sm !no-underline">
                <x-admin.icon name="download" class="w-4 h-4" />
                Approved
            </a>
            <a href="{{ route('reports.discounts.rejected') }}" class="flex items-center gap-2 text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 active:scale-[0.98] transition-all shadow-sm !no-underline">
                <x-admin.icon name="download" class="w-4 h-4" />
                Rejected
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @livewire('staff.discounts.discount-list')
</div>
@endsection

@push('scripts')
<script>
$(document).on('click', '.review-discount', function(e) {
    e.preventDefault(); // prevent default navigation
    const url = $(this).attr('href'); // get the original href

    Swal.fire({
        title: 'Enter your password',
        text: 'Reviewing discount documents requires re-authentication.',
        input: 'password',
        inputAttributes: {
            autocapitalize: 'off',
            placeholder: 'Password'
        },
        showCancelButton: true,
        confirmButtonText: 'Verify',
        confirmButtonColor: '#14532d',
        showLoaderOnConfirm: true,
        preConfirm: (password) => {
            return $.ajax({
                url: '/staff/discounts/verify-password',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    password: password
                }
            }).then(response => {
                if (!response.success) {
                    throw new Error(response.message)
                }
                return response.success;
            }).catch(err => {
                Swal.showValidationMessage(err.responseJSON?.message || err.message);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
});
</script>
@endpush
