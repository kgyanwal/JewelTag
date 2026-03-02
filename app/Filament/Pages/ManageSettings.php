<?php

namespace App\Filament\Pages;

use App\Helpers\Staff;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Repeater;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $title = 'Utilities & Configuration';
    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        // 🚀 Fetch all settings into a single collection for efficiency
        $settings = DB::table('site_settings')->pluck('value', 'key');

        $taxRate = $settings['tax_rate'] ?? '7.63';
        $barcodePrefix = $settings['barcode_prefix'] ?? 'D';
        $zebraIp = $settings['zebra_printer_ip'] ?? '192.168.1.60';
        
        $paymentMethodsJson = $settings['payment_methods'] ?? null;
        $rawPayments = $paymentMethodsJson ? json_decode($paymentMethodsJson, true) : ['CASH', 'VISA'];
        $formattedPayments = collect($rawPayments)->map(fn($item) => ['name' => $item])->toArray();

        $warrantyOptionsJson = $settings['warranty_options'] ?? null;
        $rawWarranties = $warrantyOptionsJson ? json_decode($warrantyOptionsJson, true) : ['1 Year', 'Lifetime'];
        $formattedWarranties = collect($rawWarranties)->map(fn($item) => ['name' => $item])->toArray();

        $prefixesJson = $settings['sub_department_prefixes'] ?? null;
        $formattedPrefixes = $prefixesJson ? json_decode($prefixesJson, true) : [];

        $this->form->fill([
            'tax_rate' => $taxRate,
            'barcode_prefix' => $barcodePrefix,
            'zebra_printer_ip' => $zebraIp,
            'payment_methods' => $formattedPayments, 
            'warranty_options' => $formattedWarranties, 
            'sub_department_prefixes' => $formattedPrefixes, 
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $staff = Staff::user();
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // 🚀 TABS: Matches the "Utilities" sidebar style
                Tabs::make('Utilities')
                    ->tabs([
                        
                        // 1. SYSTEM TAB
                        Tabs\Tab::make('System')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Section::make('Global Parameters')
                                    ->description('Configure base system rules.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('tax_rate')
                                                ->label('Default Sales Tax Rate')
                                                ->numeric()
                                                ->suffix('%')
                                                ->required(),
                                            TextInput::make('barcode_prefix')
                                                ->label('Global Barcode Prefix')
                                                ->placeholder('e.g. D')
                                                ->required(),

                                            TextInput::make('zebra_printer_ip')
                                                ->label('Zebra Printer IP Address')
                                                ->placeholder('e.g. 192.168.1.60')
                                                ->required()
                                                ->prefixIcon('heroicon-m-printer'),
                                        ]),
                                    ]),
                            ]),

                        // 2. SALES TAB
                        Tabs\Tab::make('Sales')
                            ->icon('heroicon-o-shopping-cart')
                            ->schema([
                                Grid::make(2)->schema([
                                    Section::make('Payment Methods')
                                        ->description('Manage active POS payment types.')
                                        ->schema([
                                            Repeater::make('payment_methods')
                                                ->hiddenLabel()
                                                ->schema([
                                                    TextInput::make('name')->required()->placeholder('e.g. KATAPULT'),
                                                ])
                                                ->addActionLabel('Add Payment Type')
                                                ->grid(2),
                                        ]),
                                    Section::make('Protection & Warranties')
                                        ->description('Manage warranty duration labels.')
                                        ->schema([
                                            Repeater::make('warranty_options')
                                                ->hiddenLabel()
                                                ->schema([
                                                    TextInput::make('name')->required()->placeholder('e.g. 2 Years'),
                                                ])
                                                ->addActionLabel('Add Option')
                                                ->grid(2),
                                        ]),
                                ]),
                            ]),

                        // 3. STOCK / INVENTORY TAB
                        Tabs\Tab::make('Stock/Supplier')
                            ->icon('heroicon-o-archive-box')
                            ->schema([
                                Section::make('Inventory Prefix Rules')
                                    ->description('Assign specific prefixes based on Sub-Department.')
                                    ->schema([
                                        Repeater::make('sub_department_prefixes')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('sub_department')->label('Sub-Dept')->required(),
                                                    TextInput::make('prefix')->label('Prefix')->required()->maxLength(3),
                                                ])
                                            ])
                                            ->addActionLabel('Add Rule')
                                            ->grid(2)
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString() // Keeps the tab open even after refresh
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->icon('heroicon-o-check-circle')
                ->submit('save')
                ->color('success')
                ->size(\Filament\Support\Enums\ActionSize::Large)
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        DB::table('site_settings')->updateOrInsert(['key' => 'tax_rate'], ['value' => $state['tax_rate'], 'updated_at' => now()]);
        DB::table('site_settings')->updateOrInsert(['key' => 'barcode_prefix'], ['value' => strtoupper($state['barcode_prefix']), 'updated_at' => now()]);
        
        // 🚀 Ensure the Zebra IP is saved correctly
        DB::table('site_settings')->updateOrInsert(['key' => 'zebra_printer_ip'], ['value' => $state['zebra_printer_ip'], 'updated_at' => now()]);

        $flatPayments = collect($state['payment_methods'])->pluck('name')->filter()->values()->toArray();
        $flatWarranties = collect($state['warranty_options'])->pluck('name')->filter()->values()->toArray();

        DB::table('site_settings')->updateOrInsert(['key' => 'payment_methods'], ['value' => json_encode($flatPayments), 'updated_at' => now()]);
        DB::table('site_settings')->updateOrInsert(['key' => 'warranty_options'], ['value' => json_encode($flatWarranties), 'updated_at' => now()]);
        DB::table('site_settings')->updateOrInsert(['key' => 'sub_department_prefixes'], ['value' => json_encode($state['sub_department_prefixes']), 'updated_at' => now()]);

        Notification::make()->title('Configuration Updated Successfully')->success()->send();
    }
}