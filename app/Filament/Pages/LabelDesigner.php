<?php

namespace App\Filament\Pages;

use App\Models\LabelLayout;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class LabelDesigner extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'Inventory';
    protected static string $view = 'filament.pages.label-designer';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = LabelLayout::all()->keyBy('field_id');
        $this->form->fill([
            'stock_no_x' => $settings->get('stock_no')->x_pos ?? 250,
            'stock_no_y' => $settings->get('stock_no')->y_pos ?? 20,
            'price_x'    => $settings->get('price')->x_pos ?? 250,
            'price_y'    => $settings->get('price')->y_pos ?? 120,
            'desc_x'     => $settings->get('desc')->x_pos ?? 250,
            'desc_y'     => $settings->get('desc')->y_pos ?? 60,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveLayout')
                ->label('Save Master Layout')
                ->color('success')
                ->action(fn () => $this->saveMasterLayout()),
        ];
    }

    public function saveMasterLayout(): void
    {
        $state = $this->form->getState();
        
        $fields = [
            'stock_no' => ['x' => $state['stock_no_x'], 'y' => $state['stock_no_y']],
            'price'    => ['x' => $state['price_x'], 'y' => $state['price_y']],
            'desc'     => ['x' => $state['desc_x'], 'y' => $state['desc_y']],
        ];

        foreach ($fields as $id => $coords) {
            LabelLayout::updateOrCreate(
                ['field_id' => $id],
                ['x_pos' => $coords['x'], 'y_pos' => $coords['y']]
            );
        }

        Notification::make()->title('Layout Saved Successfully')->success()->send();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Hidden::make('stock_no_x'),
                \Filament\Forms\Components\Hidden::make('stock_no_y'),
                \Filament\Forms\Components\Hidden::make('price_x'),
                \Filament\Forms\Components\Hidden::make('price_y'),
                \Filament\Forms\Components\Hidden::make('desc_x'),
                \Filament\Forms\Components\Hidden::make('desc_y'),
                
                \Filament\Forms\Components\ViewField::make('preview')
                    ->view('filament.components.labelary-preview')
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }
}