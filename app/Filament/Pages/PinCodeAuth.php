<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use Filament\Notifications\Notification;

class PinCodeAuth extends Page
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.pin-code-auth';
    protected static ?string $title = 'Staff Terminal Login';

    public $pin_code = '';

   public function mount()
{
    // 🚀 THE FIX: If 'switch' is in the URL, clear the staff session 
    // so the associate can enter a NEW pin.
    if (request()->has('switch')) {
        Session::forget(['active_staff_id', 'active_staff_name', 'active_staff_role', 'pin_verified_at']);
        return; 
    }

    // Otherwise, standard behavior: if already pinned in, go to dashboard
    if (Session::has('active_staff_id')) {
        return redirect()->to('/admin');
    }
}

    public static function shouldRegisterNavigation(): bool
    {
        return false; 
    }

public function verify()
{
    $staff = User::where('pin_code', $this->pin_code)
        ->where('is_active', true)
        ->first();

    if (!$staff) {
        $this->pin_code = ''; // Clear input on failure
        Notification::make()->title('Invalid PIN')->danger()->send();
        return;
    }

    // 1. 🚀 CRITICAL FIX: Clear the old staff data first
    Session::forget(['active_staff_id', 'active_staff_name', 'active_staff_role', 'pin_verified_at']);
    
    // 2. 🚀 CRITICAL FIX: Regenerate the session ID 
    // This prevents "sticky" session data from the previous associate
    Session::regenerate();

    // 3. Put the NEW associate data
    Session::put([
        'active_staff_id' => $staff->id,
        'active_staff_name' => $staff->name,
        'active_staff_role' => optional($staff->roles->first())->name ?? 'Staff',
        'pin_verified_at' => now(), 
    ]);

    Notification::make()
        ->title("Terminal Session: {$staff->name}")
        ->success()
        ->send();

    // Use a hard redirect to ensure the middleware picks up the new session immediately
    return redirect()->to(url('/admin/sales/create'));
}

    public function logoutMaster()
    {
        auth()->logout();
        Session::flush();
        return redirect()->to(filament()->getLoginUrl());
    }
}