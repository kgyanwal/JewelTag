{{-- resources/views/admin/inventory/audit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-2xl font-bold text-teal-700 mb-4">💎 Jewelry Audit: {{ $audit->session_name }}</h1>
        
        <div id="status-bar" class="bg-blue-500 text-white p-3 rounded mb-6 flex justify-between">
            <span>Scanner Ready...</span>
            <span id="scan-count" class="font-mono">Total Found: 0</span>
        </div>

        <div class="h-96 overflow-y-auto border border-gray-200 rounded">
            <table class="min-w-full">
                <thead class="bg-gray-100 sticky top-0">
                    <tr>
                        <th class="p-3 text-left">Time</th>
                        <th class="p-3 text-left">Description</th>
                    </tr>
                </thead>
                <tbody id="scan-list" class="divide-y divide-gray-100">
                    {{-- RFID Scans will populate here --}}
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Audio context for the "Success Beep"
    const successBeep = new Audio("https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3");
    let count = 0;

    // Listen for Zebra DataWedge Broadcast Intent
    window.addEventListener("message", function(event) {
        if (event.data && event.data.action === "com.dw.rfid.ACTION") {
            let rfidTag = event.data.extras["com.symbol.datawedge.data_string"];
            
            axios.post('/api/inventory/scan', {
                rfid: rfidTag,
                audit_id: {{ $audit->id }}
            })
            .then(res => {
                if(res.data.success) {
                    successBeep.play(); // 🚀 Audible feedback
                    count++;
                    document.getElementById('scan-count').innerText = `Total Found: ${count}`;
                    
                    const list = document.getElementById('scan-list');
                    const row = `<tr class="bg-green-50 animate-pulse">
                        <td class="p-3 text-xs text-gray-500">${new Date().toLocaleTimeString()}</td>
                        <td class="p-3 font-medium">${res.data.item}</td>
                    </tr>`;
                    list.insertAdjacentHTML('afterbegin', row);
                }
            });
        }
    });
</script>
@endsection