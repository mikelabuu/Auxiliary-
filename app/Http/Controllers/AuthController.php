<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    // Handle login post
    public function loginUser(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // This endpoint had no throttling at all, which made every customer
        // account brute-forceable. Mirrors the staff limiter: 5 tries, then a
        // 15-minute cooldown, keyed on email + IP so one attacker can't lock
        // out unrelated guests.
        $email = strtolower($credentials['email']);
        $key = 'login-attempt:user:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        // Attempt login
        if (Auth::attempt(['email' => $email, 'password' => $credentials['password']])) {
            $user = Auth::user();

            // Check if suspended
            if ($user->is_suspended) {
                Auth::guard('web')->logout();
                // logout() alone leaves the session record intact; without
                // this the suspended user keeps a usable session cookie.
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been suspended. Please contact support.'
                ])->onlyInput('email');
            }

            RateLimiter::clear($key);

            // Update last login timestamp
            $user->update(['last_login_at' => now()]);

            $request->session()->regenerate();
            return redirect()->intended('/checkout');
        }

        RateLimiter::hit($key, 900);

        return back()->withErrors([
            'email' => 'Invalid email or password',
        ])->onlyInput('email');
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
