<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeletionRequestResource\Pages;
use App\Models\DeletionRequest;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class DeletionRequestResource extends Resource
{
    protected static ?string $model = DeletionRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Deletion Approvals';

    /* ───────────────────────── FORM (View/Edit Safe) ───────────────────────── */

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Deletion Request Details')
                ->schema([
                    Forms\Components\TextInput::make('product_item_id')
                        ->label('Product ID')
                        ->disabled(),

                    Forms\Components\Textarea::make('reason')
                        ->label('Reason')
                        ->disabled(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'verified_by_admin' => 'Verified by Admin',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->disabled(),
                ]),
        ]);
    }

    /* ───────────────────────── TABLE ───────────────────────── */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('productItem.barcode')
                    ->label('Stock #')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) =>
                        $record->productItem
                            ? $record->productItem->custom_description
                            : 'Product not found'
                    )
                    ->url(fn ($record) =>
                        $record->productItem
                            ? route('filament.admin.resources.product-items.edit', $record->productItem)
                            : null
                    )
                    ->openUrlInNewTab(),

                TextColumn::make('user.name')
                    ->label('Requested By')
                    ->sortable(),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->reason),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'verified_by_admin',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
            ])

            ->actions([

                /* 🔹 VERIFY (Admin + Superadmin) */
                Tables\Actions\Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->visible(fn ($record) =>
                        $record->status === 'pending'
                        && auth()->user()->hasAnyRole(['Administration', 'Superadmin'])
                    )
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'verified_by_admin',
                            'verified_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Request verified and sent to Superadmin')
                            ->success()
                            ->send();
                    }),

                /* 🔹 APPROVE & DELETE (Superadmin ONLY) */
                Tables\Actions\Action::make('approve_and_delete')
                    ->label('Approve & Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        in_array($record->status, ['pending', 'verified_by_admin'])
                        && auth()->user()->hasRole('Superadmin')
                    )
                    ->action(function ($record) {

                        if ($record->productItem) {
                            $record->productItem->delete();
                        }

                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Item permanently deleted')
                            ->success()
                            ->send();
                    }),

                /* 🔹 REJECT (Admin + Superadmin) */
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        in_array($record->status, ['pending', 'verified_by_admin'])
                        && auth()->user()->hasAnyRole(['Administration', 'Superadmin'])
                    )
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'rejected',
                        ]);

                        Notification::make()
                            ->title('Deletion request rejected')
                            ->danger()
                            ->send();
                    }),
            ])

            ->defaultSort('created_at', 'desc');
    }

    /* ───────────────────────── ACCESS CONTROL ───────────────────────── */

    public static function canViewAny(): bool
    {
        // Seeder roles: Superadmin, Administration, Manager, Sales
        return auth()->user()->hasAnyRole([
            'Superadmin',
            'Administration',
            'Manager',
        ]);
    }
   public static function shouldRegisterNavigation(): bool
{
    // 🔹 Use your Staff helper to check the identity of the person who entered the PIN
    $staff = \App\Helpers\Staff::user();

    // Only allow specific roles to see the Administration menu
    return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
}

    /* ───────────────────────── PAGES ───────────────────────── */

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeletionRequests::route('/'),
        ];
    }
}
