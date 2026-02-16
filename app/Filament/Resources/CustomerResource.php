<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Customer';
protected static ?int $navigationSort = 1;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Main Content Area (Tabs)
                Group::make()
                    ->schema([
                        Tabs::make('Customer Profile')
                            ->tabs([
                                // --- TAB 1: CORE DETAILS ---
                                Tabs\Tab::make('Details & Contact')
                                    ->icon('heroicon-o-user-circle')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('customer_no')
                                                ->label('Customer No.')
                                                ->default(fn() => 'CUST-' . strtoupper(bin2hex(random_bytes(3))))
                                                ->disabled()
                                                ->dehydrated(),
                                            TextInput::make('name')->label('First Name')->required(),
                                            TextInput::make('last_name')->label('Last Name'),
                                        ]),
                                        Grid::make(3)->schema([
                                            TextInput::make('company')->label('Company'),
                                            TextInput::make('phone')
                                                ->label('Mobile Phone')
                                                ->tel()
                                                ->prefix('+1')
                                                ->required()
                                                ->unique(ignoreRecord: true)
                                                ->validationMessages([
                                                    'unique' => 'A customer with this phone number already exists.',
                                                ]),
                                            TextInput::make('email')->email(),
                                        ]),
                                        Grid::make(2)->schema([
                                           Select::make('sales_person')
    ->label('Sales Person')
    ->options(function () {
        // 🔹 DEFENSIVE FIX: Only query roles that definitely exist in the DB
        $validRoles = ['Superadmin', 'Administration', 'Manager', 'Sales']; // Changed 'Sales Associate' to 'Sales' to match seeder
        
        return \App\Models\User::whereHas('roles', function($q) use ($validRoles) {
            $q->whereIn('name', $validRoles);
        })->pluck('name', 'name');
    })
    ->searchable()
    ->preload()
                                            // TextInput::make('tax_number')->label('Tax Number'),
                                        ]),
                                        Section::make('Address')
                                            ->columns(2)->collapsible()
                                            ->schema([
                                                TextInput::make('street')->columnSpanFull(),
                                                TextInput::make('city'),
                                                TextInput::make('state'),
                                                Select::make('country')
                                                    ->options(['Australia' => 'Australia', 'United States' => 'United States', 'Canada' => 'Canada'])
                                                    ->default('Australia')->searchable(),
                                                TextInput::make('postcode'),
                                            ]),
                                    ]),

                                // --- TAB 2: MARKETING & JEWELRY ---
                                Tabs\Tab::make('Personal Details')
                                    ->icon('heroicon-o-megaphone')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            DatePicker::make('dob')->label('Birth Date'),
                                            DatePicker::make('wedding_anniversary')->label('Wedding Date'),
                                            Select::make('gender')->options(['Male' => 'Male', 'Female' => 'Female', 'Others' => 'Others']),
                                        ]),
                                        Grid::make(3)->schema([
                                            Select::make('gold_preference')
                                                ->options(['Yellow' => 'Yellow', 'White' => 'White', 'Rose' => 'Rose', 'Platinum' => 'Platinum']),
                                            Select::make('how_found_store')
                                                ->options(['Facebook' => 'Facebook', 'Google' => 'Google', 'Friend' => 'Friend', 'Walked By' => 'Walked By']),
                                            Select::make('purchase_reason')
                                                ->options(['Anniversary' => 'Anniversary', 'Birthday' => 'Birthday', 'Engagement' => 'Engagement']),
                                        ]),
                                        Textarea::make('special_interests')->rows(3)->placeholder('Enter any specific jewelry interests...'),
                                    ]),

                                // --- TAB 3: FINGER SIZES ---
                                Tabs\Tab::make('Finger Sizes')
                                    ->icon('heroicon-o-hand-raised')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Section::make('Left Hand')->columnSpan(1)->schema([
                                                Select::make('lh_thumb')->label('Thumb')->options(self::getFingerSizes()),
                                                Select::make('lh_index')->label('Index')->options(self::getFingerSizes()),
                                                Select::make('lh_middle')->label('Middle')->options(self::getFingerSizes()),
                                                Select::make('lh_ring')->label('Ring')->options(self::getFingerSizes()),
                                                Select::make('lh_pinky')->label('Pinky')->options(self::getFingerSizes()),
                                            ]),
                                            Section::make('Right Hand')->columnSpan(1)->schema([
                                                Select::make('rh_thumb')->label('Thumb')->options(self::getFingerSizes()),
                                                Select::make('rh_index')->label('Index')->options(self::getFingerSizes()),
                                                Select::make('rh_middle')->label('Middle')->options(self::getFingerSizes()),
                                                Select::make('rh_ring')->label('Ring')->options(self::getFingerSizes()),
                                                Select::make('rh_pinky')->label('Pinky')->options(self::getFingerSizes()),
                                            ]),
                                        ]),
                                    ]),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                // Sidebar Area
                Group::make()
                    ->schema([
                        Section::make('Profile Image')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('')
                                    ->image()
                                    ->avatar()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '1:1',
                                    ])
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->directory('customer-photos')
                                    ->visibility('public'),
                            ]),
                        Section::make('Status')
                            ->schema([
                                Toggle::make('is_active')->label('Active Customer')->default(true),
                                Select::make('loyalty_tier')
                                    ->options([
                                        'standard' => 'Standard',
                                        'silver' => 'Silver',
                                        'gold' => 'Gold'
                                    ])
                                    ->default('standard')
                                    ->required(),
                                TextInput::make('loyalty_card_number')->label('Loyalty Card #'),
                            ]),
                        Section::make('Internal Notes')
                            ->schema([
                                Textarea::make('customer_alerts')
                                    ->label('Customer Alerts')
                                    ->helperText('Notes here will pop up when editing this customer.')
                                    ->rows(4)
                                    ->placeholder('e.g., Prefers specific brand, allergic to nickel...'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->circular()->label(''),
                Tables\Columns\TextColumn::make('customer_no')->label('ID')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Customer Name')
                    ->formatStateUsing(fn($record) => "{$record->name} {$record->last_name}")
                    ->searchable(['name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('Phone')->copyable(),
                Tables\Columns\TextColumn::make('loyalty_tier')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'gold' => 'warning',
                        'silver' => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Status'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('loyalty_tier'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        if ($record->customer_alerts) {
                            Notification::make()
                                ->warning()
                                ->title('Customer Alert')
                                ->body($record->customer_alerts)
                                ->persistent()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    private static function getFingerSizes(): array
    {
        $sizes = ['None', '4', '4.5', '5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5', '10', '11', '12', '13', 'Other'];
        return array_combine($sizes, $sizes);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
    public static function canDelete($record): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // 🔹 Null check prevents 500 errors if user is logged out/session expires
        if (!$user) return false;

        // 🔹 Use hasRole or hasAnyRole with a check to ensure Spatie is loaded
        return $user->hasRole(['Superadmin', 'Administration', 'Sales Associate']);
    }

    public static function canDeleteAny(): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user) return false;

        return $user->hasRole(['Superadmin', 'Administration', 'Sales Associate']);
    }
}
