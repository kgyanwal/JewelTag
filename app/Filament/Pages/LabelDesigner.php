<?php

namespace App\Filament\Pages;

use App\Models\LabelLayout;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;

class LabelDesigner extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'Inventory';
    protected static string $view = 'filament.pages.label-designer';

    public ?array $data = [];
    public string $activeField = 'stock_no'; // Tracking the selected object

    public function mount(): void
    {
        $settings = LabelLayout::all()->keyBy('field_id');
        
        $this->form->fill([
            'stock_no_x' => $settings->get('stock_no')->x_pos ?? 65,
            'stock_no_y' => $settings->get('stock_no')->y_pos ?? 6,
            'desc_x'     => $settings->get('desc')->x_pos ?? 65,
            'desc_y'     => $settings->get('desc')->y_pos ?? 12,
            'price_x'    => $settings->get('price')->x_pos ?? 65,
            'price_y'    => $settings->get('price')->y_pos ?? 18,
            'custom1_x'  => $settings->get('custom1')->x_pos ?? 65,
            'custom1_y'  => $settings->get('custom1')->y_pos ?? 20,
            'custom2_x'  => $settings->get('custom2')->x_pos ?? 65,
            'custom2_y'  => $settings->get('custom2')->y_pos ?? 22,
            'custom3_x'  => $settings->get('custom3')->x_pos ?? 65,
            'custom3_y'  => $settings->get('custom3')->y_pos ?? 24,
        ]);
    }

    public function saveMasterLayout(): void
    {
        // Explicitly get state from the form to ensure it is valid
        $state = $this->form->getState();
        $fields = ['stock_no', 'desc', 'price', 'custom1', 'custom2', 'custom3'];

        foreach ($fields as $id) {
            LabelLayout::updateOrCreate(
                ['field_id' => $id],
                ['x_pos' => (int)$state[$id . '_x'], 'y_pos' => (int)$state[$id . '_y']]
            );
        }

        Notification::make()->title('Master Layout Saved')->success()->send();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ViewField::make('designer')
                    ->view('filament.pages.label-designer')
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }
}