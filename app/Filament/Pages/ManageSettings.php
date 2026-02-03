<?php

namespace App\Filament\Pages;

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
    protected static ?string $navigationGroup = 'Administration';
    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        // Load existing tax rate from DB or use 7.63 as default
        $taxRate = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? '7.63';
        
        $this->form->fill([
            'tax_rate' => $taxRate,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Financial Configuration')
                    ->schema([
                        TextInput::make('tax_rate')
                            ->label('Default Sales Tax (%)')
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                    ])
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

        // Save to Database
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'tax_rate'],
            ['value' => $state['tax_rate'], 'updated_at' => now()]
        );

        Notification::make()->title('Settings Updated')->success()->send();
    }
}