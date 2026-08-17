<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforcePlanLimits
{
    public function handle(Request $request, Closure $next)
    {
        if (!function_exists('tenant') || !tenant()) {
            return $next($request);
        }

        // tenant() IS the current Tenant model already — no re-query needed
        $tenantModel = tenant();
        $tenantModel->loadMissing('plan'); // relation defined on central connection

        if (!$tenantModel->plan) {
            return $next($request);
        }

        if ($tenantModel->plan_status === 'suspended') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Account suspended. Please contact support.'], 403);
            }
            return redirect()->route('suspended');
        }

        if ($tenantModel->plan_status === 'trial'
            && $tenantModel->trial_ends_at
            && $tenantModel->trial_ends_at->isPast()) {
            $tenantModel->update([
                'plan_status'       => 'suspended',
                'suspended_at'      => now(),
                'suspension_reason' => 'Trial expired',
            ]);
            return redirect()->route('trial-expired');
        }

        return $next($request);
    }
}