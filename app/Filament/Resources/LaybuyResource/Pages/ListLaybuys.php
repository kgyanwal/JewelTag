<?php

namespace App\Filament\Resources\LaybuyResource\Pages;

use App\Filament\Resources\LaybuyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListLaybuys extends ListRecords
{
    protected static string $resource = LaybuyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('laybuy_manual')
                ->label('Docs')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->outlined()
                ->modalHeading('Layby Plans — Docs')
                ->modalWidth('3xl')
                ->extraAttributes(['class' => 'docs-manual-btn'])
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->slideOver()
                ->form([
                    \Filament\Forms\Components\Placeholder::make('manual_content')
                        ->label('')
                        ->content(function () {
                            return new HtmlString('
<style>
  .lb-wrap{padding:1rem 0;font-family:inherit;}
  .lb-header{display:flex;align-items:center;gap:10px;margin-bottom:1.5rem;border-bottom:1px solid #e5e7eb;padding-bottom:1rem;}
  .lb-icon{width:36px;height:36px;border-radius:8px;background:#f9fafb;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
  .lb-section{margin-bottom:1.75rem;}
  .lb-section-title{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 10px;}
  .lb-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;}
  .lb-step{display:flex;gap:12px;padding:12px 14px;border-bottom:1px solid #f3f4f6;align-items:flex-start;}
  .lb-step:last-child{border-bottom:none;}
  .lb-num{width:22px;height:22px;border-radius:50%;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
  .lb-step-body{flex:1;}
  .lb-step-title{font-size:14px;font-weight:600;color:#111827;margin:0 0 2px;}
  .lb-step-desc{font-size:13px;color:#6b7280;margin:0;line-height:1.5;}
  .lb-alert{border-radius:10px;padding:14px 16px;margin:12px 0;display:flex;gap:10px;align-items:flex-start;}
  .lb-alert-icon{font-size:18px;flex-shrink:0;margin-top:1px;}
  .lb-alert-body{flex:1;}
  .lb-alert-title{font-size:13px;font-weight:700;margin:0 0 4px;}
  .lb-alert-desc{font-size:12px;margin:0;line-height:1.6;}
  .lb-alert-info{background:#f0f9ff;border:1.5px solid #0ea5e9;}
  .lb-alert-info .lb-alert-title{color:#0369a1;}
  .lb-alert-info .lb-alert-desc{color:#075985;}
  .lb-alert-success{background:#f0fdf4;border:1.5px solid #22c55e;}
  .lb-alert-success .lb-alert-title{color:#15803d;}
  .lb-alert-success .lb-alert-desc{color:#166534;}
  .lb-alert-warning{background:#fffbeb;border:1.5px solid #f59e0b;}
  .lb-alert-warning .lb-alert-title{color:#b45309;}
  .lb-alert-warning .lb-alert-desc{color:#92400e;}
  .lb-flow{display:flex;align-items:center;gap:0;flex-wrap:wrap;margin-bottom:12px;}
  .lb-flow-box{font-size:11px;font-weight:600;padding:5px 11px;border-radius:6px;border:1px solid;white-space:nowrap;}
  .lb-flow-arrow{font-size:14px;color:#9ca3af;padding:0 5px;}
  .lb-divider{display:flex;align-items:center;gap:8px;margin:1.75rem 0 1rem;}
  .lb-divider-line{flex:1;height:1px;background:#f3f4f6;}
  .lb-divider-label{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em;white-space:nowrap;}
  .lb-shortcuts{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;}
  .lb-shortcut{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;}
  .lb-shortcut-title{font-size:13px;font-weight:600;color:#111827;margin:0 0 3px;}
  .lb-shortcut-desc{font-size:12px;color:#6b7280;margin:0;}
  .lb-list{list-style:none;margin:0;padding:0;}
  .lb-list li{display:flex;gap:8px;align-items:flex-start;padding:8px 14px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#374151;}
  .lb-list li:last-child{border-bottom:none;}
  .lb-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;margin-top:5px;}
  .lb-badge{display:inline-flex;align-items:center;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;border:1px solid;white-space:nowrap;}
</style>

<div class="lb-wrap">

  <div class="lb-header">
    <div class="lb-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
    </div>
    <div>
      <p style="font-size:15px;font-weight:700;margin:0;color:#111827;">Layby Plans — Docs</p>
      <p style="font-size:13px;color:#6b7280;margin:0;">Installment plans, payment tracking, and what happens in Find Sale</p>
    </div>
  </div>

  <!-- ─── WHAT IS A LAYBY ────────────────────────────────────── -->
  <div class="lb-section">
    <p class="lb-section-title">What is a Layby plan?</p>
    <div class="lb-alert lb-alert-info">
      <div class="lb-alert-icon">💡</div>
      <div class="lb-alert-body">
        <p class="lb-alert-title">Simple idea</p>
        <p class="lb-alert-desc">A customer wants an item but pays in installments over time. The item is reserved (put On Hold) immediately so no one else can buy it. Every payment the customer makes is recorded here AND reflected in <strong>Find Sale</strong> and <strong>EOD totals</strong> automatically. When the balance reaches $0.00, the item status flips from On Hold to Sold.</p>
      </div>
    </div>
  </div>

  <!-- ─── STATUS FLOW ──────────────────────────────────────────── -->
  <div class="lb-section">
    <p class="lb-section-title">Plan lifecycle</p>
    <div class="lb-flow">
      <span class="lb-flow-box" style="background:#fffbeb;color:#b45309;border-color:#fde68a;">⏳ Active Plan</span>
      <span class="lb-flow-arrow">→ payments added →</span>
      <span class="lb-flow-box" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;">✅ Fully Paid</span>
    </div>
    <div class="lb-flow">
      <span class="lb-flow-box" style="background:#fffbeb;color:#b45309;border-color:#fde68a;">⏳ Active Plan</span>
      <span class="lb-flow-arrow">→ cancelled →</span>
      <span class="lb-flow-box" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca;">❌ Cancelled</span>
    </div>
    <p style="font-size:12px;color:#9ca3af;margin:4px 0 0;">Items return to In Stock automatically when a plan is cancelled.</p>
  </div>

  <!-- ─── CREATING A LAYBY ──────────────────────────────────────── -->
  <div class="lb-section">
    <p class="lb-section-title">Creating a new layby plan</p>
    <div class="lb-card">
      <div class="lb-step">
        <div class="lb-num">1</div>
        <div class="lb-step-body">
          <p class="lb-step-title">Select the customer</p>
          <p class="lb-step-desc">Search by name, phone, or customer number. The customer must exist in the system before you can create a plan.</p>
        </div>
      </div>
      <div class="lb-step">
        <div class="lb-num">2</div>
        <div class="lb-step-body">
          <p class="lb-step-title">Scan or search items to reserve</p>
          <p class="lb-step-desc">Use the <strong>Add Item to Plan</strong> search box to scan barcodes or search by description. Only In Stock items appear. Each item is added to the reservation list with its price. You can edit the price if needed.</p>
        </div>
      </div>
      <div class="lb-step">
        <div class="lb-num">3</div>
        <div class="lb-step-body">
          <p class="lb-step-title">Enter the initial deposit (optional)</p>
          <p class="lb-step-desc">Enter how much the customer is paying today in the <strong>Initial Deposit</strong> field and select a payment method. If $0, no deposit is recorded and the full amount remains as balance.</p>
        </div>
      </div>
      <div class="lb-step">
        <div class="lb-num">4</div>
        <div class="lb-step-body">
          <p class="lb-step-title">Set the payment deadline and save</p>
          <p class="lb-step-desc">Pick a due date by which the full balance should be paid. Add any internal notes, then save. The system automatically creates the sale record, puts items On Hold, and logs the deposit.</p>
        </div>
      </div>
      <div class="lb-step" style="background:#f0fdf4;">
        <div class="lb-num" style="background:#dcfce7;color:#15803d;">✓</div>
        <div class="lb-step-body">
          <p class="lb-step-title" style="color:#15803d;">What happens automatically after saving</p>
          <p class="lb-step-desc">A sale record is created in <strong>Find Sale</strong> with status Pending. All reserved items are marked <strong>On Hold</strong>. The deposit (if any) is recorded in both the Layby ledger and the EOD payment logs. The balance shows in Find Sale as well.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="lb-divider">
    <div class="lb-divider-line"></div>
    <span class="lb-divider-label">Taking installment payments</span>
    <div class="lb-divider-line"></div>
  </div>

  <!-- ─── ADDING PAYMENTS ──────────────────────────────────────── -->
  <div class="lb-section">
    <div class="lb-card">
      <div class="lb-step">
        <div class="lb-num">1</div>
        <div class="lb-step-body">
          <p class="lb-step-title">Click Add Payment on the table row</p>
          <p class="lb-step-desc">Find the plan in the list and click the green <strong>Add Payment</strong> button. The amount defaults to the full remaining balance — change it if the customer is paying a partial amount today.</p>
        </div>
      </div>
      <div class="lb-step">
        <div class="lb-num">2</div>
        <div class="lb-step-body">
          <p class="lb-step-title">Select the payment method and confirm</p>
          <p class="lb-step-desc">Pick CASH, VISA, etc. and click the confirm button. The system records the payment in three places simultaneously: the Layby installment ledger, the linked sale record in Find Sale, and the EOD payment log.</p>
        </div>
      </div>
      <div class="lb-step" style="background:#f0fdf4;">
        <div class="lb-num" style="background:#dcfce7;color:#15803d;">✓</div>
        <div class="lb-step-body">
          <p class="lb-step-title" style="color:#15803d;">When balance reaches $0.00</p>
          <p class="lb-step-desc">The plan status changes to <strong>Fully Paid</strong>, the sale status changes to <strong>Completed</strong>, and all reserved items are released from On Hold and marked as <strong>Sold</strong> automatically. No manual steps needed.</p>
        </div>
      </div>
    </div>

    <div class="lb-alert lb-alert-success">
      <div class="lb-alert-icon">✅</div>
      <div class="lb-alert-body">
        <p class="lb-alert-title">Payments reflect in Find Sale automatically</p>
        <p class="lb-alert-desc">Every payment you record here is linked to the sale that was created when the plan started. If you open that sale in <strong>Find Sale → Edit</strong>, you will see the payment listed in the Payment Log. EOD totals also update immediately — you do not need to do anything extra in Find Sale.</p>
      </div>
    </div>
  </div>

  <div class="lb-divider">
    <div class="lb-divider-line"></div>
    <span class="lb-divider-label">Row actions on the list</span>
    <div class="lb-divider-line"></div>
  </div>

  <!-- ─── ROW ACTIONS ──────────────────────────────────────────── -->
  <div class="lb-section">
    <div class="lb-shortcuts">
      <div class="lb-shortcut">
        <p class="lb-shortcut-title">💵 Add Payment</p>
        <p class="lb-shortcut-desc">Record a new installment. Updates the layby, the linked sale, and EOD all at once. Only visible if balance > $0.</p>
      </div>
      <div class="lb-shortcut">
        <p class="lb-shortcut-title">✏️ Edit / Ledger</p>
        <p class="lb-shortcut-desc">Opens the full edit form where you can view the complete payment history and update plan details, notes, or deadline.</p>
      </div>
      <div class="lb-shortcut">
        <p class="lb-shortcut-title">❌ Cancel Plan</p>
        <p class="lb-shortcut-desc">Cancels the plan and releases all reserved items back to In Stock. The linked sale is also marked Cancelled. Cannot be undone.</p>
      </div>
    </div>
  </div>

  <!-- ─── READING THE TABLE ────────────────────────────────────── -->
  <div class="lb-divider">
    <div class="lb-divider-line"></div>
    <span class="lb-divider-label">Reading the table columns</span>
    <div class="lb-divider-line"></div>
  </div>

  <div class="lb-section">
    <div class="lb-card">
      <ul class="lb-list">
        <li>
          <div class="lb-dot" style="background:#0369a1;"></div>
          <span><strong>Progress bar</strong> — shows paid vs total at a glance. Red = less than 50% paid, blue = over 50%, green = fully paid.</span>
        </li>
        <li>
          <div class="lb-dot" style="background:#b91c1c;"></div>
          <span><strong>Balance</strong> — the exact amount still owed. If this is $0.00 the plan should be marked Fully Paid.</span>
        </li>
        <li>
          <div class="lb-dot" style="background:#b45309;"></div>
          <span><strong>Deadline</strong> — shows in red if the due date has passed and the plan is still Active. Follow up with the customer.</span>
        </li>
        <li>
          <div class="lb-dot" style="background:#15803d;"></div>
          <span><strong>Status badge</strong> — Active (yellow), Paid (green), Cancelled (red). Status updates automatically when the balance hits $0.</span>
        </li>
      </ul>
    </div>
  </div>

  <!-- ─── FIND SALE CONNECTION ─────────────────────────────────── -->
  <div class="lb-section">
    <p class="lb-section-title">The connection to Find Sale</p>
    <div class="lb-alert lb-alert-warning">
      <div class="lb-alert-icon">⚠️</div>
      <div class="lb-alert-body">
        <p class="lb-alert-title">Always manage layby payments from the Layby Plans page</p>
        <p class="lb-alert-desc">Even though the sale exists in Find Sale, do NOT add payments to it directly from the Find Sale edit screen. Always use the <strong>Add Payment</strong> button here on the Layby Plans list. Doing it here ensures the layby ledger, the sale record, and the EOD totals all stay in sync. Adding from Find Sale directly will cause the layby balance to become out of sync.</p>
      </div>
    </div>
  </div>

</div>
                            ');
                        }),
                ]),

            Actions\CreateAction::make(),
        ];
    }
}