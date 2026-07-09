<?php

namespace App\Filament\Pages;

use App\Models\ProductItem;
use App\Models\RfidSession;
use App\Models\RfidScanLog;
use App\Services\ZebraRfidService;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class RfidTracking extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-signal';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $navigationLabel = 'RFID Tracking';
    protected static ?string $title           = 'RFID Real-Time Tracking';
    protected static string  $view            = 'filament.pages.rfid-tracking';

    // Settings
    public string $deviceType    = 'fx9600';
    public string $deviceIp      = '';
    public int    $devicePort    = 5084;
    public int    $scanDuration  = 10;
    public string $sessionType   = 'inventory_scan';
    public string $sessionName   = '';

    // State
    public string  $connectionStatus = 'idle'; // idle, connected, error
    public string  $scanStatus       = 'idle'; // idle, scanning, completed, error
    public ?int    $activeSessionId  = null;
    public array   $scanResults      = [];
    public int     $totalScanned     = 0;
    public int     $totalMatched     = 0;
    public int     $totalUnmatched   = 0;
    public string  $statusMessage    = '';
    public bool    $isHandheld       = false;
    public string  $handheldEndpoint = '';

    // Live scan feed for handheld devices
    public array $liveFeed = [];

    public function mount(): void
    {
        // Load saved settings
        $this->deviceType   = DB::table('site_settings')->where('key', 'rfid_reader_type')->value('value') ?? 'fx9600';
        $this->deviceIp     = DB::table('site_settings')->where('key', 'rfid_reader_ip')->value('value')   ?? '';
        $this->devicePort   = (int)(DB::table('site_settings')->where('key', 'rfid_reader_port')->value('value') ?? 5084);
        $this->isHandheld   = ZebraRfidService::isHandheld($this->deviceType);
        $this->sessionName  = 'Scan — ' . now()->format('M d, Y H:i');
    }

    public function updatedDeviceType(): void
    {
        $this->isHandheld = ZebraRfidService::isHandheld($this->deviceType);
        $this->connectionStatus = 'idle';
        $this->statusMessage = '';
    }

    // ── SETTINGS ─────────────────────────────────────────────────────────────

    public function saveSettings(): void
    {
        $settings = [
            'rfid_reader_type' => $this->deviceType,
            'rfid_reader_ip'   => $this->deviceIp,
            'rfid_reader_port' => $this->devicePort,
        ];

        foreach ($settings as $key => $value) {
            DB::table('site_settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        }

        $this->isHandheld = ZebraRfidService::isHandheld($this->deviceType);

        Notification::make()->title('Settings Saved')->success()->send();
    }

    // ── CONNECTION TEST ───────────────────────────────────────────────────────

    public function testConnection(): void
    {
        if ($this->isHandheld) {
            $this->connectionStatus = 'connected';
            $this->statusMessage    = 'Handheld device — no IP connection needed. Start a session and scan with your device.';
            return;
        }

        if (empty($this->deviceIp)) {
            $this->connectionStatus = 'error';
            $this->statusMessage    = 'Please enter the reader IP address first.';
            return;
        }

        $service = new ZebraRfidService($this->deviceIp, $this->devicePort, $this->deviceType);
        $result  = $service->testConnection();

        $this->connectionStatus = $result['success'] ? 'connected' : 'error';
        $this->statusMessage    = $result['message'];

        if ($result['success']) {
            Notification::make()->title('Reader Connected')->body($result['message'])->success()->send();
        } else {
            Notification::make()->title('Connection Failed')->body($result['message'])->danger()->send();
        }
    }

    // ── START SCAN ────────────────────────────────────────────────────────────

    public function startScan(): void
    {
        if (empty($this->sessionName)) {
            $this->sessionName = 'Scan — ' . now()->format('M d, Y H:i');
        }

        // Create session record
        $session = RfidSession::create([
            'session_name' => $this->sessionName,
            'session_type' => $this->sessionType,
            'device_type'  => $this->deviceType,
            'device_ip'    => $this->deviceIp,
            'device_port'  => $this->devicePort,
            'status'       => 'idle',
            'user_id'      => auth()->id(),
        ]);

        $this->activeSessionId = $session->id;
        $this->scanStatus      = 'scanning';
        $this->scanResults     = [];
        $this->totalScanned    = 0;
        $this->totalMatched    = 0;
        $this->totalUnmatched  = 0;

        if ($this->isHandheld) {
            // Handheld: just open session and wait for HTTP POSTs
            $session->update(['status' => 'scanning', 'started_at' => now()]);
            $this->handheldEndpoint = url("/api/rfid/scan/{$session->id}");
            $this->statusMessage    = 'Session open. Scan with your ' . $this->getDeviceLabel() . ' now.';

            Notification::make()
                ->title('Session Started')
                ->body('Scan with your handheld device. Results will appear in real time.')
                ->success()
                ->send();
            return;
        }

        // Fixed reader: LLRP scan
        $service = new ZebraRfidService($this->deviceIp, $this->devicePort, $this->deviceType);
        $result  = $service->startScan($session, $this->scanDuration);

        if ($result['success'] && $result['mode'] === 'llrp') {
            $this->scanResults    = $result['results']['items'] ?? [];
            $this->totalScanned   = count($this->scanResults);
            $this->totalMatched   = $result['results']['matched'] ?? 0;
            $this->totalUnmatched = $result['results']['unmatched'] ?? 0;
            $this->scanStatus     = 'completed';
            $this->statusMessage  = "Scan complete — {$this->totalScanned} tags read, {$this->totalMatched} matched.";
        } else {
            $this->scanStatus    = 'error';
            $this->statusMessage = $result['message'] ?? 'Scan failed';
            Notification::make()->title('Scan Failed')->body($this->statusMessage)->danger()->send();
        }
    }

    // ── STOP SESSION (handheld) ───────────────────────────────────────────────

    public function stopSession(): void
    {
        if (!$this->activeSessionId) return;

        $session = RfidSession::find($this->activeSessionId);
        if ($session) {
            $session->update(['status' => 'completed', 'completed_at' => now()]);
            $this->scanResults    = $session->scan_results ?? [];
            $this->totalScanned   = $session->total_scanned;
            $this->totalMatched   = $session->matched;
            $this->totalUnmatched = $session->unmatched;
        }

        $this->scanStatus    = 'completed';
        $this->statusMessage = "Session complete — {$this->totalScanned} tags read.";
    }

    // ── POLL for handheld live results ────────────────────────────────────────

    public function pollHandheldResults(): void
    {
        if (!$this->activeSessionId || !$this->isHandheld) return;

        $session = RfidSession::find($this->activeSessionId);
        if (!$session) return;

        $this->totalScanned   = $session->total_scanned;
        $this->totalMatched   = $session->matched;
        $this->totalUnmatched = $session->unmatched;
        $this->scanResults    = $session->scan_results ?? [];

        // Latest 5 scans for live feed
        $this->liveFeed = RfidScanLog::where('rfid_session_id', $this->activeSessionId)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($log) => [
                'epc'    => $log->epc_code,
                'status' => $log->match_status,
                'time'   => $log->scanned_at->format('H:i:s'),
                'name'   => $log->productItem?->custom_description ?? '—',
            ])
            ->toArray();
    }

    // ── RESET ─────────────────────────────────────────────────────────────────

    public function resetScan(): void
    {
        $this->scanStatus      = 'idle';
        $this->scanResults     = [];
        $this->totalScanned    = 0;
        $this->totalMatched    = 0;
        $this->totalUnmatched  = 0;
        $this->statusMessage   = '';
        $this->activeSessionId = null;
        $this->liveFeed        = [];
        $this->sessionName     = 'Scan — ' . now()->format('M d, Y H:i');
    }

    // ── QUICK LOOKUP ──────────────────────────────────────────────────────────

    public string $quickLookupEpc = '';
    public ?array $quickLookupResult = null;

    public function quickLookup(): void
    {
        if (empty($this->quickLookupEpc)) return;

        $epc     = strtoupper(trim($this->quickLookupEpc));
        $product = ProductItem::where('rfid_code', $epc)
            ->orWhere('rfid_code', ltrim($epc, '0'))
            ->orWhere('barcode', $epc)
            ->first();

        if ($product) {
            $this->quickLookupResult = [
                'found'       => true,
                'barcode'     => $product->barcode,
                'description' => $product->custom_description,
                'status'      => $product->status,
                'retail_price'=> '$' . number_format($product->retail_price ?? 0, 2),
                'department'  => $product->department,
                'category'    => $product->category,
                'metal_type'  => $product->metal_type,
                'rfid_code'   => $product->rfid_code,
            ];
        } else {
            $this->quickLookupResult = ['found' => false, 'epc' => $epc];
        }
    }

    // ── HISTORY ───────────────────────────────────────────────────────────────

    public function getRecentSessions(): \Illuminate\Database\Eloquent\Collection
    {
        return RfidSession::latest()->take(10)->get();
    }

    // ── HELPERS ───────────────────────────────────────────────────────────────

    public function getDeviceLabel(): string
    {
        return ZebraRfidService::getDeviceOptions()[$this->deviceType] ?? $this->deviceType;
    }

    public function getDeviceOptions(): array
    {
        return ZebraRfidService::getDeviceOptions();
    }

    public function getSessionTypeOptions(): array
    {
        return [
            'inventory_scan' => 'Inventory Scan — Count all items',
            'checkout'       => 'Checkout Verification — Verify items leaving store',
            'receiving'      => 'Receiving — Verify incoming shipment',
            'audit'          => 'Audit — Compare against expected stock',
            'search'         => 'Item Search — Find specific item',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }
}