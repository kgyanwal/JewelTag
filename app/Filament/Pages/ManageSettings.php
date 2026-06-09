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
    public string $selectedOverrideMonth = '';

    public function mount(): void
    {
        $settings = DB::table('site_settings')->pluck('value', 'key');

        $pmJson        = $settings['payment_methods'] ?? null;
        $rawPayments   = $pmJson ? json_decode($pmJson, true) : ['CASH', 'VISA'];
        $fmtPayments   = collect($rawPayments)->map(fn($i) => ['name' => $i])->toArray();

        $woJson        = $settings['warranty_options'] ?? null;
        $rawWarranties = $woJson ? json_decode($woJson, true) : ['1 Year', 'Lifetime'];
        $fmtWarranties = collect($rawWarranties)->map(fn($i) => ['name' => $i])->toArray();

        $pfJson      = $settings['sub_department_prefixes'] ?? null;
        $fmtPrefixes = $pfJson ? json_decode($pfJson, true) : [];

        $defaults = ['Resize', 'Solder / Weld', 'Bail Change', 'Shortening', 'Stone Setting', 'Engraving', 'Polishing / Rhodium'];
        $stJson   = $settings['service_types'] ?? null;
        $rawSt    = $stJson ? json_decode($stJson, true) : $defaults;
        $fmtSt    = collect($rawSt)->map(fn($i) => ['name' => $i])->toArray();

        $caJson   = $settings['certificate_agencies'] ?? null;
        $rawCa    = $caJson ? json_decode($caJson, true) : ['GIA', 'IGI', 'AGS', 'HRD', 'EGL', 'GSI'];
        $fmtCa    = collect($rawCa)->map(fn($i) => ['name' => $i])->toArray();

        $shopifyJson   = $settings['shopify_config'] ?? '{}';
        $shopifyConfig = json_decode($shopifyJson, true);

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
            'aws_default_region'        => $settings['aws_default_region'] ?? '',
            'aws_bucket'                => $settings['aws_bucket'] ?? '',
            'aws_sms_access_key_id'     => $settings['aws_sms_access_key_id'] ?? '',
            'aws_sms_secret_access_key' => $settings['aws_sms_secret_access_key'] ?? '',
            'aws_sms_default_region'    => $settings['aws_sms_default_region'] ?? 'us-east-2',
            'aws_sns_sms_from'          => $settings['aws_sns_sms_from'] ?? '',
            'certificate_agencies'      => $fmtCa,
            'monthly_sales_target'      => $settings['monthly_sales_target'] ?? '',
            'override_month'            => now()->format('Y-m'),
            'monthly_target_override'   => $settings['monthly_target_override_' . now()->format('Y-m')] ?? '',
            
            // Shopify Fields
            'shopify_store_url'    => $shopifyConfig['store_url'] ?? '',
            'shopify_access_token' => $shopifyConfig['access_token'] ?? '',
            'shopify_api_version'  => $shopifyConfig['api_version'] ?? '2024-01',
            'shopify_auto_sync'    => (bool) ($shopifyConfig['auto_sync'] ?? false),
        ]);
    }

    public function updatedDataOverrideMonth(): void
    {
        $month = $this->data['override_month'] ?? now()->format('Y-m');
        $saved = DB::table('site_settings')
            ->where('key', 'monthly_target_override_' . $month)
            ->value('value');
        $this->data['monthly_target_override'] = $saved ?? '';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $staff = Staff::user();
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }

    public function saveSystem(): void
    {
        $state = $this->form->getState();

        foreach (['tax_rate', 'barcode_prefix', 'zebra_printer_ip', 'monthly_sales_target'] as $key) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $state[$key] ?? '', 'updated_at' => now()]
            );
        }

        $selectedMonth = $state['override_month'] ?? now()->format('Y-m');
        $override      = $state['monthly_target_override'] ?? '';
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'monthly_target_override_' . $selectedMonth],
            ['value' => (string) $override, 'updated_at' => now()]
        );

        Notification::make()->title('✅ System settings saved')->success()->send();
    }

    public function savePayments(): void
    {
        $state = $this->form->getState();
        $flat  = collect($state['payment_methods'] ?? [])->pluck('name')->filter()->values()->toArray();
        DB::table('site_settings')->updateOrInsert(['key' => 'payment_methods'], ['value' => json_encode($flat), 'updated_at' => now()]);
        Notification::make()->title('✅ Payment methods saved')->success()->send();
    }

    public function saveCertificateAgencies(): void
    {
        $state = $this->form->getState();
        $flat  = collect($state['certificate_agencies'] ?? [])->pluck('name')->filter()->values()->toArray();
        DB::table('site_settings')->updateOrInsert(['key' => 'certificate_agencies'], ['value' => json_encode($flat), 'updated_at' => now()]);
        Notification::make()->title('✅ Certificate agencies saved')->success()->send();
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

    public function saveShopify(): void
    {
        $state = $this->form->getState();
        $config = [
            'store_url'    => trim($state['shopify_store_url'] ?? ''),
            'access_token' => trim($state['shopify_access_token'] ?? ''),
            'api_version'  => trim($state['shopify_api_version'] ?? '2024-01'),
            'auto_sync'    => (bool) ($state['shopify_auto_sync'] ?? false),
        ];
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'shopify_config'],
            ['value' => json_encode($config), 'updated_at' => now()]
        );
        Notification::make()->title('✅ Shopify settings saved')->success()->send();
    }

 public function testShopify(): void
{
    $state      = $this->form->getState();
    $url        = trim($state['shopify_store_url'] ?? '');
    $token      = trim($state['shopify_access_token'] ?? '');
    $apiVersion = trim($state['shopify_api_version'] ?? '2024-01');

    if (empty($url) || empty($token)) {
        Notification::make()
            ->title('Missing credentials')
            ->body('Enter store URL and access token first.')
            ->danger()
            ->send();
        return;
    }

    // Strip https:// if user accidentally included it
    $url = str_replace(['https://', 'http://'], '', $url);
    $url = rtrim($url, '/');

    try {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
            'Content-Type'           => 'application/json',
        ])->timeout(10)->get("https://{$url}/admin/api/{$apiVersion}/shop.json");

        if ($response->successful()) {
            $shop = $response->json('shop');
            Notification::make()
                ->title('✅ Connected Successfully!')
                ->body("Shop: {$shop['name']} | Plan: {$shop['plan_name']} | Email: {$shop['email']}")
                ->success()
                ->send();
        } elseif ($response->status() === 401) {
            Notification::make()
                ->title('❌ 401 — Unauthorized')
                ->body('Token is invalid or expired. Go to Shopify Admin → Apps → your app → reinstall and copy the new token.')
                ->danger()
                ->persistent()
                ->send();
        } elseif ($response->status() === 403) {
            Notification::make()
                ->title('❌ 403 — Missing Scopes')
                ->body('Token exists but lacks permissions. Add write_products and write_inventory scopes in your Shopify app, then reinstall.')
                ->danger()
                ->persistent()
                ->send();
        } elseif ($response->status() === 404) {
            Notification::make()
                ->title('❌ 404 — Store Not Found')
                ->body("Cannot find store at: {$url} — check the URL is correct.")
                ->danger()
                ->send();
        } else {
            Notification::make()
                ->title('Connection Failed — HTTP ' . $response->status())
                ->body($response->body())
                ->danger()
                ->send();
        }
    } catch (\Exception $e) {
        Notification::make()
            ->title('Connection Error')
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
}

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([

                        // ══════════════════════════════════════════
                        // TAB 1: SYSTEM
                        // ══════════════════════════════════════════
                        Tabs\Tab::make('⚙️ System')
                            ->schema([

                                // ── Hardware & Tax ────────────────────────────
                                Section::make('system_config')
                                    ->key('system_config')
                                    ->heading(false)
                                    ->description(new \Illuminate\Support\HtmlString('
                                        <div style="display:flex;align-items:center;gap:14px;padding:10px 0 6px;">
                                            <div style="width:52px;height:52px;background:linear-gradient(135deg,#0ea5e9,#0284c7);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;box-shadow:0 4px 12px rgba(14,165,233,0.3);">🖥️</div>
                                            <div>
                                                <div style="font-size:16px;font-weight:800;color:#0f172a;letter-spacing:-0.01em;">Hardware & Tax</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:3px;">Tax rate, barcode prefix, and Zebra printer IP address.</div>
                                            </div>
                                        </div>
                                    '))
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('tax_rate')
                                                ->label('Sales Tax Rate')
                                                ->numeric()->suffix('%')->required()
                                                ->prefixIcon('heroicon-m-calculator')
                                                ->helperText('Applied to all taxable items at checkout'),
                                            TextInput::make('barcode_prefix')
                                                ->label('Barcode Prefix')
                                                ->placeholder('e.g. D')->required()
                                                ->prefixIcon('heroicon-m-tag')
                                                ->helperText('Single letter prefix for invoice numbers'),
                                            TextInput::make('zebra_printer_ip')
                                                ->label('Zebra Printer IP')
                                                ->placeholder('e.g. 192.168.1.60')->required()
                                                ->prefixIcon('heroicon-m-printer')
                                                ->helperText('Local network IP for label printing'),
                                        ]),
                                    ])
                                    ->footerActions([
                                        \Filament\Forms\Components\Actions\Action::make('save_system')
                                            ->label('Save Hardware & Tax')
                                            ->icon('heroicon-o-check-circle')
                                            ->color('success')
                                            ->action(fn() => $this->saveSystem()),
                                    ])
                                    ->footerActionsAlignment(\Filament\Support\Enums\Alignment::End),

                                // ── Monthly Sales Targets ─────────────────────
                                Section::make('monthly_targets_section')
                                    ->key('monthly_targets_section')
                                    ->heading(false)
                                    ->description(new \Illuminate\Support\HtmlString('
                                        <div style="display:flex;align-items:center;gap:14px;padding:10px 0 6px;">
                                            <div style="width:52px;height:52px;background:linear-gradient(135deg,#10b981,#059669);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;box-shadow:0 4px 12px rgba(16,185,129,0.3);">🎯</div>
                                            <div>
                                                <div style="font-size:16px;font-weight:800;color:#0f172a;letter-spacing:-0.01em;">Monthly Sales Targets</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:3px;">Set a default monthly goal. EOD automatically calculates the daily target (÷ days in month). Override specific months below.</div>
                                            </div>
                                        </div>
                                    '))
                                    ->schema([

                                        \Filament\Forms\Components\Placeholder::make('default_target_label')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:-4px;">
                                                    <div style="width:3px;height:16px;background:#10b981;border-radius:2px;flex-shrink:0;"></div>
                                                    <span style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#374151;">Default Target — Applies to all months</span>
                                                </div>
                                            ')),

                                        Grid::make(3)->schema([
                                            TextInput::make('monthly_sales_target')
                                                ->label('Monthly Target ($)')
                                                ->numeric()
                                                ->prefix('$')
                                                ->placeholder('e.g. 30000')
                                                ->prefixIcon('heroicon-m-banknotes')
                                                ->helperText('Used for any month without a specific override')
                                                ->columnSpan(1),
                                        ]),

                                        \Filament\Forms\Components\Placeholder::make('override_divider')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div style="display:flex;align-items:center;gap:12px;margin:8px 0 4px;">
                                                    <div style="flex:1;height:1px;background:#e5e7eb;"></div>
                                                    <div style="display:flex;align-items:center;gap:6px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:20px;padding:4px 12px;">
                                                        <span style="font-size:13px;">📅</span>
                                                        <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#6b7280;">Month-Specific Override</span>
                                                        <span style="font-size:10px;color:#9ca3af;font-style:italic;">optional</span>
                                                    </div>
                                                    <div style="flex:1;height:1px;background:#e5e7eb;"></div>
                                                </div>
                                            ')),

                                        Grid::make(3)->schema([
                                            \Filament\Forms\Components\Select::make('override_month')
                                                ->label('Select Month to Override')
                                                ->options(collect(range(0, 11))->mapWithKeys(function ($i) {
                                                    $date = now()->subMonths($i);
                                                    return [$date->format('Y-m') => $date->format('F Y')];
                                                })->toArray())
                                                ->default(now()->format('Y-m'))
                                                ->live()
                                                ->prefixIcon('heroicon-m-calendar')
                                                ->helperText('Pick any of the last 12 months')
                                                ->columnSpan(1),

                                            TextInput::make('monthly_target_override')
                                                ->label('Override Amount ($)')
                                                ->numeric()
                                                ->prefix('$')
                                                ->placeholder('Leave blank to use default')
                                                ->prefixIcon('heroicon-m-pencil-square')
                                                ->helperText('Specific target for the selected month only')
                                                ->columnSpan(1),
                                        ]),
                                    ])
                                    ->footerActions([
                                        \Filament\Forms\Components\Actions\Action::make('save_targets')
                                            ->label('Save Targets')
                                            ->icon('heroicon-o-check-circle')
                                            ->color('success')
                                            ->action(fn() => $this->saveSystem()),
                                    ])
                                    ->footerActionsAlignment(\Filament\Support\Enums\Alignment::End),
                            ]),

                        // ══════════════════════════════════════════
                        // TAB 2: SALES
                        // ══════════════════════════════════════════
                        Tabs\Tab::make('🛒 Sales')
                            ->schema([

                                Section::make('payment_methods_section')
                                    ->key('payment_methods_section')
                                    ->heading(false)
                                    ->description(new \Illuminate\Support\HtmlString('
                                        <div style="display:flex;align-items:center;gap:14px;padding:10px 0 6px;">
                                            <div style="width:52px;height:52px;background:linear-gradient(135deg,#10b981,#059669);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;box-shadow:0 4px 12px rgba(16,185,129,0.3);">💳</div>
                                            <div>
                                                <div style="font-size:16px;font-weight:800;color:#0f172a;letter-spacing:-0.01em;">Payment Methods</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:3px;">Configure accepted payment types shown at checkout. LAYBUY is automatically handled as an installment plan.</div>
                                            </div>
                                        </div>
                                    '))
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

                                Section::make('warranty_options_section')
                                    ->key('warranty_options_section')
                                    ->heading(false)
                                    ->description(new \Illuminate\Support\HtmlString('
                                        <div style="display:flex;align-items:center;gap:14px;padding:10px 0 6px;">
                                            <div style="width:52px;height:52px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;box-shadow:0 4px 12px rgba(245,158,11,0.3);">🛡️</div>
                                            <div>
                                                <div style="font-size:16px;font-weight:800;color:#0f172a;letter-spacing:-0.01em;">Warranty Options</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:3px;">Define warranty periods available when completing a sale.</div>
                                            </div>
                                        </div>
                                    '))
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

                                Section::make('service_types_section')
                                    ->key('service_types_section')
                                    ->heading(false)
                                    ->description(new \Illuminate\Support\HtmlString('
                                        <div style="display:flex;align-items:center;gap:14px;padding:10px 0 6px;">
                                            <div style="width:52px;height:52px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;box-shadow:0 4px 12px rgba(139,92,246,0.3);">🛠️</div>
                                            <div>
                                                <div style="font-size:16px;font-weight:800;color:#0f172a;letter-spacing:-0.01em;">Workshop Service Types</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:3px;">These appear in the Workshop dropdown when creating a sale. Default types (Resize, Solder, etc.) are pre-loaded.</div>
                                            </div>
                                        </div>
                                    '))
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

                                Section::make('receipt_terms_section')
                                    ->key('receipt_terms_section')
                                    ->heading(false)
                                    ->description(new \Illuminate\Support\HtmlString('
                                        <div style="display:flex;align-items:center;gap:14px;padding:10px 0 6px;">
                                            <div style="width:52px;height:52px;background:linear-gradient(135deg,#06b6d4,#0891b2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;box-shadow:0 4px 12px rgba(6,182,212,0.3);">📄</div>
                                            <div>
                                                <div style="font-size:16px;font-weight:800;color:#0f172a;letter-spacing:-0.01em;">Receipt Terms & Conditions</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:3px;">Text printed at the bottom of customer receipts for standard sales and repairs.</div>
                                            </div>
                                        </div>
                                    '))
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

                        // ══════════════════════════════════════════
                        // TAB 3: STOCK / SUPPLIER
                        // ══════════════════════════════════════════
                        Tabs\Tab::make('📦 Stock/Supplier')
                            ->schema([

                                Section::make('prefixes_section')
                                    ->key('prefixes_section')
                                    ->heading(false)
                                    ->description(new \Illuminate\Support\HtmlString('
                                        <div style="display:flex;align-items:center;gap:14px;padding:10px 0 6px;">
                                            <div style="width:52px;height:52px;background:linear-gradient(135deg,#f97316,#ea580c);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;box-shadow:0 4px 12px rgba(249,115,22,0.3);">📦</div>
                                            <div>
                                                <div style="font-size:16px;font-weight:800;color:#0f172a;letter-spacing:-0.01em;">Inventory Prefix Rules</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:3px;">Map sub-departments to barcode prefixes for automatic item classification.</div>
                                            </div>
                                        </div>
                                    '))
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

                                Section::make('certificate_agencies_section')
                                    ->key('certificate_agencies_section')
                                    ->heading(false)
                                    ->description(new \Illuminate\Support\HtmlString('
                                        <div style="display:flex;align-items:center;gap:14px;padding:10px 0 6px;">
                                            <div style="width:52px;height:52px;background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;box-shadow:0 4px 12px rgba(99,102,241,0.3);">🏅</div>
                                            <div>
                                                <div style="font-size:16px;font-weight:800;color:#0f172a;letter-spacing:-0.01em;">Certificate Agencies</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:3px;">Grading labs available when adding jewelry specs (GIA, IGI, etc).</div>
                                            </div>
                                        </div>
                                    '))
                                    ->schema([
                                        Repeater::make('certificate_agencies')->hiddenLabel()
                                            ->schema([TextInput::make('name')->required()->placeholder('e.g. GIA, IGI, AGS')])
                                            ->addActionLabel('+ Add Agency')->grid(3),
                                    ])
                                    ->footerActions([
                                        \Filament\Forms\Components\Actions\Action::make('save_certificate_agencies')
                                            ->label('Save Certificate Agencies')->icon('heroicon-o-check-circle')->color('success')
                                            ->action(fn() => $this->saveCertificateAgencies()),
                                    ])
                                    ->footerActionsAlignment(\Filament\Support\Enums\Alignment::End),
                            ]),

                        // ══════════════════════════════════════════
                        // TAB 4: INTEGRATIONS
                        // ══════════════════════════════════════════
                        Tabs\Tab::make('☁️ Integrations')
                            ->schema([

                                Section::make('aws_section')
                                    ->key('aws_section')
                                    ->heading(false)
                                    ->description(new \Illuminate\Support\HtmlString('
                                        <div style="display:flex;align-items:center;gap:14px;padding:10px 0 6px;">
                                            <div style="width:52px;height:52px;background:linear-gradient(135deg,#FF9900,#e68a00);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;box-shadow:0 4px 12px rgba(255,153,0,0.3);">📧</div>
                                            <div>
                                                <div style="font-size:16px;font-weight:800;color:#0f172a;letter-spacing:-0.01em;">AWS S3 & Email (SES)</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:3px;">Used for file storage and transactional email delivery to customers.</div>
                                            </div>
                                        </div>
                                    '))
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

                                Section::make('sms_section')
                                    ->key('sms_section')
                                    ->heading(false)
                                    ->description(new \Illuminate\Support\HtmlString('
                                        <div style="display:flex;align-items:center;gap:14px;padding:10px 0 6px;">
                                            <div style="width:52px;height:52px;background:linear-gradient(135deg,#ec4899,#db2777);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;box-shadow:0 4px 12px rgba(236,72,153,0.3);">📱</div>
                                            <div>
                                                <div style="font-size:16px;font-weight:800;color:#0f172a;letter-spacing:-0.01em;">SMS Messaging (SNS)</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:3px;">Credentials for sending SMS notifications and receipt links to customers.</div>
                                            </div>
                                        </div>
                                    '))
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

                                Section::make('shopify_section')
                                    ->key('shopify_section')
                                    ->heading(false)
                                    ->description(new \Illuminate\Support\HtmlString('
                                        <div style="display:flex;align-items:center;gap:14px;padding:10px 0 6px;">
                                            <div style="width:52px;height:52px;background:linear-gradient(135deg,#96bf48,#5a8e22);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;box-shadow:0 4px 12px rgba(150,191,72,0.3);">🛍️</div>
                                            <div>
                                                <div style="font-size:16px;font-weight:800;color:#0f172a;letter-spacing:-0.01em;">Shopify Integration</div>
                                                <div style="font-size:12px;color:#64748b;margin-top:3px;">Connect this store\'s inventory to your Shopify storefront. Each tenant has their own credentials.</div>
                                            </div>
                                        </div>
                                    '))
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('shopify_store_url')
                                                ->label('Shopify Store URL')
                                                ->placeholder('your-store.myshopify.com')
                                                ->helperText('Domain only — no https://'),
                                            TextInput::make('shopify_access_token')
                                                ->label('Admin API Access Token')
                                                ->placeholder('shpat_xxxxxxxxxxxxxxxx')
                                                ->password()
                                                ->revealable()
                                                ->helperText('Shopify Admin → Settings → Apps → Develop apps'),
                                            TextInput::make('shopify_api_version')
                                                ->label('API Version')
                                                ->placeholder('2024-01')
                                                ->default('2024-01'),
                                            \Filament\Forms\Components\Toggle::make('shopify_auto_sync')
                                                ->label('Auto-sync qty to 0 when item sells')
                                                ->helperText('Updates Shopify inventory automatically on sale')
                                                ->inline(false),
                                        ]),
                                    ])
                                    ->footerActions([
                                        \Filament\Forms\Components\Actions\Action::make('test_shopify')
                                            ->label('Test Connection')
                                            ->icon('heroicon-o-signal')
                                            ->color('info')
                                            ->outlined()
                                            ->action(fn() => $this->testShopify()),
                                        \Filament\Forms\Components\Actions\Action::make('save_shopify')
                                            ->label('Save Shopify Settings')
                                            ->icon('heroicon-o-check-circle')
                                            ->color('success')
                                            ->action(fn() => $this->saveShopify()),
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
            'aws_sms_access_key_id', 'aws_sms_secret_access_key', 'aws_sms_default_region',
            'aws_sns_sms_from', 'monthly_sales_target',
        ];

        foreach ($keysToSave as $key) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $state[$key] ?? '', 'updated_at' => now()]
            );
        }

        // Save month override
        $selectedMonth = $state['override_month'] ?? now()->format('Y-m');
        $override      = $state['monthly_target_override'] ?? '';
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'monthly_target_override_' . $selectedMonth],
            ['value' => (string) $override, 'updated_at' => now()]
        );

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

        $flatCa = collect($state['certificate_agencies'] ?? [])->pluck('name')->filter()->values()->toArray();
        DB::table('site_settings')->updateOrInsert(['key' => 'certificate_agencies'], ['value' => json_encode($flatCa), 'updated_at' => now()]);

        // Save Shopify Config
        $shopifyConfig = [
            'store_url'    => trim($state['shopify_store_url'] ?? ''),
            'access_token' => trim($state['shopify_access_token'] ?? ''),
            'api_version'  => trim($state['shopify_api_version'] ?? '2024-01'),
            'auto_sync'    => (bool) ($state['shopify_auto_sync'] ?? false),
        ];
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'shopify_config'],
            ['value' => json_encode($shopifyConfig), 'updated_at' => now()]
        );

        Notification::make()->title('✅ All settings saved successfully')->success()->send();
    }
}