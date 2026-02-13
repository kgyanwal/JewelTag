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

        // Only log GET requests (Views) for logged-in users
        // Your existing Trait already handles Creates/Updates, so we skip POST/PUT here to avoid duplicates.
        if (Auth::check() && $request->isMethod('GET')) {
            
            // Ignore internal Filament assets/updates so we don't spam the log
            if ($request->is('livewire/*', 'filament-assets/*', '_debugbar/*')) {
                return $response;
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'View', // Matches your screenshot action
                'module' => $this->getModuleName($request),
                'identifier' => $request->route('record') ?? 'Page Visit',
                'url' => $request->path(), // Captures "/utilities" or "/sales"
                'ip_address' => $request->ip(),
            ]);
        }

        return $response;
    }

    private function getModuleName(Request $request)
    {
        // Converts "admin/sales" to "Sales"
        $segments = $request->segments();
        return isset($segments[1]) ? ucfirst($segments[1]) : 'General';
    }
}