<?php

namespace App\Filament\Resources\RepairResource\Pages;

use App\Filament\Resources\RepairResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\{Grid, Section, TextInput, Select};
use App\Forms\Components\CustomDatePicker;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

class ListRepairs extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = RepairResource::class;
    protected static string $view     = 'filament.pages.list-repairs';

    // ── URL-bound filter state (persists in browser URL like FindSale) ──

    #[\Livewire\Attributes\Url(as: 'keyword')]
    public ?string $keyword = null;

    #[\Livewire\Attributes\Url(as: 'customer')]
    public ?string $customer_name = null;

    #[\Livewire\Attributes\Url(as: 'staff')]
    public ?string $staff_name = null;

    #[\Livewire\Attributes\Url(as: 'status')]
    public ?string $filter_status = null;

    #[\Livewire\Attributes\Url(as: 'location')]
    public ?string $repair_location = null;

    #[\Livewire\Attributes\Url(as: 'from')]
    public ?string $date_from = null;

    #[\Livewire\Attributes\Url(as: 'to')]
    public ?string $date_to = null;

    public ?array $data = [];

    public function mount(): void
    {
        parent::mount();

        $this->data = [
            'keyword'         => $this->keyword,
            'customer_name'   => $this->customer_name,
            'staff_name'      => $this->staff_name,
            'filter_status'   => $this->filter_status,
            'repair_location' => $this->repair_location,
            'date_from'       => $this->date_from,
            'date_to'         => $this->date_to,
        ];

        $this->form->fill($this->data);
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->keyword         = $this->data['keyword']         ?? null;
            $this->customer_name   = $this->data['customer_name']   ?? null;
            $this->staff_name      = $this->data['staff_name']      ?? null;
            $this->filter_status   = $this->data['filter_status']   ?? null;
            $this->repair_location = $this->data['repair_location'] ?? null;
            $this->date_from       = $this->data['date_from']       ?? null;
            $this->date_to         = $this->data['date_to']         ?? null;

            $this->resetTable();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Search & Filter Repairs')
                    ->description('Filter by customer, staff, status, location, or date range')
                    ->schema([
                        Grid::make(4)->schema([

                            TextInput::make('keyword')
                                ->label('🔍 Keyword Search')
                                ->placeholder('e.g. ring, resize, R1234...')
                                ->prefixIcon('heroicon-o-magnifying-glass')
                                ->helperText('Searches job #, item description, issue, notes')
                                ->live(debounce: 600)
                                ->afterStateUpdated(function () {
                                    $this->resetPage();
                                    $this->resetTable();
                                })
                                ->columnSpanFull(),

                            TextInput::make('customer_name')
                                ->label('Customer Name')
                                ->placeholder('e.g. Smith')
                                ->live(debounce: 600)
                                ->afterStateUpdated(function () {
                                    $this->resetPage();
                                    $this->resetTable();
                                }),

                            TextInput::make('staff_name')
                                ->label('Sales Staff')
                                ->placeholder('e.g. Aaron')
                                ->live(debounce: 600)
                                ->afterStateUpdated(function () {
                                    $this->resetPage();
                                    $this->resetTable();
                                }),

                            Select::make('filter_status')
                                ->label('Status')
                                ->options([
                                    'received'    => 'Received',
                                    'in_progress' => 'In Progress',
                                    'ready'       => 'Ready for Pickup',
                                    'delivered'   => 'Delivered',
                                ])
                                ->placeholder('All Status')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),

                            Select::make('repair_location')
                                ->label('Location')
                                ->options(function () {
                                    return \App\Models\Repair::query()
                                        ->whereNotNull('repair_location')
                                        ->where('repair_location', '!=', '')
                                        ->distinct()
                                        ->orderBy('repair_location')
                                        ->pluck('repair_location', 'repair_location')
                                        ->toArray();
                                })
                                ->placeholder('All Locations')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),

                            CustomDatePicker::make('date_from')
                                ->label('Date From')
                                ->displayFormat('m/d/Y')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),

                            CustomDatePicker::make('date_to')
                                ->label('Date To')
                                ->displayFormat('m/d/Y')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                        ]),

                        \Filament\Forms\Components\Actions::make([
                            \Filament\Forms\Components\Actions\Action::make('reset_filters')
                                ->label('Clear Filters')
                                ->icon('heroicon-o-x-circle')
                                ->color('gray')
                                ->outlined()
                                ->action(function () {
                                    $this->keyword = $this->customer_name = $this->staff_name = null;
                                    $this->filter_status = $this->repair_location = null;
                                    $this->date_from = $this->date_to = null;

                                    $this->data = array_fill_keys(
                                        ['keyword', 'customer_name', 'staff_name', 'filter_status', 'repair_location', 'date_from', 'date_to'],
                                        null
                                    );

                                    $this->form->fill($this->data);
                                    $this->resetTable();
                                }),
                        ]),
                    ]),
            ]);
    }

    // ── Wire filter values into the table query ───────────────────────
    protected function applyFiltersToTableQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        // Keyword — searches job #, item descriptions, reported issues, notes
        if ($v = $this->data['keyword'] ?? $this->keyword ?? null) {
            $query->where(function ($q) use ($v) {
                $q->where('repair_no', 'like', "%{$v}%")
                  ->orWhere('repair_notes', 'like', "%{$v}%")
                  ->orWhereJsonContains('items', ['item_description' => $v])
                  ->orWhere(function ($q2) use ($v) {
                      // JSON search fallback for MySQL
                      $q2->whereRaw("JSON_SEARCH(items, 'all', ?, null, '$[*].item_description') IS NOT NULL", ["%{$v}%"])
                         ->orWhereRaw("JSON_SEARCH(items, 'all', ?, null, '$[*].reported_issue') IS NOT NULL", ["%{$v}%"]);
                  });
            });
        }

        // Customer name
        if ($v = $this->data['customer_name'] ?? $this->customer_name ?? null) {
            $query->whereHas('customer', fn($q) =>
                $q->where('name', 'like', "%{$v}%")
                  ->orWhere('last_name', 'like', "%{$v}%")
                  ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$v}%"])
                  ->orWhere('phone', 'like', "%{$v}%")
            );
        }

        // Staff name — searches sales_person_list JSON/string
        if ($v = $this->data['staff_name'] ?? $this->staff_name ?? null) {
            $query->where(function ($q) use ($v) {
                $q->whereHas('salesPerson', fn($sq) => $sq->where('name', 'like', "%{$v}%"))
                  ->orWhere('sales_person_list', 'like', "%{$v}%");
            });
        }

        // Status
        if ($v = $this->data['filter_status'] ?? $this->filter_status ?? null) {
            $query->where('status', $v);
        }

        // Location
        if ($v = $this->data['repair_location'] ?? $this->repair_location ?? null) {
            $query->where('repair_location', $v);
        }

        // Date range
        if ($v = $this->data['date_from'] ?? $this->date_from ?? null) {
            try {
                $query->whereDate('created_at', '>=', \Carbon\Carbon::parse($v)->format('Y-m-d'));
            } catch (\Exception $e) {}
        }

        if ($v = $this->data['date_to'] ?? $this->date_to ?? null) {
            try {
                $query->whereDate('created_at', '<=', \Carbon\Carbon::parse($v)->format('Y-m-d'));
            } catch (\Exception $e) {}
        }

        return $query;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('repair_manual')
                ->label('Docs')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->extraAttributes(['class' => 'docs-manual-btn'])
                ->outlined()
                ->modalHeading('Repair module guide')
                ->modalWidth('2xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->slideOver()
                ->form([
                    \Filament\Forms\Components\Placeholder::make('manual_content')
                        ->label('')
                        ->content(function () {
                            return new HtmlString('
<style>
  .rm-wrap{padding:1rem 0;font-family:inherit;}
  .rm-header{display:flex;align-items:center;gap:10px;margin-bottom:1.5rem;border-bottom:1px solid #e5e7eb;padding-bottom:1rem;}
  .rm-icon-wrap{width:36px;height:36px;border-radius:8px;background:#f9fafb;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
  .rm-section{margin-bottom:1.5rem;}
  .rm-section-title{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 10px;}
  .rm-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;}
  .rm-step{display:flex;gap:12px;padding:12px 14px;border-bottom:1px solid #f3f4f6;align-items:flex-start;}
  .rm-step:last-child{border-bottom:none;}
  .rm-num{width:22px;height:22px;border-radius:50%;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
  .rm-step-body{flex:1;}
  .rm-step-title{font-size:14px;font-weight:600;color:#111827;margin:0 0 2px;}
  .rm-step-desc{font-size:13px;color:#6b7280;margin:0;line-height:1.5;}
  .rm-tip{font-size:12px;color:#6b7280;background:#f9fafb;border-radius:6px;padding:4px 8px;margin-top:5px;display:inline-flex;align-items:center;gap:5px;}
  .rm-shortcuts{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px;}
  .rm-shortcut{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;display:flex;align-items:flex-start;gap:8px;}
  .rm-shortcut-title{font-size:13px;font-weight:600;color:#111827;margin:0 0 2px;}
  .rm-shortcut-desc{font-size:12px;color:#6b7280;margin:0;}
  .rm-flow{display:flex;align-items:center;gap:0;flex-wrap:wrap;margin-bottom:10px;}
  .rm-flow-box{font-size:11px;font-weight:600;padding:4px 10px;border-radius:6px;border:1px solid;white-space:nowrap;}
  .rm-flow-arrow{font-size:14px;color:#9ca3af;padding:0 4px;}
  .rm-divider{display:flex;align-items:center;gap:8px;margin:1.5rem 0 1rem;}
  .rm-divider-line{flex:1;height:1px;background:#f3f4f6;}
  .rm-divider-label{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em;white-space:nowrap;}
  .rm-list{list-style:none;margin:0;padding:0;}
  .rm-list li{display:flex;gap:8px;align-items:flex-start;padding:8px 14px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#374151;}
  .rm-list li:last-child{border-bottom:none;}
  .rm-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;margin-top:5px;}
</style>
<div class="rm-wrap">
  <div class="rm-header">
    <div class="rm-icon-wrap">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
    </div>
    <div>
      <p style="font-size:15px;font-weight:600;margin:0;color:#111827;">Repair module guide</p>
      <p style="font-size:13px;color:#6b7280;margin:0;">How to create, manage, notify, and bill repairs</p>
    </div>
  </div>
  <div class="rm-section">
    <p class="rm-section-title">Repair status flow</p>
    <div class="rm-flow">
      <span class="rm-flow-box" style="background:#f3f4f6;color:#374151;border-color:#d1d5db;">Received</span>
      <span class="rm-flow-arrow">→</span>
      <span class="rm-flow-box" style="background:#fef3c7;color:#b45309;border-color:#fde68a;">In progress</span>
      <span class="rm-flow-arrow">→</span>
      <span class="rm-flow-box" style="background:#dcfce7;color:#15803d;border-color:#bbf7d0;">Ready for pickup</span>
      <span class="rm-flow-arrow">→</span>
      <span class="rm-flow-box" style="background:#dbeafe;color:#1d4ed8;border-color:#bfdbfe;">Delivered</span>
    </div>
    <p style="font-size:12px;color:#9ca3af;margin:0;">Update status directly on the list table.</p>
  </div>
  <div class="rm-section">
    <p class="rm-section-title">Creating a new repair</p>
    <div class="rm-card">
      <div class="rm-step"><div class="rm-num">1</div><div class="rm-step-body"><p class="rm-step-title">Select or create a customer</p><p class="rm-step-desc">Search by name, phone, or customer number. Use the <strong>+</strong> button to add a new customer on the spot.</p></div></div>
      <div class="rm-step"><div class="rm-num">2</div><div class="rm-step-body"><p class="rm-step-title">Add jewelry items</p><p class="rm-step-desc">Click <strong>+ Add another jewelry item</strong> for each piece. For warranty items, toggle <em>Covered under warranty</em>.</p></div></div>
      <div class="rm-step"><div class="rm-num">3</div><div class="rm-step-body"><p class="rm-step-title">Assign staff &amp; set tracking</p><p class="rm-step-desc">Pick the sales associate(s). Fill in Dropped by, Date dropped, and Repair location.</p></div></div>
      <div class="rm-step"><div class="rm-num">4</div><div class="rm-step-body"><p class="rm-step-title">Save &amp; print</p><p class="rm-step-desc">The <em>Print job packet after saving</em> toggle auto-opens the printable card.</p></div></div>
    </div>
  </div>
</div>
                            ');
                        }),
                ]),

            Actions\CreateAction::make(),
        ];
    }

    public function resetFilters(): void
    {
        $this->keyword = $this->customer_name = $this->staff_name = null;
        $this->filter_status = $this->repair_location = null;
        $this->date_from = $this->date_to = null;

        $this->form->fill(array_fill_keys(
            ['keyword', 'customer_name', 'staff_name', 'filter_status', 'repair_location', 'date_from', 'date_to'],
            null
        ));
        $this->resetTable();
    }

    // ── Override the table query to apply our custom filters ─────────
    // This is called by Filament on every table render, giving us direct
    // access to $this (the Livewire component) so we can read filter state.
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = \App\Models\Repair::query()->with(['customer', 'sale']);

        // Keyword — repair_no, notes, item descriptions, reported issues
        if ($v = $this->data['keyword'] ?? $this->keyword ?? null) {
            $query->where(function ($q) use ($v) {
                $q->where('repair_no', 'like', "%{$v}%")
                  ->orWhere('repair_notes', 'like', "%{$v}%")
                  ->orWhereRaw("JSON_SEARCH(items, 'all', ?, null, '\$[*].item_description') IS NOT NULL", ["%{$v}%"])
                  ->orWhereRaw("JSON_SEARCH(items, 'all', ?, null, '\$[*].reported_issue') IS NOT NULL", ["%{$v}%"]);
            });
        }

        // Customer name / phone
        if ($v = $this->data['customer_name'] ?? $this->customer_name ?? null) {
            $query->whereHas('customer', fn($q) =>
                $q->where('name', 'like', "%{$v}%")
                  ->orWhere('last_name', 'like', "%{$v}%")
                  ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$v}%"])
                  ->orWhere('phone', 'like', "%{$v}%")
            );
        }

        // Staff name
        if ($v = $this->data['staff_name'] ?? $this->staff_name ?? null) {
            $query->where(function ($q) use ($v) {
                $q->whereHas('salesPerson', fn($sq) => $sq->where('name', 'like', "%{$v}%"))
                  ->orWhere('sales_person_list', 'like', "%{$v}%");
            });
        }

        // Status
        if ($v = $this->data['filter_status'] ?? $this->filter_status ?? null) {
            $query->where('status', $v);
        }

        // Location
        if ($v = $this->data['repair_location'] ?? $this->repair_location ?? null) {
            $query->where('repair_location', $v);
        }

        // Date from
        if ($v = $this->data['date_from'] ?? $this->date_from ?? null) {
            try { $query->whereDate('created_at', '>=', \Carbon\Carbon::parse($v)->format('Y-m-d')); } catch (\Exception $e) {}
        }

        // Date to
        if ($v = $this->data['date_to'] ?? $this->date_to ?? null) {
            try { $query->whereDate('created_at', '<=', \Carbon\Carbon::parse($v)->format('Y-m-d')); } catch (\Exception $e) {}
        }

        return $query->latest('created_at');
    }
}