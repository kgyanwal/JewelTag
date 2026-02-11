<?php

namespace App\Filament\Pages;

use App\Models\LabelLayout;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use App\Services\ZebraPrinterService;

class LabelDesigner extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'Inventory';
    protected static string $view = 'filament.pages.label-designer';

    public array $data = [];
    public string $activeField = 'stock_no';

    public function mount(): void { $this->loadLayout(); }

    public function loadLayout(): void {
        $settings = LabelLayout::all()->keyBy('field_id');
        
        // 🚀 RESTORED EXACT DEFAULTS AND MAPPINGS
        $this->data = [
            'stock_no_x' => $settings->get('stock_no')->x_pos ?? 60,
            'stock_no_y' => $settings->get('stock_no')->y_pos ?? 6,
            'stock_no_font' => $settings->get('stock_no')->font_size ?? 1,
            'stock_no_is_bold' => $settings->get('stock_no')->is_bold ?? false,
            'stock_no_val' => 'D1001',

            'desc_x' => $settings->get('desc')->x_pos ?? 60,
            'desc_y' => $settings->get('desc')->y_pos ?? 9,
            'desc_font' => $settings->get('desc')->font_size ?? 1,
            'desc_is_bold' => $settings->get('desc')->is_bold ?? false,
            'desc_val' => 'Gold Rope Chain',

            'barcode_x' => $settings->get('barcode')->x_pos ?? 60,
            'barcode_y' => $settings->get('barcode')->y_pos ?? 12,
            'barcode_height' => $settings->get('barcode')->height ?? 4, // Exact Default
            'barcode_width' => $settings->get('barcode')->width ?? 0.2,   // Exact Default
            'barcode_val' => 'D1001',

            'price_x' => $settings->get('price')->x_pos ?? 60,
            'price_y' => $settings->get('price')->y_pos ?? 19,
            'price_font' => $settings->get('price')->font_size ?? 1,
            'price_is_bold' => $settings->get('price')->is_bold ?? false,
            'price_val' => '$1,299.00',

            'dwmtmk_x' => $settings->get('dwmtmk')->x_pos ?? 60,
            'dwmtmk_y' => $settings->get('dwmtmk')->y_pos ?? 22,
            'dwmtmk_font' => $settings->get('dwmtmk')->font_size ?? 1,
            'dwmtmk_is_bold' => $settings->get('dwmtmk')->is_bold ?? false,
            'dwmtmk_val' => '1.38g 14K',

            'deptcat_x' => $settings->get('deptcat')->x_pos ?? 60,
            'deptcat_y' => $settings->get('deptcat')->y_pos ?? 24,
            'deptcat_font' => $settings->get('deptcat')->font_size ?? 1,
            'deptcat_is_bold' => $settings->get('deptcat')->is_bold ?? false,
            'deptcat_val' => 'GOLD/CHAIN',

            'rfid_x' => $settings->get('rfid')->x_pos ?? 60,
            'rfid_y' => $settings->get('rfid')->y_pos ?? 26, // Exact Default
            'rfid_font' => $settings->get('rfid')->font_size ?? 1,
            'rfid_is_bold' => $settings->get('rfid')->is_bold ?? false,
            'rfid_val' => '303405C0',
        ];
    }

    public function resetToDefault(): void {
        $defaults = [
            'stock_no' => ['x_pos' => 60, 'y_pos' => 6, 'font_size' => 1, 'is_bold' => false],
            'desc'     => ['x_pos' => 60, 'y_pos' => 9, 'font_size' => 1, 'is_bold' => false],
            'barcode'  => ['x_pos' => 60, 'y_pos' => 12, 'height' => 4, 'width' => 0.2],
            'price'    => ['x_pos' => 60, 'y_pos' => 19, 'font_size' => 1, 'is_bold' => false],
            'dwmtmk'   => ['x_pos' => 60, 'y_pos' => 22, 'font_size' => 1, 'is_bold' => false],
            'deptcat'  => ['x_pos' => 60, 'y_pos' => 24, 'font_size' => 1, 'is_bold' => false],
            'rfid'     => ['x_pos' => 60, 'y_pos' => 26, 'font_size' => 1, 'is_bold' => false],
        ];
        foreach ($defaults as $id => $v) { LabelLayout::updateOrCreate(['field_id' => $id], $v); }
        $this->loadLayout();
        Notification::make()->title('Reset to Defaults')->success()->send();
    }

    public function saveMasterLayout(): void {
        foreach (['stock_no', 'desc', 'barcode', 'price', 'dwmtmk', 'deptcat', 'rfid'] as $f) {
            LabelLayout::updateOrCreate(['field_id' => $f], [
                'x_pos' => $this->data[$f.'_x'] ?? 60,
                'y_pos' => $this->data[$f.'_y'] ?? 0,
                'font_size' => $this->data[$f.'_font'] ?? 1,
                'is_bold' => $this->data[$f.'_is_bold'] ?? false,
                'height' => $this->data[$f.'_height'] ?? ($f == 'barcode' ? 4 : 0),
                'width' => $this->data[$f.'_width'] ?? ($f == 'barcode' ? 0.2 : 0),
            ]);
        }
        Notification::make()->title('Layout Synchronized')->success()->send();
    }

    public function testPrint(ZebraPrinterService $service) {
        $record = \App\Models\ProductItem::first();
        if ($record && $service->printJewelryTag($record)) {
            Notification::make()->title('Test Tag Printed')->success()->send();
        } else {
            Notification::make()->title('Print Failed')->danger()->send();
        }
    }

    public function form(Form $form): Form {
        return $form->schema([ViewField::make('designer')->view('filament.pages.label-designer-preview')->columnSpanFull()])->statePath('data');
    }
}