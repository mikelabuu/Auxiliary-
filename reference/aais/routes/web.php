<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('aais.home');
})->name('aais.home');

Route::prefix('admin')->name('aais.admin.')->group(function () {
    Route::view('/dashboard', 'aais.dashboard')->name('dashboard');
    Route::view('/transactions', 'aais.transactions')->name('transactions');
    Route::view('/portal', 'aais.portal')->name('portal');
    Route::view('/reports', 'aais.reports')->name('reports');
});

Route::prefix('client')->name('aais.client.')->group(function () {
    Route::view('/kiosk', 'aais.kiosk')->name('kiosk');
    Route::view('/tracker', 'aais.tracker')->name('tracker');
});

Route::redirect('/dashboard', '/admin/dashboard');
Route::redirect('/portal', '/admin/portal');
Route::redirect('/reports', '/admin/reports');
Route::redirect('/kiosk', '/client/kiosk');
Route::redirect('/tracker', '/client/tracker');
