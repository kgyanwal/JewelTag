<x-filament-panels::page>

    {{-- ── FILTER — always visible so staff can switch customer ─── --}}
    {{ $this->form }}

{{-- ── TOP CUSTOMERS QUICK PICK + INACTIVE CUSTOMERS — only when no prediction yet ── --}}
    @if(!$prediction)
    @php
        $topCustomers      = $this->getTopCustomers();
        $inactiveCustomers = $this->getInactiveCustomers();
    @endphp

    @if(!empty($topCustomers))
    <div style="margin-top:20px;">
        <p style="font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8;margin-bottom:10px;">
            ⚡ Quick Pick — Top Customers by Spend
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @foreach($topCustomers as $tc)
            <button wire:click="quickPick({{ $tc['id'] }})"
                style="padding:7px 16px;border-radius:99px;border:1.5px solid #e2e8f0;background:#fff;font-size:12px;font-weight:700;color:#1e293b;cursor:pointer;transition:all .15s;"
                onmouseover="this.style.borderColor='#C9A24B';this.style.color='#0B3D3C';"
                onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#1e293b';"
            >
                {{ $tc['name'] }}
                <span style="color:#94a3b8;font-weight:500;margin-left:4px;">&middot; ${{ number_format($tc['total'], 0) }}</span>
            </button>
            @endforeach
        </div>
    </div>
    @endif

    {{-- 🚀 NEW — INACTIVE CUSTOMERS --}}
    <div style="margin-top:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
            <p style="font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8;margin:0;">
                💤 Inactive Customers — Haven't Bought Recently
            </p>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:11px;color:#94a3b8;font-weight:600;">Inactive for at least</span>
                <select wire:model.live="inactiveMonths"
                    style="font-size:12px;font-weight:700;color:#1e293b;border:1.5px solid #e2e8f0;border-radius:8px;padding:4px 8px;background:#fff;cursor:pointer;">
                    <option value="1">1 month</option>
                    <option value="2">2 months</option>
                    <option value="3">3 months</option>
                    <option value="6">6 months</option>
                    <option value="12">12 months</option>
                </select>
            </div>
        </div>

        @if(empty($inactiveCustomers))
        <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;padding:16px 20px;font-size:12px;color:#15803d;font-weight:600;">
            ✅ No customers have gone quiet for {{ $inactiveMonths }}+ month(s) — everyone's been in recently.
        </div>
        @else
        <div style="display:flex;flex-direction:column;gap:6px;">
            @foreach($inactiveCustomers as $ic)
            <button wire:click="quickPick({{ $ic['id'] }})"
                style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 16px;border-radius:10px;border:1.5px solid #fde68a;background:#fffbeb;cursor:pointer;text-align:left;transition:all .15s;width:100%;"
                onmouseover="this.style.borderColor='#C9A24B';this.style.background='#fef3c7';"
                onmouseout="this.style.borderColor='#fde68a';this.style.background='#fffbeb';"
            >
                <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                    <div style="width:32px;height:32px;border-radius:50%;background:#fde68a;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:900;color:#92400e;flex-shrink:0;">
                        {{ strtoupper(substr($ic['name'], 0, 1)) }}
                    </div>
                    <div style="min-width:0;">
                        <div style="font-size:13px;font-weight:800;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $ic['name'] }}</div>
                        <div style="font-size:11px;color:#94a3b8;">{{ $ic['phone'] ?? '—' }} &middot; {{ $ic['total_purchases'] }} past purchase{{ $ic['total_purchases'] == 1 ? '' : 's' }}</div>
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-size:12px;font-weight:800;color:#b45309;">{{ $ic['days_inactive'] }}d quiet</div>
                    <div style="font-size:10px;color:#94a3b8;">since {{ $ic['last_purchase'] }} &middot; ${{ number_format($ic['total_spent'], 0) }} lifetime</div>
                </div>
            </button>
            @endforeach
        </div>
        @endif
    </div>
    @endif


    {{-- ── SELECTED CUSTOMER PREVIEW — always show when customer picked ── --}}
    @if($selectedCustomer)
    <div style="margin-top:20px;background:#fff;border:1.5px solid {{ $prediction ? '#C9A24B' : '#e2e8f0' }};border-radius:14px;padding:18px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;border-radius:50%;background:#0B3D3C;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;color:#C9A24B;flex-shrink:0;">
                {{ strtoupper(substr($selectedCustomer['name'], 0, 1)) }}
            </div>
            <div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="font-size:16px;font-weight:900;color:#1e293b;">{{ $selectedCustomer['name'] }}</div>
                    @if($prediction)
                    <span style="font-size:9px;font-weight:800;padding:2px 8px;border-radius:99px;background:rgba(201,162,75,0.12);color:#C9A24B;border:1px solid rgba(201,162,75,0.3);text-transform:uppercase;letter-spacing:.08em;">Predicted</span>
                    @endif
                </div>
                <div style="font-size:12px;color:#94a3b8;margin-top:2px;">{{ $selectedCustomer['phone'] ?? '—' }} &middot; #{{ $selectedCustomer['no'] ?? '—' }}</div>
            </div>
        </div>
        <button wire:click="runPrediction" wire:loading.attr="disabled"
            style="padding:11px 24px;border-radius:10px;font-size:13px;font-weight:800;background:{{ $prediction ? 'transparent' : 'linear-gradient(135deg,#0B3D3C,#1a6b65)' }};color:{{ $prediction ? '#C9A24B' : '#fff' }};border:{{ $prediction ? '1.5px solid #C9A24B' : 'none' }};cursor:pointer;white-space:nowrap;"
        >
            <span wire:loading.remove wire:target="runPrediction">{{ $prediction ? '↻ Refresh Prediction' : '✦ Run AI Prediction' }}</span>
            <span wire:loading wire:target="runPrediction">Analysing...</span>
        </button>
    </div>
    @endif


    {{-- ── LOADING ─────────────────────────────────────────────────── --}}
    @if($aiStatus === 'loading')
    <div style="margin-top:20px;display:flex;align-items:center;gap:16px;padding:24px;background:#f8fafc;border-radius:14px;border:1px solid #e2e8f0;">
        <div style="width:40px;height:40px;border-radius:50%;border:3px solid #e2e8f0;border-top-color:#C9A24B;animation:clv-spin 0.8s linear infinite;flex-shrink:0;"></div>
        <div>
            <div style="font-size:15px;font-weight:700;color:#1e293b;">Gemini is analysing {{ $customerStats['name'] ?? 'customer' }}...</div>
            <div style="font-size:12px;color:#94a3b8;margin-top:3px;">Reviewing purchase history, frequency, spending patterns and behavioural signals.</div>
        </div>
    </div>
    <style>@keyframes clv-spin { to { transform:rotate(360deg); } }</style>
    @endif

    {{-- ── ERROR ───────────────────────────────────────────────────── --}}
    @if($aiStatus === 'error')
    <div style="margin-top:20px;padding:16px 20px;background:#fef2f2;border:1.5px solid #fecaca;border-left:4px solid #dc2626;border-radius:12px;color:#dc2626;font-size:13px;font-weight:600;">
        ⚠️ {{ $aiError }}
    </div>
    @endif

    {{-- ── PREDICTION RESULT ───────────────────────────────────────── --}}
    @if($aiStatus === 'done' && $prediction && $customerStats)
    @php
        $conf      = $prediction['confidence'] ?? 'medium';
        $churnRisk = (bool)($prediction['churn_risk'] ?? false);
        $confColor = match($conf) { 'high' => '#16a34a', 'medium' => '#C9A24B', default => '#94a3b8' };
        $sc        = $selectedCustomer ?? [];
    @endphp

    <div style="margin-top:24px;display:flex;flex-direction:column;gap:16px;">

        {{-- MAIN PREDICTION CARD --}}
        <div style="background:linear-gradient(135deg,#0B1929 0%,#0F2744 100%);border-radius:20px;padding:32px;position:relative;overflow:hidden;">
            {{-- glow --}}
            <div style="position:absolute;top:-60px;right:-60px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(201,162,75,0.15) 0%,transparent 70%);pointer-events:none;"></div>
            <div style="position:absolute;bottom:-40px;left:-40px;width:160px;height:160px;border-radius:50%;background:radial-gradient(circle,rgba(11,61,60,0.4) 0%,transparent 70%);pointer-events:none;"></div>

            {{-- Customer header --}}
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px;position:relative;">
                <div style="width:52px;height:52px;border-radius:50%;background:rgba(201,162,75,0.2);border:2px solid rgba(201,162,75,0.4);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;color:#C9A24B;flex-shrink:0;">
                    {{ strtoupper(substr($sc['name'] ?? $customerStats['name'], 0, 1)) }}
                </div>
                <div>
                    <div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#C9A24B;margin-bottom:2px;">AI Lifetime Value Prediction</div>
                    <div style="font-size:18px;font-weight:900;color:#F8F9FB;">{{ $sc['name'] ?? $customerStats['name'] }}</div>
                    <div style="font-size:11px;color:rgba(168,212,245,0.5);margin-top:1px;">{{ $sc['phone'] ?? '' }}</div>
                </div>
            </div>

            {{-- THE MAIN SENTENCE --}}
            <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(201,162,75,0.35);border-radius:14px;padding:24px 28px;margin-bottom:16px;position:relative;">
                <div style="font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:rgba(201,162,75,0.7);margin-bottom:10px;">Prediction</div>
                <div style="font-size:24px;font-weight:900;color:#F8F9FB;line-height:1.35;">
                    {{ $prediction['sentence'] ?? 'Prediction unavailable.' }}
                </div>
                <div style="margin-top:12px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-size:10px;font-weight:800;padding:3px 12px;border-radius:99px;background:{{ $confColor }}20;color:{{ $confColor }};border:1px solid {{ $confColor }}40;text-transform:uppercase;letter-spacing:.08em;">
                        {{ $conf }} confidence
                    </span>
                    <span style="font-size:10px;color:rgba(168,212,245,0.4);font-weight:600;">· Powered by Gemini AI</span>
                </div>
            </div>

            {{-- CHURN RISK --}}
            @if($churnRisk && !empty($prediction['risk_sentence']))
            <div style="background:rgba(220,38,38,0.12);border:1px solid rgba(220,38,38,0.35);border-radius:12px;padding:16px 20px;margin-bottom:12px;">
                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#fca5a5;margin-bottom:6px;">⚠️ Churn Risk Detected</div>
                <div style="font-size:15px;font-weight:600;color:#fff;line-height:1.4;">{{ $prediction['risk_sentence'] }}</div>
            </div>
            @endif

            {{-- INSIGHT --}}
            @if(!empty($prediction['insight_sentence']))
            <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:16px 20px;margin-bottom:12px;">
                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:rgba(168,212,245,0.6);margin-bottom:6px;">💡 Behaviour Insight</div>
                <div style="font-size:15px;font-weight:600;color:#F8F9FB;line-height:1.4;">{{ $prediction['insight_sentence'] }}</div>
            </div>
            @endif

            {{-- ACTION --}}
            @if(!empty($prediction['action_sentence']))
            <div style="background:rgba(201,162,75,0.1);border:1px solid rgba(201,162,75,0.3);border-radius:12px;padding:16px 20px;">
                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#C9A24B;margin-bottom:6px;">✅ Recommended Action</div>
                <div style="font-size:15px;font-weight:600;color:#E4CD8E;line-height:1.4;">{{ $prediction['action_sentence'] }}</div>
            </div>
            @endif

            {{-- CONTACT BUTTONS --}}
            <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">
                @if(!empty($selectedCustomer['phone']))
                <button wire:click="openContactModal"
                    style="display:flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:800;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 12px rgba(22,163,74,0.3);">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-3.555-.695L3 20.25l1.388-3.665A8.98 8.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
                    </svg>
                    Send SMS
                </button>
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $selectedCustomer['phone']) }}"
                    style="display:flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:800;background:rgba(255,255,255,0.08);color:#F8F9FB;border:1px solid rgba(255,255,255,0.15);text-decoration:none;cursor:pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                    </svg>
                    Call {{ $selectedCustomer['phone'] }}
                </a>
                @endif
            </div>
        </div>

        {{-- ── SMS MODAL ──────────────────────────────────────────── --}}
        @if($showContactModal)
        <div style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);">
            <div style="background:#fff;border-radius:20px;padding:28px;width:100%;max-width:480px;box-shadow:0 24px 64px rgba(0,0,0,0.25);margin:20px;">

                {{-- Header --}}
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                    <div>
                        <div style="font-size:16px;font-weight:900;color:#1e293b;">Send SMS to {{ $selectedCustomer['name'] }}</div>
                        <div style="font-size:12px;color:#94a3b8;margin-top:2px;">{{ $selectedCustomer['phone'] }}</div>
                    </div>
                    <button wire:click="closeContactModal"
                        style="width:32px;height:32px;border-radius:50%;border:1.5px solid #e2e8f0;background:#f8fafc;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;color:#64748b;">
                        ✕
                    </button>
                </div>

                {{-- AI suggestion label --}}
                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin-bottom:6px;">
                    ✦ AI-Suggested Message
                </div>

                {{-- Message textarea --}}
                <textarea wire:model="contactMessage"
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:12px 14px;font-size:13px;font-family:inherit;color:#1e293b;resize:vertical;min-height:100px;outline:none;line-height:1.5;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#0B3D3C'"
                    onblur="this.style.borderColor='#e2e8f0'"
                    maxlength="160"
                ></textarea>
                <div style="font-size:11px;color:#94a3b8;text-align:right;margin-top:4px;">
                    {{ strlen($contactMessage) }}/160 characters
                </div>

                {{-- Actions --}}
                <div style="display:flex;gap:10px;margin-top:16px;">
                    <button wire:click="sendSms" wire:loading.attr="disabled"
                        style="flex:1;padding:12px;border-radius:10px;font-size:14px;font-weight:800;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 12px rgba(22,163,74,0.25);">
                        <span wire:loading.remove wire:target="sendSms">📱 Send SMS Now</span>
                        <span wire:loading wire:target="sendSms">Sending...</span>
                    </button>
                    <button wire:click="closeContactModal"
                        style="padding:12px 20px;border-radius:10px;font-size:14px;font-weight:700;background:#f8fafc;color:#64748b;border:1.5px solid #e2e8f0;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- STATS ROW --}}
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
            @foreach([
                ['Lifetime Spent',   '$'.number_format($customerStats['total_spent'],0),    '#0B3D3C'],
                ['Total Purchases',  $customerStats['total_purchases'],                      '#C9A24B'],
                ['Avg Sale',         '$'.number_format($customerStats['avg_sale'],0),        '#0284c7'],
                ['Days Since Visit', $customerStats['days_since_last'].'d',                  $churnRisk ? '#dc2626' : '#7c3aed'],
            ] as [$lbl, $val, $clr])
            <div style="background:#fff;border:1px solid #e2e8f0;border-top:3px solid {{ $clr }};border-radius:12px;padding:16px;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                <div style="font-size:22px;font-weight:900;color:{{ $clr }};">{{ $val }}</div>
                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-top:4px;">{{ $lbl }}</div>
            </div>
            @endforeach
        </div>

        {{-- DATA USED --}}
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
            <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#0B3D3C;margin-bottom:14px;">📋 Data Used for This Prediction</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                @foreach([
                    ['First Purchase',        $customerStats['first_purchase']],
                    ['Last Purchase',         $customerStats['last_purchase']],
                    ['Buys Every',            $customerStats['purchase_frequency_days'].' days avg'],
                    ['Peak Month',            $customerStats['peak_month']],
                    ['Top Category',          $customerStats['top_category']],
                    ['Payment Preference',    $customerStats['payment_method']],
                    ['Months as Customer',    $customerStats['months_as_customer'].' months'],
                    ['Uses Layaway',          $customerStats['has_laybuy']],
                ] as [$label, $val])
                <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 12px;background:#f8fafc;border-radius:8px;font-size:12px;">
                    <span style="color:#94a3b8;font-weight:600;">{{ $label }}</span>
                    <span style="color:#1e293b;font-weight:800;">{{ $val }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- CLEAR BUTTON --}}
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button wire:click="clearPrediction"
                style="padding:10px 20px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;font-size:13px;font-weight:700;color:#64748b;cursor:pointer;">
                ← Predict Another Customer
            </button>
        </div>

    </div>
    @endif

    <x-filament-actions::modals />

</x-filament-panels::page>