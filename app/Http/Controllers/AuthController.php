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
            'email' => 'required',
            'password' => 'required',
        ]);

        // Attempt login
        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            $user = Auth::user();

            // Check if suspended
            if ($user->is_suspended) {
                Auth::logout();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been suspended. Please contact support.'
                ])->onlyInput('email');
            }

            // Update last login timestamp
            $user->update(['last_login_at' => now()]);

            $request->session()->regenerate();
            return redirect()->intended('/booking');
        } 

        return back()->withErrors([
            'email' => 'Invalid email or password',
        ])->onlyInput('email');
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
            'first_name' => 'required|string|max:255',
            'middle_initial' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|min:3|alpha_num|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'terms'    => 'required'
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
