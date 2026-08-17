<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class DiamondSearch extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Diamond Vault';
    protected static ?string $title           = 'Diamond Vault Search';
    protected static ?int    $navigationSort  = 5;
    protected static string  $view            = 'filament.pages.diamond-search';

    // ── FILTER STATE ──────────────────────────────────────────────────────
    public array  $selectedShapes = [];
    public string $priceMin       = '';
    public string $priceMax       = '';
    public string $caratMin       = '';
    public string $caratMax       = '';
    public int    $cutMin         = 0;   // 0=Good 1=Very Good 2=Excellent 3=Ideal
    public int    $cutMax         = 3;
    public int    $clarityMin     = 0;   // 0=FL ... 14=P3
    public int    $clarityMax     = 14;
    public int    $colourMin      = 0;   // 0=D ... 22=Z
    public int    $colourMax      = 22;
    public string $stockType      = 'in_house'; // in_house | memo | all

    public array  $results        = [];
    public bool   $searched       = false;
    public string $sortBy         = 'carat';
    public string $sortDir        = 'desc';

    // ── CONSTANTS ─────────────────────────────────────────────────────────
    public static array $SHAPES = [
        'Round', 'Princess', 'Emerald', 'Asscher',
        'Marquise', 'Oval', 'Radiant', 'Pear',
        'Heart', 'Cushion', 'Trillion', 'Other',
    ];

    public static array $CUT_LABELS = ['Good', 'Very Good', 'Excellent', 'Ideal'];

    public static array $CLARITY_LABELS = [
        'FL','IF','LC','VVS1','VVS2','VS1','VS2','SI1','SI2','SI3','I1','I2','I3','P1','P3'
    ];

    public static array $COLOUR_LABELS = [
        'D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'
    ];

    public function search(): void
    {
        $cutValues      = ['Good','Very Good','Excellent','Ideal'];
        $clarityValues  = ['FL','IF','LC','VVS1','VVS2','VS1','VS2','SI1','SI2','SI3','I1','I2','I3','P1','P3'];
        $colourValues   = ['D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

        $selectedCuts      = array_slice($cutValues,     $this->cutMin,     $this->cutMax     - $this->cutMin     + 1);
        $selectedClarities = array_slice($clarityValues, $this->clarityMin, $this->clarityMax - $this->clarityMin + 1);
        $selectedColours   = array_slice($colourValues,  $this->colourMin,  $this->colourMax  - $this->colourMin  + 1);

        $query = DB::table('product_items')
            ->where(function ($q) {
                $q->where('department', 'Diamonds')
                  ->orWhere('department', 'Diamond')
                  ->orWhere('category', 'Diamond')
                  ->orWhereNotNull('diamond_weight');
            })
            ->whereIn('status', ['in_stock', 'memo']);

        if ($this->stockType === 'in_house') $query->where('status', 'in_stock');
        if ($this->stockType === 'memo')     $query->where('status', 'memo');

        if (!empty($this->selectedShapes)) {
            $query->whereIn('shape', $this->selectedShapes);
        }
        if ($this->priceMin !== '') $query->where('retail_price', '>=', floatval($this->priceMin));
        if ($this->priceMax !== '') $query->where('retail_price', '<=', floatval($this->priceMax));
        if ($this->caratMin !== '') $query->where('diamond_weight', '>=', floatval($this->caratMin));
        if ($this->caratMax !== '') $query->where('diamond_weight', '<=', floatval($this->caratMax));

        if ($this->cutMax < 3 || $this->cutMin > 0) {
            $query->whereIn('cut', $selectedCuts);
        }
        if ($this->clarityMax < 14 || $this->clarityMin > 0) {
            $query->whereIn('clarity', $selectedClarities);
        }
        if ($this->colourMax < 22 || $this->colourMin > 0) {
            $query->whereIn('color', $selectedColours); // ← actual DB column name
        }

        $colMap = [
            'barcode'        => 'barcode',
            'diamond_weight' => 'diamond_weight',
            'retail_price'   => 'retail_price',
            'cut'            => 'cut',
            'clarity'        => 'clarity',
            'colour'         => 'color',   // map UI name → DB column
            'shape'          => 'shape',
        ];
        $sortCol = $colMap[$this->sortBy] ?? 'diamond_weight';
        $sortDir = $this->sortDir === 'asc' ? 'asc' : 'desc';

        $this->results = $query->orderBy($sortCol, $sortDir)
            ->select([
                'id', 'barcode', 'shape', 'diamond_weight', 'cut', 'clarity',
                'color',               // DB column
                'retail_price', 'cost_price',
                'sub_department',      // use as location
                'status', 'custom_description',
                'certificate_number', 'certificate_agency',
                'fluorescence', 'polish', 'symmetry',
                'is_lab_grown',
            ])
            ->limit(200)
            ->get()
            ->toArray();

        $this->searched = true;
    }

    public function clearSearch(): void
    {
        $this->selectedShapes = [];
        $this->priceMin = $this->priceMax = $this->caratMin = $this->caratMax = '';
        $this->cutMin = $this->clarityMin = $this->colourMin = 0;
        $this->cutMax = 3; $this->clarityMax = 14; $this->colourMax = 22;
        $this->stockType = 'in_house';
        $this->results = [];
        $this->searched = false;
    }

    public function sortResults(string $col): void
    {
        if ($this->sortBy === $col) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $col;
            $this->sortDir = 'asc';
        }
        if ($this->searched) $this->search();
    }

    public function toggleShape(string $shape): void
    {
        if (in_array($shape, $this->selectedShapes)) {
            $this->selectedShapes = array_values(array_filter($this->selectedShapes, fn($s) => $s !== $shape));
        } else {
            $this->selectedShapes[] = $shape;
        }
    }
}