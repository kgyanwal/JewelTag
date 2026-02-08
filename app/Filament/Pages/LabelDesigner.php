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
                ->label('Reset to 1/4th Height Barcode')
                ->color('danger')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(fn () => $this->resetToQuarterHeight()),
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

            'desc_x'         => $settings->get('desc')->x_pos ?? 60,
            'desc_y'         => $settings->get('desc')->y_pos ?? 9,
            'desc_font'      => $settings->get('desc')->font_size ?? 1,
            'desc_val'       => 'Gold Rope Chain',

            // Barcode - 1/4th HEIGHT, NARROW WIDTH
            'barcode_x'      => $settings->get('barcode')->x_pos ?? 60,
            'barcode_y'      => $settings->get('barcode')->y_pos ?? 12,
            'barcode_height' => $settings->get('barcode')->height ?? 2, // 1/4th of 8 = 2
            'barcode_width'  => $settings->get('barcode')->width ?? 0.2, // Keep narrow width
            'barcode_val'    => 'D1001',

            // Bottom fields (will have +75 added in service)
            'price_x'        => $settings->get('price')->x_pos ?? 60,
            'price_y'        => $settings->get('price')->y_pos ?? 19,
            'price_font'     => $settings->get('price')->font_size ?? 1,
            'price_val'      => '$1,299.00',

            'dwmtmk_x'       => $settings->get('dwmtmk')->x_pos ?? 60,
            'dwmtmk_y'       => $settings->get('dwmtmk')->y_pos ?? 22,
            'dwmtmk_font'    => $settings->get('dwmtmk')->font_size ?? 1,
            'dwmtmk_val'     => '1.38g 14K',

            'deptcat_x'      => $settings->get('deptcat')->x_pos ?? 60,
            'deptcat_y'      => $settings->get('deptcat')->y_pos ?? 24,
            'deptcat_font'   => $settings->get('deptcat')->font_size ?? 1,
            'deptcat_val'    => 'GOLD/CHAIN',

            'rfid_x'         => $settings->get('rfid')->x_pos ?? 60,
            'rfid_y'         => $settings->get('rfid')->y_pos ?? 30,
            'rfid_font'      => $settings->get('rfid')->font_size ?? 1,
            'rfid_val'       => '303405C0',
        ];
    }

    public function saveMasterLayout(): void
    {
        try {
            // Save text fields
            foreach (['stock_no', 'desc', 'price', 'dwmtmk', 'deptcat', 'rfid'] as $id) {
                LabelLayout::updateOrCreate(
                    ['field_id' => $id],
                    [
                        'x_pos'     => (int)($this->data[$id . '_x'] ?? 60),
                        'y_pos'     => (int)($this->data[$id . '_y'] ?? 6),
                        'font_size' => (int)($this->data[$id . '_font'] ?? 1),
                    ]
                );
            }
            
            // Save barcode with 1/4th height and narrow width
            LabelLayout::updateOrCreate(
                ['field_id' => 'barcode'],
                [
                    'x_pos'     => (int)($this->data['barcode_x'] ?? 60),
                    'y_pos'     => (int)($this->data['barcode_y'] ?? 12),
                    'height'    => max(2, (int)($this->data['barcode_height'] ?? 2)), // Min 2 dots
                    'width'     => max(0.15, min(1.0, (float)($this->data['barcode_width'] ?? 0.2))), // 0.15-1.0
                ]
            );
            
            // Clear cache and reload
            $this->loadLayout();
            
            Notification::make()
                ->title('Master Layout Saved Successfully')
                ->success()
                ->send();
                
            Log::info("Layout saved", ['data' => $this->data]);
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Saving Layout')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
            Log::error("Save error: " . $e->getMessage());
        }
    }

    public function resetToQuarterHeight(): void
    {
        $quarterHeightDefaults = [
            'stock_no' => ['x_pos' => 60, 'y_pos' => 6, 'font_size' => 1],
            'desc'     => ['x_pos' => 60, 'y_pos' => 9, 'font_size' => 1],
            'barcode'  => ['x_pos' => 60, 'y_pos' => 12, 'height' => 2, 'width' => 0.2], // 1/4th HEIGHT
            'price'    => ['x_pos' => 60, 'y_pos' => 19, 'font_size' => 1],
            'dwmtmk'   => ['x_pos' => 60, 'y_pos' => 22, 'font_size' => 1],
            'deptcat'  => ['x_pos' => 60, 'y_pos' => 24, 'font_size' => 1],
            'rfid'     => ['x_pos' => 60, 'y_pos' => 30, 'font_size' => 1],
        ];

        foreach ($quarterHeightDefaults as $id => $values) {
            LabelLayout::updateOrCreate(['field_id' => $id], $values);
        }
        
        $this->loadLayout();
        Notification::make()->title('Reset to 1/4th Height Barcode')->warning()->send();
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