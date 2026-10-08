<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
  public function register(): void
{
    $this->hideSensitiveRequestDetails();

    // Capture everything in local
    Telescope::filter(function (IncomingEntry $entry) {
        return app()->environment('local');
    });
}

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
   protected function gate(): void
{
    Gate::define('viewTelescope', function ($user) {
        if (app()->environment('local')) {
            return true; // open for everyone locally
        }
        return in_array($user->email, ['nabin@gmail.com']);
    });
}
}
