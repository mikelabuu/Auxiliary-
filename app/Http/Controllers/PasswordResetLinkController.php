<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesPasswordBroker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    use ResolvesPasswordBroker;

    public function create()
    {
        return view('public.auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // One form serves guests and staff, exactly as the login does, so the
        // address decides which broker answers. Sent through the default
        // broker this only ever searched the guest table, which is why no
        // staff account could recover a password — see ResolvesPasswordBroker.
        $status = $this->brokerFor($request->input('email'))->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
