<?php

namespace App\Filament\Resources\StockTransferResource\Pages;

use App\Filament\Resources\StockTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListStockTransfers extends ListRecords
{
    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('stock_transfer_manual')
                ->label('Docs')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->outlined()
                ->extraAttributes(['class' => 'docs-manual-btn'])
                ->modalHeading('Stock Transfers — Docs')
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
        $css = "
<style>
.st-wrap{padding:1rem 0;font-family:inherit;}
.st-header{display:flex;align-items:center;gap:10px;margin-bottom:1.5rem;border-bottom:1px solid #e5e7eb;padding-bottom:1rem;}
.st-icon{width:36px;height:36px;border-radius:8px;background:#f9fafb;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.st-section{margin-bottom:1.75rem;}
.st-section-title{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 10px;}
.st-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;}
.st-step{display:flex;gap:12px;padding:12px 14px;border-bottom:1px solid #f3f4f6;align-items:flex-start;}
.st-step:last-child{border-bottom:none;}
.st-num{width:22px;height:22px;border-radius:50%;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
.st-step-body{flex:1;}
.st-step-title{font-size:14px;font-weight:600;color:#111827;margin:0 0 2px;}
.st-step-desc{font-size:13px;color:#6b7280;margin:0;line-height:1.5;}
.st-alert{border-radius:10px;padding:14px 16px;margin:12px 0;display:flex;gap:10px;align-items:flex-start;}
.st-alert-icon{font-size:18px;flex-shrink:0;margin-top:1px;}
.st-alert-body{flex:1;}
.st-alert-title{font-size:13px;font-weight:700;margin:0 0 4px;}
.st-alert-desc{font-size:12px;margin:0;line-height:1.6;}
.st-info{background:#f0f9ff;border:1.5px solid #0ea5e9;}
.st-info .st-alert-title{color:#0369a1;}
.st-info .st-alert-desc{color:#075985;}
.st-success{background:#f0fdf4;border:1.5px solid #22c55e;}
.st-success .st-alert-title{color:#15803d;}
.st-success .st-alert-desc{color:#166534;}
.st-warning{background:#fffbeb;border:1.5px solid #f59e0b;}
.st-warning .st-alert-title{color:#b45309;}
.st-warning .st-alert-desc{color:#92400e;}
.st-danger{background:#fef2f2;border:1.5px solid #ef4444;}
.st-danger .st-alert-title{color:#b91c1c;}
.st-danger .st-alert-desc{color:#991b1b;}
.st-flow{display:flex;align-items:center;gap:0;flex-wrap:wrap;margin-bottom:12px;}
.st-flow-box{font-size:11px;font-weight:600;padding:5px 11px;border-radius:6px;border:1px solid;white-space:nowrap;}
.st-flow-arrow{font-size:14px;color:#9ca3af;padding:0 5px;}
.st-divider{display:flex;align-items:center;gap:8px;margin:1.75rem 0 1rem;}
.st-divider-line{flex:1;height:1px;background:#f3f4f6;}
.st-divider-label{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em;white-space:nowrap;}
.st-grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:4px;}
.st-col{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;}
.st-col-head{padding:8px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;}
.st-col-body{padding:10px 12px;}
.st-col-body p{font-size:12px;color:#374151;margin:0 0 5px;line-height:1.5;}
.st-shortcuts{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;}
.st-shortcut{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;}
.st-shortcut-title{font-size:13px;font-weight:600;color:#111827;margin:0 0 3px;}
.st-shortcut-desc{font-size:12px;color:#6b7280;margin:0;}
.st-list{list-style:none;margin:0;padding:0;}
.st-list li{display:flex;gap:8px;align-items:flex-start;padding:8px 14px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#374151;}
.st-list li:last-child{border-bottom:none;}
.st-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;margin-top:5px;}
</style>";

        $body = '
<div class="st-wrap">

  <div class="st-header">
    <div class="st-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
    </div>
    <div>
      <p style="font-size:15px;font-weight:700;margin:0;color:#111827;">Stock Transfers &#8212; Docs</p>
      <p style="font-size:13px;color:#6b7280;margin:0;">Moving inventory between stores, accepting incoming stock, and what happens to item status</p>
    </div>
  </div>

  <div class="st-section">
    <p class="st-section-title">What is a stock transfer?</p>
    <div class="st-alert st-info">
      <div class="st-alert-icon">&#128161;</div>
      <div class="st-alert-body">
        <p class="st-alert-title">Moving items between stores</p>
        <p class="st-alert-desc">A stock transfer lets you send one or more inventory items from your store to another store location. Items are marked <strong>In Transit</strong> on your end while the destination store reviews and accepts or denies. The red badge on the Stock Transfers menu shows how many <strong>incoming</strong> transfers are waiting for your action.</p>
      </div>
    </div>
  </div>

  <div class="st-section">
    <p class="st-section-title">Transfer lifecycle</p>
    <div class="st-flow">
      <span class="st-flow-box" style="background:#fffbeb;color:#b45309;border-color:#fde68a;">&#9203; Pending</span>
      <span class="st-flow-arrow">&#8594;</span>
      <span class="st-flow-box" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">&#128666; In Transit</span>
      <span class="st-flow-arrow">&#8594; accepted &#8594;</span>
      <span class="st-flow-box" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;">&#10003; Accepted</span>
    </div>
    <div class="st-flow">
      <span class="st-flow-box" style="background:#fffbeb;color:#b45309;border-color:#fde68a;">&#9203; Pending</span>
      <span class="st-flow-arrow">&#8594; denied or cancelled &#8594;</span>
      <span class="st-flow-box" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca;">&#10007; Denied</span>
    </div>
    <p style="font-size:12px;color:#9ca3af;margin:4px 0 0;">If denied or cancelled, items automatically return to <strong>In Stock</strong> at the sending store.</p>
  </div>

  <div class="st-section">
    <p class="st-section-title">Two ways to start a transfer</p>
    <div class="st-grid2">
      <div class="st-col">
        <div class="st-col-head" style="background:#eff6ff;color:#1d4ed8;">WAY A &#8212; From Find Stock (bulk)</div>
        <div class="st-col-body">
          <p>Go to <strong>Inventory &#8594; Find Stock</strong>.</p>
          <p>Tick the checkboxes to select one or multiple items.</p>
          <p>At the bottom click <strong>Batch Transfer</strong> from the bulk actions menu.</p>
          <p>Pick the destination store, add notes, and confirm.</p>
          <p style="color:#1d4ed8;font-weight:600;">Best for moving multiple items quickly.</p>
        </div>
      </div>
      <div class="st-col">
        <div class="st-col-head" style="background:#f5f3ff;color:#7c3aed;">WAY B &#8212; From this page (manual)</div>
        <div class="st-col-body">
          <p>Click the <strong>New</strong> button on this page.</p>
          <p>Select the destination store and search for items one by one.</p>
          <p>Add notes and save.</p>
          <p style="color:#7c3aed;font-weight:600;">Best when you know exact barcodes.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="st-divider">
    <div class="st-divider-line"></div>
    <span class="st-divider-label">Sending a transfer &#8212; step by step</span>
    <div class="st-divider-line"></div>
  </div>

  <div class="st-section">
    <div class="st-card">
      <div class="st-step">
        <div class="st-num">1</div>
        <div class="st-step-body">
          <p class="st-step-title">Select items and destination</p>
          <p class="st-step-desc">From Find Stock (bulk) or this page (manual), select the items and the destination store. Add any notes about why the transfer is happening.</p>
        </div>
      </div>
      <div class="st-step">
        <div class="st-num">2</div>
        <div class="st-step-body">
          <p class="st-step-title">Items go In Transit</p>
          <p class="st-step-desc">As soon as you confirm, the items change status to <strong>In Transit</strong>. They no longer appear as In Stock in Find Stock. The destination store sees a <strong>red badge</strong> on their Stock Transfers menu.</p>
        </div>
      </div>
      <div class="st-step">
        <div class="st-num">3</div>
        <div class="st-step-body">
          <p class="st-step-title">Destination store gets a notification</p>
          <p class="st-step-desc">All staff at the destination store receive an in-app notification with the transfer number and item count.</p>
        </div>
      </div>
      <div class="st-step">
        <div class="st-num">4</div>
        <div class="st-step-body">
          <p class="st-step-title">Wait for Accept or Deny</p>
          <p class="st-step-desc">You can monitor status on this page. Use <strong>View Items</strong> to review what is in the transfer. You can also <strong>Cancel</strong> a pending outgoing transfer before the other store acts &#8212; items return to In Stock immediately.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="st-divider">
    <div class="st-divider-line"></div>
    <span class="st-divider-label">Receiving a transfer &#8212; accepting incoming stock</span>
    <div class="st-divider-line"></div>
  </div>

  <div class="st-section">
    <div class="st-card">
      <div class="st-step">
        <div class="st-num">1</div>
        <div class="st-step-body">
          <p class="st-step-title">Check the red badge on the menu</p>
          <p class="st-step-desc">The number on the Stock Transfers menu icon shows pending incoming transfers. Incoming rows are marked with the <strong>&#128229; INCOMING</strong> badge in the TYPE column.</p>
        </div>
      </div>
      <div class="st-step">
        <div class="st-num">2</div>
        <div class="st-step-body">
          <p class="st-step-title">Click View Items to review</p>
          <p class="st-step-desc">Before accepting, use <strong>View Items</strong> to see the full list of barcodes, descriptions, and prices. Verify these match the physical items you have received in hand.</p>
        </div>
      </div>
      <div class="st-step">
        <div class="st-num">3</div>
        <div class="st-step-body">
          <p class="st-step-title">Click Accept and map the vendors</p>
          <p class="st-step-desc">Click the green <strong>Accept</strong> button. A modal appears asking you to map each incoming vendor to your local database. Choose <em>Match to existing vendor</em> (avoids duplicates) or <em>Create new vendor</em>. Take your time here &#8212; this links items correctly to your supplier records.</p>
        </div>
      </div>
      <div class="st-step" style="background:#f0fdf4;">
        <div class="st-num" style="background:#dcfce7;color:#15803d;">&#10003;</div>
        <div class="st-step-body">
          <p class="st-step-title" style="color:#15803d;">Items appear in your inventory instantly</p>
          <p class="st-step-desc">All items are added to your Find Stock with status <strong>In Stock</strong>. Barcodes stay exactly the same. The sending store is notified and their items are removed from their inventory automatically.</p>
        </div>
      </div>
      <div class="st-step" style="background:#fef2f2;">
        <div class="st-step-body" style="padding-left:6px;">
          <p class="st-step-title" style="color:#b91c1c;">If you click Deny instead</p>
          <p class="st-step-desc" style="color:#991b1b;">The transfer is marked Denied. Items return to In Stock at the sender automatically. Use this only if the physical items have not arrived or the transfer was sent in error.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="st-divider">
    <div class="st-divider-line"></div>
    <span class="st-divider-label">Actions on each row</span>
    <div class="st-divider-line"></div>
  </div>

  <div class="st-section">
    <div class="st-shortcuts">
      <div class="st-shortcut">
        <p class="st-shortcut-title">&#10003; Accept</p>
        <p class="st-shortcut-desc">Only on INCOMING pending transfers. Opens vendor mapping then imports items.</p>
      </div>
      <div class="st-shortcut">
        <p class="st-shortcut-title">&#10007; Deny</p>
        <p class="st-shortcut-desc">Only on INCOMING pending transfers. Rejects and restores items to sender.</p>
      </div>
      <div class="st-shortcut">
        <p class="st-shortcut-title">Cancel</p>
        <p class="st-shortcut-desc">Only on YOUR OWN pending outgoing transfers. Withdraws before action is taken.</p>
      </div>
      <div class="st-shortcut">
        <p class="st-shortcut-title">&#128065; View Items</p>
        <p class="st-shortcut-desc">Slide-over with full item list including barcodes, descriptions, and prices.</p>
      </div>
      <div class="st-shortcut">
        <p class="st-shortcut-title">&#9998; Edit</p>
        <p class="st-shortcut-desc">Only on your own pending outgoing transfers. Add or remove items before the destination acts.</p>
      </div>
    </div>
  </div>

  <div class="st-divider">
    <div class="st-divider-line"></div>
    <span class="st-divider-label">Reading the table columns</span>
    <div class="st-divider-line"></div>
  </div>

  <div class="st-section">
    <div class="st-card">
      <ul class="st-list">
        <li><div class="st-dot" style="background:#1d4ed8;"></div><span><strong>TYPE</strong> &#8212; &#128229; INCOMING = another store sent to you. &#128228; OUTGOING = you sent to another store.</span></li>
        <li><div class="st-dot" style="background:#1d4ed8;"></div><span><strong>FROM / TO</strong> &#8212; tenant IDs of the sending and receiving store.</span></li>
        <li><div class="st-dot" style="background:#b45309;"></div><span><strong># ITEMS</strong> &#8212; number of stock items included in this transfer batch.</span></li>
        <li><div class="st-dot" style="background:#b45309;"></div><span><strong>STATUS</strong> &#8212; Pending (waiting), In Transit (en route), Accepted (done), Denied (rejected).</span></li>
        <li><div class="st-dot" style="background:#6b7280;"></div><span><strong>SENT BY / ACTIONED BY</strong> &#8212; staff who sent and who accepted or denied the transfer.</span></li>
      </ul>
    </div>
  </div>

  <div class="st-section">
    <p class="st-section-title">Important rules</p>
    <div class="st-alert st-warning">
      <div class="st-alert-icon">&#9888;</div>
      <div class="st-alert-body">
        <p class="st-alert-title">Map vendors carefully when accepting</p>
        <p class="st-alert-desc">When accepting, match each vendor to your local database. Choosing "Create new vendor" when one already exists creates a duplicate supplier record that is difficult to clean up later.</p>
      </div>
    </div>
    <div class="st-alert st-danger">
      <div class="st-alert-icon">&#128683;</div>
      <div class="st-alert-body">
        <p class="st-alert-title">Do not manually edit items while they are In Transit</p>
        <p class="st-alert-desc">Items with status In Transit should not be edited in Find Stock while the transfer is pending. Wait until the transfer is Accepted or Denied before making changes to those barcodes.</p>
      </div>
    </div>
    <div class="st-alert st-success">
      <div class="st-alert-icon">&#10003;</div>
      <div class="st-alert-body">
        <p class="st-alert-title">Barcodes never change during a transfer</p>
        <p class="st-alert-desc">Item barcodes stay exactly the same when moving between stores. Receipts, customer histories, and tags all remain valid and do not need to be reprinted.</p>
      </div>
    </div>
  </div>

</div>';

        return $css . $body;
    }
}