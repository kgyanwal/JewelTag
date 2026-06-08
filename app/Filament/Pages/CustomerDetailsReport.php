<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\{Section, Grid, CheckboxList, TextInput, Group};
use App\Forms\Components\CustomDatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CustomerDetailsReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'Customer Report Builder';
    protected static string  $view            = 'filament.pages.customer-details-report';

    public ?array $filterData    = [];
    public array  $selectedFields = ['customer_no', 'name', 'phone', 'email', 'city'];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getColumnOptions(): array
    {
        return [
            'id'                 => 'System ID',
            'customer_no'        => 'Customer ID',
            'name'               => 'First Name',
            'last_name'          => 'Last Name',
            'phone'              => 'Mobile Phone',
            'email'              => 'Email Address',
            'dob'                => 'Date of Birth',
            'wedding_anniversary'=> 'Anniversary',
            'address'            => 'Full Address',
            'city'               => 'City',
            'state'              => 'State',
            'postcode'           => 'Postcode',
            'loyalty_tier'       => 'Loyalty Tier',
            'gold_preference'    => 'Gold Preference',
            'gender'             => 'Gender',
            'spouse_name'        => 'Spouse Name',
            'lh_ring'            => 'Left Ring Size',
            'rh_ring'            => 'Right Ring Size',
            'is_active'          => 'Status',
            'created_at'         => 'Date Created',
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([

            // ── DATE RANGE ──────────────────────────────────────────────────
            Section::make('Date Range')
                ->description('Filter customers by when they were added.')
                ->compact()
                ->schema([
                    Grid::make(2)->schema([
                        CustomDatePicker::make('date_from')
                            ->label('From Date')
                            ->placeholder('Start date')
                            ->live(),
                        CustomDatePicker::make('date_to')
                            ->label('To Date')
                            ->placeholder('End date')
                            ->live(),
                    ]),
                ]),

            // ── OTHER FILTERS ────────────────────────────────────────────────
            Section::make('Other Filters')
                ->compact()
                ->schema([
                    TextInput::make('postcode')
                        ->label('Filter by Postcode')
                        ->placeholder('e.g. 80203')
                        ->live(),
                ]),

            // ── COLUMN PICKER ────────────────────────────────────────────────
            Section::make('Select Columns to Export')
                ->description('Choose which data points appear in the preview and CSV download.')
                ->compact()
                ->schema([
                    CheckboxList::make('selectedFields')
                        ->label('')
                        ->options($this->getColumnOptions())
                        ->columns(2)
                        ->bulkToggleable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn($state) => $this->selectedFields = $state ?? []),
                ]),

        ])->statePath('filterData');
    }

    public function table(Table $table): Table
    {
        $allPossible = $this->getColumnOptions();
        $columns     = [];

        foreach ($allPossible as $key => $label) {
            $columns[] = TextColumn::make($key)
                ->label(strtoupper($label))
                ->visible(fn() => in_array($key, $this->selectedFields))
                ->sortable()
                ->limit(30);
        }

        return $table
            ->query(Customer::query())
            ->modifyQueryUsing(fn(Builder $query) => $query
                ->when($this->filterData['date_from'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                ->when($this->filterData['date_to']   ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
                ->when($this->filterData['postcode']  ?? null, fn($q, $v) => $q->where('postcode', 'like', "%{$v}%"))
                ->latest()
            )
            ->columns($columns)
            ->paginated([10, 25, 50])
            ->striped();
    }

    public function exportReport(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $allPossible = $this->getColumnOptions();
        $fields      = $this->selectedFields;

        if (empty($fields)) {
            Notification::make()->title('No columns selected')->body('Please select at least one column before exporting.')->warning()->send();
        }

        $query = Customer::query()
            ->when($this->filterData['date_from'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($this->filterData['date_to']   ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($this->filterData['postcode']  ?? null, fn($q, $v) => $q->where('postcode', 'like', "%{$v}%"))
            ->latest();

        $filename = 'customer-report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($query, $fields, $allPossible) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, array_map(fn($f) => $allPossible[$f] ?? $f, $fields));

            // Data rows
            $query->chunk(200, function ($customers) use ($handle, $fields) {
                foreach ($customers as $customer) {
                    $row = [];
                    foreach ($fields as $field) {
                        $row[] = match ($field) {
                            'address'            => trim("{$customer->street} {$customer->city} {$customer->state} {$customer->postcode}"),
                            'is_active'          => $customer->is_active ? 'Active' : 'Inactive',
                            'dob',
                            'wedding_anniversary'=> $customer->$field ? \Carbon\Carbon::parse($customer->$field)->format('m/d/Y') : '',
                            'created_at'         => $customer->created_at?->format('m/d/Y'),
                            default              => $customer->$field ?? '',
                        };
                    }
                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}