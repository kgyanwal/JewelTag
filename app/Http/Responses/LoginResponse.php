<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\LoginResponse as FilamentLoginResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse extends FilamentLoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        // Check if Laravel captured a deep-link target before sending the user to login
        if (session()->has('url.intended')) {
            return redirect()->intended();
        }

        // Fallback to Filament's default redirection behavior if no specific URL was intended
        return parent::toResponse($request);
    }
}