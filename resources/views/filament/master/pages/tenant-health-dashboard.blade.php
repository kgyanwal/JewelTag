<x-filament-panels::page>
    <div style="display:flex;flex-direction:column;gap:20px;">

        <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:14px;">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                <div style="font-size:10px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Total Tenants</div>
                <div style="font-size:26px;font-weight:900;color:#1e293b;margin-top:4px;">{{ $summary['total_tenants'] }}</div>
            </div>
            <div style="background:#fff;border:1px solid #bbf7d0;border-radius:12px;padding:16px;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                <div style="font-size:10px;font-weight:800;color:#16a34a;text-transform:uppercase;letter-spacing:.05em;">Active</div>
                <div style="font-size:26px;font-weight:900;color:#15803d;margin-top:4px;">{{ $summary['active_tenants'] }}</div>
            </div>
            <div style="background:#fff;border:1px solid #fde68a;border-radius:12px;padding:16px;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                <div style="font-size:10px;font-weight:800;color:#b45309;text-transform:uppercase;letter-spacing:.05em;">Trial</div>
                <div style="font-size:26px;font-weight:900;color:#92400e;margin-top:4px;">{{ $summary['trial_tenants'] }}</div>
            </div>
            <div style="background:#fff;border:1px solid #fca5a5;border-radius:12px;padding:16px;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                <div style="font-size:10px;font-weight:800;color:#dc2626;text-transform:uppercase;letter-spacing:.05em;">Suspended</div>
                <div style="font-size:26px;font-weight:900;color:#991b1b;margin-top:4px;">{{ $summary['suspended'] }}</div>
            </div>
            <div style="background:#fff;border:1px solid #fdba74;border-radius:12px;padding:16px;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                <div style="font-size:10px;font-weight:800;color:#c2410c;text-transform:uppercase;letter-spacing:.05em;">At Risk (14d+ idle)</div>
                <div style="font-size:26px;font-weight:900;color:#9a3412;margin-top:4px;">{{ $summary['at_risk'] }}</div>
            </div>
            <div style="background:linear-gradient(135deg,#0B3D3C,#07292A);border-radius:12px;padding:16px;box-shadow:0 4px 10px rgba(11,61,60,0.25);">
                <div style="font-size:10px;font-weight:800;color:#E4CD8E;text-transform:uppercase;letter-spacing:.05em;">Total MRR</div>
                <div style="font-size:26px;font-weight:900;color:#fff;margin-top:4px;">${{ number_format($summary['total_mrr'], 0) }}</div>
            </div>
        </div>

        <div style="display:flex;gap:12px;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 18px;">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by tenant ID or domain..."
                style="flex:1;border:1px solid #cbd5e1;border-radius:8px;padding:8px 12px;font-size:13px;"
            >
            <select wire:model.live="filterStatus" style="border:1px solid #cbd5e1;border-radius:8px;padding:8px 12px;font-size:13px;">
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="trial">Trial</option>
                <option value="suspended">Suspended</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <button
                wire:click="refreshStats"
                style="background:#0B3D3C;color:#F8F6F1;border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;"
            >
                🔄 Refresh Stats
            </button>
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#0B3D3C;">
                        <th style="padding:10px 14px;text-align:left;color:#E4CD8E;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;cursor:pointer;" wire:click="sort('id')">
                            Tenant {{ $sortBy === 'id' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
                        </th>
                        <th style="padding:10px 14px;text-align:left;color:#E4CD8E;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Plan</th>
                        <th style="padding:10px 14px;text-align:left;color:#E4CD8E;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Status</th>
                        <th style="padding:10px 14px;text-align:left;color:#E4CD8E;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;cursor:pointer;" wire:click="sort('user_count')">
                            Users {{ $sortBy === 'user_count' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
                        </th>
                        <th style="padding:10px 14px;text-align:left;color:#E4CD8E;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;cursor:pointer;" wire:click="sort('laybuy_count')">
                            Laybuys {{ $sortBy === 'laybuy_count' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
                        </th>
                        <th style="padding:10px 14px;text-align:left;color:#E4CD8E;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;cursor:pointer;" wire:click="sort('db_size_mb')">
                            DB Size {{ $sortBy === 'db_size_mb' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
                        </th>
                        <th style="padding:10px 14px;text-align:left;color:#E4CD8E;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;cursor:pointer;" wire:click="sort('days_since_login')">
                            Last Login {{ $sortBy === 'days_since_login' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
                        </th>
                        <th style="padding:10px 14px;text-align:right;color:#E4CD8E;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;cursor:pointer;" wire:click="sort('mrr')">
                            MRR {{ $sortBy === 'mrr' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
                        </th>
                        <th style="padding:10px 14px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->getFilteredStats() as $t)
                        @php
                            $userColor = $t['user_pct'] >= 100 ? '#dc2626' : ($t['user_pct'] >= 75 ? '#d97706' : '#16a34a');
                            $laybuyColor = $t['laybuy_pct'] >= 100 ? '#dc2626' : ($t['laybuy_pct'] >= 75 ? '#d97706' : '#16a34a');
                            $statusColors = [
                                'active'    => ['bg' => '#dcfce7', 'text' => '#15803d'],
                                'trial'     => ['bg' => '#fef3c7', 'text' => '#b45309'],
                                'suspended' => ['bg' => '#fee2e2', 'text' => '#dc2626'],
                                'cancelled' => ['bg' => '#f1f5f9', 'text' => '#64748b'],
                            ];
                            $sc = $statusColors[$t['plan_status']] ?? $statusColors['cancelled'];
                            $loginWarn = $t['days_since_login'] !== null && $t['days_since_login'] > 14;
                        @endphp
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:12px 14px;">
                                <div style="font-weight:800;color:#0B3D3C;">{{ $t['id'] }}</div>
                                <div style="font-size:11px;color:#94a3b8;">{{ $t['domain'] }}</div>
                            </td>
                            <td style="padding:12px 14px;">
                                <span style="background:#f1f5f9;color:#334155;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;">{{ $t['plan_name'] }}</span>
                            </td>
                            <td style="padding:12px 14px;">
                                <span style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }};padding:2px 10px;border-radius:99px;font-size:11px;font-weight:800;text-transform:uppercase;">{{ $t['plan_status'] }}</span>
                            </td>
                            <td style="padding:12px 14px;min-width:110px;">
                                <div style="font-size:11px;font-weight:700;color:{{ $userColor }};margin-bottom:3px;">
                                    {{ $t['user_count'] }} / {{ $t['max_users'] == -1 ? '∞' : $t['max_users'] }}
                                </div>
                                @if($t['max_users'] != -1)
                                    <div style="background:#e2e8f0;border-radius:99px;height:5px;width:80px;overflow:hidden;">
                                        <div style="background:{{ $userColor }};height:100%;width:{{ $t['user_pct'] }}%;"></div>
                                    </div>
                                @endif
                            </td>
                            <td style="padding:12px 14px;min-width:110px;">
                                <div style="font-size:11px;font-weight:700;color:{{ $laybuyColor }};margin-bottom:3px;">
                                    {{ $t['laybuy_count'] }} / {{ $t['max_laybuys'] == -1 ? '∞' : $t['max_laybuys'] }}
                                </div>
                                @if($t['max_laybuys'] != -1)
                                    <div style="background:#e2e8f0;border-radius:99px;height:5px;width:80px;overflow:hidden;">
                                        <div style="background:{{ $laybuyColor }};height:100%;width:{{ $t['laybuy_pct'] }}%;"></div>
                                    </div>
                                @endif
                            </td>
                            <td style="padding:12px 14px;">
                                <div style="font-weight:700;color:#334155;">{{ $t['db_size_mb'] }} MB</div>
                                @if($t['storage_mb'] > 0)
                                    <div style="font-size:10px;color:#94a3b8;">+{{ $t['storage_mb'] }} MB files</div>
                                @endif
                            </td>
                            <td style="padding:12px 14px;">
                                @if($t['last_login_at'])
                                    <div style="font-weight:700;color:{{ $loginWarn ? '#dc2626' : '#334155' }};">
                                        {{ $t['days_since_login'] === 0 ? 'Today' : $t['days_since_login'] . 'd ago' }}
                                    </div>
                                    <div style="font-size:10px;color:#94a3b8;">{{ $t['last_login_at']->format('M d, Y') }}</div>
                                @else
                                    <span style="color:#cbd5e1;font-style:italic;">Never</span>
                                @endif
                            </td>
                            <td style="padding:12px 14px;text-align:right;">
                                <span style="font-weight:900;color:#0B3D3C;">${{ number_format($t['mrr'], 0) }}</span>
                                <div style="font-size:10px;color:#94a3b8;">/mo</div>
                            </td>
                            <td style="padding:12px 14px;">
                                <a href="/master/tenants?tableFilters[plan_id][value]=&search={{ $t['id'] }}"
                                   style="background:#0B3D3C;color:#F8F6F1;padding:5px 12px;border-radius:6px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;">
                                    Manage →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding:40px;text-align:center;color:#94a3b8;font-style:italic;">
                                No tenants match your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
