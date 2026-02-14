{{-- resources/views/admin/inventory/audit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="p-4 md:p-6 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold text-teal-700">💎 Jewelry Audit: {{ $audit->session_name }}</h1>
            <a href="/admin/inventory-audits" class="text-sm text-gray-500 hover:underline">← Back to Admin</a>
        </div>
        
        <div id="status-bar" class="bg-blue-600 text-white p-3 rounded mb-6 flex justify-between items-center">
            <div class="flex items-center">
                <span class="relative flex h-3 w-3 mr-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-100 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                </span>
                <span>Scanner Active...</span>
            </div>
            <span id="scan-count" class="font-mono text-lg font-bold">Total Found: 0</span>
        </div>

        <div class="h-[500px] overflow-y-auto border border-gray-200 rounded shadow-inner bg-gray-50">
            <table class="min-w-full">
                <thead class="bg-gray-100 sticky top-0">
                    <tr>
                        <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase">Time</th>
                        <th class="p-3 text-left text-xs font-semibold text-gray-600 uppercase">Item Description / RFID</th>
                    </tr>
                </thead>
                <tbody id="scan-list" class="divide-y divide-gray-200">
                    {{-- RFID Scans will populate here --}}
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const successBeep = new Audio("https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3");
    let count = 0;
    const scannedRfids = new Set(); // Prevent duplicate visual rows in same session

    window.addEventListener("message", function(event) {
        // Log to console so you can debug in the browser if needed
        console.log("DataWedge Message Received:", event.data);

        if (event.data && event.data.action === "com.dw.rfid.ACTION") {
            let rfidTag = event.data.extras["com.symbol.datawedge.data_string"];
            
            if(scannedRfids.has(rfidTag)) return; // Skip if already seen in this session

            axios.post('/api/inventory/scan', {
                rfid: rfidTag,
                audit_id: {{ $audit->id }}
            })
            .then(res => {
                if(res.data.success) {
                    successBeep.play().catch(e => console.log("Audio play blocked"));
                    count++;
                    scannedRfids.add(rfidTag);
                    
                    document.getElementById('scan-count').innerText = `Total Found: ${count}`;
                    
                    const list = document.getElementById('scan-list');
                    const row = `
                    <tr class="bg-green-50 scan-row">
                        <td class="p-3 text-xs text-gray-500">${new Date().toLocaleTimeString()}</td>
                        <td class="p-3">
                            <div class="font-medium text-gray-800">${res.data.item}</div>
                            <div class="text-[10px] text-gray-400 font-mono">${rfidTag}</div>
                        </td>
                    </tr>`;
                    list.insertAdjacentHTML('afterbegin', row);
                }
            })
            .catch(err => console.error("Scan Error:", err));
        }
    });
</script>
@endsection