<?php

namespace App\Filament\Resources\CustomOrderResource\Pages;

use App\Filament\Resources\CustomOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListCustomOrders extends ListRecords
{
    protected static string $resource = CustomOrderResource::class;

    public function mount(): void
    {
        parent::mount();

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::body.end',
            fn () => new HtmlString('
                <script>
                    window.addEventListener("open-create-sale-now", (event) => {
                        const detail = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                        const recordId = detail && detail.recordId;
                        let attempts = 0;

                        const tryClick = () => {
                            attempts++;

                            const candidates = Array.from(document.querySelectorAll("button"))
                                .filter(b => b.textContent.trim().includes("Create Sale"));

                            const btn = candidates.find(b => {
                                const row = b.closest("tr");
                                return row && recordId && row.innerHTML.includes(recordId);
                            }) || candidates[0];

                            if (btn) {
                                btn.click();
                            } else if (attempts < 25) {
                                setTimeout(tryClick, 200);
                            }
                        };

                        setTimeout(tryClick, 400);
                    });
                </script>
            ')
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('custom_order_manual')
                ->label('Docs')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->outlined()
                ->modalHeading('Custom Orders — Docs')
                ->modalWidth('3xl')
                ->modalSubmitAction(false)
                ->extraAttributes(['class' => 'docs-manual-btn'])
                ->modalCancelActionLabel('Close')
                ->slideOver()
                ->form([
                    \Filament\Forms\Components\Placeholder::make('manual_content')
                        ->label('')
                        ->content(function () {
                            return new HtmlString('
<div class="co-wrap">
  <p style="font-size:15px;font-weight:700;margin:0;color:#111827;">Custom Orders — Docs</p>
  <p style="font-size:13px;color:#6b7280;margin-top:8px;">
      When the balance reaches $0.00, a green "Create Sale" button appears on the row.
      Click it, enter your staff PIN, and the sale is created instantly with all deposits already credited.
  </p>
</div>
                            ');
                        }),
                ]),

            Actions\CreateAction::make(),
        ];
    }
}