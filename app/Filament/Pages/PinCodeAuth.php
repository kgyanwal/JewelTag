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
        // If already pinned in, go to dashboard
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
    // Ensure we only find ACTIVE staff with this PIN
    $staff = User::where('pin_code', $this->pin_code)
        ->where('is_active', true)
        ->first();

    if (!$staff) {
        Notification::make()->title('Invalid PIN')->danger()->send();
        $this->pin_code = '';
        return;
    }

    // 🔹 FIX: Safely get the role name. If no role, default to 'Staff'
    // This prevents the 500 error if Spatie roles aren't loaded yet.
    $roleName = optional($staff->roles->first())->name ?? 'Staff';

    Session::put([
        'active_staff_id' => $staff->id,
        'active_staff_name' => $staff->name,
        'active_staff_role' => $roleName,
    ]);

    Notification::make()->title("Welcome {$staff->name}")->success()->send();

    // Use a hard-coded fallback for the redirect to ensure it works
    return redirect()->intended(url('/admin'));
}

    public function logoutMaster()
    {
        auth()->logout();
        Session::flush();
        return redirect()->to(filament()->getLoginUrl());
    }
}