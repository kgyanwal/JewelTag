<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\License;

class CheckLicense
{
    public function handle(Request $request, Closure $next)
    {
        // If not in a tenant context, ignore and let them through
        if (!tenant()) {
            return $next($request);
        }

        // Query the central DB for this tenant's license
        $license = License::where('tenant_id', tenant('id'))->first();

        // 1. Missing License
        if (!$license) {
            return response()->view('filament.license-expired', [
                'reason' => 'No license record found for this store.',
                'tenant' => tenant('id'),
            ], 403);
        }

        // 2. Invalid or Expired License
        if (!$license->isValid()) {
            $reason = $license->status === 'suspended'
                ? 'Your license has been suspended. Please contact JewelTag support.'
                : 'Your CRM license expired on ' . $license->expires_at->format('M d, Y') . '.';
                
            return response()->view('filament.license-expired', [
                'reason' => $reason,
                'plan'   => $license->plan,
                'tenant' => tenant('id'),
            ], 403);
        }

        return $next($request);
    }
}