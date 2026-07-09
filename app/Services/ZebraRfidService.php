<?php

namespace App\Services;

use App\Models\ProductItem;
use App\Models\RfidSession;
use App\Models\RfidScanLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ZebraRfidService
{
    // Device type constants
    const DEVICE_FX9600  = 'fx9600';
    const DEVICE_FX7500  = 'fx7500';
    const DEVICE_RFD40   = 'rfd40';
    const DEVICE_RFD8500 = 'rfd8500';
    const DEVICE_ATR7000 = 'atr7000';
    const DEVICE_MC9300  = 'mc9300';

    // LLRP Protocol constants
    const LLRP_PORT         = 5084;
    const LLRP_KEEPALIVE    = 10;
    const LLRP_TIMEOUT      = 30;

    protected string $deviceIp;
    protected int    $devicePort;
    protected string $deviceType;

    public function __construct(string $deviceIp = '', int $devicePort = 5084, string $deviceType = self::DEVICE_FX9600)
    {
        $this->deviceIp   = $deviceIp   ?: DB::table('site_settings')->where('key', 'rfid_reader_ip')->value('value')   ?? '';
        $this->devicePort = $devicePort ?: (int)(DB::table('site_settings')->where('key', 'rfid_reader_port')->value('value') ?? 5084);
        $this->deviceType = $deviceType ?: DB::table('site_settings')->where('key', 'rfid_reader_type')->value('value') ?? self::DEVICE_FX9600;
    }

    /**
     * Test connection to the RFID reader
     */
    public function testConnection(): array
    {
        if (empty($this->deviceIp)) {
            return ['success' => false, 'message' => 'No reader IP configured'];
        }

        try {
            $socket = @fsockopen($this->deviceIp, $this->devicePort, $errno, $errstr, 5);

            if (!$socket) {
                return [
                    'success' => false,
                    'message' => "Cannot connect to {$this->deviceIp}:{$this->devicePort} — {$errstr} ({$errno})",
                ];
            }

            fclose($socket);

            return [
                'success' => true,
                'message' => "Connected to {$this->deviceType} at {$this->deviceIp}:{$this->devicePort}",
                'device'  => $this->deviceType,
                'ip'      => $this->deviceIp,
                'port'    => $this->devicePort,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Start a scan session — connects to reader, sends LLRP START_ROSPEC
     * and reads EPC data for the specified duration
     */
    public function startScan(RfidSession $session, int $durationSeconds = 10): array
    {
        $session->update(['status' => 'connecting', 'started_at' => now()]);

        try {
            if (in_array($this->deviceType, [self::DEVICE_RFD40, self::DEVICE_RFD8500, self::DEVICE_MC9300])) {
                // Handheld devices: wait for HTTP POST from device app (DNA/Enterprise Browser)
                // The device will POST scanned EPCs to /api/rfid/scan/{session}
                $session->update(['status' => 'scanning']);
                return [
                    'success' => true,
                    'mode'    => 'http_post',
                    'message' => 'Handheld mode — scan with your device. Data will appear automatically.',
                    'endpoint' => url("/api/rfid/scan/{$session->id}"),
                ];
            }

            // Fixed readers (FX9600, FX7500, ATR7000) — LLRP over TCP
            $epcs = $this->llrpScan($durationSeconds);

            $session->update(['status' => 'scanning']);

            $results = $this->processEpcs($epcs, $session);

            $session->update([
                'status'       => 'completed',
                'completed_at' => now(),
                'total_scanned' => count($epcs),
                'matched'      => $results['matched'],
                'unmatched'    => $results['unmatched'],
                'scan_results' => $results['items'],
            ]);

            return ['success' => true, 'mode' => 'llrp', 'results' => $results];

        } catch (\Exception $e) {
            $session->update(['status' => 'error']);
            Log::error('RFID Scan Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * LLRP scan — raw TCP socket communication with Zebra fixed readers
     * Sends: SET_READER_CONFIG → ADD_ROSPEC → ENABLE_ROSPEC → START_ROSPEC
     * Reads: RO_ACCESS_REPORT messages containing EPCs
     */
    protected function llrpScan(int $duration = 10): array
    {
        $socket = @fsockopen($this->deviceIp, $this->devicePort, $errno, $errstr, self::LLRP_TIMEOUT);

        if (!$socket) {
            throw new \Exception("LLRP Connection failed to {$this->deviceIp}:{$this->devicePort} — {$errstr}");
        }

        stream_set_timeout($socket, self::LLRP_TIMEOUT);

        $epcs = [];

        try {
            // 1. SET_READER_CONFIG — reset to defaults
            fwrite($socket, $this->buildSetReaderConfig());
            $this->readLlrpResponse($socket); // read SET_READER_CONFIG_RESPONSE

            // 2. ADD_ROSPEC — define read operation
            fwrite($socket, $this->buildAddRospec($duration));
            $this->readLlrpResponse($socket); // read ADD_ROSPEC_RESPONSE

            // 3. ENABLE_ROSPEC
            fwrite($socket, $this->buildEnableRospec());
            $this->readLlrpResponse($socket); // read ENABLE_ROSPEC_RESPONSE

            // 4. START_ROSPEC
            fwrite($socket, $this->buildStartRospec());

            // 5. Read EPC data until duration expires or reader sends RO_ACCESS_REPORT
            $startTime = time();
            while ((time() - $startTime) < ($duration + 5)) {
                $data = $this->readLlrpResponse($socket);
                if (empty($data)) break;

                $parsed = $this->parseLlrpMessage($data);

                if ($parsed['type'] === 'RO_ACCESS_REPORT') {
                    foreach ($parsed['epcs'] as $epc) {
                        $epcs[$epc['epc']] = $epc; // deduplicate by EPC
                    }
                }

                if ($parsed['type'] === 'READER_EVENT_NOTIFICATION') {
                    // Check if ROSpec finished
                    if ($parsed['event'] === 'ROSpecStopEvent') break;
                }
            }

            // 6. DELETE_ROSPEC — cleanup
            fwrite($socket, $this->buildDeleteRospec());

        } finally {
            fclose($socket);
        }

        return array_values($epcs);
    }

    /**
     * Process scanned EPCs against database
     */
    protected function processEpcs(array $epcs, RfidSession $session): array
    {
        $matched   = 0;
        $unmatched = 0;
        $items     = [];

        foreach ($epcs as $epcData) {
            $epc       = $epcData['epc'] ?? '';
            $rfidCode  = $this->normalizeEpc($epc);

            // Try to find product by rfid_code
            $product = ProductItem::where('rfid_code', $rfidCode)
                ->orWhere('rfid_code', $epc)
                ->orWhere('rfid_code', strtolower($rfidCode))
                ->first();

            $matchStatus = $product ? 'matched' : 'unmatched';
            $product ? $matched++ : $unmatched++;

            // Log individual scan
            RfidScanLog::create([
                'rfid_session_id' => $session->id,
                'epc_code'        => $epc,
                'rfid_code'       => $rfidCode,
                'rssi'            => $epcData['rssi'] ?? null,
                'antenna_port'    => $epcData['antenna'] ?? null,
                'read_count'      => $epcData['count'] ?? 1,
                'product_item_id' => $product?->id,
                'match_status'    => $matchStatus,
                'scanned_at'      => now(),
            ]);

            $items[] = [
                'epc'          => $epc,
                'rfid_code'    => $rfidCode,
                'match_status' => $matchStatus,
                'rssi'         => $epcData['rssi'] ?? null,
                'antenna'      => $epcData['antenna'] ?? null,
                'read_count'   => $epcData['count'] ?? 1,
                'product'      => $product ? [
                    'id'          => $product->id,
                    'barcode'     => $product->barcode,
                    'description' => $product->custom_description,
                    'status'      => $product->status,
                    'retail_price'=> $product->retail_price,
                    'department'  => $product->department,
                    'category'    => $product->category,
                    'metal_type'  => $product->metal_type,
                ] : null,
            ];
        }

        return compact('matched', 'unmatched', 'items');
    }

    /**
     * Process EPC posted from handheld device (RFD40/RFD8500/MC9300)
     * Called from API controller when device POSTs scan data
     */
    public function processHandheldScan(RfidSession $session, array $epcs): array
    {
        $results = $this->processEpcs($epcs, $session);

        $session->increment('total_scanned', count($epcs));
        $session->increment('matched',   $results['matched']);
        $session->increment('unmatched', $results['unmatched']);

        // Merge with existing results
        $existing = $session->scan_results ?? [];
        $merged   = array_merge($existing, $results['items']);

        $session->update([
            'scan_results' => $merged,
            'status'       => 'scanning',
        ]);

        return $results;
    }

    /**
     * Normalize EPC hex string to match rfid_code format in DB
     */
    protected function normalizeEpc(string $epc): string
    {
        // Remove spaces, uppercase
        $clean = strtoupper(str_replace(' ', '', $epc));
        // Strip leading zeros if short code stored without them
        return ltrim($clean, '0') ?: '0';
    }

    // ── LLRP Binary Message Builders ─────────────────────────────────────────

    protected function buildSetReaderConfig(): string
    {
        // LLRP SET_READER_CONFIG (reset to defaults)
        // Message type: 3, Version: 1, Reserved: 0
        $payload = pack('n', 0x0000); // RestoreFactorySettings = false
        return $this->wrapLlrp(3, $payload);
    }

    protected function buildAddRospec(int $duration = 10): string
    {
        // Simplified ADD_ROSPEC for inventory scan
        // This is a minimal but functional LLRP ADD_ROSPEC
        $durationMs = $duration * 1000; // convert to milliseconds

        $payload = pack('N', 1)           // ROSpecID = 1
                 . pack('C', 0)           // Priority = 0
                 . pack('C', 0)           // CurrentState = Disabled
                 // ROBoundarySpec
                 . pack('n', 178)         // ROSpecStartTrigger TLV type
                 . pack('n', 7)           // Length
                 . pack('C', 1)           // Periodic trigger
                 . pack('n', 179)         // ROSpecStopTrigger TLV type
                 . pack('n', 9)           // Length
                 . pack('C', 2)           // Duration trigger
                 . pack('N', $durationMs) // Duration in ms
                 // AISpec (Antenna Inventory Spec)
                 . pack('n', 183)         // AISpec TLV type
                 . pack('n', 10)          // Length
                 . pack('n', 1)           // AntennaCount
                 . pack('n', 0)           // AntennaID 0 = all antennas
                 // AISpecStopTrigger
                 . pack('n', 184)         // TLV type
                 . pack('n', 7)           // Length
                 . pack('C', 0);          // Null trigger (stop with ROSpec)

        return $this->wrapLlrp(20, $payload); // ADD_ROSPEC = type 20
    }

    protected function buildEnableRospec(): string
    {
        $payload = pack('N', 1); // ROSpecID = 1
        return $this->wrapLlrp(24, $payload); // ENABLE_ROSPEC = type 24
    }

    protected function buildStartRospec(): string
    {
        $payload = pack('N', 1); // ROSpecID = 1
        return $this->wrapLlrp(26, $payload); // START_ROSPEC = type 26
    }

    protected function buildDeleteRospec(): string
    {
        $payload = pack('N', 1); // ROSpecID = 1
        return $this->wrapLlrp(21, $payload); // DELETE_ROSPEC = type 21
    }

    protected function wrapLlrp(int $type, string $payload): string
    {
        static $messageId = 1;
        $length = 10 + strlen($payload); // header(10) + payload

        // LLRP header: Version(3bit) + Type(10bit) + Length(32bit) + MessageID(32bit)
        $version = 1;
        $header  = (($version & 0x7) << 10) | ($type & 0x3FF);

        return pack('n', $header)
             . pack('N', $length)
             . pack('N', $messageId++)
             . $payload;
    }

    protected function readLlrpResponse($socket, int $timeout = 5): string
    {
        stream_set_timeout($socket, $timeout);
        $header = fread($socket, 10);
        if (strlen($header) < 10) return '';

        $unpacked = unpack('nheader/Nlength/NmessageId', $header);
        $remaining = $unpacked['length'] - 10;

        if ($remaining <= 0) return $header;

        $payload = '';
        while (strlen($payload) < $remaining) {
            $chunk = fread($socket, $remaining - strlen($payload));
            if ($chunk === false || $chunk === '') break;
            $payload .= $chunk;
        }

        return $header . $payload;
    }

    protected function parseLlrpMessage(string $data): array
    {
        if (strlen($data) < 10) return ['type' => 'UNKNOWN', 'epcs' => []];

        $unpacked = unpack('nheader/Nlength/NmessageId', $data);
        $msgType  = $unpacked['header'] & 0x3FF;

        // Message type 61 = RO_ACCESS_REPORT
        if ($msgType === 61) {
            return [
                'type' => 'RO_ACCESS_REPORT',
                'epcs' => $this->parseRoAccessReport(substr($data, 10)),
            ];
        }

        // Message type 63 = READER_EVENT_NOTIFICATION
        if ($msgType === 63) {
            return ['type' => 'READER_EVENT_NOTIFICATION', 'event' => '', 'epcs' => []];
        }

        return ['type' => 'OTHER_' . $msgType, 'epcs' => []];
    }

    protected function parseRoAccessReport(string $payload): array
    {
        $epcs    = [];
        $offset  = 0;
        $length  = strlen($payload);

        while ($offset < $length - 4) {
            if ($offset + 4 > $length) break;

            $tlvHeader = unpack('ntype/nlength', substr($payload, $offset, 4));
            $tlvType   = $tlvHeader['type'] & 0x3FF;
            $tlvLength = $tlvHeader['length'];

            if ($tlvLength < 4 || $offset + $tlvLength > $length) break;

            // TLV type 14 = TagReportData
            if ($tlvType === 14) {
                $epcData = $this->parseTagReport(substr($payload, $offset + 4, $tlvLength - 4));
                if (!empty($epcData['epc'])) {
                    $epcs[] = $epcData;
                }
            }

            $offset += $tlvLength;
        }

        return $epcs;
    }

    protected function parseTagReport(string $data): array
    {
        $result = ['epc' => '', 'rssi' => null, 'antenna' => null, 'count' => 1];
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length - 2) {
            if ($offset + 2 > $length) break;

            // Check for TV (1-byte type) or TLV (2-byte type)
            $firstByte = ord($data[$offset]);

            if ($firstByte & 0x80) {
                // TV parameter (1-byte type)
                $tvType = $firstByte & 0x7F;

                // TV type 8 = AntennaID (2 bytes value)
                if ($tvType === 8 && $offset + 3 <= $length) {
                    $result['antenna'] = unpack('n', substr($data, $offset + 1, 2))[1];
                    $offset += 3;
                }
                // TV type 9 = PeakRSSI (1 byte, signed)
                elseif ($tvType === 9 && $offset + 2 <= $length) {
                    $rssiRaw = ord($data[$offset + 1]);
                    $result['rssi'] = $rssiRaw > 127 ? $rssiRaw - 256 : $rssiRaw;
                    $offset += 2;
                }
                // TV type 14 = TagSeenCount (2 bytes)
                elseif ($tvType === 14 && $offset + 3 <= $length) {
                    $result['count'] = unpack('n', substr($data, $offset + 1, 2))[1];
                    $offset += 3;
                } else {
                    $offset++; // skip unknown TV
                }
            } else {
                // TLV parameter (2-byte type + 2-byte length)
                if ($offset + 4 > $length) break;
                $tlv = unpack('ntype/nlength', substr($data, $offset, 4));

                if ($tlv['length'] < 4) break;

                // TLV type 13 = EPC_Data
                if ($tlv['type'] === 13 && $offset + $tlv['length'] <= $length) {
                    $epcBytes     = substr($data, $offset + 4, $tlv['length'] - 4);
                    $result['epc'] = strtoupper(bin2hex($epcBytes));
                }

                $offset += max(4, $tlv['length']);
            }
        }

        return $result;
    }

    public static function getDeviceOptions(): array
    {
        return [
            self::DEVICE_FX9600  => 'Zebra FX9600 — Fixed Reader (LLRP/Ethernet)',
            self::DEVICE_FX7500  => 'Zebra FX7500 — Fixed Reader (LLRP/Ethernet)',
            self::DEVICE_ATR7000 => 'Zebra ATR7000 — RFID Tunnel (LLRP/Ethernet)',
            self::DEVICE_RFD40   => 'Zebra RFD40 — Handheld Bluetooth (HTTP POST)',
            self::DEVICE_RFD8500 => 'Zebra RFD8500 — Handheld Bluetooth (HTTP POST)',
            self::DEVICE_MC9300  => 'Zebra MC9300 — Mobile Computer (HTTP POST)',
        ];
    }

    public static function isHandheld(string $deviceType): bool
    {
        return in_array($deviceType, [self::DEVICE_RFD40, self::DEVICE_RFD8500, self::DEVICE_MC9300]);
    }
}