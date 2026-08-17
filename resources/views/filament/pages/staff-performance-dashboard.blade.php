<x-filament-panels::page>
    @php
        $d          = $this->getDashboardData();
        $staff      = $d['staff'];
        $maxTotal   = count($staff) > 0 ? max(array_column($staff, 'total')) : 1;
        $rankColors = ['#C9A24B','#94a3b8','#b45309','#0B3D3C','#7c3aed','#0284c7','#059669','#dc2626'];
        $payColors  = ['#0B3D3C','#C9A24B','#0284c7','#7c3aed','#dc2626','#059669','#94a3b8','#f59e0b'];
    @endphp

    {{-- FILTERS --}}
    {{ $this->form }}

    {{-- DATE LABEL --}}
    <p style="font-size:12px;color:#94a3b8;font-weight:600;margin:12px 0;">
        Showing: <strong style="color:#0B3D3C;">{{ $d['from_label'] }}</strong>
        &rarr; <strong style="color:#0B3D3C;">{{ $d['to_label'] }}</strong>
    </p>

    {{-- KPI CARDS --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);border-top:3px solid #0B3D3C;">
            <div style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Total Sales</div>
            <div style="font-size:28px;font-weight:900;color:#1e293b;line-height:1;">{{ number_format($d['total_sales']) }}</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:4px;">Completed transactions</div>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);border-top:3px solid #C9A24B;">
            <div style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Total Revenue</div>
            <div style="font-size:28px;font-weight:900;color:#1e293b;line-height:1;">${{ number_format($d['total_revenue'], 0) }}</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:4px;">All staff combined</div>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);border-top:3px solid #0284c7;">
            <div style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Avg Sale Value</div>
            <div style="font-size:28px;font-weight:900;color:#1e293b;line-height:1;">${{ number_format($d['avg_sale'], 0) }}</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:4px;">Per transaction</div>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);border-top:3px solid #7c3aed;">
            <div style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Staff Active</div>
            <div style="font-size:28px;font-weight:900;color:#1e293b;line-height:1;">{{ count($staff) }}</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:4px;">With sales this period</div>
        </div>
    </div>

    {{-- CHARTS ROW 1: Trend + Payment --}}
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:20px;">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#0B3D3C;margin-bottom:16px;">📈 Revenue Trend</div>
            <canvas id="spd-trend" height="100"></canvas>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#0B3D3C;margin-bottom:16px;">💳 Payment Methods</div>
            <canvas id="spd-payment" height="160"></canvas>
            <div id="spd-pay-legend" style="display:flex;flex-direction:column;gap:8px;margin-top:12px;"></div>
        </div>
    </div>

    {{-- CHARTS ROW 2: Staff bar + Avg --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#0B3D3C;margin-bottom:16px;">👥 Staff Revenue Comparison</div>
            <canvas id="spd-staff-bar" height="120"></canvas>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#0B3D3C;margin-bottom:16px;">🎯 Avg Sale Per Staff</div>
            <canvas id="spd-avg" height="120"></canvas>
        </div>
    </div>

    {{-- LEADERBOARD + DETAIL CARDS --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">

        {{-- Leaderboard --}}
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#0B3D3C;margin-bottom:16px;">🏆 Leaderboard — Revenue Share</div>
            @forelse($staff as $i => $s)
                @php
                    $pct    = $maxTotal > 0 ? round(($s['total'] / $maxTotal) * 100) : 0;
                    $color  = $rankColors[$i] ?? '#94a3b8';
                    $medal  = ['🥇','🥈','🥉'][$i] ?? ('#'.($i+1));
                    $change = $s['change_pct'] ?? 0;
                    $chgBg  = $change > 0 ? '#f0fdf4' : ($change < 0 ? '#fef2f2' : '#f8fafc');
                    $chgClr = $change > 0 ? '#16a34a' : ($change < 0 ? '#dc2626' : '#94a3b8');
                    $chgStr = ($change > 0 ? '+' : '') . $change . '%';
                @endphp
                <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:10px;border:1px solid #f1f5f9;margin-bottom:6px;">
                    <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:900;flex-shrink:0;background:{{ $color }}22;color:{{ $color }};">{{ $medal }}</div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:13px;font-weight:700;color:#1e293b;">{{ $s['name'] }}</span>
                            <span style="font-size:10px;font-weight:800;padding:2px 7px;border-radius:99px;background:{{ $chgBg }};color:{{ $chgClr }};">{{ $chgStr }}</span>
                        </div>
                        <div style="background:#f1f5f9;border-radius:99px;height:6px;margin-top:4px;">
                            <div style="width:{{ $pct }}%;height:6px;border-radius:99px;background:{{ $color }};"></div>
                        </div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px;">{{ $s['sales_count'] }} sales &middot; Avg ${{ number_format($s['avg_sale'], 0) }}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:14px;font-weight:900;color:#0B3D3C;">${{ number_format($s['total'], 0) }}</div>
                        @if(($s['prev_total'] ?? 0) > 0)
                            <div style="font-size:10px;color:#94a3b8;">prev ${{ number_format($s['prev_total'], 0) }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <div style="text-align:center;color:#94a3b8;padding:40px 0;font-size:13px;">No completed sales in this period.</div>
            @endforelse
        </div>

        {{-- Detail Cards --}}
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow-y:auto;max-height:600px;">
            <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#0B3D3C;margin-bottom:16px;">📊 Staff Detail Cards</div>
            @forelse($staff as $i => $s)
                @php $color = $rankColors[$i] ?? '#94a3b8'; @endphp
                <div style="border:1.5px solid {{ $color }}33;border-left:4px solid {{ $color }};border-radius:10px;padding:14px;margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <span style="font-size:13px;font-weight:800;color:#1e293b;">{{ $s['name'] }}</span>
                        <span style="font-size:16px;font-weight:900;color:{{ $color }};">${{ number_format($s['total'], 2) }}</span>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:10px;">
                        <div style="text-align:center;padding:8px 4px;background:#f8fafc;border-radius:8px;">
                            <div style="font-size:14px;font-weight:900;color:#1e293b;">{{ $s['sales_count'] }}</div>
                            <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Sales</div>
                        </div>
                        <div style="text-align:center;padding:8px 4px;background:#f8fafc;border-radius:8px;">
                            <div style="font-size:14px;font-weight:900;color:#1e293b;">${{ number_format($s['avg_sale'], 0) }}</div>
                            <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Avg Sale</div>
                        </div>
                        <div style="text-align:center;padding:8px 4px;background:#f8fafc;border-radius:8px;">
                            <div style="font-size:14px;font-weight:900;color:#1e293b;">{{ $s['solo_count'] }}</div>
                            <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Solo</div>
                        </div>
                    </div>
                    @if(!empty($s['methods']))
                        <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:4px;">
                            @foreach($s['methods'] as $method => $cnt)
                                <span style="font-size:9px;font-weight:700;padding:2px 7px;border-radius:99px;background:{{ $color }}15;color:{{ $color }};border:1px solid {{ $color }}33;">{{ $method }}: {{ $cnt }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div style="text-align:center;color:#94a3b8;padding:40px 0;">No data</div>
            @endforelse
        </div>
    </div>

    {{-- TOP 4 vs BOTTOM 4 --}}
    @if(count($staff) >= 4)
        @php
            $top4  = array_slice($staff, 0, 4);
            $bot4  = array_slice($staff, -4);
            $top4n = implode(', ', array_column($top4, 'name'));
            $top4t = array_map(fn($s) => round($s['total'], 2), $top4);
            $bot4n = implode(', ', array_column($bot4, 'name'));
            $bot4t = array_map(fn($s) => round($s['total'], 2), $bot4);
        @endphp
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:20px;">
            <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#0B3D3C;margin-bottom:16px;">⚡ Top 4 vs Bottom 4 — Head to Head</div>
            <canvas id="spd-topbottom" height="80"></canvas>
        </div>
    @endif

    <x-filament-actions::modals />

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var rankColors = {!! json_encode($rankColors) !!};
        var payColors  = {!! json_encode($payColors) !!};

        new Chart(document.getElementById('spd-trend'), {
            type: 'line',
            data: {
                labels: {!! json_encode($d['trend_labels']) !!},
                datasets: [{ data: {!! json_encode($d['trend_values']) !!}, borderColor:'#0B3D3C', backgroundColor:'rgba(11,61,60,0.08)', borderWidth:2.5, pointRadius:3, pointBackgroundColor:'#C9A24B', fill:true, tension:0.4 }]
            },
            options: { responsive:true, plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false},ticks:{maxTicksLimit:10,font:{size:10}}}, y:{grid:{color:'rgba(0,0,0,0.04)'},ticks:{callback:function(v){return '$'+v.toLocaleString();},font:{size:10}}} } }
        });

        var payLabels = {!! json_encode(array_keys($d['payment_breakdown'])) !!};
        var payValues = {!! json_encode(array_values($d['payment_breakdown'])) !!};
        new Chart(document.getElementById('spd-payment'), {
            type: 'doughnut',
            data: { labels:payLabels, datasets:[{data:payValues, backgroundColor:payColors.slice(0,payLabels.length), borderWidth:2, borderColor:'#fff'}] },
            options: { responsive:true, cutout:'65%', plugins:{legend:{display:false}} }
        });
        var leg = document.getElementById('spd-pay-legend');
        payLabels.forEach(function(lbl,i){
            var row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:8px;font-size:12px;';
            var dot = document.createElement('div');
            dot.style.cssText = 'width:10px;height:10px;border-radius:50%;flex-shrink:0;background:'+payColors[i]+';';
            var name = document.createElement('span');
            name.style.cssText = 'font-weight:600;flex:1;';
            name.textContent = lbl;
            var val = document.createElement('span');
            val.style.fontWeight = '800';
            val.textContent = payValues[i];
            row.appendChild(dot); row.appendChild(name); row.appendChild(val);
            leg.appendChild(row);
        });

        var staffNames  = {!! json_encode(array_column($d['staff'], 'name')) !!};
        var staffTotals = {!! json_encode(array_map(fn($s) => round($s['total'],2), $d['staff'])) !!};
        var staffAvgs   = {!! json_encode(array_map(fn($s) => round($s['avg_sale'],2), $d['staff'])) !!};

        new Chart(document.getElementById('spd-staff-bar'), {
            type:'bar',
            data:{ labels:staffNames, datasets:[{data:staffTotals, backgroundColor:rankColors.slice(0,staffNames.length), borderRadius:6, borderSkipped:false}] },
            options:{ responsive:true, plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false},ticks:{font:{size:10}}}, y:{grid:{color:'rgba(0,0,0,0.04)'},ticks:{callback:function(v){return '$'+v.toLocaleString();},font:{size:10}}} } }
        });

        new Chart(document.getElementById('spd-avg'), {
            type:'bar',
            data:{ labels:staffNames, datasets:[{data:staffAvgs, backgroundColor:'rgba(11,61,60,0.15)', borderColor:'#0B3D3C', borderWidth:2, borderRadius:6, borderSkipped:false}] },
            options:{ responsive:true, plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false},ticks:{font:{size:10}}}, y:{grid:{color:'rgba(0,0,0,0.04)'},ticks:{callback:function(v){return '$'+v.toLocaleString();},font:{size:10}}} } }
        });

        @if(count($staff) >= 4)
        new Chart(document.getElementById('spd-topbottom'), {
            type:'bar',
            data:{
                labels:['1st','2nd','3rd','4th'],
                datasets:[
                    { label:'Top 4 — {{ $top4n }}', data:{!! json_encode($top4t) !!}, backgroundColor:'rgba(11,61,60,0.8)', borderRadius:6, borderSkipped:false },
                    { label:'Bottom 4 — {{ $bot4n }}', data:{!! json_encode($bot4t) !!}, backgroundColor:'rgba(201,162,75,0.6)', borderRadius:6, borderSkipped:false }
                ]
            },
            options:{ responsive:true, plugins:{legend:{position:'top',labels:{font:{size:11},padding:16}}}, scales:{ x:{grid:{display:false}}, y:{grid:{color:'rgba(0,0,0,0.04)'},ticks:{callback:function(v){return '$'+v.toLocaleString();},font:{size:10}}} } }
        });
        @endif
    });
    </script>
    @endpush

</x-filament-panels::page>