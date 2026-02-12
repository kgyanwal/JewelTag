<?php

namespace App\Filament\Pages;

use App\Helpers\Staff;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Admin';
    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        // Load existing settings from DB
        $taxRate = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? '7.63';
        $barcodePrefix = DB::table('site_settings')->where('key', 'barcode_prefix')->value('value') ?? 'D';
        
        $this->form->fill([
            'tax_rate' => $taxRate,
            'barcode_prefix' => $barcodePrefix,
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $staff = Staff::user();
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Global Configuration')
                    ->schema([
                        TextInput::make('tax_rate')
                            ->label('Default Sales Tax (%)')
                            ->numeric()
                            ->suffix('%')
                            ->required(),

                        TextInput::make('barcode_prefix')
                            ->label('Stock Number Prefix')
                            ->placeholder('e.g., D, G, R')
                            ->maxLength(3)
                            ->required()
                            ->helperText('This character will begin all new stock numbers (e.g., D1001).'),
                    ])->columns(2)
            ])->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save')
                ->color('primary'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Update Tax Rate
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'tax_rate'],
            ['value' => $state['tax_rate'], 'updated_at' => now()]
        );

        // Update Barcode Prefix
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'barcode_prefix'],
            ['value' => strtoupper($state['barcode_prefix']), 'updated_at' => now()]
        );

        Notification::make()->title('Settings Updated')->success()->send();
    }
}