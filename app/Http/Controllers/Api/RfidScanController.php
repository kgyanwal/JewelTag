<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RfidSession;
use App\Services\ZebraRfidService;
use Illuminate\Http\Request;

/**
 * Receives RFID scan data posted from handheld Zebra devices
 * (RFD40, RFD8500, MC9300 via DNA App or Enterprise Browser)
 */
class RfidScanController extends Controller
{
    public function receive(Request $request, int $sessionId)
    {
        $session = RfidSession::find($sessionId);

        if (!$session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        if ($session->status !== 'scanning') {
            return response()->json(['error' => 'Session is not active'], 400);
        }

        // Normalize incoming EPC data
        // DNA App sends: {"TagList":[{"IDHex":"AABBCCDD...","RSSI":"-65"}]}
        // Enterprise Browser sends: {"epcs":[{"epc":"AABBCC...","rssi":-65,"antenna":1}]}
        $epcs = [];

        // DNA App format
        if ($request->has('TagList')) {
            foreach ($request->input('TagList', []) as $tag) {
                $epcs[] = [
                    'epc'     => strtoupper($tag['IDHex'] ?? ''),
                    'rssi'    => (int)($tag['RSSI'] ?? 0),
                    'antenna' => (int)($tag['AntennaPort'] ?? 1),
                    'count'   => (int)($tag['Count'] ?? 1),
                ];
            }
        }
        // Enterprise Browser / custom app format
        elseif ($request->has('epcs')) {
            foreach ($request->input('epcs', []) as $tag) {
                $epcs[] = [
                    'epc'     => strtoupper($tag['epc'] ?? ''),
                    'rssi'    => (int)($tag['rssi'] ?? 0),
                    'antenna' => (int)($tag['antenna'] ?? 1),
                    'count'   => (int)($tag['count'] ?? 1),
                ];
            }
        }
        // Simple array of EPC strings
        elseif ($request->has('tags')) {
            foreach ($request->input('tags', []) as $epc) {
                $epcs[] = ['epc' => strtoupper($epc), 'rssi' => null, 'antenna' => 1, 'count' => 1];
            }
        }

        if (empty($epcs)) {
            return response()->json(['error' => 'No EPC data found in request'], 400);
        }

        $service = new ZebraRfidService('', 0, $session->device_type);
        $results = $service->processHandheldScan($session, $epcs);

        return response()->json([
            'success'   => true,
            'processed' => count($epcs),
            'matched'   => $results['matched'],
            'unmatched' => $results['unmatched'],
            'session'   => [
                'id'            => $session->id,
                'total_scanned' => $session->fresh()->total_scanned,
            ],
        ]);
    }

    public function status(int $sessionId)
    {
        $session = RfidSession::find($sessionId);
        if (!$session) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'status'        => $session->status,
            'total_scanned' => $session->total_scanned,
            'matched'       => $session->matched,
            'unmatched'     => $session->unmatched,
        ]);
    }
}