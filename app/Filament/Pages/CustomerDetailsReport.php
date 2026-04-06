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
use Filament\Forms\Components\{Section, Grid, CheckboxList, TextInput, Actions, Group};
use App\Forms\Components\CustomDatePicker;
use COM;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CustomerDetailsReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Analytics & Reports'; 
    protected static ?string $navigationLabel = 'Customer Report Builder';
    protected static string $view = 'filament.pages.customer-details-report';

    public ?array $filterData = [];
    
    // Controlled by the CheckboxList
    public array $selectedFields = ['customer_no', 'name', 'phone', 'email', 'city']; 

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getColumnOptions(): array
    {
        return [
            'id' => 'System ID',
            'customer_no' => 'Customer ID',
            'name' => 'First Name',
            'last_name' => 'Last Name',
            'phone' => 'Mobile Phone',
            'email' => 'Email Address',
            'dob' => 'Date of Birth',
            'wedding_anniversary' => 'Anniversary',
            'address' => 'Full Address',
            'city' => 'City',
            'state' => 'State',
            'postcode' => 'Postcode',
            'loyalty_tier' => 'Loyalty Tier',
            'gold_preference' => 'Gold Preference',
            'gender' => 'Gender',
            'spouse_name' => 'Spouse Name',
            'lh_ring' => 'Left Ring Size',
            'rh_ring' => 'Right Ring Size',
            'is_active' => 'Status',
            'created_at' => 'Date Created',
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Report Configuration')
                ->description('Configure dates and specific data points for extraction.')
                ->schema([
                    Grid::make(3)->schema([
                        CustomDatePicker::make('date_from')->label('Date Created From')->live(),
                        CustomDatePicker::make('date_to')->label('Date Created To')->live(),
                        TextInput::make('postcode')->placeholder('Filter by Postcode')->live(),
                    ]),

                    Group::make()->schema([
                        CheckboxList::make('selectedFields')
                            ->label('Select Columns to Include')
                            ->options($this->getColumnOptions())
                            ->columns(4) // Clean 4-column grid
                            ->bulkToggleable() // 🚀 The "Select All" functionality
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->selectedFields = $state),
                    ])->extraAttributes(['class' => 'mt-6']),
                ]),
        ])->statePath('filterData');
    }

    public function table(Table $table): Table
    {
        $allPossible = $this->getColumnOptions();
        $columns = [];

        foreach ($allPossible as $key => $label) {
            $columns[] = TextColumn::make($key)
                ->label(strtoupper($label))
                ->visible(fn() => in_array($key, $this->selectedFields))
                ->sortable();
        }

        return $table
            ->query(Customer::query())
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->when($this->filterData['date_from'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                ->when($this->filterData['date_to'] ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
                ->when($this->filterData['postcode'] ?? null, fn($q, $v) => $q->where('postcode', 'like', "%{$v}%"))
                ->latest()
            )
            ->columns($columns)
            ->paginated([10, 25, 50]);
    }

  public function exportReport(): \Symfony\Component\HttpFoundation\StreamedResponse
{
    $allPossible = $this->getColumnOptions();
    $fields = $this->selectedFields;

    // Build query with same filters as table
    $query = Customer::query()
        ->when($this->filterData['date_from'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
        ->when($this->filterData['date_to'] ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
        ->when($this->filterData['postcode'] ?? null, fn($q, $v) => $q->where('postcode', 'like', "%{$v}%"))
        ->latest();

    $filename = 'customer-report-' . now()->format('Y-m-d-His') . '.csv';

    return response()->streamDownload(function () use ($query, $fields, $allPossible) {
        $handle = fopen('php://output', 'w');

        // Header row — use the human-readable labels
        $headers = array_map(fn($f) => $allPossible[$f] ?? $f, $fields);
        fputcsv($handle, $headers);

        // Data rows — chunk for memory efficiency
        $query->chunk(200, function ($customers) use ($handle, $fields) {
            foreach ($customers as $customer) {
                $row = [];
                foreach ($fields as $field) {
                    if ($field === 'address') {
                        $row[] = trim("{$customer->street} {$customer->city} {$customer->state} {$customer->postcode}");
                    } elseif ($field === 'is_active') {
                        $row[] = $customer->is_active ? 'Active' : 'Inactive';
                    } elseif ($field === 'dob' || $field === 'wedding_anniversary') {
                        $row[] = $customer->$field ? \Carbon\Carbon::parse($customer->$field)->format('m/d/Y') : '';
                    } elseif ($field === 'created_at') {
                        $row[] = $customer->created_at?->format('m/d/Y');
                    } else {
                        $row[] = $customer->$field ?? '';
                    }
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