<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class PinCodeAuth extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.pin-code-auth';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'pin-code-auth';
    
    protected static ?string $navigationIcon = null;

    public $pin_code;

    public function mount()
    {
        // Check if PIN is already verified
        if (session()->get('pin_verified')) {
            return redirect(request('next', '/admin'));
        }
    }

    public function verify()
    {
        // Validate PIN
        $userPin = auth()->user()->pin_code;
        
        // Check if PIN matches
        if ($this->pin_code == $userPin) {
            session(['pin_verified' => true]);
            return redirect(request('next', '/admin'));
        }

        // Send error notification
        Notification::make()
            ->title('Invalid PIN')
            ->body('Please enter the correct 4-digit PIN')
            ->danger()
            ->send();
            
        // Clear the PIN field
        $this->pin_code = '';
        
        // Don't redirect - stay on page
        return null;
    }
}