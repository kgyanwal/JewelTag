<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromTenantDomains;
use App\Models\Tenant;
use App\Models\Plan;

$centralDomains = ['localhost', '127.0.0.1', 'jeweltag.us', 'www.jeweltag.us'];

foreach ($centralDomains as $domain) {
    Route::domain($domain)->middleware(['web'])->group(function () {

        // Landing page
        Route::get('/', function () {
            return view('welcome'); // Central landing page
        });

        // Filament Welcome Page route
        Route::get('/welcome-page', function () {
            return \App\Filament\Master\Pages\Welcome::render();
        });

        // Master Login
        Route::get('/master-login', function () {
            return redirect('/master/login');
        });

        // Create Store
        Route::middleware([PreventAccessFromTenantDomains::class])->group(function () {
            Route::get('/create-store/{store_name}', function ($store_name) {
                $proPlan = Plan::where('slug', 'pro')->first();

                $tenant = Tenant::create([
                    'id'            => $store_name,
                    'plan_id'       => $proPlan?->id,
                    'plan_status'   => 'trial',
                    'trial_ends_at' => now()->addDays(3),
                ]);

                $baseDomain = app()->isLocal() ? 'localhost' : 'jeweltag.us';
                $fullDomain = $store_name . '.' . $baseDomain;
                $tenant->domains()->create(['domain' => $fullDomain]);

                return "Success! Store '{$store_name}' created on the Pro plan with a 3-day trial (expires {$tenant->trial_ends_at->format('M j, Y g:i A')}).";
            });
        });
    });
}

Route::get('/google-test', function () {

    $address = "31-00 47th Avenue, Long Island City, NY";

    $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
        'address' => $address,
        'key' => config('services.google.key'),
    ]);

    return $response->json();
});

Route::post('/contact', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Mail::raw(
        "Name: {$request->name}\nEmail: {$request->email}\nBusiness: {$request->business}\nType: {$request->type}\n\nMessage:\n{$request->message}",
        function ($message) use ($request) {
            $message->to('info@jeweltag.us')
                    ->subject("JewelTag Inquiry from {$request->business}")
                    ->replyTo($request->email);
        }
    );
    return response()->json(['success' => true]);
});

Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/documentation', [PageController::class, 'documentation'])->name('docs');
Route::get('/api-reference', [PageController::class, 'apiReference'])->name('api');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

// Plan-limit redirect targets used by EnforcePlanLimits middleware
Route::get('/suspended', fn () => view('errors.suspended'))->name('suspended');
Route::get('/trial-expired', fn () => view('errors.trial-expired'))->name('trial-expired');