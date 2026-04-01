<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\AlumniMaintenanceService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAlumniNotInMaintenance
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isAlumni()) {
            return $next($request);
        }

        $maintenance = app(AlumniMaintenanceService::class);

        if (! $maintenance->isActive()) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('maintenance.alumni');
    }
}
