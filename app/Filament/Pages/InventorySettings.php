<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
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
        // We transform the flat array from Cache into a format the Repeater understands
        $this->form->fill([
            'departments' => collect(Cache::get('inventory_departments', []))->map(fn($item) => ['name' => $item])->toArray(),
            'categories' => collect(Cache::get('inventory_categories', []))->map(fn($item) => ['name' => $item])->toArray(),
            'metal_types' => collect(Cache::get('inventory_metal_types', []))->map(fn($item) => ['name' => $item])->toArray(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3) // Three columns for side-by-side tables
                    ->schema([
                        $this->getTableSection('departments', 'Departments'),
                        $this->getTableSection('categories', 'Categories'),
                        $this->getTableSection('metal_types', 'Metal Types'),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Helper to create the tabular Repeater section
     */
    protected function getTableSection(string $key, string $label): Section
    {
        return Section::make($label)
            ->compact()
            ->schema([
                Repeater::make($key)
                    ->label('')
                    ->schema([
                        TextInput::make('name')
                            ->label('Option Name')
                            ->required()
                            ->distinct() // Prevents duplicate names in the same list
                            ->disableLabel(),
                    ])
                    ->reorderableWithButtons() // Allows moving items up/down
                    ->addActionLabel("Add New $label")
                    ->collapsible()
                    ->defaultItems(0),
            ])->columnSpan(1);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
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

        // Convert the Repeater arrays back into flat lists for the rest of the app to use
        Cache::forever('inventory_departments', collect($state['departments'])->pluck('name')->filter()->toArray());
        Cache::forever('inventory_categories', collect($state['categories'])->pluck('name')->filter()->toArray());
        Cache::forever('inventory_metal_types', collect($state['metal_types'])->pluck('name')->filter()->toArray());

        Notification::make()
            ->title('Settings Saved')
            ->body('Tabular lists have been updated.')
            ->success()
            ->send();
    }
}