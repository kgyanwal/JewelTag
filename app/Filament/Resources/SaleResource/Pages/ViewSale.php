<?php
namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    /**
     * 🔹 This ensures that when the page loads, the Sale record 
     * brings all the related product and supplier data with it.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load(['items.productItem.supplier']);
        return $data;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Transaction Details')
                    ->schema([
                        Components\Grid::make(3)->schema([
                            Components\TextEntry::make('invoice_number')->label('Invoice #')->weight('bold'),
                            Components\TextEntry::make('status')->badge(),
                            Components\TextEntry::make('final_total')->label('Grand Total')->money('USD')->color('success'),
                        ]),
                    ]),

                Components\Section::make('Inventory Specifications')
                    ->schema([
                        Components\RepeatableEntry::make('items')
                            ->schema([
                                Components\Tabs::make('Product Information')->tabs([
                                    
                                    // 🔹 TAB 1: TECHNICAL SPECS
                                    Components\Tabs\Tab::make('Technical Specs')->schema([
                                        Components\Grid::make(4)->schema([
                                            // Ensure 'productItem' matches the relationship name in SaleItem model
                                            Components\TextEntry::make('productItem.barcode')
                                                ->label('Stock #')
                                                ->placeholder('N/A'),
                                            Components\TextEntry::make('productItem.metal_type')
                                                ->label('Metal'),
                                            Components\TextEntry::make('productItem.metal_weight')
                                                ->label('Metal Wt'),
                                            Components\TextEntry::make('productItem.diamond_weight')
                                                ->label('Diamond Wt'),
                                            Components\TextEntry::make('productItem.size')
                                                ->label('Size'),
                                            Components\TextEntry::make('productItem.rfid_code')
                                                ->label('RFID'),
                                            Components\TextEntry::make('productItem.serial_number')
                                                ->label('Serial #'),
                                        ]),
                                    ]),

                                    // 🔹 TAB 2: CATEGORIZATION
                                    Components\Tabs\Tab::make('Categorization')->schema([
                                        Components\Grid::make(3)->schema([
                                            Components\TextEntry::make('productItem.department')->label('Dept'),
                                            Components\TextEntry::make('productItem.category')->label('Category'),
                                            Components\TextEntry::make('productItem.supplier.company_name')->label('Supplier'),
                                        ]),
                                    ]),

                                    // 🔹 TAB 3: SALES INFO (The part currently working)
                                    Components\Tabs\Tab::make('Sales Info')->schema([
                                        Components\Grid::make(3)->schema([
                                            Components\TextEntry::make('qty')->label('Sold Qty'),
                                            Components\TextEntry::make('sold_price')->label('Price')->money('USD'),
                                            Components\TextEntry::make('discount')->label('Discount')->money('USD'),
                                        ]),
                                    ]),
                                ]),
                            ])->columnSpanFull(),
                    ]),
            ]);
    }
}