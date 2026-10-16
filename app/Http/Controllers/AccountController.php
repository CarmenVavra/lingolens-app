<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Account page: profile overview and password change.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('account.show', compact('user'));
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return redirect()->route('account.show')->with('status', 'Dein Passwort wurde geändert.');
    }
}
