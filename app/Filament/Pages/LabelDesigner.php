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

    /**
     * Fallbacks used only when a store has no saved row for a field.
     * Keep these identical to ZebraPrinterService::setDefaultLayout().
     * Order = order of the table in the designer.
     * [x, y, font size, bold, sample value]
     */
    private const FIELDS = [
        'stock_no' => [550,  60, 30, true,  'D1001'],
        'dwmtmk'   => [550, 110, 20, false, '1.38g 14K'],
        'barcode'  => [550, 150,  1, false, 'D1001'],
        'price'    => [550, 220, 30, true,  '$1,299.00'],
        'desc'     => [550, 260, 20, false, 'ST SILVER MOISSANITE LION RING'],
        'deptcat'  => [550, 290, 20, false, 'GOLD/CHAIN'],
        'rfid'     => [560, 310, 17, false, '303405C0'],
    ];

    /** Fields whose `width` column stores max characters per line */
    private const TEXT_LIMIT = ['desc', 'dwmtmk', 'deptcat'];

    public function mount(): void { $this->loadLayout(); }

    public function loadLayout(): void {
        $settings = LabelLayout::all()->keyBy('field_id');
        $data = [];

        foreach (self::FIELDS as $id => [$x, $y, $font, $bold, $sample]) {
            $row = $settings->get($id);

            $data[$id . '_x'] = (int) ($row->x_pos ?? $x);
            $data[$id . '_y'] = (int) ($row->y_pos ?? $y);
            $data[$id . '_val'] = $sample;

            if ($id === 'barcode') {
                $data['barcode_height'] = max(1, (int) ($row->height ?? 20));
                // whole dots only, 1-3
                $data['barcode_width']  = min(3, max(1, (int) round((float) ($row->width ?? 1))));
            } else {
                $data[$id . '_font']    = (int) ($row->font_size ?? $font);
                $data[$id . '_is_bold'] = (bool) ($row->is_bold ?? $bold);
                // max characters per line (0 = automatic) - only used by wrapping/truncating fields
                $data[$id . '_chars']   = in_array($id, self::TEXT_LIMIT, true) ? max(0, min(60, (int) ($row->width ?? 0))) : 0;
            }
        }

        $this->data = $data;
    }

    public function resetToDefault(): void {
        (new ZebraPrinterService())->setDefaultLayout();
        $this->loadLayout();
        Notification::make()->title('Reset to Defaults')->success()->send();
    }

    public function saveMasterLayout(): void {
        // make sure older store databases have the newer columns
        (new ZebraPrinterService())->ensureLayoutColumns();

        foreach (array_keys(self::FIELDS) as $f) {
            $isBarcode = $f === 'barcode';

            LabelLayout::updateOrCreate(['field_id' => $f], [
                'x_pos'     => (int) ($this->data[$f . '_x'] ?? self::FIELDS[$f][0]),
                'y_pos'     => (int) ($this->data[$f . '_y'] ?? self::FIELDS[$f][1]),
                'font_size' => $isBarcode ? 1 : max(1, (int) ($this->data[$f . '_font'] ?? self::FIELDS[$f][2])),
                'is_bold'   => $isBarcode ? false : (bool) ($this->data[$f . '_is_bold'] ?? false),
                'height'    => $isBarcode ? max(1, (int) ($this->data['barcode_height'] ?? 20)) : 0,
                'width'     => $isBarcode
                    ? min(3, max(1, (int) round((float) ($this->data['barcode_width'] ?? 1))))
                    : (in_array($f, self::TEXT_LIMIT, true) ? max(0, min(60, (int) ($this->data[$f . '_chars'] ?? 0))) : 0),
            ]);
        }

        $this->loadLayout();
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