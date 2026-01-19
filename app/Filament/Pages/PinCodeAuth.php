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
    
    // 🔹 These two lines remove the sidebar and topbar from the lock screen
    protected static ?string $navigationIcon = null;
   

    public $pin_code;

    public function mount()
    {
        if (session('pin_verified')) {
            return redirect(request('next', '/admin'));
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('pin_code')
                    ->label('POS PIN')
                    ->password()
                    ->required()
                    ->numeric()
                    ->length(4)
                    ->extraInputAttributes([
                        'autofocus' => true,
                        'class' => 'text-center text-2xl tracking-widest'
                    ])
            ]);
    }

    public function verify()
    {
        if ($this->pin_code == auth()->user()->pin_code) {
            session(['pin_verified' => true]);
            return redirect(request('next', '/admin'));
        }

        Notification::make()->title('Invalid PIN')->danger()->send();
        $this->pin_code = null;
    }
}