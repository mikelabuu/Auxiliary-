<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // Show login page
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Handle login post
    public function loginUser(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Attempt login
        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']])) {
            $user = Auth::user();

            // Check if suspended
            if ($user->is_suspended) {
                Auth::logout();
                return redirect()->route('login')->withErrors([
                    'username' => 'Your account has been suspended. Please contact support.'
                ])->onlyInput('username');
            }

            // Update last login timestamp
            $user->update(['last_login_at' => now()]);

            $request->session()->regenerate();
            return redirect()->intended('/booking');
        } 

        return back()->withErrors([
            'username' => 'Invalid username or password',
        ])->onlyInput('username');
    }

    // Show signup page
    public function showSignupForm()
    {
        return view('auth.signup');
    }

    // Handle signup post
    public function signup(Request $request){
        
        // Validate input data
        $request->validate([
            'username' => 'required|min:3|alpha_num|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        // Create the user
        $user = User::create([
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Redirect to your desired page after signup
        return redirect('/')->with('success', 'Registration successful');
    }
}
