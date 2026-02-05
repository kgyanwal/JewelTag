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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4)
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
                            ->placeholder('Dept Name')
                            ->disableLabel(),
                        
                        TextInput::make('multiplier')
                            ->label('Markup')
                            ->numeric()
                            ->default(7.0)
                            ->visible($key === 'departments') 
                            ->placeholder('e.g. 7.0')
                            ->suffix('x'),
                    ])
                    ->columns($key === 'departments' ? 2 : 1)
                    ->reorderableWithButtons()
                    ->addActionLabel("Add $label")
                    ->collapsible()
                    ->defaultItems(0),
            ])->columnSpan(1);
    }

    public function mount(): void
    {
        $settings = \App\Models\InventorySetting::pluck('value', 'key');

        $this->form->fill([
            'departments' => $settings->get('departments', []), 
            'sub_departments' => collect($settings->get('sub_departments', []))->map(fn($item) => ['name' => $item])->toArray(),
            'categories' => collect($settings->get('categories', []))->map(fn($item) => ['name' => $item])->toArray(),
            'metal_types' => collect($settings->get('metal_types', []))->map(fn($item) => ['name' => $item])->toArray(),
        ]);
    }

    /**
     * 🔹 FIX: Define the save action so it can be called from the Blade view
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save Settings'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        \App\Models\InventorySetting::updateOrCreate(
            ['key' => 'departments'],
            ['value' => $state['departments']]
        );

        foreach (['sub_departments', 'categories', 'metal_types'] as $key) {
            \App\Models\InventorySetting::updateOrCreate(
                ['key' => $key],
                ['value' => collect($state[$key])->pluck('name')->filter()->toArray()]
            );
        }

        Cache::forget('inventory_departments'); 
        
        Notification::make()->title('Pricing & Inventory Settings Saved')->success()->send();
    }
}