<x-filament-panels::page>

<style>
.rfid-root { font-family:'Inter',sans-serif; }
:root {
    --rfid-teal:#0d9488; --rfid-teal-light:#ccfbf1; --rfid-teal-dark:#0f766e;
    --rfid-red:#dc2626; --rfid-red-light:#fef2f2;
    --rfid-amber:#d97706; --rfid-amber-light:#fffbeb;
    --rfid-green:#16a34a; --rfid-green-light:#f0fdf4;
    --rfid-ink:#1e293b; --rfid-soft:#64748b;
}

.rfid-hero {
    background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%);
    border-radius:18px; padding:24px 28px; margin-bottom:20px;
    position:relative; overflow:hidden;
}
.rfid-hero::before {
    content:''; position:absolute; inset:0; opacity:0.4;
    background-image:linear-gradient(rgba(255,255,255,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.03) 1px,transparent 1px);
    background-size:32px 32px;
}
.rfid-hero-title { font-size:1.4rem; font-weight:800; color:#fff; position:relative; z-index:1; }
.rfid-hero-sub { font-size:0.82rem; color:rgba(255,255,255,0.55); position:relative; z-index:1; margin-top:4px; }

.rfid-status-dot { width:10px; height:10px; border-radius:50%; display:inline-block; margin-right:6px; }
.rfid-status-dot.idle       { background:#94a3b8; }
.rfid-status-dot.connected  { background:#22c55e; animation:rfid-pulse 2s infinite; }
.rfid-status-dot.scanning   { background:#f59e0b; animation:rfid-pulse 0.8s infinite; }
.rfid-status-dot.completed  { background:#22c55e; }
.rfid-status-dot.error      { background:#ef4444; }
@keyframes rfid-pulse { 0%,100%{opacity:1;box-shadow:0 0 0 0 currentColor;} 50%{opacity:0.7;box-shadow:0 0 0 6px rgba(0,0,0,0);} }

.rfid-card { background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:22px; margin-bottom:16px; box-shadow:0 1px 4px rgba(0,0,0,0.04); }
.rfid-card-title { font-size:0.78rem; font-weight:800; text-transform:uppercase; letter-spacing:0.08em; color:var(--rfid-soft); margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.rfid-card-title::before { content:''; width:16px; height:1.5px; background:var(--rfid-teal); }

.rfid-input { width:100%; border:1.5px solid #e2e8f0; border-radius:9px; padding:9px 14px; font-size:0.875rem; transition:border-color 150ms; background:#f8fafc; color:#1e293b; box-sizing:border-box; }
.rfid-input:focus { outline:none; border-color:var(--rfid-teal); background:#fff; box-shadow:0 0 0 4px rgba(13,148,136,0.08); }
.rfid-label { font-size:0.72rem; font-weight:700; color:var(--rfid-soft); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.06em; }
.rfid-select { width:100%; border:1.5px solid #e2e8f0; border-radius:9px; padding:9px 14px; font-size:0.875rem; background:#f8fafc; color:#1e293b; box-sizing:border-box; -webkit-appearance:none; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; background-size:18px; padding-right:36px; }
.rfid-select:focus { outline:none; border-color:var(--rfid-teal); }

.rfid-btn { display:inline-flex; align-items:center; gap:7px; padding:10px 20px; border-radius:9px; font-size:0.84rem; font-weight:700; cursor:pointer; border:none; transition:all 150ms; }
.rfid-btn-primary { background:var(--rfid-teal); color:#fff; }
.rfid-btn-primary:hover { background:var(--rfid-teal-dark); }
.rfid-btn-danger  { background:var(--rfid-red); color:#fff; }
.rfid-btn-gray    { background:#f1f5f9; color:var(--rfid-ink); border:1px solid #e2e8f0; }
.rfid-btn-gray:hover { background:#e2e8f0; }
.rfid-btn-amber   { background:var(--rfid-amber); color:#fff; }
.rfid-btn:disabled { opacity:0.5; cursor:not-allowed; }

.rfid-stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:16px; }
@media(max-width:768px){ .rfid-stat-grid { grid-template-columns:repeat(2,1fr); } }
.rfid-stat { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px; text-align:center; }
.rfid-stat-num { font-size:1.8rem; font-weight:800; color:var(--rfid-ink); line-height:1; }
.rfid-stat-num.green { color:var(--rfid-green); }
.rfid-stat-num.red   { color:var(--rfid-red); }
.rfid-stat-num.amber { color:var(--rfid-amber); }
.rfid-stat-lbl { font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--rfid-soft); margin-top:4px; }

.rfid-result-row { display:grid; grid-template-columns:auto 1fr auto auto; gap:10px; align-items:center; padding:10px 14px; border-radius:9px; margin-bottom:6px; }
.rfid-result-row.matched   { background:var(--rfid-green-light); border:1px solid #bbf7d0; }
.rfid-result-row.unmatched { background:var(--rfid-red-light);   border:1px solid #fecaca; }
.rfid-result-row.duplicate { background:var(--rfid-amber-light); border:1px solid #fde68a; }

.rfid-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:0.68rem; font-weight:800; text-transform:uppercase; }
.rfid-badge.matched   { background:#dcfce7; color:var(--rfid-green); }
.rfid-badge.unmatched { background:#fee2e2; color:var(--rfid-red); }
.rfid-badge.duplicate { background:#fef3c7; color:var(--rfid-amber); }

.rfid-status-banner { border-radius:10px; padding:12px 16px; font-size:0.84rem; font-weight:600; margin-bottom:14px; display:flex; align-items:center; gap:10px; }
.rfid-status-banner.scanning  { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }
.rfid-status-banner.completed { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.rfid-status-banner.error     { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.rfid-status-banner.connected { background:#f0fdfa; border:1px solid #99f6e4; color:#0f766e; }

.rfid-endpoint { background:#1e293b; border-radius:9px; padding:12px 16px; font-family:'JetBrains Mono',monospace; font-size:0.8rem; color:#94d2bd; margin:10px 0; word-break:break-all; }

.rfid-live-feed { max-height:200px; overflow-y:auto; }
.rfid-live-item { display:flex; justify-content:space-between; align-items:center; padding:6px 10px; border-radius:7px; margin-bottom:4px; font-size:0.78rem; }
.rfid-live-item.matched   { background:#f0fdf4; }
.rfid-live-item.unmatched { background:#fef2f2; }

.rfid-session-row { display:flex; justify-content:space-between; align-items:center; padding:10px 14px; border:1px solid #e2e8f0; border-radius:9px; margin-bottom:6px; background:#f8fafc; }
.rfid-session-row:hover { background:#f1f5f9; }

.rfid-lookup-result { border-radius:12px; padding:16px; margin-top:12px; }
.rfid-lookup-result.found    { background:var(--rfid-green-light); border:1.5px solid #86efac; }
.rfid-lookup-result.notfound { background:var(--rfid-red-light);   border:1.5px solid #fca5a5; }

.rfid-main-grid { display:grid; grid-template-columns:360px 1fr; gap:20px; }
.rfid-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.rfid-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
@media(max-width:1024px){ .rfid-main-grid { grid-template-columns:1fr; } }
@media(max-width:768px){ .rfid-grid-2,.rfid-grid-3 { grid-template-columns:1fr; } }

.rfid-device-pill { display:inline-flex; align-items:center; gap:6px; background:#0f172a; color:#94d2bd; border-radius:999px; padding:4px 12px; font-size:0.72rem; font-weight:700; font-family:'JetBrains Mono',monospace; }
</style>

<div class="rfid-root" style="background:#f1f5f9;padding:20px;border-radius:16px;min-height:80vh;">

    {{-- HERO --}}
    <div class="rfid-hero">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;position:relative;z-index:1;">
            <div>
                <div class="rfid-hero-title">
                    <i class="fas fa-broadcast-tower" style="color:#94d2bd;margin-right:8px;"></i>
                    RFID Real-Time Tracking
                </div>
                <div class="rfid-hero-sub">Zebra reader integration — scan, identify, and audit inventory in real time</div>
                <div style="margin-top:12px;">
                    <span class="rfid-device-pill">
                        <i class="fas fa-microchip" style="font-size:10px;"></i>
                        {{ $this->getDeviceLabel() }}
                    </span>
                    @if(!$isHandheld && $deviceIp)
                        <span class="rfid-device-pill" style="margin-left:6px;">
                            <i class="fas fa-network-wired" style="font-size:10px;"></i>
                            {{ $deviceIp }}:{{ $devicePort }}
                        </span>
                    @endif
                </div>
            </div>
            <div style="text-align:right;">
                <span class="rfid-status-dot {{ $connectionStatus }}"></span>
                <span style="color:rgba(255,255,255,0.7);font-size:0.78rem;font-weight:700;">
                    {{ ucfirst($connectionStatus) }}
                </span>
            </div>
        </div>
    </div>

    <div class="rfid-main-grid">

        {{-- LEFT COLUMN: Settings + Controls --}}
        <div>

            {{-- DEVICE SETTINGS --}}
            <div class="rfid-card">
                <div class="rfid-card-title"><i class="fas fa-sliders"></i> Device Settings</div>

                <div style="margin-bottom:12px;">
                    <label class="rfid-label">Reader / Device Type</label>
                    <select wire:model.live="deviceType" class="rfid-select">
                        @foreach($this->getDeviceOptions() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if(!$isHandheld)
                <div class="rfid-grid-2" style="margin-bottom:12px;">
                    <div>
                        <label class="rfid-label">Reader IP Address</label>
                        <input wire:model="deviceIp" type="text" placeholder="192.168.1.100" class="rfid-input">
                    </div>
                    <div>
                        <label class="rfid-label">Port</label>
                        <input wire:model="devicePort" type="number" value="5084" class="rfid-input">
                    </div>
                </div>
                <div style="margin-bottom:14px;">
                    <label class="rfid-label">Scan Duration (seconds)</label>
                    <input wire:model="scanDuration" type="number" min="3" max="60" class="rfid-input">
                    <div style="font-size:0.7rem;color:var(--rfid-soft);margin-top:4px;">How long the reader scans before stopping automatically</div>
                </div>
                @else
                <div style="background:#f0fdfa;border:1px solid #99f6e4;border-radius:9px;padding:10px 14px;margin-bottom:14px;font-size:0.78rem;color:#0f766e;">
                    <i class="fas fa-info-circle mr-2"></i>
                    Handheld device — no IP needed. Start a session and scan. Data posts automatically via the device's DNA/Enterprise Browser app.
                </div>
                @endif

                <div style="display:flex;gap:8px;">
                    <button wire:click="saveSettings" class="rfid-btn rfid-btn-primary" style="flex:1;">
                        <i class="fas fa-save text-xs"></i> Save Settings
                    </button>
                    @if(!$isHandheld)
                    <button wire:click="testConnection" class="rfid-btn rfid-btn-gray">
                        <i class="fas fa-plug text-xs"></i> Test
                    </button>
                    @endif
                </div>

                @if($statusMessage && $scanStatus === 'idle')
                <div class="rfid-status-banner {{ $connectionStatus }}" style="margin-top:10px;margin-bottom:0;">
                    <i class="fas fa-{{ $connectionStatus === 'connected' ? 'check-circle' : 'exclamation-circle' }}"></i>
                    {{ $statusMessage }}
                </div>
                @endif
            </div>

            {{-- SESSION SETUP --}}
            <div class="rfid-card">
                <div class="rfid-card-title"><i class="fas fa-play-circle"></i> Start Scan Session</div>

                <div style="margin-bottom:12px;">
                    <label class="rfid-label">Session Name</label>
                    <input wire:model="sessionName" type="text" class="rfid-input" placeholder="e.g. Morning Inventory Check">
                </div>

                <div style="margin-bottom:14px;">
                    <label class="rfid-label">Session Type</label>
                    <select wire:model="sessionType" class="rfid-select">
                        @foreach($this->getSessionTypeOptions() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if($scanStatus === 'idle')
                    <button wire:click="startScan" class="rfid-btn rfid-btn-primary" style="width:100%;justify-content:center;">
                        <i class="fas fa-broadcast-tower text-xs"></i>
                        {{ $isHandheld ? 'Open Session' : 'Start Scanning (' . $scanDuration . 's)' }}
                    </button>
                @elseif($scanStatus === 'scanning')
                    @if($isHandheld)
                    <div style="margin-bottom:10px;">
                        <button wire:click="pollHandheldResults" wire:poll.2s="pollHandheldResults" class="rfid-btn rfid-btn-gray" style="width:100%;justify-content:center;">
                            <i class="fas fa-sync-alt text-xs"></i> Live — {{ $totalScanned }} tags read
                        </button>
                    </div>
                    <button wire:click="stopSession" class="rfid-btn rfid-btn-danger" style="width:100%;justify-content:center;">
                        <i class="fas fa-stop-circle text-xs"></i> Stop Session
                    </button>
                    @else
                    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:9px;padding:14px;text-align:center;">
                        <div style="font-size:1.1rem;font-weight:800;color:#92400e;"><i class="fas fa-circle-notch fa-spin mr-2"></i>Scanning...</div>
                        <div style="font-size:0.75rem;color:#b45309;margin-top:4px;">Reading RFID tags from {{ $getDeviceLabel() }}</div>
                    </div>
                    @endif
                @elseif($scanStatus === 'completed' || $scanStatus === 'error')
                    <button wire:click="resetScan" class="rfid-btn rfid-btn-gray" style="width:100%;justify-content:center;">
                        <i class="fas fa-redo text-xs"></i> New Scan
                    </button>
                @endif
            </div>

            {{-- QUICK LOOKUP --}}
            <div class="rfid-card">
                <div class="rfid-card-title"><i class="fas fa-search"></i> Quick EPC Lookup</div>
                <div style="display:flex;gap:8px;margin-bottom:10px;">
                    <input wire:model="quickLookupEpc" type="text" placeholder="Scan or enter EPC / barcode..." class="rfid-input" wire:keydown.enter="quickLookup" style="flex:1;">
                    <button wire:click="quickLookup" class="rfid-btn rfid-btn-primary">
                        <i class="fas fa-search text-xs"></i>
                    </button>
                </div>

                @if($quickLookupResult !== null)
                    @if($quickLookupResult['found'])
                    <div class="rfid-lookup-result found">
                        <div style="font-weight:800;font-size:0.92rem;color:#166534;margin-bottom:8px;">
                            <i class="fas fa-check-circle mr-2"></i>{{ $quickLookupResult['barcode'] }}
                        </div>
                        <div style="font-size:0.82rem;color:#374151;margin-bottom:4px;">{{ $quickLookupResult['description'] }}</div>
                        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;">
                            <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:999px;font-size:0.68rem;font-weight:700;">{{ strtoupper($quickLookupResult['status']) }}</span>
                            <span style="background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:999px;font-size:0.68rem;font-weight:700;">{{ $quickLookupResult['retail_price'] }}</span>
                            @if($quickLookupResult['department'])
                            <span style="background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:999px;font-size:0.68rem;font-weight:700;">{{ $quickLookupResult['department'] }}</span>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="rfid-lookup-result notfound">
                        <i class="fas fa-times-circle" style="color:#dc2626;margin-right:6px;"></i>
                        <span style="font-weight:700;color:#991b1b;">No item found for EPC: {{ $quickLookupResult['epc'] }}</span>
                    </div>
                    @endif
                @endif
            </div>

            {{-- RECENT SESSIONS --}}
            <div class="rfid-card">
                <div class="rfid-card-title"><i class="fas fa-history"></i> Recent Sessions</div>
                @forelse($this->getRecentSessions() as $sess)
                <div class="rfid-session-row">
                    <div>
                        <div style="font-size:0.82rem;font-weight:700;color:var(--rfid-ink);">{{ $sess->session_name }}</div>
                        <div style="font-size:0.7rem;color:var(--rfid-soft);">{{ $sess->created_at->format('M d, H:i') }} · {{ $sess->device_type }}</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:0.75rem;font-weight:800;color:var(--rfid-teal);">{{ $sess->total_scanned }} tags</div>
                        <span style="font-size:0.65rem;font-weight:700;padding:1px 6px;border-radius:999px;background:{{ $sess->status === 'completed' ? '#dcfce7' : ($sess->status === 'error' ? '#fee2e2' : '#f1f5f9') }};color:{{ $sess->status === 'completed' ? '#166534' : ($sess->status === 'error' ? '#dc2626' : '#64748b') }};">
                            {{ strtoupper($sess->status) }}
                        </span>
                    </div>
                </div>
                @empty
                <div style="text-align:center;color:var(--rfid-soft);font-size:0.82rem;padding:20px;">No sessions yet</div>
                @endforelse
            </div>

        </div>

        {{-- RIGHT COLUMN: Results --}}
        <div>

            {{-- STATUS BANNER --}}
            @if($statusMessage && $scanStatus !== 'idle')
            <div class="rfid-status-banner {{ $scanStatus }}">
                <i class="fas fa-{{ $scanStatus === 'completed' ? 'check-circle' : ($scanStatus === 'error' ? 'times-circle' : 'circle-notch fa-spin') }}"></i>
                {{ $statusMessage }}
            </div>
            @endif

            {{-- HANDHELD ENDPOINT --}}
            @if($isHandheld && $scanStatus === 'scanning' && $handheldEndpoint)
            <div class="rfid-card" style="margin-bottom:16px;">
                <div class="rfid-card-title"><i class="fas fa-mobile-alt"></i> Handheld Device Endpoint</div>
                <p style="font-size:0.78rem;color:var(--rfid-soft);margin-bottom:8px;">Configure your Zebra device's DNA app or Enterprise Browser to POST scanned EPCs to this URL:</p>
                <div class="rfid-endpoint">{{ $handheldEndpoint }}</div>
                <p style="font-size:0.72rem;color:var(--rfid-soft);margin-top:6px;">
                    Expected POST body: <code style="background:#f1f5f9;padding:1px 5px;border-radius:4px;">{"epcs":[{"epc":"AABBCCDD...","rssi":-65,"antenna":1}]}</code>
                </p>

                {{-- Live Feed --}}
                @if(!empty($liveFeed))
                <div style="margin-top:14px;">
                    <div style="font-size:0.72rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:var(--rfid-soft);margin-bottom:8px;">Live Feed</div>
                    <div class="rfid-live-feed">
                        @foreach($liveFeed as $item)
                        <div class="rfid-live-item {{ $item['status'] }}">
                            <span style="font-family:monospace;font-size:0.75rem;font-weight:700;">{{ $item['epc'] }}</span>
                            <span style="font-size:0.75rem;color:var(--rfid-soft);">{{ $item['name'] }}</span>
                            <span style="font-size:0.68rem;color:var(--rfid-soft);">{{ $item['time'] }}</span>
                            <span class="rfid-badge {{ $item['status'] }}">{{ $item['status'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif

            {{-- STATS --}}
            @if($scanStatus !== 'idle')
            <div class="rfid-stat-grid">
                <div class="rfid-stat">
                    <div class="rfid-stat-num">{{ $totalScanned }}</div>
                    <div class="rfid-stat-lbl">Tags Read</div>
                </div>
                <div class="rfid-stat">
                    <div class="rfid-stat-num green">{{ $totalMatched }}</div>
                    <div class="rfid-stat-lbl">Matched</div>
                </div>
                <div class="rfid-stat">
                    <div class="rfid-stat-num red">{{ $totalUnmatched }}</div>
                    <div class="rfid-stat-lbl">Unmatched</div>
                </div>
                <div class="rfid-stat">
                    <div class="rfid-stat-num amber">{{ $totalScanned > 0 ? round(($totalMatched / $totalScanned) * 100) : 0 }}%</div>
                    <div class="rfid-stat-lbl">Match Rate</div>
                </div>
            </div>
            @endif

            {{-- RESULTS TABLE --}}
            @if(!empty($scanResults))
            <div class="rfid-card">
                <div class="rfid-card-title" style="justify-content:space-between;">
                    <span><i class="fas fa-list"></i> Scan Results ({{ count($scanResults) }})</span>
                    <div style="display:flex;gap:6px;">
                        <span class="rfid-badge matched">{{ $totalMatched }} matched</span>
                        <span class="rfid-badge unmatched">{{ $totalUnmatched }} unmatched</span>
                    </div>
                </div>

                {{-- Filter tabs --}}
                <div style="display:flex;gap:6px;margin-bottom:14px;">
                    <button onclick="filterResults('all')"    id="tab-all"       class="rfid-btn rfid-btn-primary" style="font-size:0.72rem;padding:5px 12px;">All</button>
                    <button onclick="filterResults('matched')"    id="tab-matched"   class="rfid-btn rfid-btn-gray"    style="font-size:0.72rem;padding:5px 12px;">Matched</button>
                    <button onclick="filterResults('unmatched')"  id="tab-unmatched" class="rfid-btn rfid-btn-gray"    style="font-size:0.72rem;padding:5px 12px;">Unmatched</button>
                </div>

                <div id="rfid-results-list" style="max-height:500px;overflow-y:auto;">
                    @foreach($scanResults as $result)
                    <div class="rfid-result-row {{ $result['match_status'] }}" data-status="{{ $result['match_status'] }}">
                        <span class="rfid-badge {{ $result['match_status'] }}">{{ $result['match_status'] }}</span>
                        <div>
                            <div style="font-family:monospace;font-size:0.78rem;font-weight:700;color:var(--rfid-ink);">{{ $result['epc'] }}</div>
                            @if($result['product'])
                            <div style="font-size:0.78rem;color:var(--rfid-soft);">
                                <strong>{{ $result['product']['barcode'] }}</strong>
                                · {{ \Illuminate\Support\Str::limit($result['product']['description'], 40) }}
                            </div>
                            <div style="display:flex;gap:4px;margin-top:3px;">
                                <span style="background:#f1f5f9;padding:1px 6px;border-radius:999px;font-size:0.65rem;font-weight:700;">{{ strtoupper($result['product']['status']) }}</span>
                                @if($result['product']['department'])
                                <span style="background:#e0f2fe;color:#0369a1;padding:1px 6px;border-radius:999px;font-size:0.65rem;font-weight:700;">{{ $result['product']['department'] }}</span>
                                @endif
                                @if($result['product']['retail_price'])
                                <span style="background:#dcfce7;color:#166534;padding:1px 6px;border-radius:999px;font-size:0.65rem;font-weight:700;">${{ number_format($result['product']['retail_price'], 2) }}</span>
                                @endif
                            </div>
                            @else
                            <div style="font-size:0.75rem;color:#ef4444;font-weight:600;">⚠ Not in inventory — tag may need to be registered</div>
                            @endif
                        </div>
                        <div style="text-align:right;font-size:0.72rem;color:var(--rfid-soft);">
                            @if($result['rssi']) RSSI: {{ $result['rssi'] }}dBm @endif
                            @if($result['antenna']) <br>Ant: {{ $result['antenna'] }} @endif
                            @if($result['read_count'] > 1) <br>×{{ $result['read_count'] }} @endif
                        </div>
                        @if($result['product'])
                        <a href="{{ \App\Filament\Resources\ProductItemResource::getUrl('edit', ['record' => $result['product']['id']]) }}" target="_blank"
                           style="color:var(--rfid-teal);font-size:0.75rem;font-weight:700;text-decoration:none;">
                            View →
                        </a>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @elseif($scanStatus === 'idle')
            <div class="rfid-card" style="text-align:center;padding:48px 24px;">
                <i class="fas fa-broadcast-tower" style="font-size:3rem;color:#e2e8f0;margin-bottom:16px;display:block;"></i>
                <div style="font-size:1rem;font-weight:700;color:var(--rfid-soft);">Ready to scan</div>
                <div style="font-size:0.82rem;color:#94a3b8;margin-top:6px;">Configure your device settings and start a session</div>
            </div>
            @endif

        </div>
    </div>

</div>

<script>
function filterResults(status) {
    const rows = document.querySelectorAll('[data-status]');
    rows.forEach(row => {
        row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
    });
    ['all','matched','unmatched'].forEach(s => {
        const btn = document.getElementById('tab-' + s);
        if(btn) {
            btn.className = s === status
                ? 'rfid-btn rfid-btn-primary'
                : 'rfid-btn rfid-btn-gray';
            btn.style.fontSize = '0.72rem';
            btn.style.padding  = '5px 12px';
        }
    });
}
</script>

</x-filament-panels::page>