<?php

namespace App\Filament\Resources\RepairResource\Pages;

use App\Filament\Resources\RepairResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListRepairs extends ListRecords
{
    protected static string $resource = RepairResource::class;

    protected static string $view = 'filament.pages.list-repairs';

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

  <!-- STATUS FLOW -->
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
    <p style="font-size:12px;color:#9ca3af;margin:0;">Update status directly on the list table — no need to open the edit form.</p>
  </div>

  <!-- CREATE A REPAIR -->
  <div class="rm-section">
    <p class="rm-section-title">Creating a new repair</p>
    <div class="rm-card">
      <div class="rm-step">
        <div class="rm-num">1</div>
        <div class="rm-step-body">
          <p class="rm-step-title">Select or create a customer</p>
          <p class="rm-step-desc">Search by name, phone, or customer number. Use the <strong>+</strong> button next to the field to add a new customer on the spot.</p>
        </div>
      </div>
      <div class="rm-step">
        <div class="rm-num">2</div>
        <div class="rm-step-body">
          <p class="rm-step-title">Add jewelry items</p>
          <p class="rm-step-desc">Click <strong>+ Add another jewelry item</strong> for each piece. Fill in the description and the issue reported. For warranty items, toggle <em>Covered under warranty</em> — costs auto-set to $0.</p>
          <div class="rm-tip">💡 If the item was bought here, toggle <em>Was this bought from our store?</em> to link the stock number.</div>
        </div>
      </div>
      <div class="rm-step">
        <div class="rm-num">3</div>
        <div class="rm-step-body">
          <p class="rm-step-title">Assign staff &amp; set tracking</p>
          <p class="rm-step-desc">Pick the sales associate(s) in the Staff assignment panel. Fill in Dropped by, Date dropped, and Repair location in the Repair tracking panel.</p>
        </div>
      </div>
      <div class="rm-step">
        <div class="rm-num">4</div>
        <div class="rm-step-body">
          <p class="rm-step-title">Save &amp; print</p>
          <p class="rm-step-desc">The <em>Print job packet after saving</em> toggle auto-opens the printable card. Hand one copy to the customer, keep one with the jewelry.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="rm-divider">
    <div class="rm-divider-line"></div>
    <span class="rm-divider-label">Row actions — click the three dots (...) on any row</span>
    <div class="rm-divider-line"></div>
  </div>

  <!-- THREE DOTS ACTIONS -->
  <div class="rm-section">
    <div class="rm-shortcuts">
      <div class="rm-shortcut">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        <div>
          <p class="rm-shortcut-title">Edit repair</p>
          <p class="rm-shortcut-desc">Opens the full edit form to update any field, add items, or change status.</p>
        </div>
      </div>
      <div class="rm-shortcut">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <div>
          <p class="rm-shortcut-title">Delay notification</p>
          <p class="rm-shortcut-desc">Sends an SMS, email, or both to the customer about the delay. Message is logged automatically.</p>
        </div>
      </div>
      <div class="rm-shortcut">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div>
          <p class="rm-shortcut-title">Ready for pickup</p>
          <p class="rm-shortcut-desc">Marks status as Ready and optionally notifies the customer via SMS or email.</p>
        </div>
      </div>
      <div class="rm-shortcut">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        <div>
          <p class="rm-shortcut-title">Bill to POS</p>
          <p class="rm-shortcut-desc">Opens the Quick Sale screen pre-loaded with this repair and the customer. Complete payment there as a normal sale.</p>
        </div>
      </div>
      <div class="rm-shortcut">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        <div>
          <p class="rm-shortcut-title">Print job packet</p>
          <p class="rm-shortcut-desc">Reprints the job card at any time — useful when the original copy is lost.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="rm-divider">
    <div class="rm-divider-line"></div>
    <span class="rm-divider-label">Quick edits directly on the list (no form needed)</span>
    <div class="rm-divider-line"></div>
  </div>

  <!-- INLINE EDITABLE COLUMNS -->
  <div class="rm-section">
    <div class="rm-card">
      <ul class="rm-list">
        <li>
          <div class="rm-dot" style="background:#15803d;"></div>
          <span><strong>Status</strong> — click the dropdown to move the repair through stages without opening the edit form.</span>
        </li>
        <li>
          <div class="rm-dot" style="background:#15803d;"></div>
          <span><strong>Drop by / Pick by</strong> — assign or change the staff member who dropped off or picked up the jewelry in-line.</span>
        </li>
        <li>
          <div class="rm-dot" style="background:#15803d;"></div>
          <span><strong>Location</strong> — select or update the current repair vendor location directly from the list column.</span>
        </li>
        <li>
          <div class="rm-dot" style="background:#b45309;"></div>
          <span><strong>Notified</strong> — shows date of last notification. Click it to view the full communication history log for that repair.</span>
        </li>
        <li>
          <div class="rm-dot" style="background:#b45309;"></div>
          <span><strong>Quote</strong> — sum of estimated costs across all items on the ticket. Update individual costs inside the edit form.</span>
        </li>
      </ul>
    </div>
  </div>

  <!-- TIPS -->
  <div class="rm-section">
    <p class="rm-section-title">Tips</p>
    <div class="rm-card">
      <ul class="rm-list">
        <li>
          <span style="font-size:14px;flex-shrink:0;margin-top:1px;">💡</span>
          <span>Set the <strong>Customer pickup date</strong> field so the team knows when to expect the customer — it shows highlighted green on the list.</span>
        </li>
        <li>
          <span style="font-size:14px;flex-shrink:0;margin-top:1px;">💡</span>
          <span>All notification messages are saved in the communication log. Click the Notified date on any row to read the full history.</span>
        </li>
        <li>
          <span style="font-size:14px;flex-shrink:0;margin-top:1px;">💡</span>
          <span>Use <strong>Bill to POS</strong> only when the customer is present to pay — it transfers directly to the Quick Sale screen with all details prefilled.</span>
        </li>
        <li>
          <span style="font-size:14px;flex-shrink:0;margin-top:1px;">💡</span>
          <span>The job number shows only the last 4 digits on the table for readability. The full number is printed on the job packet.</span>
        </li>
      </ul>
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