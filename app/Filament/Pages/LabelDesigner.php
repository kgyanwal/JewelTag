<?php

namespace App\Filament\Pages;

use App\Models\LabelLayout;
use Filament\Pages\Page;
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

    public array $data = [];
    public string $activeField = 'stock_no';

    public function mount(): void
    {
        $this->loadLayout();
    }

    /**
     * Loads layout from DB or applies hardcoded defaults
     */
    public function loadLayout(): void
    {
        $settings = LabelLayout::all()->keyBy('field_id');

        $this->data = [
            'stock_no_x' => $settings->get('stock_no')->x_pos ?? 65,
            'stock_no_y' => $settings->get('stock_no')->y_pos ?? 6,
            'barcode_x'  => $settings->get('barcode')->x_pos ?? 65,
            'barcode_y'  => $settings->get('barcode')->y_pos ?? 12,
            'desc_x'     => $settings->get('desc')->x_pos ?? 65,
            'desc_y'     => $settings->get('desc')->y_pos ?? 9,
            'price_x'    => $settings->get('price')->x_pos ?? 65,
            'price_y'    => $settings->get('price')->y_pos ?? 20,
            'price_value'=> '$1,299.00',
            'dwmtmk_x'   => $settings->get('dwmtmk')->x_pos ?? 65,
            'dwmtmk_y'   => $settings->get('dwmtmk')->y_pos ?? 22,
            'deptcat_x'  => $settings->get('deptcat')->x_pos ?? 65,
            'deptcat_y'  => $settings->get('deptcat')->y_pos ?? 24,
            'rfid_x'     => $settings->get('rfid')->x_pos ?? 65,
            'rfid_y'     => $settings->get('rfid')->y_pos ?? 26,
        ];
    }

    /**
     * Resets all coordinates to factory defaults
     */
    public function resetToDefault(): void
    {
        // Delete custom positions to fall back to hardcoded defaults
        LabelLayout::whereIn('field_id', ['stock_no', 'barcode', 'desc', 'price', 'dwmtmk', 'deptcat', 'rfid'])->delete();
        
        $this->loadLayout();

        Notification::make()
            ->title('Layout Reset to Defaults')
            ->warning()
            ->send();
    }

    public function saveMasterLayout(): void
    {
        $state = $this->data; 
        $fields = ['stock_no', 'barcode', 'desc', 'price', 'dwmtmk', 'deptcat', 'rfid'];

        foreach ($fields as $id) {
            LabelLayout::updateOrCreate(
                ['field_id' => $id],
                [
                    'x_pos' => (int) ($state[$id . '_x'] ?? 0),
                    'y_pos' => (int) ($state[$id . '_y'] ?? 0),
                ]
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