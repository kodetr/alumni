<?php

namespace App\Http\Controllers;

use App\Support\AlumniMaintenanceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AlumniMaintenanceController extends Controller
{
    public function show(AlumniMaintenanceService $maintenance): Response|RedirectResponse
    {
        $status = $maintenance->status();

        if (! $status['enabled']) {
            return redirect()->route('login');
        }

        return Inertia::render('Maintenance/Alumni', [
            'maintenance' => $status,
        ]);
    }
}
