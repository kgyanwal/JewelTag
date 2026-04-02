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
use Filament\Forms\Components\RichEditor;
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
        $settings = DB::table('site_settings')->pluck('value', 'key');

        // ── Payment Methods ───────────────────────────────────────────────────
        $pmJson        = $settings['payment_methods'] ?? null;
        $rawPayments   = $pmJson ? json_decode($pmJson, true) : ['CASH', 'VISA'];
        $fmtPayments   = collect($rawPayments)->map(fn($i) => ['name' => $i])->toArray();

        // ── Warranty Options ──────────────────────────────────────────────────
        $woJson        = $settings['warranty_options'] ?? null;
        $rawWarranties = $woJson ? json_decode($woJson, true) : ['1 Year', 'Lifetime'];
        $fmtWarranties = collect($rawWarranties)->map(fn($i) => ['name' => $i])->toArray();

        // ── Inventory Prefixes ────────────────────────────────────────────────
        $pfJson      = $settings['sub_department_prefixes'] ?? null;
        $fmtPrefixes = $pfJson ? json_decode($pfJson, true) : [];

        // ── Service Types ─────────────────────────────────────────────────────
        $defaults = ['Resize', 'Solder / Weld', 'Bail Change', 'Shortening', 'Stone Setting', 'Engraving', 'Polishing / Rhodium'];
        $stJson   = $settings['service_types'] ?? null;
        $rawSt    = $stJson ? json_decode($stJson, true) : $defaults;
        $fmtSt    = collect($rawSt)->map(fn($i) => ['name' => $i])->toArray();

        $this->form->fill([
            'tax_rate'                  => $settings['tax_rate'] ?? '7.63',
            'barcode_prefix'            => $settings['barcode_prefix'] ?? 'D',
            'zebra_printer_ip'          => $settings['zebra_printer_ip'] ?? '192.168.1.60',
            'payment_methods'           => $fmtPayments,
            'warranty_options'          => $fmtWarranties,
            'sub_department_prefixes'   => $fmtPrefixes,
            'service_types'             => $fmtSt,
            'receipt_terms'             => $settings['receipt_terms'] ?? 'All sales final. Returns accepted within 14 days...',
            'repair_terms'              => $settings['repair_terms'] ?? '',
            'aws_access_key_id'         => $settings['aws_access_key_id'] ?? '',
            'aws_secret_access_key'     => $settings['aws_secret_access_key'] ?? '',
            'aws_default_region'        => $settings['aws_default_region'] ?? 'us-east-1',
            'aws_bucket'                => $settings['aws_bucket'] ?? '',
            'aws_sms_access_key_id'     => $settings['aws_sms_access_key_id'] ?? '',
            'aws_sms_secret_access_key' => $settings['aws_sms_secret_access_key'] ?? '',
            'aws_sms_default_region'    => $settings['aws_sms_default_region'] ?? 'us-east-2',
            'aws_sns_sms_from'          => $settings['aws_sns_sms_from'] ?? '',
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $staff = Staff::user();
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }

    // ── INDIVIDUAL SAVE METHODS ───────────────────────────────────────────────

    public function saveSystem(): void
    {
        $state = $this->form->getState();
        foreach (['tax_rate', 'barcode_prefix', 'zebra_printer_ip'] as $key) {
            DB::table('site_settings')->updateOrInsert(['key' => $key], ['value' => $state[$key] ?? '', 'updated_at' => now()]);
        }
        Notification::make()->title('✅ System settings saved')->success()->send();
    }

    public function savePayments(): void
    {
        $state = $this->form->getState();
        $flat  = collect($state['payment_methods'] ?? [])->pluck('name')->filter()->values()->toArray();
        DB::table('site_settings')->updateOrInsert(['key' => 'payment_methods'], ['value' => json_encode($flat), 'updated_at' => now()]);
        Notification::make()->title('✅ Payment methods saved')->success()->send();
    }

    public function saveWarranties(): void
    {
        $state = $this->form->getState();
        $flat  = collect($state['warranty_options'] ?? [])->pluck('name')->filter()->values()->toArray();
        DB::table('site_settings')->updateOrInsert(['key' => 'warranty_options'], ['value' => json_encode($flat), 'updated_at' => now()]);
        Notification::make()->title('✅ Warranty options saved')->success()->send();
    }

    public function saveServices(): void
    {
        $state = $this->form->getState();
        $flat  = collect($state['service_types'] ?? [])->pluck('name')->filter()->values()->toArray();
        DB::table('site_settings')->updateOrInsert(['key' => 'service_types'], ['value' => json_encode($flat), 'updated_at' => now()]);
        Notification::make()->title('✅ Service types saved')->success()->send();
    }

    public function saveTerms(): void
    {
        $state = $this->form->getState();
        foreach (['receipt_terms', 'repair_terms'] as $key) {
            DB::table('site_settings')->updateOrInsert(['key' => $key], ['value' => $state[$key] ?? '', 'updated_at' => now()]);
        }
        Notification::make()->title('✅ Receipt terms saved')->success()->send();
    }

    public function savePrefixes(): void
    {
        $state = $this->form->getState();
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'sub_department_prefixes'],
            ['value' => json_encode($state['sub_department_prefixes'] ?? []), 'updated_at' => now()]
        );
        Notification::make()->title('✅ Prefix rules saved')->success()->send();
    }

    public function saveAws(): void
    {
        $state = $this->form->getState();
        foreach (['aws_access_key_id', 'aws_secret_access_key', 'aws_default_region', 'aws_bucket'] as $key) {
            DB::table('site_settings')->updateOrInsert(['key' => $key], ['value' => $state[$key] ?? '', 'updated_at' => now()]);
        }
        Notification::make()->title('✅ AWS settings saved')->success()->send();
    }

    public function saveSms(): void
    {
        $state = $this->form->getState();
        foreach (['aws_sms_access_key_id', 'aws_sms_secret_access_key', 'aws_sms_default_region', 'aws_sns_sms_from'] as $key) {
            DB::table('site_settings')->updateOrInsert(['key' => $key], ['value' => $state[$key] ?? '', 'updated_at' => now()]);
        }
        Notification::make()->title('✅ SMS settings saved')->success()->send();
    }

    // ── FORM ──────────────────────────────────────────────────────────────────

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([

                        // ── 1. SYSTEM ─────────────────────────────────────────
                        Tabs\Tab::make('⚙️ System')
                            ->schema([
                                Section::make('system_config')
                                    ->key('system_config')
                                    ->description(new \Illuminate\Support\HtmlString(
                                        '<div style="display:flex;align-items:center;gap:12px;padding:8px 0 4px;">
                                            <div style="width:48px;height:48px;background:linear-gradient(135deg,#0ea5e9,#0284c7);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">🖥️</div>
                                            <div>
                                                <div style="font-size:15px;font-weight:800;color:#0f172a;">System Configuration</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:2px;">Core system rules — tax rate, barcode prefix, and hardware settings.</div>
                                            </div>
                                        </div>'
                                    ))
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('tax_rate')
                                                ->label('💰 Sales Tax Rate')
                                                ->numeric()->suffix('%')->required()
                                                ->helperText('Applied to all taxable items at checkout'),
                                            TextInput::make('barcode_prefix')
                                                ->label('🏷️ Barcode Prefix')
                                                ->placeholder('e.g. D')->required()
                                                ->helperText('Single letter prefix for invoice numbers'),
                                            TextInput::make('zebra_printer_ip')
                                                ->label('🖨️ Zebra Printer IP')
                                                ->placeholder('e.g. 192.168.1.60')->required()
                                                ->prefixIcon('heroicon-m-printer')
                                                ->helperText('Local network IP for label printing'),
                                        ]),
                                    ])
                                    ->footerActions([
                                        \Filament\Forms\Components\Actions\Action::make('save_system')
                                            ->label('Save System Settings')
                                            ->icon('heroicon-o-check-circle')
                                            ->color('success')
                                            ->action(fn() => $this->saveSystem()),
                                    ])
                                    ->footerActionsAlignment(\Filament\Support\Enums\Alignment::End),
                            ]),

                        // ── 2. SALES ──────────────────────────────────────────
                        Tabs\Tab::make('🛒 Sales')
                            ->schema([

                                // Payment Methods
                                Section::make('payment_methods_section')
                                    ->key('payment_methods_section')
                                    ->description(new \Illuminate\Support\HtmlString(
                                        '<div style="display:flex;align-items:center;gap:12px;padding:8px 0 4px;">
                                            <div style="width:48px;height:48px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">💳</div>
                                            <div>
                                                <div style="font-size:15px;font-weight:800;color:#0f172a;">Payment Methods</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:2px;">Configure accepted payment types shown at checkout. LAYBUY is automatically handled as an installment plan.</div>
                                            </div>
                                        </div>'
                                    ))
                                    ->schema([
                                        Repeater::make('payment_methods')->hiddenLabel()
                                            ->schema([TextInput::make('name')->required()->placeholder('e.g. KATAPULT, AFFIRM, ZELLE')])
                                            ->addActionLabel('+ Add Payment Type')->grid(3),
                                    ])
                                    ->footerActions([
                                        \Filament\Forms\Components\Actions\Action::make('save_payments')
                                            ->label('Save Payment Methods')->icon('heroicon-o-check-circle')->color('success')
                                            ->action(fn() => $this->savePayments()),
                                    ])
                                    ->footerActionsAlignment(\Filament\Support\Enums\Alignment::End),

                                // Warranty Options
                                Section::make('warranty_options_section')
                                    ->key('warranty_options_section')
                                    ->description(new \Illuminate\Support\HtmlString(
                                        '<div style="display:flex;align-items:center;gap:12px;padding:8px 0 4px;">
                                            <div style="width:48px;height:48px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">🛡️</div>
                                            <div>
                                                <div style="font-size:15px;font-weight:800;color:#0f172a;">Warranty Options</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:2px;">Define warranty periods available when completing a sale.</div>
                                            </div>
                                        </div>'
                                    ))
                                    ->schema([
                                        Repeater::make('warranty_options')->hiddenLabel()
                                            ->schema([TextInput::make('name')->required()->placeholder('e.g. 2 Years, Lifetime')])
                                            ->addActionLabel('+ Add Warranty Option')->grid(3),
                                    ])
                                    ->footerActions([
                                        \Filament\Forms\Components\Actions\Action::make('save_warranties')
                                            ->label('Save Warranty Options')->icon('heroicon-o-check-circle')->color('success')
                                            ->action(fn() => $this->saveWarranties()),
                                    ])
                                    ->footerActionsAlignment(\Filament\Support\Enums\Alignment::End),

                                // Workshop Service Types
                                Section::make('service_types_section')
                                    ->key('service_types_section')
                                    ->description(new \Illuminate\Support\HtmlString(
                                        '<div style="display:flex;align-items:center;gap:12px;padding:8px 0 4px;">
                                            <div style="width:48px;height:48px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">🛠️</div>
                                            <div>
                                                <div style="font-size:15px;font-weight:800;color:#0f172a;">Workshop Service Types</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:2px;">These appear in the Workshop dropdown when creating a sale. Default types (Resize, Solder, etc.) are pre-loaded.</div>
                                            </div>
                                        </div>'
                                    ))
                                    ->schema([
                                        Repeater::make('service_types')->hiddenLabel()
                                            ->schema([TextInput::make('name')->label('Service Name')->required()->placeholder('e.g. Chain Soldering, Pearl Restringing')])
                                            ->addActionLabel('+ Add Service Type')->grid(3),
                                    ])
                                    ->footerActions([
                                        \Filament\Forms\Components\Actions\Action::make('save_services')
                                            ->label('Save Service Types')->icon('heroicon-o-check-circle')->color('success')
                                            ->action(fn() => $this->saveServices()),
                                    ])
                                    ->footerActionsAlignment(\Filament\Support\Enums\Alignment::End),

                                // Receipt Terms
                                Section::make('receipt_terms_section')
                                    ->key('receipt_terms_section')
                                    ->description(new \Illuminate\Support\HtmlString(
                                        '<div style="display:flex;align-items:center;gap:12px;padding:8px 0 4px;">
                                            <div style="width:48px;height:48px;background:linear-gradient(135deg,#06b6d4,#0891b2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">📄</div>
                                            <div>
                                                <div style="font-size:15px;font-weight:800;color:#0f172a;">Receipt Terms & Conditions</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:2px;">Text printed at the bottom of customer receipts for standard sales and repairs.</div>
                                            </div>
                                        </div>'
                                    ))
                                    ->schema([
                                        RichEditor::make('receipt_terms')
                                            ->label('Standard Sale Receipt Terms')
                                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),
                                        RichEditor::make('repair_terms')
                                            ->label('Repair Receipt Terms')
                                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),
                                    ])
                                    ->footerActions([
                                        \Filament\Forms\Components\Actions\Action::make('save_terms')
                                            ->label('Save Receipt Terms')->icon('heroicon-o-check-circle')->color('success')
                                            ->action(fn() => $this->saveTerms()),
                                    ])
                                    ->footerActionsAlignment(\Filament\Support\Enums\Alignment::End),
                            ]),

                        // ── 3. STOCK / INVENTORY ──────────────────────────────
                        Tabs\Tab::make('📦 Stock/Supplier')
                            ->schema([
                                Section::make('prefixes_section')
                                    ->key('prefixes_section')
                                    ->description(new \Illuminate\Support\HtmlString(
                                        '<div style="display:flex;align-items:center;gap:12px;padding:8px 0 4px;">
                                            <div style="width:48px;height:48px;background:linear-gradient(135deg,#f97316,#ea580c);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">📦</div>
                                            <div>
                                                <div style="font-size:15px;font-weight:800;color:#0f172a;">Inventory Prefix Rules</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:2px;">Map sub-departments to barcode prefixes for automatic item classification.</div>
                                            </div>
                                        </div>'
                                    ))
                                    ->schema([
                                        Repeater::make('sub_department_prefixes')->hiddenLabel()
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('sub_department')->label('Sub-Department')->required()->placeholder('e.g. Rings, Necklaces'),
                                                    TextInput::make('prefix')->label('Prefix Code')->required()->maxLength(3)->placeholder('e.g. RNG'),
                                                ]),
                                            ])
                                            ->addActionLabel('+ Add Prefix Rule')->grid(2),
                                    ])
                                    ->footerActions([
                                        \Filament\Forms\Components\Actions\Action::make('save_prefixes')
                                            ->label('Save Prefix Rules')->icon('heroicon-o-check-circle')->color('success')
                                            ->action(fn() => $this->savePrefixes()),
                                    ])
                                    ->footerActionsAlignment(\Filament\Support\Enums\Alignment::End),
                            ]),

                        // ── 4. INTEGRATIONS ───────────────────────────────────
                        Tabs\Tab::make('☁️ Integrations')
                            ->schema([

                                // AWS S3 / SES
                                Section::make('aws_section')
                                    ->key('aws_section')
                                    ->description(new \Illuminate\Support\HtmlString(
                                        '<div style="display:flex;align-items:center;gap:12px;padding:8px 0 4px;">
                                            <div style="width:48px;height:48px;background:linear-gradient(135deg,#FF9900,#e68a00);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">📧</div>
                                            <div>
                                                <div style="font-size:15px;font-weight:800;color:#0f172a;">AWS S3 & Email (SES)</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:2px;">Used for file storage and transactional email delivery to customers.</div>
                                            </div>
                                        </div>'
                                    ))
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('aws_access_key_id')->label('Access Key ID')->placeholder('AKIA...'),
                                            TextInput::make('aws_secret_access_key')->label('Secret Access Key')->password()->revealable(),
                                            TextInput::make('aws_default_region')->label('Region')->placeholder('us-east-1'),
                                            TextInput::make('aws_bucket')->label('S3 Bucket Name'),
                                        ]),
                                    ])
                                    ->footerActions([
                                        \Filament\Forms\Components\Actions\Action::make('save_aws')
                                            ->label('Save AWS Settings')->icon('heroicon-o-check-circle')->color('success')
                                            ->action(fn() => $this->saveAws()),
                                    ])
                                    ->footerActionsAlignment(\Filament\Support\Enums\Alignment::End),

                                // SMS / SNS
                                Section::make('sms_section')
                                    ->key('sms_section')
                                    ->description(new \Illuminate\Support\HtmlString(
                                        '<div style="display:flex;align-items:center;gap:12px;padding:8px 0 4px;">
                                            <div style="width:48px;height:48px;background:linear-gradient(135deg,#ec4899,#db2777);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">📱</div>
                                            <div>
                                                <div style="font-size:15px;font-weight:800;color:#0f172a;">SMS Messaging (SNS)</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:2px;">Credentials for sending SMS notifications and receipt links to customers.</div>
                                            </div>
                                        </div>'
                                    ))
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('aws_sms_access_key_id')->label('SMS Access Key ID')->placeholder('AKIA...'),
                                            TextInput::make('aws_sms_secret_access_key')->label('SMS Secret Access Key')->password()->revealable(),
                                            TextInput::make('aws_sms_default_region')->label('SMS Region')->placeholder('us-east-2'),
                                            TextInput::make('aws_sns_sms_from')->label('SMS From Number')->placeholder('+1XXXXXXXXXX'),
                                        ]),
                                    ])
                                    ->footerActions([
                                        \Filament\Forms\Components\Actions\Action::make('save_sms')
                                            ->label('Save SMS Settings')->icon('heroicon-o-check-circle')->color('success')
                                            ->action(fn() => $this->saveSms()),
                                    ])
                                    ->footerActionsAlignment(\Filament\Support\Enums\Alignment::End),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('💾 Save All Changes')
                ->icon('heroicon-o-check-circle')
                ->submit('save')
                ->color('success')
                ->size(\Filament\Support\Enums\ActionSize::Large),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $keysToSave = [
            'tax_rate', 'barcode_prefix', 'zebra_printer_ip', 'receipt_terms', 'repair_terms',
            'aws_access_key_id', 'aws_secret_access_key', 'aws_default_region', 'aws_bucket',
            'aws_sms_access_key_id', 'aws_sms_secret_access_key', 'aws_sms_default_region', 'aws_sns_sms_from',
        ];

        foreach ($keysToSave as $key) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $state[$key] ?? '', 'updated_at' => now()]
            );
        }

        $flatPayments = collect($state['payment_methods'] ?? [])->pluck('name')->filter()->values()->toArray();
        DB::table('site_settings')->updateOrInsert(['key' => 'payment_methods'], ['value' => json_encode($flatPayments), 'updated_at' => now()]);

        $flatWarranties = collect($state['warranty_options'] ?? [])->pluck('name')->filter()->values()->toArray();
        DB::table('site_settings')->updateOrInsert(['key' => 'warranty_options'], ['value' => json_encode($flatWarranties), 'updated_at' => now()]);

        DB::table('site_settings')->updateOrInsert(
            ['key' => 'sub_department_prefixes'],
            ['value' => json_encode($state['sub_department_prefixes'] ?? []), 'updated_at' => now()]
        );

        $flatServiceTypes = collect($state['service_types'] ?? [])->pluck('name')->filter()->values()->toArray();
        DB::table('site_settings')->updateOrInsert(['key' => 'service_types'], ['value' => json_encode($flatServiceTypes), 'updated_at' => now()]);

        Notification::make()->title('✅ All settings saved successfully')->success()->send();
    }
}