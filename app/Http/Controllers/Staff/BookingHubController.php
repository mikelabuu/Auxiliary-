<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class BookingHubController extends Controller
{
    public function index()
    {
        return view('staff.bookings.index'); // renders the Blade wrapper
    }
    public function verifyPassword(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $staff = Auth::guard('staff')->user();

        if (! $staff || ! Hash::check($request->password, $staff->password)) {
            return response()->json(['success' => false, 'message' => 'Incorrect password'], 422);
        }

        return response()->json(['success' => true]);
    }
}

