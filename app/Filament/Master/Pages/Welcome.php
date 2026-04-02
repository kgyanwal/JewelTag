<?php

namespace App\Filament\Master\Pages;

use Filament\Pages\Page;

class Welcome extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.master.pages.welcome';
    public static function shouldRegisterNavigation(): bool
{
    return false; // Hide from sidebar
}

public function getHeader(): ?\Illuminate\Contracts\View\View
{
    return view('welcome'); // This loads your welcome.blade.php
}
}
