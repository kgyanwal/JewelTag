<?php

namespace App\Filament\Master\Resources;

use App\Filament\Master\Resources\LicenseResource\Pages;
use App\Filament\Master\Resources\LicenseResource\RelationManagers;
use App\Models\License;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LicenseResource extends Resource
{
  protected static ?string $model = License::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'SaaS Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('License Details')->schema([
                    Forms\Components\Select::make('tenant_id')
                        ->label('Target Tenant Store')
                        ->relationship('tenant', 'id')
                        ->required(),
                        
                    Forms\Components\TextInput::make('license_key')
                        ->default(fn() => License::generate())
                        ->required()
                        ->unique(ignoreRecord: true),
                        
                    Forms\Components\Select::make('plan')
                        ->options([
                            'essential' => 'Essential',
                            'professional' => 'Professional',
                            'enterprise' => 'Enterprise',
                        ])
                        ->required(),
                        
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'expired' => 'Expired',
                            'suspended' => 'Suspended (Banned)',
                        ])
                        ->required(),
                        
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Valid Until')
                        ->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.id')->label('Store')->searchable(),
                Tables\Columns\TextColumn::make('plan')->badge(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'expired',
                        'warning' => 'suspended',
                    ]),
                Tables\Columns\TextColumn::make('expires_at')->dateTime('M d, Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Active', 'expired' => 'Expired', 'suspended' => 'Suspended']),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLicenses::route('/'),
            'create' => Pages\CreateLicense::route('/create'),
            'edit' => Pages\EditLicense::route('/{record}/edit'),
        ];
    }
}