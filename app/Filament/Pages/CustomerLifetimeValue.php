<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\Customer;
use App\Services\GeminiCustomerPredictorService;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\{Section, Select};
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerLifetimeValue extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'CLV Predictor';
    protected static ?string $title           = 'Customer Lifetime Value Predictor';
    protected static string  $view            = 'filament.pages.customer-lifetime-value';

    public ?int    $customer_id   = null;
    public ?array  $prediction    = null;
    public ?array  $customerStats = null;
    public ?array  $selectedCustomer = null;
    public string  $aiStatus      = '';
    public string  $aiError       = '';
    public ?array  $data          = [];

    public function mount(): void
    {
        $this->form->fill([]);

        // Restore selectedCustomer if customer_id already in state
        if ($this->customer_id) {
            $c = Customer::find($this->customer_id);
            if ($c) {
                $this->selectedCustomer = [
                    'id'    => $c->id,
                    'name'  => trim($c->name . ' ' . ($c->last_name ?? '')),
                    'phone' => $c->phone,
                    'no'    => $c->customer_no,
                    'image' => $c->image ? asset('storage/' . $c->image) : null,
                ];
                $this->form->fill(['customer_id' => $c->id]);
            }
        }
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Section::make()->schema([
                Select::make('customer_id')
                    ->label('Select Customer')
                    ->placeholder('Search by name or phone...')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return Customer::query()
                            ->where(function ($q) use ($search) {
                                $q->whereRaw("CONCAT(name,' ',last_name) LIKE ?", ["%{$search}%"])
                                  ->orWhere('phone', 'like', "%{$search}%")
                                  ->orWhere('customer_no', 'like', "%{$search}%");
                            })
                            ->limit(30)
                            ->get()
                            ->mapWithKeys(fn($c) => [
                                $c->id => "{$c->name} {$c->last_name} | {$c->phone} (#{$c->customer_no})"
                            ]);
                    })
                    ->getOptionLabelUsing(fn($value) => optional(Customer::find($value), fn($c) =>
                        "{$c->name} {$c->last_name} | {$c->phone}"
                    ))
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->customer_id         = $state ? (int)$state : null;
                        $this->data['customer_id'] = $state ? (int)$state : null;
                        $this->prediction          = null;
                        $this->customerStats       = null;
                        $this->selectedCustomer    = null;
                        $this->aiStatus            = '';
                        $this->aiError             = '';

                        if ($state) {
                            $c = Customer::find($state);
                            if ($c) {
                                $this->selectedCustomer = [
                                    'id'    => $c->id,
                                    'name'  => $c->name . ' ' . ($c->last_name ?? ''),
                                    'phone' => $c->phone,
                                    'no'    => $c->customer_no,
                                    'image' => $c->image ? asset('storage/' . $c->image) : null,
                                ];
                            }
                        }
                    })
                    ->native(false),
            ]),
        ]);
    }

    public function quickPick(int $id): void
    {
        $this->customer_id         = $id;
        $this->data['customer_id'] = $id;
        $this->prediction          = null;
        $this->customerStats       = null;
        $this->aiStatus            = '';
        $this->aiError             = '';

        $c = Customer::find($id);
        if ($c) {
            $this->selectedCustomer = [
                'id'    => $c->id,
                'name'  => $c->name . ' ' . ($c->last_name ?? ''),
                'phone' => $c->phone,
                'no'    => $c->customer_no,
                'image' => $c->image ? asset('storage/' . $c->image) : null,
            ];
        }

        $this->form->fill(['customer_id' => $id]);
    }

    public function runPrediction(): void
    {
        $id = $this->customer_id ?? ($this->data['customer_id'] ?? null);
        if (!$id) return;

        $this->aiStatus = 'loading';
        $this->prediction = null;
        $this->aiError = '';

        $customer = Customer::find($id);
        if (!$customer) {
            $this->aiStatus = 'error';
            $this->aiError  = 'Customer not found.';
            return;
        }

        $sales = Sale::query()
            ->where('customer_id', $id)
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get(['id','final_total','payment_method','created_at','is_split_payment']);

        if ($sales->count() === 0) {
            $this->aiStatus = 'error';
            $this->aiError  = "{$customer->name} has no completed sales yet. Need at least 1 sale to predict.";
            return;
        }

        $totalSpent     = round($sales->sum('final_total'), 2);
        $totalPurchases = $sales->count();
        $avgSale        = round($totalSpent / $totalPurchases, 2);
        $firstPurchase  = Carbon::parse($sales->first()->created_at)->format('M d, Y');
        $lastPurchase   = Carbon::parse($sales->last()->created_at)->format('M d, Y');
        $daysSinceLast  = Carbon::parse($sales->last()->created_at)->diffInDays(now());
        $monthsAsCustomer = max(1, Carbon::parse($sales->first()->created_at)->diffInMonths(now()));

        $gaps = [];
        for ($i = 1; $i < $sales->count(); $i++) {
            $gaps[] = Carbon::parse($sales[$i-1]->created_at)->diffInDays($sales[$i]->created_at);
        }
        $avgFrequency = count($gaps) > 0 ? round(array_sum($gaps) / count($gaps)) : $monthsAsCustomer * 30;

        $monthCounts = $sales->groupBy(fn($s) => Carbon::parse($s->created_at)->format('F'))
            ->map->count()->sortDesc();
        $peakMonth = $monthCounts->keys()->first() ?? 'N/A';

        $topCategory = DB::table('sale_items')
            ->join('product_items', 'sale_items.product_item_id', '=', 'product_items.id')
            ->whereIn('sale_items.sale_id', $sales->pluck('id'))
            ->select('product_items.category', DB::raw('count(*) as cnt'))
            ->groupBy('product_items.category')
            ->orderByDesc('cnt')
            ->value('category') ?? 'Jewelry';

        $topMethod = $sales->groupBy('payment_method')->map->count()->sortDesc()->keys()->first() ?? 'CASH';
        $hasLaybuy = $sales->where('payment_method', 'laybuy')->count() > 0;

        $stats = [
            'name'                    => $customer->name,
            'total_purchases'         => $totalPurchases,
            'total_spent'             => $totalSpent,
            'avg_sale'                => $avgSale,
            'first_purchase'          => $firstPurchase,
            'last_purchase'           => $lastPurchase,
            'days_since_last'         => $daysSinceLast,
            'purchase_frequency_days' => $avgFrequency,
            'months_as_customer'      => $monthsAsCustomer,
            'top_category'            => $topCategory,
            'payment_method'          => strtoupper($topMethod),
            'has_laybuy'              => $hasLaybuy ? 'Yes' : 'No',
            'peak_month'              => $peakMonth,
        ];

        $this->customerStats = $stats;

        // Always ensure selectedCustomer is populated
        if (!$this->selectedCustomer) {
            $this->selectedCustomer = [
                'id'    => $customer->id,
                'name'  => trim($customer->name . ' ' . ($customer->last_name ?? '')),
                'phone' => $customer->phone,
                'no'    => $customer->customer_no,
                'image' => $customer->image ? asset('storage/' . $customer->image) : null,
            ];
        }

        $cacheKey = 'clv_prediction_' . tenant('id') . '_' . $id;
        $cached   = Cache::get($cacheKey);

        if ($cached) {
            $this->prediction = $cached;
            $this->aiStatus   = 'done';
            return;
        }

        $service    = new GeminiCustomerPredictorService();
        $prediction = $service->predict($stats);

        if (!$prediction) {
            $this->aiStatus = 'error';
            $this->aiError  = 'Gemini could not generate a prediction. Please try again.';
            return;
        }

        Cache::put($cacheKey, $prediction, now()->addHours(24));
        $this->prediction = $prediction;
        $this->aiStatus   = 'done';
    }

    public bool   $showContactModal = false;
    public string $contactMessage   = '';
    public string $contactMethod    = 'sms'; // sms | note

    public function openContactModal(): void
    {
        if (!$this->selectedCustomer || !$this->prediction) return;

        $name   = $this->selectedCustomer['name'];
        $action = $this->prediction['action_sentence'] ?? '';

        // Pre-fill a smart message from the AI action
        $this->contactMessage = "Hi {$name}, " . lcfirst($action);
        $this->showContactModal = true;
    }

    public function sendSms(): void
    {
        $phone = $this->selectedCustomer['phone'] ?? null;
        if (!$phone || !$this->contactMessage) return;

        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($digits, '1') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }
        $formattedPhone = '+1' . $digits;

        try {
            $settings = DB::table('site_settings')->pluck('value', 'key');
            $sns = new \Aws\Sns\SnsClient([
                'version'     => 'latest',
                'region'      => $settings['aws_sms_default_region'] ?? config('services.sns.region'),
                'credentials' => [
                    'key'    => $settings['aws_sms_access_key_id'] ?? config('services.sns.key'),
                    'secret' => $settings['aws_sms_secret_access_key'] ?? config('services.sns.secret'),
                ],
            ]);
            $sns->publish([
                'Message'     => $this->contactMessage,
                'PhoneNumber' => $formattedPhone,
                'MessageAttributes' => [
                    'OriginationNumber' => [
                        'DataType'    => 'String',
                        'StringValue' => $settings['aws_sns_sms_from'] ?? config('services.sns.sms_from'),
                    ],
                ],
            ]);

            \Filament\Notifications\Notification::make()
                ->title('SMS Sent ✓')
                ->body("Message sent to {$this->selectedCustomer['name']}")
                ->success()->send();

            $this->showContactModal = false;
            $this->contactMessage   = '';

        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('SMS Failed')
                ->body($e->getMessage())
                ->danger()->send();
        }
    }

    public function closeContactModal(): void
    {
        $this->showContactModal = false;
        $this->contactMessage   = '';
    }

    public function clearPrediction(): void
    {
        $this->prediction       = null;
        $this->customerStats    = null;
        $this->selectedCustomer = null;
        $this->aiStatus         = '';
        $this->aiError          = '';
        $this->customer_id      = null;
        $this->data             = [];
        $this->form->fill([]);
    }

  public function getTopCustomers(): array
    {
        return Sale::query()
            ->where('status', 'completed')
            ->with('customer')
            ->select('customer_id', DB::raw('SUM(final_total) as total'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->filter(fn($r) => $r->customer)
            ->map(fn($r) => [
                'id'    => $r->customer_id,
                'name'  => trim($r->customer->name . ' ' . ($r->customer->last_name ?? '')),
                'total' => round($r->total, 2),
                'cnt'   => $r->cnt,
            ])
            ->values()
            ->toArray();
    }

    // 🚀 NEW — how many months of no purchase counts as "inactive". Staff
    // can tweak this via the dropdown on the page without editing code.
    public int $inactiveMonths = 3;

    /**
     * 🚀 NEW — customers whose most recent completed sale is older than
     * $inactiveMonths ago (or who have purchased before but gone quiet),
     * sorted with the longest-dormant customers first. Only includes
     * customers with at least one completed sale — brand-new customers
     * with zero purchase history aren't "inactive", they're just new, and
     * the CLV predictor requires at least 1 sale to run anyway.
     */
    public function getInactiveCustomers(): array
    {
        $cutoff = now()->subMonths($this->inactiveMonths);

        return Sale::query()
            ->where('status', 'completed')
            ->with('customer')
            ->select(
                'customer_id',
                DB::raw('MAX(created_at) as last_purchase_at'),
                DB::raw('SUM(final_total) as total_spent'),
                DB::raw('COUNT(*) as total_purchases')
            )
            ->groupBy('customer_id')
            ->having('last_purchase_at', '<', $cutoff)
            ->orderBy('last_purchase_at', 'asc') // longest-dormant first
            ->limit(20)
            ->get()
            ->filter(fn($r) => $r->customer && $r->customer->is_active)
            ->map(fn($r) => [
                'id'             => $r->customer_id,
                'name'           => trim($r->customer->name . ' ' . ($r->customer->last_name ?? '')),
                'phone'          => $r->customer->phone,
                'last_purchase'  => Carbon::parse($r->last_purchase_at)->format('M d, Y'),
                'days_inactive'  => Carbon::parse($r->last_purchase_at)->diffInDays(now()),
                'total_spent'    => round($r->total_spent, 2),
                'total_purchases' => $r->total_purchases,
            ])
            ->values()
            ->toArray();
    }

    // 🚀 NEW — lets the "Inactive for" dropdown on the page re-filter live.
    public function updatedInactiveMonths(): void
    {
        // no-op body — Livewire re-renders and getInactiveCustomers() is
        // called fresh on every render since it's a plain method, not cached.
    }
}