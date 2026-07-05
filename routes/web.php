<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\PasswordResetLinkController;
use App\Http\Controllers\NewPasswordController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DiscountController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffDashboardController;
use App\Http\Controllers\Staff\RoomController;
use App\Http\Controllers\Staff\RoomTypeController;
use App\Http\Controllers\Staff\DiscountAdminController;
use App\Http\Controllers\Staff\BookingHubController;
use App\Http\Controllers\Staff\CompletedBookingsController;
use App\Http\Controllers\Staff\BookingLogsController;
use App\Http\Controllers\Staff\PaymentLogsController;
use App\Http\Controllers\Staff\UserRecordsController;
use App\Http\Controllers\Staff\StaffRecordsController;
use App\Http\Controllers\Staff\AuditLogController;

use App\Http\Controllers\Staff\ManualBookingController;

use App\Http\Controllers\Staff\Reports\BookingReportController;
use App\Http\Controllers\Staff\Reports\PaymentReportController;
use App\Http\Controllers\Staff\Reports\UserReportController;
use App\Http\Controllers\Staff\Reports\DiscountReportController;
use App\Http\Controllers\Staff\Reports\MainReportsController;

use App\Http\Controllers\Staff\frontdesk\FrontDeskDashboardController;
use App\Http\Controllers\Staff\frontdesk\FrontDeskRoomController;
use App\Http\Controllers\Staff\frontdesk\WalkInBookingController;
use App\Http\Controllers\Staff\frontdesk\BookingsController;


//for simulation only
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Payments\SandboxGatewayController;
use App\Http\Middleware\VerifyCsrfToken;
//test receipts
use App\Models\Booking;
use App\Models\Payments;
use App\Http\Controllers\ReceiptController;
/*
|--------------------------------------------------------------------------
| Guest-only Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [BookingController::class, 'welcome'])->name('home');

Route::post('/rooms/available', [BookingController::class, 'getAvailableRooms'])->name('rooms.available');
Route::post('/rooms/availability-summary', [BookingController::class, 'availabilitySummary'])->name('rooms.availability_summary');

Route::middleware('guest')->group(function () {
    // Login form
    Route::get('/login', function () {
        return view('auth');
    })->name('login');

    // User Login actions
    Route::post('/login/user', [AuthController::class, 'loginUser'])->name('login.user');
    // Signup
    Route::post('/signup', [AuthController::class, 'signup'])->name('signup');

    // Staff Login Form
    Route::get('/staff/login', [StaffAuthController::class, 'showLoginForm'])->name('staff.login');
    Route::post('/staff/login', [StaffAuthController::class, 'loginStaff'])->name('staff.login.submit');
     // OTP verification
    Route::get('/staff/otp', [StaffAuthController::class, 'showOtpForm'])->name('staff.otp.form');
    Route::post('/staff/otp', [StaffAuthController::class, 'verifyOtp'])->name('staff.otp.verify');
    // Resend OTP
    Route::post('/staff/otp/resend', [StaffAuthController::class, 'resendOtp'])->name('staff.otp.resend');

    // Password reset
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Auth + Verified Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Checkout page
    Route::get('/checkout', [BookingController::class, 'showCheckoutForm'])->name('checkout.form');
    Route::get('/booking/{booking}', [BookingController::class, 'show'])->name('booking.show');
    Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');

    //discount
    Route::get('discount/{booking}/create', [DiscountController::class, 'create'])->name('discount.create');
    Route::post('discount/{booking}', [DiscountController::class, 'store'])->name('discount.store');
    Route::post('/discount/{booking}/cancel', [DiscountController::class, 'cancel'])->name('discount.cancel');

    //user settings page
    Route::get('/settings', [SettingsController::class, 'profile'])->name('settings.profile');
    Route::get('/my-bookings', [SettingsController::class, 'bookings'])->name('settings.bookings');
    Route::get('/transactions', [SettingsController::class, 'transactions'])->name('settings.transactions');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    //cancel booking
    Route::post('/booking/{booking}/cancel', [SettingsController::class, 'cancelBooking'])->name('booking.cancel');

    //payments

    // Logout
    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:staff', 'staff.role:admin,master_admin'])->group(function () {

    // Home and Dashboard
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');

    // Room Management
    Route::get('/staff/rooms', [RoomController::class, 'index'])->name('staff.rooms');
    Route::post('/staff/rooms/store', [RoomController::class, 'store'])->name('staff.rooms.store');

    // Verify staff password before delete (AJAX)
    Route::post('/staff/rooms/{room}/verify-password', [RoomController::class, 'verifyPassword'])->name('staff.rooms.verifyPassword');

    Route::get('/staff/rooms/{room}/edit', [RoomController::class, 'edit'])->name('staff.rooms.edit');
    Route::put('/staff/rooms/{room}', [RoomController::class, 'update'])->name('staff.rooms.update');
    Route::patch('/staff/rooms/{room}/status', [RoomController::class, 'updateStatus'])->name('staff.rooms.updateStatus');

    // Delete room
    Route::delete('/staff/rooms/{room}', [RoomController::class, 'destroy'])->name('staff.rooms.destroy');

    // Room Types
    Route::post('/staff/room-types', [RoomTypeController::class, 'store'])->name('staff.roomtypes.store');
    Route::put('/staff/room-types/{roomType}', [RoomTypeController::class, 'update'])->name('staff.roomtypes.update');

    //Booking Hub
    Route::get('/bookings', [BookingHubController::class, 'index'])->name('staff.bookings.index');
    

    // Route::get('/staff/balance', [AdminBalanceController::class, 'index'])->name('staff.balance');

    Route::prefix('staff')->group(function () {
        Route::get('/completed-bookings', [CompletedBookingsController::class, 'index'])
            ->name('staff.completedbookings.index');

        Route::post('/completed-bookings/verify-password', [CompletedBookingsController::class, 'verifyPassword'])
            ->name('staff.completedbookings.verify-password');

        Route::get('/completed-bookings/{id}/details', [CompletedBookingsController::class, 'showDetails'])
            ->name('staff.completedbookings.details');

        Route::get('/booking-logs', [BookingLogsController::class, 'index'])->name('staff.bookinglogs.index');
        Route::get('/payment-logs', [PaymentLogsController::class, 'index'])->name('staff.paymentlogs.index');

        Route::get('/user-records', [UserRecordsController::class, 'index'])->name('staff.userrecords.index');
        // Verify staff password
        Route::post('/user-records/verify-password', [UserRecordsController::class, 'verifyPassword'])->name('staff.userrecords.verify-password');

        // Perform suspend/unsuspend action (only called after password verified)
        Route::post('/user-records/{user}/suspend', [UserRecordsController::class, 'suspend'])->name('staff.userrecords.suspend');
        Route::post('/user-records/{user}/unsuspend', [UserRecordsController::class, 'unsuspend'])->name('staff.userrecords.unsuspend');

        Route::get('/staff-records', [StaffRecordsController::class, 'index'])->name('staff.staffrecords.index');
        Route::post('/staff/create', [StaffRecordsController::class, 'createStaff'])->name('staff.create-staff');
        Route::put('/staff/{staff}/update', [StaffRecordsController::class, 'update'])->name('staff.update');

        Route::post('/staff-records/verify-password', [StaffRecordsController::class, 'verifyPassword'])->name('staff.staffrecords.verify-password');

        // Perform suspend/unsuspend action (only called after password verified)
        Route::post('/staff-records/{staff}/suspend', [StaffRecordsController::class, 'suspend'])->name('staff.staffrecords.suspend');
        Route::post('/staff-records/{staff}/unsuspend', [StaffRecordsController::class, 'unsuspend'])->name('staff.staffrecords.unsuspend');
        Route::put('/staff/master-update', [StaffRecordsController::class, 'updateByMasterAdmin'])->name('staff.master-update');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('staff.audit.index');
        Route::get('audit-logs/{id}', [AuditLogController::class, 'show'])->name('staff.audit.show');
        
        Route::get('/manual-booking', [ManualBookingController::class, 'create'])->name('staff.manualbooking');
        Route::post('/manual-booking/store', [ManualBookingController::class, 'store'])->name('staff.manualbooking.store');
        Route::get('/manual-booking/{booking}', [ManualBookingController::class, 'show'])->name('staff.manualbooking.show');
        Route::post('/manual-booking/available-rooms', [ManualBookingController::class, 'getAvailableRoomsAjax'])->name('staff.manualbooking.available');
        
    });


    //Discount Management
        // Discount requests index (list all)
    Route::get('/staff/discounts', [DiscountAdminController::class, 'index'])->name('staff.discounts.index');

    // Single discount review page
    Route::get('/staff/discounts/{discount}', [DiscountAdminController::class, 'show'])->name('staff.discounts.show');

    // Final approve/reject for entire discount request
    Route::post('/staff/discounts/{discount}/approve', [DiscountAdminController::class, 'approve'])->name('staff.discounts.approve');
    Route::post('/staff/discounts/{discount}/reject', [DiscountAdminController::class, 'reject'])->name('staff.discounts.reject');

    // Per-file approve/reject
    Route::post('/staff/discounts/{discount}/file/{file}/approve', [DiscountAdminController::class, 'approveFile'])->name('staff.discounts.file.approve');
    Route::post('/staff/discounts/{discount}/file/{file}/reject', [DiscountAdminController::class, 'rejectFile'])->name('staff.discounts.file.reject');

    //verify password before review
    Route::post('/staff/discounts/verify-password', [DiscountAdminController::class, 'verifyPassword'])->name('staff.discounts.verify-password');

    // Secure preview for file (stream private storage)
    Route::get('/staff/discounts/file/{file}/preview', [DiscountAdminController::class, 'previewFile'])->name('staff.discounts.file.preview');

    //reports group for staff
    Route::prefix('/staff/reports')->group(function () {

        // Bookings Reports
        Route::get('bookings/full', [BookingReportController::class, 'exportFull'])->name('reports.bookings.full');
        Route::get('bookings/paid', [BookingReportController::class, 'exportPaid'])->name('reports.bookings.paid');
        Route::get('bookings/completed', [BookingReportController::class, 'exportCompleted'])->name('reports.bookings.completed');

        // Payments Reports
        Route::get('payments/all', [PaymentReportController::class, 'exportAll'])->name('reports.payments.all');
        Route::get('payments/cash', [PaymentReportController::class, 'exportCash'])->name('reports.payments.cash');
        Route::get('payments/sandbox', [PaymentReportController::class, 'exportSandbox'])->name('reports.payments.sandbox');

         // Users Reports
        Route::get('users/all', [UserReportController::class, 'exportAll'])->name('reports.users.all');
        Route::get('users/active', [UserReportController::class, 'exportActive'])->name('reports.users.active');
        Route::get('users/suspended', [UserReportController::class, 'exportSuspended'])->name('reports.users.suspended');

        // Discounts Reports
        Route::get('discounts/all', [DiscountReportController::class, 'exportAll'])->name('reports.discounts.all');
        Route::get('discounts/pending', [DiscountReportController::class, 'exportPending'])->name('reports.discounts.pending');
        Route::get('discounts/approved', [DiscountReportController::class, 'exportApproved'])->name('reports.discounts.approved');
        Route::get('discounts/rejected', [DiscountReportController::class, 'exportRejected'])->name('reports.discounts.rejected');
        
        Route::get('/view', [MainReportsController::class, 'index'])->name('staff.reports.index');
        Route::post('/generate', [MainReportsController::class, 'generate'])->name('reports.generate');
        Route::post('/export', [MainReportsController::class, 'export'])->name('reports.export');


        // // Central Hub
        // Route::get('central', [CentralHubController::class, 'index'])->name('reports.central');

        // // Central Hub Exports
        // Route::get('central/bookings', [CentralHubController::class, 'exportBookings'])->name('reports.central.bookings');
        // Route::get('central/revenue', [CentralHubController::class, 'exportRevenue'])->name('reports.central.revenue');
        // Route::get('central/occupancy', [CentralHubController::class, 'exportOccupancy'])->name('reports.central.occupancy');
        // Route::get('central/payments', [CentralHubController::class, 'exportPayments'])->name('reports.central.payments');
        // Route::get('central/discounts', [CentralHubController::class, 'exportDiscounts'])->name('reports.central.discounts');

    });

    //testing route for receipt
    Route::get('/test-receipt/{id}', function ($id) {
        $booking = Booking::findOrFail($id);
        $payments = $booking->payments;

        return view('pdf.receipt', compact('booking', 'payments'));
    });
});

/*
|--------------------------------------------------------------------------
| Admin & Front Desk Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:staff')->group(function () {
    Route::post('/staff/logout', function (Request $request) {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/staff/login');
    })->name('staff.logout');

    Route::post('/staff/bookings/verify-password', [BookingHubController::class, 'verifyPassword'])->name('staff.bookings.verify-password');
    Route::get('/staff/rooms/{room}/occupancy', [RoomController::class, 'occupancyForRoom'])->name('staff.rooms.occupancy');
    
    //verify receipt route
    Route::get('/verify-receipt/{number}', [ReceiptController::class, 'verify'])
    ->name('receipts.verify');
});

/*
|--------------------------------------------------------------------------
| Front Desk Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:staff', 'staff.role:frontdesk,master_admin'])
    ->prefix('front-desk')
    ->group(function () {

        Route::get('/dashboard', [FrontDeskDashboardController::class, 'index'])->name('frontdesk.dashboard.index');
        Route::get('/rooms', [FrontDeskRoomController::class, 'index'])->name('frontdesk.room.index');

        Route::get('/create', [WalkInBookingController::class, 'create'])->name('frontdesk.walkin.create');
        Route::post('/store', [WalkInBookingController::class, 'store'])->name('frontdesk.walkin.store');
        Route::get('/booking/{booking}', [WalkInBookingController::class, 'show'])->name('frontdesk.walkin.show');

        Route::post('/available-rooms', [WalkInBookingController::class, 'getAvailableRoomsAjax'])->name('frontdesk.available');

        route::get('/bookings', [BookingsController::class, 'viewBookings'])->name('frontdesk.booking');
        route::post('/booking/{booking}/checkout', [BookingsController::class, 'checkout'])->name('frontdesk.booking.checkout');

});

/*
|--------------------------------------------------------------------------
| Email Verification Routes (Auth but not verified yet)
|--------------------------------------------------------------------------
*/

// Verification notice
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

// Verify via link
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill(); // updates email_verified_at
    return redirect('/checkout'); // redirect after verification
})->middleware(['auth', 'signed'])->name('verification.verify');

// Resend verification email
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

/*--------------------------------------------
----------------------------------------------
-------------- TEMPORARY ROUTES --------------
----------------------------------------------
----------------------------------------------
*/
//fake sandbox routes
Route::get('/booking/{booking}/pay', [PaymentController::class, 'pay'])->name('bookings.pay');

Route::prefix('sandbox')->name('sandbox.')->group(function () {
    Route::get('/pay/{payment}', [SandboxGatewayController::class, 'showPaymentPage'])->name('pay');
    Route::post('/process/{payment}', [SandboxGatewayController::class, 'processPayment'])->name('process');
    Route::get('/result/{status}/{payment}', [SandboxGatewayController::class, 'result'])->name('result');
    Route::get('/status/{payment}', [SandboxGatewayController::class, 'status'])->name('status');
});

//sandbox route for testing
Route::post('sandbox/webhook/{payment}', [SandboxGatewayController::class, 'webhook'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('sandbox.webhook');


    
