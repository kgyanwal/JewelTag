<?php

namespace App\Filament\Pages;

use App\Helpers\Staff;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\KeyValue;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Admin';
    protected static string $view = 'filament.pages.manage-settings';

    // This property holds the form state
    public ?array $data = [];

    public function mount(): void
    {
        // 1. Load existing settings from the database
        $taxRate = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? '7.63';
        $barcodePrefix = DB::table('site_settings')->where('key', 'barcode_prefix')->value('value') ?? 'D';
        
        // 2. Load Payment Methods & Handle JSON Decoding
        $paymentMethodsJson = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
        
        // If DB is empty, use these defaults
        $defaultMethods = ['CASH', 'laybuy', 'MASTERCARD', 'AMEX', 'VISA', 'CHECK'];

        $paymentMethods = $paymentMethodsJson 
            ? json_decode($paymentMethodsJson, true) 
            : $defaultMethods;

        $warrantyOptionsJson = DB::table('site_settings')->where('key', 'warranty_options')->value('value');
        $defaultWarranties = ['1 Year', '2 Years', 'Lifetime', '6 Months'];
        $warrantyOptions = $warrantyOptionsJson ? json_decode($warrantyOptionsJson, true) : $defaultWarranties;    
        // 3. Fill the form
        $this->form->fill([
            'tax_rate' => $taxRate,
            'barcode_prefix' => $barcodePrefix,
            'payment_methods' => $paymentMethods, 
            'warranty_options' => $warrantyOptions,
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Only allow Superadmin or Administration roles to see this page
        $staff = Staff::user();
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Global Configuration')
                    ->schema([
                        TextInput::make('tax_rate')
                            ->label('Default Sales Tax (%)')
                            ->numeric()
                            ->suffix('%')
                            ->required(),

                        TextInput::make('barcode_prefix')
                            ->label('Stock Number Prefix')
                            ->placeholder('e.g., D, G, R')
                            ->maxLength(3)
                            ->required()
                            ->helperText('This character will begin all new stock numbers (e.g., D1001).'),
                    ])->columns(2),

                Section::make('Payment Options')
                    ->schema([
                        TagsInput::make('payment_methods')
                            ->label('Active Payment Methods')
                            ->placeholder('Type method and hit Enter (e.g. ZELLE)')
                            ->helperText('Add or remove payment methods available at checkout.')
                            ->reorderable()
                            ->required(),

                         TagsInput::make('warranty_options')
                            ->label('Warranty Duration Options')
                            ->placeholder('Type duration (e.g. 1 Year) and hit Enter')
                            ->helperText('These options will appear in the Sales dropdown.')
                            ->reorderable(),   
                    ])
            ])
            ->statePath('data'); // Binds the form to the $data array
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save')
                ->color('primary'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // 1. Update Tax Rate
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'tax_rate'],
            ['value' => $state['tax_rate'], 'updated_at' => now()]
        );

        // 2. Update Barcode Prefix
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'barcode_prefix'],
            ['value' => strtoupper($state['barcode_prefix']), 'updated_at' => now()]
        );

        // 3. Update Payment Methods (Encode array back to JSON)
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'payment_methods'],
            ['value' => json_encode($state['payment_methods']), 'updated_at' => now()]
        );

        DB::table('site_settings')->updateOrInsert(['key' => 'warranty_options'], ['value' => json_encode($state['warranty_options']), 'updated_at' => now()]);
        Notification::make()
            ->title('Settings Updated')
            ->success()
            ->send();
    }
}