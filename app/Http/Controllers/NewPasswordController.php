<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesPasswordBroker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class NewPasswordController extends Controller
{
    use ResolvesPasswordBroker;

    /**
     * The page the emailed link lands on.
     *
     * `$email` was never passed, while the view has always read
     * `old('email', $email)` — so this route threw an undefined-variable
     * ErrorException on every single request. With APP_DEBUG on that showed as
     * a stack trace and looked like a glitch; with it off, as it must be in
     * production, it is a bare 500. Either way the reset link in the email went
     * nowhere, which is why nobody had noticed the broker was wrong underneath
     * it: the page in front of it never worked either.
     *
     * Laravel's ResetPassword notification builds the URL with the address
     * attached (`?email=…`), which is where this reads it from.
     */
    public function create(Request $request, string $token)
    {
        return view('public.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(Request $request)
    {
        // A staff account resets to the staff floor, not the guest one.
        // StaffRecordsController asks for 10 because these accounts reach the
        // console; a reset that quietly accepted 8 would be the way around it.
        $isStaff = $this->isStaffAddress($request->input('email'));

        $request->validate([
            'email'                 => ['required', 'email'],
            // Matches the floor at signup. `max:72` because bcrypt silently
            // ignores anything beyond it — a longer password would not be the
            // password the reset actually set.
            'password'              => ['required', 'min:' . ($isStaff ? 10 : 8), 'max:72', 'confirmed'],
            'token'                 => ['required'],
        ], [
            'password.min' => $isStaff
                ? 'A staff password must be at least 10 characters.'
                : 'The password must be at least 8 characters.',
        ]);

        $status = $this->brokerFor($request->input('email'))->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
