<x-filament-widgets::widget>
    @php $alerts = $this->getAlerts(); @endphp

    @if(count($alerts) > 0)
    <div style="background:#1e293b;border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:12px;flex-wrap:nowrap;overflow-x:auto;">

        {{-- Header label --}}
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;padding-right:12px;border-right:1px solid #334155;">
            <div style="background:#ef4444;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>
            <div>
                <div style="font-size:11px;font-weight:800;color:#f1f5f9;white-space:nowrap;">⚠️ NEEDS ATTENTION</div>
                <div style="font-size:10px;color:#64748b;white-space:nowrap;">{{ count($alerts) }} alert{{ count($alerts) > 1 ? 's' : '' }}</div>
            </div>
        </div>

        {{-- Alert pills --}}
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:nowrap;overflow-x:auto;padding-bottom:2px;">
            @foreach($alerts as $alert)
            <a href="{{ $alert['url'] }}" style="
                display:inline-flex;
                align-items:center;
                gap:6px;
                background:{{ $alert['bg'] }};
                border:1px solid {{ $alert['border'] }};
                border-radius:99px;
                padding:5px 12px 5px 8px;
                text-decoration:none;
                white-space:nowrap;
                flex-shrink:0;
                transition:opacity 0.15s;
            " onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'"
               title="{{ $alert['message'] }}">

                {{-- Dot indicator --}}
                <span style="width:7px;height:7px;border-radius:50%;background:{{ $alert['iconBg'] }};flex-shrink:0;display:inline-block;"></span>

                {{-- Count badge --}}
                <span style="background:{{ $alert['iconBg'] }};color:white;font-size:10px;font-weight:900;padding:1px 6px;border-radius:99px;flex-shrink:0;">
                    {{ $alert['count'] }}
                </span>

                {{-- Label --}}
                <span style="font-size:11px;font-weight:700;color:{{ $alert['text'] }};">
                    {{ $alert['short'] }}
                </span>

            </a>
            @endforeach
        </div>

    </div>
    @endif
</x-filament-widgets::widget>