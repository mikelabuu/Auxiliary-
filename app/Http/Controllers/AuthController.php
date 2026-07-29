<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Guest registration. Signing in — for guests and staff alike — lives in
 * LoginController.
 */
class AuthController extends Controller
{
    // Handle signup post
    public function signup(Request $request){

        // Validate input data
        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_initial' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|min:3|alpha_num|unique:users,username',
            'email'    => [
                'required',
                'email',
                'unique:users,email',
                // One login serves both guests and staff, so an address can
                // only mean one person. Without this, registering with a staff
                // address would create an account nobody could ever sign into
                // — staff takes precedence at the login.
                Rule::unique('staff', 'email'),
            ],
            'password' => 'required|min:6|confirmed',
            'terms'    => 'required'
        ], [
            'email.unique' => 'That email address is already registered.',
        ]);

        $full_name = implode(', ', [
            $request->last_name,
            $request->first_name,
            $request->middle_initial,
        ]);

        // Create the user
        $user = User::create([
            'full_name' => $full_name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Redirect to your desired page after signup
        return redirect('/')->with('success', 'Registration successful');
    }
}
