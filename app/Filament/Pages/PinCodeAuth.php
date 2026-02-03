<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Filament\Notifications\Notification;

class PinCodeAuth extends Page
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.pin-code-auth';

    public $pin_code = '';

    public function mount()
    {
        if (Session::has('active_staff_id')) {
            return redirect()->to('/admin');
        }
    }
public static function shouldRegisterNavigation(): bool
{
    // This page should be hit via middleware redirect, not the menu
    return false; 
}
    public function verify()
    {
        $staff = User::where('pin_code', $this->pin_code)->first();

        if (!$staff) {
            Notification::make()->title('Invalid PIN')->danger()->send();
            return;
        }

        Session::put([
            'active_staff_id' => $staff->id,
            'active_staff_name' => $staff->name,
            'active_staff_role' => $staff->roles->pluck('name')->first(),
        ]);

        Notification::make()->title("Welcome {$staff->name}")->success()->send();

        return redirect()->intended(url('/admin'));
    }

    public function logoutMaster()
    {
        auth()->logout();
        Session::flush();
        return redirect()->to(filament()->getLoginUrl());
    }
}
