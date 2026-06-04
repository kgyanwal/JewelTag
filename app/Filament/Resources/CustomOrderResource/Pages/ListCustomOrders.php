<?php

namespace App\Filament\Resources\CustomOrderResource\Pages;

use App\Filament\Resources\CustomOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListCustomOrders extends ListRecords
{
    protected static string $resource = CustomOrderResource::class;

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
<style>
  .co-wrap{padding:1rem 0;font-family:inherit;}
  .co-header{display:flex;align-items:center;gap:10px;margin-bottom:1.5rem;border-bottom:1px solid #e5e7eb;padding-bottom:1rem;}
  .co-icon{width:36px;height:36px;border-radius:8px;background:#f9fafb;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
  .co-section{margin-bottom:1.75rem;}
  .co-section-title{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 10px;}
  .co-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;}
  .co-step{display:flex;gap:12px;padding:12px 14px;border-bottom:1px solid #f3f4f6;align-items:flex-start;}
  .co-step:last-child{border-bottom:none;}
  .co-num{width:22px;height:22px;border-radius:50%;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
  .co-step-body{flex:1;}
  .co-step-title{font-size:14px;font-weight:600;color:#111827;margin:0 0 2px;}
  .co-step-desc{font-size:13px;color:#6b7280;margin:0;line-height:1.5;}

  /* ─── ALERT BOXES ─── */
  .co-alert{border-radius:10px;padding:14px 16px;margin:12px 0;display:flex;gap:10px;align-items:flex-start;}
  .co-alert-icon{font-size:18px;flex-shrink:0;margin-top:1px;}
  .co-alert-body{flex:1;}
  .co-alert-title{font-size:13px;font-weight:700;margin:0 0 4px;}
  .co-alert-desc{font-size:12px;margin:0;line-height:1.5;}
  .co-alert-warning{background:#fffbeb;border:1.5px solid #f59e0b;}
  .co-alert-warning .co-alert-title{color:#b45309;}
  .co-alert-warning .co-alert-desc{color:#92400e;}
  .co-alert-danger{background:#fef2f2;border:1.5px solid #ef4444;}
  .co-alert-danger .co-alert-title{color:#b91c1c;}
  .co-alert-danger .co-alert-desc{color:#991b1b;}
  .co-alert-info{background:#f0f9ff;border:1.5px solid #0ea5e9;}
  .co-alert-info .co-alert-title{color:#0369a1;}
  .co-alert-info .co-alert-desc{color:#075985;}
  .co-alert-success{background:#f0fdf4;border:1.5px solid #22c55e;}
  .co-alert-success .co-alert-title{color:#15803d;}
  .co-alert-success .co-alert-desc{color:#166534;}

  /* ─── FLOW ─── */
  .co-flow{display:flex;align-items:center;gap:0;flex-wrap:wrap;margin-bottom:12px;}
  .co-flow-box{font-size:11px;font-weight:600;padding:5px 11px;border-radius:6px;border:1px solid;white-space:nowrap;}
  .co-flow-arrow{font-size:14px;color:#9ca3af;padding:0 5px;}
  .co-divider{display:flex;align-items:center;gap:8px;margin:1.75rem 0 1rem;}
  .co-divider-line{flex:1;height:1px;background:#f3f4f6;}
  .co-divider-label{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em;white-space:nowrap;}
  .co-shortcuts{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;}
  .co-shortcut{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;}
  .co-shortcut-title{font-size:13px;font-weight:600;color:#111827;margin:0 0 3px;}
  .co-shortcut-desc{font-size:12px;color:#6b7280;margin:0;}
  .co-list{list-style:none;margin:0;padding:0;}
  .co-list li{display:flex;gap:8px;align-items:flex-start;padding:8px 14px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#374151;}
  .co-list li:last-child{border-bottom:none;}
  .co-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;margin-top:5px;}
  .co-compare{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:4px;}
  .co-compare-col{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;}
  .co-compare-head{padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;}
  .co-compare-body{padding:10px 12px;}
  .co-compare-body p{font-size:12px;color:#374151;margin:0 0 5px;line-height:1.5;}
  .co-compare-body p:last-child{margin-bottom:0;}
</style>

<div class="co-wrap">

  <div class="co-header">
    <div class="co-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
    </div>
    <div>
      <p style="font-size:15px;font-weight:700;margin:0;color:#111827;">Custom Orders — Docs</p>
      <p style="font-size:13px;color:#6b7280;margin:0;">Two paths to a custom order. Read this before you start.</p>
    </div>
  </div>

  <!-- ─── THE TWO PATHS ─────────────────────────────────────── -->
  <div class="co-section">
    <p class="co-section-title">The two ways a custom order is created</p>
    <div class="co-compare">
      <div class="co-compare-col">
        <div class="co-compare-head" style="background:#eff6ff;color:#1d4ed8;">PATH A — From Quick Sale</div>
        <div class="co-compare-body">
          <p>Staff goes to <strong>Quick Sale → + New Custom Order</strong> inside the sale form.</p>
          <p>A custom order record is created automatically in the background.</p>
          <p>The deposit paid at that sale is recorded on the <strong>sale</strong>.</p>
          <p style="color:#15803d;font-weight:600;">✅ The sale IS the transaction. It appears in sales reports immediately after status completed.</p>
        </div>
      </div>
      <div class="co-compare-col">
        <div class="co-compare-head" style="background:#f5f3ff;color:#7c3aed;">PATH B — From Custom Orders page</div>
        <div class="co-compare-body">
          <p>Staff goes to <strong>Custom Orders → New</strong> and creates the order here.</p>
          <p>The deposit is recorded on the custom order only — <strong>no sale exists yet</strong>.</p>
          <p>Payments added here go to the custom order deposit ledger.</p>
          <p style="color:#b45309;font-weight:600;">⚠️ This does NOT appear in sales reports until you Convert to Sale.</p>
        </div>
      </div>
    </div>

    <div class="co-alert co-alert-warning">
      <div class="co-alert-icon">⚠️</div>
      <div class="co-alert-body">
        <p class="co-alert-title">Why your sale might not show in the Sales Report</p>
        <p class="co-alert-desc">If you created the custom order from the <strong>Custom Orders page (Path B)</strong> and have not yet converted it to a sale, it will <strong>not appear</strong> in My Sales Report. You must complete the order AND convert it to a sale first. The SALE column in this table will show "Not linked to sale" until that happens.</p>
      </div>
    </div>
  </div>

  <!-- ─── STATUS FLOW ─────────────────────────────────────────── -->
  <div class="co-section">
    <p class="co-section-title">Status flow</p>
    <div class="co-flow">
      <span class="co-flow-box" style="background:#f3f4f6;color:#374151;border-color:#d1d5db;">Draft</span>
      <span class="co-flow-arrow">→</span>
      <span class="co-flow-box" style="background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe;">Approved</span>
      <span class="co-flow-arrow">→</span>
      <span class="co-flow-box" style="background:#fffbeb;color:#b45309;border-color:#fde68a;">In Production</span>
      <span class="co-flow-arrow">→</span>
      <span class="co-flow-box" style="background:#f0f9ff;color:#0369a1;border-color:#bae6fd;">Ready for Pickup ⬅️ key step</span>
      <span class="co-flow-arrow">→</span>
      <span class="co-flow-box" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;">Completed</span>
    </div>
    <p style="font-size:12px;color:#9ca3af;margin:0;">You can update status directly on the list — no need to open edit. The <strong>Ready for Pickup</strong> stage unlocks the Convert to Sale action.</p>
  </div>

  <!-- ─── PATH B FULL WALKTHROUGH ──────────────────────────────── -->
  <div class="co-section">
    <p class="co-section-title">Path B — Full walkthrough (Custom Orders page)</p>
    <div class="co-card">
      <div class="co-step">
        <div class="co-num">1</div>
        <div class="co-step-body">
          <p class="co-step-title">Create the order & take the deposit</p>
          <p class="co-step-desc">Fill in customer, product details, quoted price, and deposit amount. Select a payment method and save. The deposit is stored against this custom order record.</p>
        </div>
      </div>
      <div class="co-step">
        <div class="co-num">2</div>
        <div class="co-step-body">
          <p class="co-step-title">Collect additional deposits (if needed)</p>
          <p class="co-step-desc">Use the <strong>Add Deposit</strong> button on the table row to record more payments at any time. Each payment is logged in the deposit history. All of these will be credited when converting to sale.</p>
        </div>
      </div>
      <div class="co-step">
        <div class="co-num">3</div>
        <div class="co-step-body">
          <p class="co-step-title">Mark as Ready for Pickup</p>
          <p class="co-step-desc">When the piece is finished, click <strong>More → Mark Ready &amp; Notify</strong>. This changes status to <em>Ready for Pickup</em> and optionally sends the customer an SMS or email. This step is required before converting.</p>
        </div>
      </div>
      <div class="co-step">
        <div class="co-num">4</div>
        <div class="co-step-body">
          <p class="co-step-title">Convert to Sale — click More → Convert to Sale</p>
          <p class="co-step-desc">This opens the Quick Sale screen pre-loaded with the customer and the custom order. All prior deposits appear as split payment rows automatically.</p>
        </div>
      </div>

      <div style="padding:12px 14px;background:#fef2f2;border-top:1px solid #fee2e2;">
        <div style="font-size:12px;font-weight:700;color:#b91c1c;margin-bottom:6px;">🚫 CRITICAL — Read before you touch the sale</div>
        <div style="font-size:12px;color:#991b1b;line-height:1.6;">
          When the Quick Sale screen opens after Convert to Sale, the prior deposits are already pre-filled as rows in the payment breakdown. <strong>Do not change the amounts. Do not add a new payment row unless the customer is paying the remaining balance right now.</strong> Simply verify the totals look correct and click <strong>Complete Sale</strong>. Editing existing payment rows will cause double-counting in EOD and payment records.
        </div>
      </div>

      <div class="co-step" style="background:#f0fdf4;">
        <div class="co-num" style="background:#dcfce7;color:#15803d;">5</div>
        <div class="co-step-body">
          <p class="co-step-title" style="color:#15803d;">Done — now it appears in Sales Reports</p>
          <p class="co-step-desc">Once the sale is saved, the SALE column on the custom orders table shows a link to the invoice. The transaction now appears in My Sales Report and EOD totals.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="co-divider">
    <div class="co-divider-line"></div>
    <span class="co-divider-label">Row actions — click the three dots (...) on any row</span>
    <div class="co-divider-line"></div>
  </div>

  <!-- ─── THREE DOTS ACTIONS ───────────────────────────────────── -->
  <div class="co-section">
    <div class="co-shortcuts">
      <div class="co-shortcut">
        <p class="co-shortcut-title">✏️ Edit</p>
        <p class="co-shortcut-desc">Opens the full edit form. Update specs, pricing, vendor details, or status.</p>
      </div>
      <div class="co-shortcut">
        <p class="co-shortcut-title">🕐 Notify: Delay</p>
        <p class="co-shortcut-desc">Sends the customer an SMS or email that their order is delayed. Message is logged.</p>
      </div>
      <div class="co-shortcut">
        <p class="co-shortcut-title">✅ Mark Ready</p>
        <p class="co-shortcut-desc">Sets status to Ready for Pickup and notifies the customer. Required before converting.</p>
      </div>
      <div class="co-shortcut">
        <p class="co-shortcut-title">🛒 Convert to Sale</p>
        <p class="co-shortcut-desc">Only visible when status = Ready for Pickup. Opens Quick Sale with everything pre-filled.</p>
      </div>
      <div class="co-shortcut">
        <p class="co-shortcut-title">🖨️ Print Receipt</p>
        <p class="co-shortcut-desc">Prints the deposit receipt. If linked to a sale, prints the full sale receipt instead.</p>
      </div>
      <div class="co-shortcut">
        <p class="co-shortcut-title">💬 Message History</p>
        <p class="co-shortcut-desc">Shows all SMS/email notifications sent to the customer for this order.</p>
      </div>
    </div>
  </div>

  <!-- ─── SALE LINK COLUMN ─────────────────────────────────────── -->
  <div class="co-divider">
    <div class="co-divider-line"></div>
    <span class="co-divider-label">Reading the table columns</span>
    <div class="co-divider-line"></div>
  </div>

  <div class="co-section">
    <div class="co-card">
      <ul class="co-list">
        <li>
          <div class="co-dot" style="background:#0369a1;"></div>
          <span><strong>SALE column</strong> — shows a blue invoice link if this order has been converted to a sale. Shows "Not linked to sale" if not yet converted (Path B only). Path A orders are always linked from day one.</span>
        </li>
        <li>
          <div class="co-dot" style="background:#15803d;"></div>
          <span><strong>NEXT ACTION column</strong> — shows the recommended next step as a clickable button. When the order is fully converted, it shows "✅ Converted to Sale" with the invoice number.</span>
        </li>
        <li>
          <div class="co-dot" style="background:#15803d;"></div>
          <span><strong>STATUS column</strong> — editable inline. Changing to <em>Ready for Pickup</em> here does NOT automatically notify the customer — use <strong>More → Mark Ready &amp; Notify</strong> for that.</span>
        </li>
        <li>
          <div class="co-dot" style="background:#b45309;"></div>
          <span><strong>PAID column</strong> — sum of all deposits collected against this order. Does not include the final payment if that was made at the sale screen.</span>
        </li>
        <li>
          <div class="co-dot" style="background:#b45309;"></div>
          <span><strong>BALANCE column</strong> — red means money still owed, green means fully paid. Always rechecks the live deposit total from the database.</span>
        </li>
        <li>
          <div class="co-dot" style="background:#6b7280;"></div>
          <span><strong>NOTIFIED column</strong> — shows the date of the last customer notification. Click the date to view the full message history log.</span>
        </li>
      </ul>
    </div>
  </div>

  <!-- ─── IMPORTANT WARNINGS ───────────────────────────────────── -->
  <div class="co-section">
    <p class="co-section-title">Important rules to remember</p>

    <div class="co-alert co-alert-danger">
      <div class="co-alert-icon">🚫</div>
      <div class="co-alert-body">
        <p class="co-alert-title">Do NOT edit the sale after Convert to Sale</p>
        <p class="co-alert-desc">After Convert to Sale opens the Quick Sale screen, only click Complete Sale. Do not manually add or remove payment rows that were pre-filled from deposits. Doing so will cause the EOD to count those deposits twice and show inflated totals.</p>
      </div>
    </div>

    <div class="co-alert co-alert-warning">
      <div class="co-alert-icon">⚠️</div>
      <div class="co-alert-body">
        <p class="co-alert-title">Sales Reports — Path B orders need Convert to Sale first</p>
        <p class="co-alert-desc">If you created the order from this page (Path B) and it still says "Not linked to sale", it will not appear in My Sales Report. Once converted and the sale is saved, it will show up in reports with the correct date.</p>
      </div>
    </div>

    <div class="co-alert co-alert-info">
      <div class="co-alert-icon">💡</div>
      <div class="co-alert-body">
        <p class="co-alert-title">Paying the remaining balance before pickup</p>
        <p class="co-alert-desc">Use the <strong>Add Deposit</strong> button on the table row to collect partial payments at any time before converting. When you finally convert to sale, only the true remaining balance will be left to collect — the prior deposits are already credited automatically.</p>
      </div>
    </div>

    <div class="co-alert co-alert-success">
      <div class="co-alert-icon">✅</div>
      <div class="co-alert-body">
        <p class="co-alert-title">How to tell if everything is correct</p>
        <p class="co-alert-desc">After converting: the SALE column shows a blue invoice link, NEXT ACTION shows "✅ Converted to Sale", BALANCE shows $0.00 in green, and the sale appears in My Sales Report. If any of these are missing, check with a manager.</p>
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