<?php

namespace App\Filament\Pages;

use App\Models\LabelLayout;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class LabelDesigner extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'Inventory';
    protected static string $view = 'filament.pages.label-designer';

    public array $data = [];
    public string $activeField = 'stock_no';

    public function mount(): void { $this->loadLayout(); }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Master Layout')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->action(fn () => $this->saveMasterLayout()),

            Action::make('reset')
                ->label('Reset to Default')
                ->color('danger')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(fn () => $this->resetToDefault()),
        ];
    }

    public function loadLayout(): void
    {
        $settings = LabelLayout::all()->keyBy('field_id');

        $this->data = [
            'stock_no_x'     => $settings->get('stock_no')->x_pos ?? 60,
            'stock_no_y'     => $settings->get('stock_no')->y_pos ?? 30,
            'stock_no_font'  => $settings->get('stock_no')->font_size ?? 3,
            'stock_no_val'   => 'D1001', 

            'desc_x'         => $settings->get('desc')->x_pos ?? 60,
            'desc_y'         => $settings->get('desc')->y_pos ?? 70,
            'desc_font'      => $settings->get('desc')->font_size ?? 2,
            'desc_val'       => 'GOLD ROPE CHAIN',

            'barcode_x'      => $settings->get('barcode')->x_pos ?? 60,
            'barcode_y'      => $settings->get('barcode')->y_pos ?? 110,
            'barcode_height' => $settings->get('barcode')->height ?? 35,
            'barcode_width'  => $settings->get('barcode')->width ?? 2,

            'price_x'        => $settings->get('price')->x_pos ?? 60,
            'price_y'        => $settings->get('price')->y_pos ?? 30, // Corrected for Side 2
            'price_font'     => $settings->get('price')->font_size ?? 3,
            'price_val'      => '$1,299.00',

            'dwmtmk_x'       => $settings->get('dwmtmk')->x_pos ?? 60,
            'dwmtmk_y'       => $settings->get('dwmtmk')->y_pos ?? 60,
            'dwmtmk_font'    => $settings->get('dwmtmk')->font_size ?? 2,
            'dwmtmk_val'     => '1.38 CTW / 14K',

            'deptcat_x'      => $settings->get('deptcat')->x_pos ?? 60,
            'deptcat_y'      => $settings->get('deptcat')->y_pos ?? 90,
            'deptcat_font'   => $settings->get('deptcat')->font_size ?? 2,
            'deptcat_val'    => 'GOLD / CHAIN',

            'rfid_x'         => $settings->get('rfid')->x_pos ?? 60,
            'rfid_y'         => $settings->get('rfid')->y_pos ?? 120,
            'rfid_font'      => $settings->get('rfid')->font_size ?? 2,
            'rfid_val'       => '303405C000',
        ];
    }

    public function saveMasterLayout(): void
    {
        foreach (['stock_no', 'barcode', 'desc', 'price', 'dwmtmk', 'deptcat', 'rfid'] as $id) {
            LabelLayout::updateOrCreate(['field_id' => $id], [
                'x_pos'     => (int)($this->data[$id . '_x'] ?? 0),
                'y_pos'     => (int)($this->data[$id . '_y'] ?? 0),
                'font_size' => (int)($this->data[$id . '_font'] ?? 2),
                'height'    => (int)($this->data[$id . '_height'] ?? 0),
                'width'     => (int)($this->data[$id . '_width'] ?? 2),
            ]);
        }
        Notification::make()->title('Master Layout Saved')->success()->send();
    }

    public function resetToDefault(): void
    {
        LabelLayout::whereIn('field_id', ['stock_no', 'barcode', 'desc', 'price', 'dwmtmk', 'deptcat', 'rfid'])->delete();
        $this->loadLayout();
        Notification::make()->title('Reset to 300 DPI Defaults')->warning()->send();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            ViewField::make('designer')->view('filament.pages.label-designer-preview')->columnSpanFull()
        ])->statePath('data');
    }
}