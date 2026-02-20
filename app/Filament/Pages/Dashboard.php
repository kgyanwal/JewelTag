<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    // Keep navigation label working
    protected static ?string $navigationLabel = 'Dashboard';

    // Remove the large page heading
    public function getHeading(): string | Htmlable
    {
        return '';
    }
}