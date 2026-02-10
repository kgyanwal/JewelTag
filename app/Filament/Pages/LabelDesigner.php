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
use Illuminate\Support\Facades\Log;

class LabelDesigner extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'Inventory';
    protected static string $view = 'filament.pages.label-designer';

    public array $data = [];
    public string $activeField = 'stock_no';

    public function mount(): void { 
        $this->loadLayout(); 
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Master Layout')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->action(fn () => $this->saveMasterLayout()),

            Action::make('reset')
                ->label('Reset to Defaults')  // Fixed label
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
            // Top fields
            'stock_no_x'     => $settings->get('stock_no')->x_pos ?? 60,
            'stock_no_y'     => $settings->get('stock_no')->y_pos ?? 6,
            'stock_no_font'  => $settings->get('stock_no')->font_size ?? 1,
            'stock_no_val'   => 'D1001',
            'stock_no_is_bold' => false, // Added missing field

            'desc_x'         => $settings->get('desc')->x_pos ?? 60,
            'desc_y'         => $settings->get('desc')->y_pos ?? 9,
            'desc_font'      => $settings->get('desc')->font_size ?? 1,
            'desc_val'       => 'Gold Rope Chain',
            'desc_is_bold'   => false, // Added missing field

            // Barcode - Use database value (should be 4, not 2)
            'barcode_x'      => $settings->get('barcode')->x_pos ?? 60,
            'barcode_y'      => $settings->get('barcode')->y_pos ?? 12,
            'barcode_height' => $settings->get('barcode')->height ?? 4, // CHANGED: 4 not 2
            'barcode_width'  => $settings->get('barcode')->width ?? 0.2,
            'barcode_val'    => 'D1001',

            // Bottom fields
            'price_x'        => $settings->get('price')->x_pos ?? 60,
            'price_y'        => $settings->get('price')->y_pos ?? 19,
            'price_font'     => $settings->get('price')->font_size ?? 1,
            'price_val'      => '$1,299.00',
            'price_is_bold'  => false, // Added missing field

            'dwmtmk_x'       => $settings->get('dwmtmk')->x_pos ?? 60,
            'dwmtmk_y'       => $settings->get('dwmtmk')->y_pos ?? 22,
            'dwmtmk_font'    => $settings->get('dwmtmk')->font_size ?? 1,
            'dwmtmk_val'     => '1.38g 14K',
            'dwmtmk_is_bold' => false, // Added missing field

            'deptcat_x'      => $settings->get('deptcat')->x_pos ?? 60,
            'deptcat_y'      => $settings->get('deptcat')->y_pos ?? 24,
            'deptcat_font'   => $settings->get('deptcat')->font_size ?? 1,
            'deptcat_val'    => 'GOLD/CHAIN',
            'deptcat_is_bold' => false, // Added missing field

            'rfid_x'         => $settings->get('rfid')->x_pos ?? 60,
            'rfid_y'         => $settings->get('rfid')->y_pos ?? 26, // CHANGED: 26 not 30
            'rfid_font'      => $settings->get('rfid')->font_size ?? 1,
            'rfid_val'       => '303405C0',
            'rfid_is_bold'   => false, // Added missing field
        ];
    }

    public function saveMasterLayout(): void
    {
        try {
            // Save ALL fields properly with all required columns
            $fieldsToSave = [
                'stock_no' => [
                    'x_pos'     => (int)($this->data['stock_no_x'] ?? 60),
                    'y_pos'     => (int)($this->data['stock_no_y'] ?? 6),
                    'font_size' => (int)($this->data['stock_no_font'] ?? 1),
                    'height'    => 0,
                    'width'     => 0,
                ],
                'desc' => [
                    'x_pos'     => (int)($this->data['desc_x'] ?? 60),
                    'y_pos'     => (int)($this->data['desc_y'] ?? 9),
                    'font_size' => (int)($this->data['desc_font'] ?? 1),
                    'height'    => 0,
                    'width'     => 0,
                ],
                'barcode' => [
                    'x_pos'     => (int)($this->data['barcode_x'] ?? 60),
                    'y_pos'     => (int)($this->data['barcode_y'] ?? 12),
                    'font_size' => 1, // Required column, set to 1
                    'height'    => (int)($this->data['barcode_height'] ?? 4), // Cast to int
                    'width'     => (float)($this->data['barcode_width'] ?? 0.2), // Cast to float
                ],
                'price' => [
                    'x_pos'     => (int)($this->data['price_x'] ?? 60),
                    'y_pos'     => (int)($this->data['price_y'] ?? 19),
                    'font_size' => (int)($this->data['price_font'] ?? 1),
                    'height'    => 0,
                    'width'     => 0,
                ],
                'dwmtmk' => [
                    'x_pos'     => (int)($this->data['dwmtmk_x'] ?? 60),
                    'y_pos'     => (int)($this->data['dwmtmk_y'] ?? 22),
                    'font_size' => (int)($this->data['dwmtmk_font'] ?? 1),
                    'height'    => 0,
                    'width'     => 0,
                ],
                'deptcat' => [
                    'x_pos'     => (int)($this->data['deptcat_x'] ?? 60),
                    'y_pos'     => (int)($this->data['deptcat_y'] ?? 24),
                    'font_size' => (int)($this->data['deptcat_font'] ?? 1),
                    'height'    => 0,
                    'width'     => 0,
                ],
                'rfid' => [
                    'x_pos'     => (int)($this->data['rfid_x'] ?? 60),
                    'y_pos'     => (int)($this->data['rfid_y'] ?? 26), // CHANGED: 26
                    'font_size' => (int)($this->data['rfid_font'] ?? 1),
                    'height'    => 0,
                    'width'     => 0,
                ],
            ];
            
            foreach ($fieldsToSave as $fieldId => $values) {
                LabelLayout::updateOrCreate(
                    ['field_id' => $fieldId],
                    $values
                );
            }
            
            // Clear cache and reload
            $this->loadLayout();
            
            Notification::make()
                ->title('Master Layout Saved Successfully')
                ->success()
                ->send();
                
            Log::info("Layout saved successfully", [
                'barcode_height' => $this->data['barcode_height'] ?? 'not set',
                'barcode_width' => $this->data['barcode_width'] ?? 'not set',
                'rfid_y' => $this->data['rfid_y'] ?? 'not set',
            ]);
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Saving Layout')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
            Log::error("Save error: " . $e->getMessage());
        }
    }

    public function resetToDefault(): void 
    {
        $defaults = [
            'stock_no' => ['x_pos' => 60, 'y_pos' => 6, 'font_size' => 1, 'height' => 0, 'width' => 0],
            'desc'     => ['x_pos' => 60, 'y_pos' => 9, 'font_size' => 1, 'height' => 0, 'width' => 0],
            'barcode'  => ['x_pos' => 60, 'y_pos' => 12, 'height' => 4, 'width' => 0.2, 'font_size' => 1],
            'price'    => ['x_pos' => 60, 'y_pos' => 19, 'font_size' => 1, 'height' => 0, 'width' => 0],
            'dwmtmk'   => ['x_pos' => 60, 'y_pos' => 22, 'font_size' => 1, 'height' => 0, 'width' => 0],
            'deptcat'  => ['x_pos' => 60, 'y_pos' => 24, 'font_size' => 1, 'height' => 0, 'width' => 0],
            'rfid'     => ['x_pos' => 60, 'y_pos' => 26, 'font_size' => 1, 'height' => 0, 'width' => 0], // CHANGED: 26
        ];

        foreach ($defaults as $id => $values) {
            LabelLayout::updateOrCreate(['field_id' => $id], $values);
        }
        
        $this->loadLayout();
        Notification::make()
            ->title('Reset to Defaults')
            ->body('Barcode height=4, width=0.2, RFID y=26')
            ->success()
            ->send();
            
        Log::info("Layout reset to defaults");
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            ViewField::make('designer')
                ->view('filament.pages.label-designer-preview')
                ->columnSpanFull()
        ])->statePath('data');
    }
}