<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Rules\PersonName;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
        //
        // The name fields were 'string|max:255', which is not a name check at
        // all: "123" passed as a middle initial, and a 200-character run of
        // punctuation passed as a surname. PersonName is the rule every
        // booking form already uses (letters, spaces and . , ' -), so a guest
        // is validated the same way here as at checkout.
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:60', new PersonName],
            // One letter, optionally written with a full stop. The field is a
            // single initial — the form has said so with maxlength="2" all
            // along, and only the browser was enforcing it.
            'middle_initial' => ['required', 'string', 'max:2', 'regex:/^\p{L}\.?$/u'],
            'last_name' => ['required', 'string', 'max:60', new PersonName],
            'username' => ['required', 'string', 'min:3', 'max:30', 'alpha_num', 'unique:users,username'],
            'email'    => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                // One login serves both guests and staff, so an address can
                // only mean one person. Without this, registering with a staff
                // address would create an account nobody could ever sign into
                // — staff takes precedence at the login.
                Rule::unique('staff', 'email'),
            ],
            // bcrypt silently ignores anything past the 72nd byte, so a longer
            // password would not be the password on the account.
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            // 'required' passes on any truthy value; 'accepted' is the rule
            // that actually means a checkbox was ticked.
            'terms'    => ['accepted'],
        ], [
            'email.unique' => 'That email address is already registered.',
            'middle_initial.regex' => 'Middle initial must be a single letter — no numbers or symbols.',
            'terms.accepted' => 'Please agree to the Terms of Use and Privacy Policy.',
        ]);

        $full_name = implode(', ', [
            $validated['last_name'],
            $validated['first_name'],
            // Stored as the bare letter so every record reads the same way,
            // whether the guest typed "a", "A" or "A.".
            Str::upper(rtrim($validated['middle_initial'], '.')),
        ]);

        // Create the user
        User::create([
            'full_name' => $full_name,
            'username' => $validated['username'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Registration does not sign anyone in, so the next step is the login
        // form — not the landing page, which sent a brand-new guest back to
        // the room search still logged out with nothing to show for it.
        // 'status' is the key the auth board's notes partial renders.
        return redirect()->route('login')
            ->with('status', 'Your account is ready. Sign in to continue.');
    }
}
