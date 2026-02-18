<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;

class ScrapGoldCalculator extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.scrap-gold-calculator';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'spot_price' => 0,
            'karat' => '14',
            'weight' => 0,
            'payout_percentage' => 70,
            'calculated_value' => '0.00',
            'calculated_profit' => '0.00',
        ]);

        $this->fetchLivePrice();
    }

    public function fetchLivePrice(): void
    {
        try {
            $response = Http::timeout(3)->get('https://data-asg.goldprice.org/dbXRates/USD');

            if ($response->successful()) {
                $price = $response->json()['items'][0]['xauPrice'] ?? 2650.00;

                $this->form->fill([
                    ...$this->form->getRawState(),
                    'spot_price' => number_format($price, 2, '.', ''),
                ]);

                Notification::make()
                    ->title('Live Market Data Connected')
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            // silently fail if API fails
        }

        $this->calculate($this->form->getRawState());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(4)->schema([

                    // 1. Spot Price
                    Forms\Components\TextInput::make('spot_price')
                        ->label('Spot Price (1 oz)')
                        ->prefix('$')
                        ->numeric()
                        ->live(onBlur: true)
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('refresh')
                                ->icon('heroicon-m-arrow-path')
                                ->color('primary')
                                ->action(fn () => $this->fetchLivePrice())
                        )
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            $this->calculate($this->form->getRawState(), $set)
                        ),

                    // 2. Karat
                    Forms\Components\Select::make('karat')
                        ->label('Gold Purity')
                        ->options([
                            '10' => '10k (41.7%)',
                            '14' => '14k (58.5%)',
                            '18' => '18k (75%)',
                            '22' => '22k (91.6%)',
                            '24' => '24k (99.9%)',
                        ])
                        ->default('14')
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            $this->calculate($this->form->getRawState(), $set)
                        ),

                    // 3. Weight
                    Forms\Components\TextInput::make('weight')
                        ->label('Weight (g)')
                        ->numeric()
                        ->suffix('g')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            $this->calculate($this->form->getRawState(), $set)
                        ),

                    // 4. Payout Percentage
                    Forms\Components\TextInput::make('payout_percentage')
                        ->label('Payout %')
                        ->numeric()
                        ->suffix('%')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) =>
                            $this->calculate($this->form->getRawState(), $set)
                        ),
                ]),

                // Hidden fields for calculation output
                Forms\Components\Hidden::make('calculated_value'),
                Forms\Components\Hidden::make('calculated_profit'),

            ])
            ->statePath('data');
    }

    public function calculate(array $state, ?Set $set = null): void
    {
        $setter = $set ?? fn ($k, $v) =>
            $this->form->fill([
                ...$this->form->getRawState(),
                $k => $v,
            ]);

        $spot = (float) ($state['spot_price'] ?? 0);
        $karat = (int) ($state['karat'] ?? 14);
        $weight = (float) ($state['weight'] ?? 0);
        $percent = ((float) ($state['payout_percentage'] ?? 70)) / 100;

        if ($spot > 0 && $weight > 0) {
            $pricePerGram = ($spot / 31.1035) * ($karat / 24);
            $totalValue = $pricePerGram * $weight;

            $setter('calculated_value', number_format($totalValue * $percent, 2));
            $setter('calculated_profit', number_format($totalValue * (1 - $percent), 2));
        } else {
            $setter('calculated_value', '0.00');
            $setter('calculated_profit', '0.00');
        }
    }
}