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
        $this->form->fill([
            'departments' => collect(Cache::get('inventory_departments', []))->map(fn($item) => ['name' => $item])->toArray(),
            'sub_departments' => collect(Cache::get('inventory_sub_departments', []))->map(fn($item) => ['name' => $item])->toArray(),
            'categories' => collect(Cache::get('inventory_categories', []))->map(fn($item) => ['name' => $item])->toArray(),
            'metal_types' => collect(Cache::get('inventory_metal_types', []))->map(fn($item) => ['name' => $item])->toArray(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4) // Increased to 4 columns for the new list
                    ->schema([
                        $this->getTableSection('departments', 'Departments'),
                        $this->getTableSection('sub_departments', 'Sub-Departments'),
                        $this->getTableSection('categories', 'Categories'),
                        $this->getTableSection('metal_types', 'Metal Karat'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getTableSection(string $key, string $label): Section
    {
        return Section::make($label)
            ->compact()
            ->schema([
                Repeater::make($key)
                    ->label('')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->distinct()
                            ->disableLabel(),
                    ])
                    ->reorderableWithButtons()
                    ->addActionLabel("Add $label")
                    ->collapsible()
                    ->defaultItems(0),
            ])->columnSpan(1);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Cache::forever('inventory_departments', collect($state['departments'])->pluck('name')->filter()->toArray());
        Cache::forever('inventory_sub_departments', collect($state['sub_departments'])->pluck('name')->filter()->toArray());
        Cache::forever('inventory_categories', collect($state['categories'])->pluck('name')->filter()->toArray());
        Cache::forever('inventory_metal_types', collect($state['metal_types'])->pluck('name')->filter()->toArray());

        Notification::make()->title('Settings Saved')->success()->send();
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

}