@extends('layouts.admin')
<link rel="stylesheet" href="{{ asset('css/staff-discounts.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
@section('title', 'Admin - Discount Requests')
@section('page-title', 'Discount Requests')

@section('content')
    <div class="flex justify-end mb-4 space-x-2">
        <a href="{{ route('reports.discounts.all') }}" class="btn btn-primary">Export All Discounts</a>
        <a href="{{ route('reports.discounts.pending') }}" class="btn btn-warning">Export Pending</a>
        <a href="{{ route('reports.discounts.approved') }}" class="btn btn-success">Export Approved</a>
        <a href="{{ route('reports.discounts.rejected') }}" class="btn btn-danger">Export Rejected</a>
    </div>
    @livewire('staff.discounts.discount-list')
@endsection
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).on('click', '.review-discount', function(e) {
    e.preventDefault(); // prevent default navigation
    const url = $(this).attr('href'); // get the original href
    const discountId = $(this).data('discount-id');

    Swal.fire({
        title: 'Enter your password',
        input: 'password',
        inputAttributes: {
            autocapitalize: 'off',
            placeholder: 'Password'
        },
        showCancelButton: true,
        confirmButtonText: 'Verify',
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

