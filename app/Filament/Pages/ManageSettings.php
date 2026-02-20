<?php

namespace App\Filament\Pages;

use App\Helpers\Staff;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $title = 'System Configuration';
    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $taxRate = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? '7.63';
        $barcodePrefix = DB::table('site_settings')->where('key', 'barcode_prefix')->value('value') ?? 'D';
        
        $paymentMethodsJson = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
        $defaultMethods = ['CASH', 'VISA', 'MASTERCARD', 'AMEX', 'LAYBUY', 'CHECK'];
        $rawPayments = $paymentMethodsJson ? json_decode($paymentMethodsJson, true) : $defaultMethods;
        $formattedPayments = collect($rawPayments)->map(fn($item) => ['name' => $item])->toArray();

        $warrantyOptionsJson = DB::table('site_settings')->where('key', 'warranty_options')->value('value');
        $defaultWarranties = ['1 Year', '2 Years', 'Lifetime', '6 Months'];
        $rawWarranties = $warrantyOptionsJson ? json_decode($warrantyOptionsJson, true) : $defaultWarranties;
        $formattedWarranties = collect($rawWarranties)->map(fn($item) => ['name' => $item])->toArray();

        // 🆕 Load Sub-Department Prefixes mapping
        $prefixesJson = DB::table('site_settings')->where('key', 'sub_department_prefixes')->value('value');
        $defaultPrefixes = [
            ['sub_department' => 'Ring', 'prefix' => 'R'],
            ['sub_department' => 'Necklace', 'prefix' => 'N'],
            ['sub_department' => 'Pendant', 'prefix' => 'P'],
            ['sub_department' => 'Bracelet', 'prefix' => 'B'],
            ['sub_department' => 'Earring', 'prefix' => 'E'],
            ['sub_department' => 'Watch', 'prefix' => 'W'],
        ];
        $formattedPrefixes = $prefixesJson ? json_decode($prefixesJson, true) : $defaultPrefixes;

        $this->form->fill([
            'tax_rate' => $taxRate,
            'barcode_prefix' => $barcodePrefix,
            'payment_methods' => $formattedPayments, 
            'warranty_options' => $formattedWarranties, 
            'sub_department_prefixes' => $formattedPrefixes, // 🆕 Add to form
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
                Section::make('Global Configuration')
                    ->description('Set your base store parameters.')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('tax_rate')->label('Sales Tax Rate')->numeric()->suffix('%')->prefixIcon('heroicon-m-receipt-percent')->required(),
                            TextInput::make('barcode_prefix')
                                ->label('Default Inventory Prefix')
                                ->placeholder('e.g. D')
                                ->maxLength(3)
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required()
                                ->helperText('Used if a sub-department prefix is not set.'),
                        ]),
                    ]),

                // 🆕 PREFIX MAPPING REPEATER
                Section::make('Inventory Prefix Rules')
                    ->description('Map Sub-Departments to specific starting letters (e.g. Ring -> R).')
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->schema([
                        Repeater::make('sub_department_prefixes')
                            ->label('')
                            ->schema([
                                TextInput::make('sub_department')->label('Sub-Department Name')->required()->placeholder('e.g. Ring'),
                                TextInput::make('prefix')->label('Prefix Letter(s)')->required()->maxLength(3)->placeholder('e.g. R'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Prefix Rule')
                            ->reorderableWithButtons(),
                    ]),

                Grid::make(2)->schema([
                    Section::make('Payment Methods')->icon('heroicon-o-credit-card')->schema([
                        Repeater::make('payment_methods')->label('')->schema([TextInput::make('name')->label('Method Name')->required()])->addActionLabel('Add Method')->reorderableWithButtons()->grid(1),
                    ]),
                    Section::make('Warranty Options')->icon('heroicon-o-shield-check')->schema([
                        Repeater::make('warranty_options')->label('')->schema([TextInput::make('name')->label('Duration Label')->required()])->addActionLabel('Add Option')->reorderableWithButtons()->grid(1),
                    ]),
                ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Save Changes')->icon('heroicon-o-check-circle')->submit('save')->color('primary')];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        DB::table('site_settings')->updateOrInsert(['key' => 'tax_rate'], ['value' => $state['tax_rate'], 'updated_at' => now()]);
        DB::table('site_settings')->updateOrInsert(['key' => 'barcode_prefix'], ['value' => strtoupper($state['barcode_prefix']), 'updated_at' => now()]);

        $flatPayments = collect($state['payment_methods'])->pluck('name')->filter()->values()->toArray();
        $flatWarranties = collect($state['warranty_options'])->pluck('name')->filter()->values()->toArray();

        DB::table('site_settings')->updateOrInsert(['key' => 'payment_methods'], ['value' => json_encode($flatPayments), 'updated_at' => now()]);
        DB::table('site_settings')->updateOrInsert(['key' => 'warranty_options'], ['value' => json_encode($flatWarranties), 'updated_at' => now()]);
        
        // 🆕 Save Prefix Map
        DB::table('site_settings')->updateOrInsert(['key' => 'sub_department_prefixes'], ['value' => json_encode($state['sub_department_prefixes']), 'updated_at' => now()]);

        Notification::make()->title('System Settings Saved')->success()->send();
    }
}