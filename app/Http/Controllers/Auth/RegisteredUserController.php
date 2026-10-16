<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $departments = config('lingolens.departments');
        $schoolForms = array_values(array_diff(config('lingolens.school_forms'), ['AHS']));
        $requiresCode = filled(config('lingolens.registration_code'));

        return view('auth.register', compact('departments', 'schoolForms', 'requiresCode'));
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create($request->profile());

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
