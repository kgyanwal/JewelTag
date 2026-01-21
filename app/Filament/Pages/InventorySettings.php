<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Placeholder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class InventorySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Inventory';
    protected static string $view = 'filament.pages.inventory-settings';

    public ?array $data = []; 

    public function mount(): void
    {
        $this->form->fill([
            'departments' => Cache::get('inventory_departments', []),
            'categories' => Cache::get('inventory_categories', []),
            'metal_types' => Cache::get('inventory_metal_types', []),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Global Inventory Options')
                    ->description('Manage your dropdown lists. To delete, click the (x). To add, type and press Enter.')
                    ->schema([
                        TagsInput::make('departments')
                            ->label('Departments List')
                            ->placeholder('New Department...')
                            ->helperText('Current: ' . implode(', ', Cache::get('inventory_departments', [])))
                            ->required(),

                        TagsInput::make('categories')
                            ->label('Categories List')
                            ->placeholder('New Category...')
                            ->helperText('Current: ' . implode(', ', Cache::get('inventory_categories', [])))
                            ->required(),

                        TagsInput::make('metal_types')
                            ->label('Metal Types List')
                            ->placeholder('New Metal Type...')
                            ->helperText('Current: ' . implode(', ', Cache::get('inventory_metal_types', [])))
                            ->required(),
                    ])->columns(1), // Stacked for better visibility of long lists
            ])
            ->statePath('data'); 
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Update Lists')
                ->submit('save')
                ->color('success')
                ->icon('heroicon-m-check-circle'),
                
            Action::make('clear')
                ->label('Reset All')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    Cache::forget('inventory_departments');
                    Cache::forget('inventory_categories');
                    Cache::forget('inventory_metal_types');
                    $this->mount();
                    Notification::make()->title('All lists cleared')->warning()->send();
                }),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        
        Cache::forever('inventory_departments', $state['departments']);
        Cache::forever('inventory_categories', $state['categories']);
        Cache::forever('inventory_metal_types', $state['metal_types']);

        Notification::make()
            ->title('Inventory Lists Updated')
            ->body('Changes are now live in the Assemble Stock dropdowns.')
            ->success()
            ->send();
    }
}