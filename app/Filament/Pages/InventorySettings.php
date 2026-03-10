<?php

namespace App\Filament\Pages;

use App\Models\InventorySetting;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// 🚀 IMPORT THESE FOR TABLE SUPPORT
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;

class InventorySettings extends Page implements HasTable // 🚀 IMPLEMENT THIS
{
    use InteractsWithTable; // 🚀 USE THIS

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $title = 'Inventory Settings';
    protected static string $view = 'filament.pages.inventory-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = InventorySetting::pluck('value', 'key');

        $this->form->fill([
            'departments' => $settings->get('departments', []), 
            'sub_departments' => collect($settings->get('sub_departments', []))->map(fn($item) => ['name' => $item])->toArray(),
            'categories' => collect($settings->get('categories', []))->map(fn($item) => ['name' => $item])->toArray(),
            'metal_types' => collect($settings->get('metal_types', []))->map(fn($item) => ['name' => $item])->toArray(),
        ]);
    }

    /**
     * 🚀 FIX THE PROPERTY NOT FOUND ERROR
     * This method defines the table that the Blade file is looking for.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(InventorySetting::query())
            ->columns([
                TextColumn::make('key')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->weight('bold'),
                TextColumn::make('value')
                    ->label('Values')
                    ->badge()
                    ->getStateUsing(function (InventorySetting $record) {
                        // Logic to handle different value formats in the table preview
                        if ($record->key === 'departments') {
                            return collect($record->value)->pluck('name')->toArray();
                        }
                        return $record->value;
                    }),
            ])
            ->paginated(false);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        $this->generateTab('departments', 'Departments', 'heroicon-o-building-office'),
                        $this->generateTab('sub_departments', 'Sub-Departments', 'heroicon-o-rectangle-stack'),
                        $this->generateTab('categories', 'Categories', 'heroicon-o-tag'),
                        $this->generateTab('metal_types', 'Metal Karat', 'heroicon-o-sparkles'),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function generateTab(string $key, string $label, string $icon): Tabs\Tab
    {
        return Tabs\Tab::make($label)
            ->icon($icon)
            ->schema([
                Section::make($label)
                    ->description("Manage list items for $label. Remember to click 'Update' to save changes to this specific list.")
                    ->schema([
                        Repeater::make($key)
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->distinct()
                                    ->placeholder('Name')
                                    ->disableLabel(),
                                
                                TextInput::make('multiplier')
                                    ->label('Markup Multiplier')
                                    ->numeric()
                                    ->default(7.0)
                                    ->visible($key === 'departments') 
                                    ->placeholder('e.g. 7.0')
                                    ->suffix('x'),
                            ])
                            ->columns($key === 'departments' ? 2 : 1)
                            ->reorderableWithButtons()
                            ->addActionLabel("Add New Item to $label")
                            ->collapsible()
                            ->collapsed() // 🚀 Keeps long lists clean
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->grid(2) // 🚀 Condenses the UI horizontally
                            ->defaultItems(0),

                        Actions::make([
                            FormAction::make("save_$key")
                                ->label("Update $label List")
                                ->color('success')
                                ->icon('heroicon-m-check-badge')
                                ->action(fn() => $this->saveSection($key)),
                        ])->alignEnd(),
                    ]),
            ]);
    }

    public function saveSection(string $key): void
    {
        $sectionData = $this->data[$key] ?? [];

        if ($key === 'departments') {
            InventorySetting::updateOrCreate(['key' => 'departments'], ['value' => $sectionData]);
        } else {
            $flatData = collect($sectionData)->pluck('name')->filter()->values()->toArray();
            InventorySetting::updateOrCreate(['key' => $key], ['value' => $flatData]);
        }

        Cache::forget('inventory_departments'); 
        
        Notification::make()
            ->title(ucwords(str_replace('_', ' ', $key)) . ' Saved')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save_all')
                ->label('Save All Sections')
                ->icon('heroicon-o-check-circle')
                ->submit('save_all_logic')
                ->color('primary'),
        ];
    }

    public function save_all_logic(): void
    {
        foreach (['departments', 'sub_departments', 'categories', 'metal_types'] as $key) {
            $this->saveSection($key);
        }
        
        Notification::make()->title('All Inventory Settings Synchronized')->success()->send();
    }
}