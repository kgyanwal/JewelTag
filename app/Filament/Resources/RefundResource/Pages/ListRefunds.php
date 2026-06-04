<?php

namespace App\Filament\Resources\RefundResource\Pages;

use App\Filament\Resources\RefundResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListRefunds extends ListRecords
{
    protected static string $resource = RefundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refund_manual')
                ->label('Docs')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->extraAttributes(['class' => 'docs-manual-btn'])
                ->outlined()
                ->modalHeading('Refunds &#8212; Docs')
                ->modalWidth('3xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->slideOver()
                ->form([
                    \Filament\Forms\Components\Placeholder::make('manual_content')
                        ->label('')
                        ->content(fn() => new HtmlString(self::getManualHtml())),
                ]),

            Actions\CreateAction::make(),
        ];
    }

    private static function getManualHtml(): string
    {
        $css = '
<style>
.rf-wrap{padding:1rem 0;font-family:inherit;}
.rf-header{display:flex;align-items:center;gap:10px;margin-bottom:1.5rem;border-bottom:1px solid #e5e7eb;padding-bottom:1rem;}
.rf-icon{width:36px;height:36px;border-radius:8px;background:#f9fafb;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.rf-section{margin-bottom:1.75rem;}
.rf-section-title{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 10px;}
.rf-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;}
.rf-step{display:flex;gap:12px;padding:12px 14px;border-bottom:1px solid #f3f4f6;align-items:flex-start;}
.rf-step:last-child{border-bottom:none;}
.rf-num{width:22px;height:22px;border-radius:50%;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
.rf-step-body{flex:1;}
.rf-step-title{font-size:14px;font-weight:600;color:#111827;margin:0 0 2px;}
.rf-step-desc{font-size:13px;color:#6b7280;margin:0;line-height:1.5;}
.rf-alert{border-radius:10px;padding:14px 16px;margin:12px 0;display:flex;gap:10px;align-items:flex-start;}
.rf-alert-icon{font-size:18px;flex-shrink:0;margin-top:1px;}
.rf-alert-body{flex:1;}
.rf-alert-title{font-size:13px;font-weight:700;margin:0 0 4px;}
.rf-alert-desc{font-size:12px;margin:0;line-height:1.6;}
.rf-info{background:#f0f9ff;border:1.5px solid #0ea5e9;}
.rf-info .rf-alert-title{color:#0369a1;}
.rf-info .rf-alert-desc{color:#075985;}
.rf-success{background:#f0fdf4;border:1.5px solid #22c55e;}
.rf-success .rf-alert-title{color:#15803d;}
.rf-success .rf-alert-desc{color:#166534;}
.rf-warning{background:#fffbeb;border:1.5px solid #f59e0b;}
.rf-warning .rf-alert-title{color:#b45309;}
.rf-warning .rf-alert-desc{color:#92400e;}
.rf-danger{background:#fef2f2;border:1.5px solid #ef4444;}
.rf-danger .rf-alert-title{color:#b91c1c;}
.rf-danger .rf-alert-desc{color:#991b1b;}
.rf-flow{display:flex;align-items:center;gap:0;flex-wrap:wrap;margin-bottom:12px;}
.rf-flow-box{font-size:11px;font-weight:600;padding:5px 11px;border-radius:6px;border:1px solid;white-space:nowrap;}
.rf-flow-arrow{font-size:14px;color:#9ca3af;padding:0 5px;}
.rf-divider{display:flex;align-items:center;gap:8px;margin:1.75rem 0 1rem;}
.rf-divider-line{flex:1;height:1px;background:#f3f4f6;}
.rf-divider-label{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em;white-space:nowrap;}
.rf-grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:4px;}
.rf-col{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;}
.rf-col-head{padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;}
.rf-col-body{padding:10px 12px;}
.rf-col-body p{font-size:12px;color:#374151;margin:0 0 5px;line-height:1.5;}
.rf-list{list-style:none;margin:0;padding:0;}
.rf-list li{display:flex;gap:8px;align-items:flex-start;padding:8px 14px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#374151;}
.rf-list li:last-child{border-bottom:none;}
.rf-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;margin-top:5px;}
</style>';

        $body = '
<div class="rf-wrap">

  <div class="rf-header">
    <div class="rf-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3"/></svg>
    </div>
    <div>
      <p style="font-size:15px;font-weight:700;margin:0;color:#111827;">Refunds &#8212; Docs</p>
      <p style="font-size:13px;color:#6b7280;margin:0;">Approving a refund returns items to stock and updates the sale record</p>
    </div>
  </div>

  <!-- WHAT IS A REFUND -->
  <div class="rf-section">
    <p class="rf-section-title">What happens when a refund is approved</p>
    <div class="rf-alert rf-info">
      <div class="rf-alert-icon">&#128161;</div>
      <div class="rf-alert-body">
        <p class="rf-alert-title">Three things happen automatically</p>
        <p class="rf-alert-desc">When a refund is <strong>approved</strong>: (1) all refunded stock items are returned to <strong>In Stock</strong> in Find Stock, (2) the original sale is marked as <strong>Refunded</strong> or <strong>Partially Refunded</strong>, and (3) the refund amount is logged for EOD reconciliation. Nothing happens until a manager approves &#8212; pending refunds have no effect on stock or reports.</p>
      </div>
    </div>
  </div>

  <!-- REFUND STATUS FLOW -->
  <div class="rf-section">
    <p class="rf-section-title">Refund lifecycle</p>
    <div class="rf-flow">
      <span class="rf-flow-box" style="background:#fffbeb;color:#b45309;border-color:#fde68a;">&#9203; Pending</span>
      <span class="rf-flow-arrow">&#8594; approved &#8594;</span>
      <span class="rf-flow-box" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;">&#10003; Approved &#8212; items back to stock</span>
    </div>
    <div class="rf-flow">
      <span class="rf-flow-box" style="background:#fffbeb;color:#b45309;border-color:#fde68a;">&#9203; Pending</span>
      <span class="rf-flow-arrow">&#8594; rejected &#8594;</span>
      <span class="rf-flow-box" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca;">&#10007; Rejected &#8212; no changes made</span>
    </div>
    <p style="font-size:12px;color:#9ca3af;margin:4px 0 0;">A <strong>Partial refund</strong> means only some items on the sale were returned. The sale status changes to <em>Partially Refunded</em> and only those specific items go back to stock.</p>
  </div>

  <!-- HOW TO START A REFUND -->
  <div class="rf-section">
    <p class="rf-section-title">How to start a refund</p>
    <div class="rf-alert rf-info">
      <div class="rf-alert-icon">&#128276;</div>
      <div class="rf-alert-body">
        <p class="rf-alert-title">Refunds are started from Find Sale &#8212; not from this page</p>
        <p class="rf-alert-desc">Go to <strong>Sales &#8594; Find Sale</strong>, find the completed sale, and click the red <strong>Refund</strong> button in the actions column. This opens the refund creation form pre-loaded with the sale details. You cannot start a refund from this Refunds page directly.</p>
      </div>
    </div>
  </div>

  <div class="rf-divider">
    <div class="rf-divider-line"></div>
    <span class="rf-divider-label">Creating a refund &#8212; step by step</span>
    <div class="rf-divider-line"></div>
  </div>

  <!-- STEP BY STEP -->
  <div class="rf-section">
    <div class="rf-card">
      <div class="rf-step">
        <div class="rf-num">1</div>
        <div class="rf-step-body">
          <p class="rf-step-title">Click Refund on the completed sale</p>
          <p class="rf-step-desc">In <strong>Find Sale</strong>, the red Refund button appears only on sales with status <em>Completed</em>. Click it to open the refund form pre-filled with the sale&#39;s invoice number and items.</p>
        </div>
      </div>
      <div class="rf-step">
        <div class="rf-num">2</div>
        <div class="rf-step-body">
          <p class="rf-step-title">Select which items to refund</p>
          <p class="rf-step-desc">Tick the checkboxes next to the items being returned. For a full refund, select all items. For a partial refund, select only the items coming back. The refund amount calculates automatically from the item prices.</p>
        </div>
      </div>
      <div class="rf-step">
        <div class="rf-num">3</div>
        <div class="rf-step-body">
          <p class="rf-step-title">Fill in reason and refund method</p>
          <p class="rf-step-desc">Enter the reason for the return and select how the customer will be refunded (Cash, Card, Store Credit, etc.). This is required for the manager reviewing the refund.</p>
        </div>
      </div>
      <div class="rf-step">
        <div class="rf-num">4</div>
        <div class="rf-step-body">
          <p class="rf-step-title">Save &#8212; refund goes to Pending status</p>
          <p class="rf-step-desc">The refund is saved with status <strong>Pending</strong>. No stock or sale changes yet. A notification is sent to managers for review. The refund now appears on this page.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="rf-divider">
    <div class="rf-divider-line"></div>
    <span class="rf-divider-label">Approving or rejecting &#8212; manager action</span>
    <div class="rf-divider-line"></div>
  </div>

  <!-- APPROVAL -->
  <div class="rf-section">
    <div class="rf-card">
      <div class="rf-step">
        <div class="rf-num">1</div>
        <div class="rf-step-body">
          <p class="rf-step-title">Manager reviews the refund on this page</p>
          <p class="rf-step-desc">Find the pending refund in the list. Click <strong>View</strong> or open it to see the full details: which items, which sale, the reason, and the refund amount.</p>
        </div>
      </div>
      <div class="rf-step">
        <div class="rf-num">2</div>
        <div class="rf-step-body">
          <p class="rf-step-title">Click Approve</p>
          <p class="rf-step-desc">Clicking <strong>Approve</strong> triggers all three automatic actions simultaneously: items go back to <strong>In Stock</strong> in Find Stock, the original sale status changes to <em>Refunded</em> (or <em>Partially Refunded</em>), and the refund is logged in EOD records.</p>
        </div>
      </div>
      <div class="rf-step" style="background:#f0fdf4;">
        <div class="rf-num" style="background:#dcfce7;color:#15803d;">&#10003;</div>
        <div class="rf-step-body">
          <p class="rf-step-title" style="color:#15803d;">What happens to the items in Find Stock</p>
          <p class="rf-step-desc">Each refunded item with a barcode (product_item_id) is updated: status changes from <strong>Sold</strong> back to <strong>In Stock</strong>, hold flags are cleared, and the item reappears immediately in Find Stock searches with full availability. Items can be sold again immediately.</p>
        </div>
      </div>
      <div class="rf-step" style="background:#fef2f2;">
        <div class="rf-step-body" style="padding-left:6px;">
          <p class="rf-step-title" style="color:#b91c1c;">If Reject is clicked instead</p>
          <p class="rf-step-desc" style="color:#991b1b;">Nothing changes. Stock stays as Sold, the sale stays Completed, and no payment adjustment is made. The refund status changes to Rejected and is logged for audit purposes only.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- FULL vs PARTIAL -->
  <div class="rf-section">
    <p class="rf-section-title">Full refund vs partial refund</p>
    <div class="rf-grid2">
      <div class="rf-col">
        <div class="rf-col-head" style="background:#fef2f2;color:#b91c1c;">Full Refund</div>
        <div class="rf-col-body">
          <p>All items on the sale are returned.</p>
          <p>Sale status changes to <strong>Refunded</strong>.</p>
          <p>All barcoded items return to <strong>In Stock</strong>.</p>
          <p>Full refund amount is logged in EOD.</p>
        </div>
      </div>
      <div class="rf-col">
        <div class="rf-col-head" style="background:#fffbeb;color:#b45309;">Partial Refund</div>
        <div class="rf-col-body">
          <p>Only selected items are returned.</p>
          <p>Sale status changes to <strong>Partially Refunded</strong>.</p>
          <p>Only the returned item barcodes go back to In Stock.</p>
          <p>Partial amount logged in EOD.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- TABLE COLUMNS -->
  <div class="rf-divider">
    <div class="rf-divider-line"></div>
    <span class="rf-divider-label">Reading the table columns</span>
    <div class="rf-divider-line"></div>
  </div>

  <div class="rf-section">
    <div class="rf-card">
      <ul class="rf-list">
        <li><div class="rf-dot" style="background:#b91c1c;"></div><span><strong>STATUS</strong> &#8212; Pending (waiting for manager), Approved (done, stock returned), Rejected (no action taken).</span></li>
        <li><div class="rf-dot" style="background:#0369a1;"></div><span><strong>SALE / INVOICE</strong> &#8212; links back to the original sale record. Click to open the sale in Find Sale.</span></li>
        <li><div class="rf-dot" style="background:#b45309;"></div><span><strong>ITEMS</strong> &#8212; lists which items are being returned. Non-tag items (engravings, fees) can be refunded for the amount but have no physical stock to restore.</span></li>
        <li><div class="rf-dot" style="background:#b45309;"></div><span><strong>AMOUNT</strong> &#8212; total dollar value of the refund. This is what the customer receives back.</span></li>
        <li><div class="rf-dot" style="background:#6b7280;"></div><span><strong>REASON</strong> &#8212; the staff-entered reason for the return. Required for all refunds.</span></li>
        <li><div class="rf-dot" style="background:#6b7280;"></div><span><strong>REFUND METHOD</strong> &#8212; how the money is being returned to the customer (Cash, Card, Store Credit, etc.).</span></li>
      </ul>
    </div>
  </div>

  <!-- IMPORTANT RULES -->
  <div class="rf-section">
    <p class="rf-section-title">Important rules</p>

    <div class="rf-alert rf-danger">
      <div class="rf-alert-icon">&#128683;</div>
      <div class="rf-alert-body">
        <p class="rf-alert-title">Only managers can approve refunds</p>
        <p class="rf-alert-desc">Staff can create a refund request but cannot approve it themselves. The Approve and Reject buttons are only visible to users with Manager, Administration, or Superadmin roles. This protects against unauthorized stock adjustments.</p>
      </div>
    </div>

    <div class="rf-alert rf-warning">
      <div class="rf-alert-icon">&#9888;</div>
      <div class="rf-alert-body">
        <p class="rf-alert-title">Non-tag items return money only &#8212; no stock change</p>
        <p class="rf-alert-desc">If the sale included non-tag items like engraving fees, cleaning charges, or labour, those will be included in the refund amount when selected, but there is no physical item to put back into stock. Only barcoded jewelry items (with a stock number) are returned to Find Stock.</p>
      </div>
    </div>

    <div class="rf-alert rf-success">
      <div class="rf-alert-icon">&#10003;</div>
      <div class="rf-alert-body">
        <p class="rf-alert-title">Refunded items are immediately available to sell again</p>
        <p class="rf-alert-desc">As soon as a refund is approved, the returned items appear as <strong>In Stock</strong> in Find Stock with their original barcodes. They can be added to a new sale immediately. No manual stock adjustment is needed.</p>
      </div>
    </div>
  </div>

</div>';

        return $css . $body;
    }
}