<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class LogPageViews
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // 1. Check if user is logged in (using Filament-friendly check)
        $user = Auth::user();

        if ($user && $request->isMethod('GET')) {
            
            // 2. Ignore internal noise
            if ($request->is('livewire/*', 'filament-assets/*', '_debugbar/*', 'sanctum/*')) {
                return $response;
            }

            try {
                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'View',
                    'module' => $this->getModuleName($request),
                    // Pull record ID from URL if it exists (e.g., /admin/customers/5/edit)
                    'identifier' => $request->route('record') ?? $request->route('id'),
                    'url' => '/' . $request->path(),
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Activity Log Failed: ' . $e->getMessage());
            }
        }

        return $response;
    }

    private function getModuleName(Request $request)
    {
        $segments = $request->segments();
        // Index 0 is 'admin', index 1 is usually the resource (e.g., 'customers')
        if (isset($segments[1])) {
            return ucfirst(str_replace('-', ' ', $segments[1]));
        }
        return 'Dashboard';
    }
}