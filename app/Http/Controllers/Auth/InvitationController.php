<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Link from the invitation e-mail: set the first password, confirm privacy, start.
 */
class InvitationController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $email = (string) $request->query('email', '');

        return view('auth.accept-invitation', compact('token', 'email'));
    }

    public function store(AcceptInvitationRequest $request): RedirectResponse
    {
        $status = Password::broker('invitations')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'privacy_accepted_at' => now(),
                ])->save();

                event(new PasswordReset($user));
                Auth::login($user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $message = $status === Password::INVALID_TOKEN
                ? 'Der Einladungslink ist ungültig oder abgelaufen. Bitte die Administration, dir eine neue Einladung zu schicken.'
                : __($status);

            return back()->withInput($request->only('email'))->withErrors(['email' => $message]);
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Willkommen bei LingoLens AI! Dein Passwort ist gespeichert.');
    }
}
