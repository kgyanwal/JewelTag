<?php

namespace App\Filament\Pages;

use App\Models\ProductItem;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\{Section, Grid, Select, DatePicker, TextInput, CheckboxList};
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class InactiveStockReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-x-mark';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'Inactive Stock Report';
    protected static ?int $navigationSort = 6; 

    protected static string $view = 'filament.pages.inactive-stock-report';

    public ?array $filterData = [
        'inactivated_from' => null,
        'inactivated_to' => null,
        'reason' => null,
    ];

    public array $selectedFields = ['barcode', 'custom_description', 'cost_price', 'inactivated_at', 'inactivated_reason', 'inactivated_by']; 

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Report Filters')
                ->schema([
                    Grid::make(3)->schema([
                        DatePicker::make('filterData.inactivated_from')->label('Inactivated From')->live(),
                        DatePicker::make('filterData.inactivated_to')->label('To')->live(),
                        Select::make('filterData.reason')
                            ->label('Reason')
                            ->options(ProductItem::whereNotNull('inactivated_reason')->distinct()->pluck('inactivated_reason', 'inactivated_reason'))
                            ->placeholder('All Reasons')
                            ->live(),
                    ]),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ProductItem::query()->where('status', 'inactive'))
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->when($this->filterData['inactivated_from'] ?? null, fn($q, $d) => $q->whereDate('inactivated_at', '>=', $d))
                ->when($this->filterData['inactivated_to'] ?? null, fn($q, $d) => $q->whereDate('inactivated_at', '<=', $d))
                ->when($this->filterData['reason'] ?? null, fn($q, $v) => $q->where('inactivated_reason', $v))
                ->latest('inactivated_at')
            )
            ->columns([
                TextColumn::make('barcode')->label('STOCK NO.')->searchable()->sortable(),
                TextColumn::make('custom_description')->label('DESCRIPTION')->limit(40),
                TextColumn::make('cost_price')->label('COST')->money('USD'),
                TextColumn::make('retail_price')->label('RETAIL')->money('USD'),
                TextColumn::make('inactivated_at')->label('DATE REMOVED')->date()->sortable(),
                TextColumn::make('inactivated_by')->label('REMOVED BY')->badge()->color('gray'),
                TextColumn::make('inactivated_reason')->label('REASON')->wrap()->color('danger'),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $staff = \App\Helpers\Staff::user();
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }
}