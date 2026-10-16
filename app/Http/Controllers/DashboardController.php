<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatistics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Progress centre: hero, Matura-Ready-Score, error distribution, focus and latest scans.
     */
    public function index(Request $request, DashboardStatistics $statistics): View
    {
        $user = $request->user();
        $dashboard = $statistics->forUser($user);

        return view('dashboard', compact('user', 'dashboard'));
    }
}
