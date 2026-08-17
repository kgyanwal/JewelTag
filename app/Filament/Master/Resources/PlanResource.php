<?php

namespace App\Filament\Master\Resources;

use App\Filament\Master\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{Section, Grid, TextInput, Textarea, Toggle, Placeholder};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class PlanResource extends Resource
{
    protected static ?string $model          = Plan::class;
    protected static ?string $navigationIcon  = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'SaaS Management';
    protected static ?string $navigationLabel = 'Plans & Pricing';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(12)->schema([

                Forms\Components\Group::make()->columnSpan(8)->schema([

                    Section::make('Plan Details')->icon('heroicon-o-tag')->schema([
                        Grid::make(3)->schema([
                            TextInput::make('name')->required()->placeholder('e.g. Pro + CRM'),
                            TextInput::make('slug')->required()->unique(ignoreRecord: true)
                                ->helperText('URL-safe key: basic, pro, enterprise'),
                            Forms\Components\ColorPicker::make('badge_color')
                                ->label('Badge Color')->helperText('For UI badges'),
                        ]),
                        Textarea::make('description')->rows(2)->placeholder('Short tagline shown to customers'),
                        Forms\Components\KeyValue::make('custom_features')
                            ->label('Extra Bullet Points (shown on pricing page)')
                            ->keyLabel('Key')->valueLabel('Feature text')
                            ->addActionLabel('+ Add Feature')
                            ->reorderable(),
                    ]),

                    Section::make('Hard Limits')->icon('heroicon-o-adjustments-horizontal')
                        ->description('Set -1 for unlimited')->schema([
                        Grid::make(3)->schema([
                            TextInput::make('max_users')->label('Max Users')->numeric()->required()
                                ->helperText('-1 = unlimited'),
                            TextInput::make('max_items')->label('Max Inventory Items')->numeric()->required()
                                ->helperText('-1 = unlimited'),
                            TextInput::make('max_locations')->label('Max Locations')->numeric()->required()
                                ->helperText('-1 = unlimited'),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('max_custom_orders_month')->label('Max Custom Orders/Month')->numeric()->required()
                                ->helperText('-1 = unlimited'),
                            TextInput::make('max_repairs_month')->label('Max Repairs/Month')->numeric()->required()
                                ->helperText('-1 = unlimited'),
                        ]),
                    ]),

                    Section::make('Feature Flags')->icon('heroicon-o-sparkles')
                        ->description('Toggle which features are available on this plan')->schema([
                        Grid::make(3)->schema([
                            Toggle::make('feature_diamond_vault')->label('💎 Diamond Vault')->inline(false),
                            Toggle::make('feature_layaway')->label('⏳ Layaway / Financing')->inline(false),
                            Toggle::make('feature_api')->label('🔌 API Access')->inline(false),
                            Toggle::make('feature_sms')->label('📱 SMS Notifications')->inline(false),
                            Toggle::make('feature_crm')->label('🎯 CRM & Marketing')->inline(false),
                            Toggle::make('feature_advanced_analytics')->label('📊 Advanced Analytics')->inline(false),
                            Toggle::make('feature_exchange')->label('🔄 Exchanges')->inline(false),
                            Toggle::make('feature_rfid')->label('📡 RFID Tracking')->inline(false),
                            Toggle::make('feature_multi_store')->label('🏪 Multi-Store Sync')->inline(false),
                            Toggle::make('feature_white_label')->label('🏷️ White Label')->inline(false),
                            Toggle::make('feature_custom_integrations')->label('🔧 Custom Integrations')->inline(false),
                        ]),
                    ]),
                ]),

                Forms\Components\Group::make()->columnSpan(4)->schema([

                    Section::make('Pricing')->icon('heroicon-o-banknotes')->schema([
                        TextInput::make('price_monthly')->label('Monthly Price ($)')->numeric()->prefix('$')->required(),
                        TextInput::make('price_yearly')->label('Yearly Price ($)')->numeric()->prefix('$')
                            ->helperText('0 = custom/contact sales'),
                        TextInput::make('sort_order')->label('Display Order')->numeric()->default(0),
                    ]),

                    Section::make('Visibility')->icon('heroicon-o-eye')->schema([
                        Toggle::make('is_active')->label('Plan is Active')->default(true)->inline(false),
                        Toggle::make('is_popular')->label('Mark as Most Popular')->inline(false)
                            ->helperText('Shows "MOST POPULAR" badge on pricing page'),
                    ]),

                    Section::make('Plan Preview')->icon('heroicon-o-credit-card')->schema([
                        Placeholder::make('preview')->label('')->live()
                            ->content(function ($record) {
                                if (!$record) {
                                    return new HtmlString('<div style="color:#6b7280;font-size:12px;">Save the plan to see preview</div>');
                                }
                                $tenantCount = \App\Models\Tenant::where('plan_id', $record->id)->count();
                                $maxU = $record->max_users == -1 ? '∞' : $record->max_users;
                                $maxI = $record->max_items == -1 ? '∞' : number_format($record->max_items);
                                $maxL = $record->max_locations == -1 ? '∞' : $record->max_locations;
                                return new HtmlString("
                                    <div style='background:#0f172a;border-radius:12px;padding:16px;color:#f8fafc;'>
                                        <div style='font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;margin-bottom:6px;'>{$record->name}</div>
                                        <div style='font-size:22px;font-weight:900;color:#C9A24B;'>\${$record->price_monthly}<span style='font-size:12px;font-weight:400;color:#94a3b8;'>/mo</span></div>
                                        <div style='margin-top:12px;font-size:11px;color:#94a3b8;border-top:1px solid #1e293b;padding-top:10px;'>
                                            <div>👥 {$maxU} users</div>
                                            <div>📦 {$maxI} items</div>
                                            <div>🏪 {$maxL} location(s)</div>
                                        </div>
                                        <div style='margin-top:10px;font-size:12px;font-weight:700;color:#60a5fa;border-top:1px solid #1e293b;padding-top:10px;'>
                                            {$tenantCount} active tenant(s) on this plan
                                        </div>
                                    </div>
                                ");
                            }),
                    ]),
                    Section::make('Hard Limits')->icon('heroicon-o-adjustments-horizontal')
    ->description('Set -1 for unlimited')->schema([
    Grid::make(3)->schema([
        TextInput::make('max_users')->label('Max Users')->numeric()->required()
            ->helperText('-1 = unlimited'),
        TextInput::make('max_items')->label('Max Inventory Items')->numeric()->required()
            ->helperText('-1 = unlimited'),
        TextInput::make('max_locations')->label('Max Locations')->numeric()->required()
            ->helperText('-1 = unlimited'),
    ]),
    Grid::make(3)->schema([   // ← changed from Grid::make(2)
        TextInput::make('max_custom_orders_month')->label('Max Custom Orders/Month')->numeric()->required()
            ->helperText('-1 = unlimited'),
        TextInput::make('max_repairs_month')->label('Max Repairs/Month')->numeric()->required()
            ->helperText('-1 = unlimited'),
        TextInput::make('max_laybuys')->label('Max Laybuy Plans')->numeric()->required()   // ← ADD
            ->helperText('-1 = unlimited'),
    ]),
]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('#')->sortable()->grow(false),

                Tables\Columns\TextColumn::make('name')->label('Plan')->weight('bold')
                    ->description(fn ($record) => $record->description)
                    ->formatStateUsing(function ($state, $record) {
                        $badge = $record->is_popular
                            ? "<span style='background:#C9A24B;color:#0f172a;padding:1px 8px;border-radius:99px;font-size:9px;font-weight:900;margin-left:6px;'>POPULAR</span>"
                            : '';
                        return new HtmlString("<span style='font-weight:800;'>{$state}</span>{$badge}");
                    }),

                Tables\Columns\TextColumn::make('price_monthly')->label('Monthly')
                    ->formatStateUsing(fn ($state) => $state > 0 ? '$'.number_format($state, 0).'/mo' : 'Custom')
                    ->color('success')->weight('bold'),

                Tables\Columns\TextColumn::make('max_users')->label('Users')
                    ->formatStateUsing(fn ($state) => $state == -1 ? '∞ Unlimited' : $state)
                    ->badge()->color('info'),

                Tables\Columns\TextColumn::make('max_items')->label('Items')
                    ->formatStateUsing(fn ($state) => $state == -1 ? '∞ Unlimited' : number_format($state))
                    ->badge()->color('gray'),

                Tables\Columns\TextColumn::make('max_locations')->label('Locations')
                    ->formatStateUsing(fn ($state) => $state == -1 ? '∞' : $state)
                    ->badge()->color('gray'),

                Tables\Columns\TextColumn::make('features_summary')->label('Key Features')->html()
                    ->getStateUsing(function ($record) {
                        $flags = [
                            'feature_diamond_vault'      => '💎 Diamond Vault',
                            'feature_layaway'            => '⏳ Layaway',
                            'feature_api'                => '🔌 API',
                            'feature_sms'                => '📱 SMS',
                            'feature_crm'                => '🎯 CRM',
                            'feature_advanced_analytics' => '📊 Analytics',
                            'feature_rfid'               => '📡 RFID',
                            'feature_multi_store'        => '🏪 Multi-Store',
                            'feature_white_label'        => '🏷️ White Label',
                        ];
                        $pills = '';
                        foreach ($flags as $col => $label) {
                            if ($record->$col) {
                                $pills .= "<span style='display:inline-block;background:#1e3a5f;color:#a8d4f5;border:1px solid #1d4ed8;border-radius:99px;padding:1px 8px;font-size:10px;font-weight:700;margin:2px 2px;white-space:nowrap;'>{$label}</span>";
                            }
                        }
                        return $pills ?: '<span style="color:#6b7280;font-size:11px;">No features</span>';
                    }),

                Tables\Columns\TextColumn::make('tenants_count')->label('Tenants')
                    ->getStateUsing(fn ($record) => \App\Models\Tenant::where('plan_id', $record->id)->count())
                    ->badge()->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_tenants')
                    ->label('View Tenants')
                    ->icon('heroicon-o-building-storefront')
                    ->color('info')
                    ->url(fn ($record) => \App\Filament\Master\Resources\TenantResource::getUrl('index', [
                        'tableFilters[plan_id][value]' => $record->id,
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit'   => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}