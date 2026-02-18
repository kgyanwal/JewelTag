<x-filament-widgets::widget>

    @php
        $data = $this->data ?? [];
    @endphp

    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);">

        {{-- HEADER --}}
        <div style="background:#0f172a;padding:16px 24px;color:white;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h2 style="margin:0;font-weight:800;letter-spacing:.05em;text-transform:uppercase;">
                    Gold Exchange
                </h2>
                <p style="margin:0;font-size:.75rem;color:#94a3b8;">
                    Live Trading Terminal
                </p>
            </div>

            <div style="color:#34d399;font-weight:700;font-size:.8rem;">
                ● LIVE USD
            </div>
        </div>

        {{-- FORM --}}
        <div style="background:#f8fafc;padding:24px;border-bottom:1px solid #e2e8f0;">
            {{ $this->form }}
        </div>

        {{-- FOOTER --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));">

            {{-- CASH OFFER --}}
            <div style="padding:30px;background:linear-gradient(135deg,#064e3b,#065f46);color:white;">
                <div style="font-size:.75rem;text-transform:uppercase;color:#6ee7b7;font-weight:700;">
                    Customer Cash Payout
                </div>

                <div style="font-size:3rem;font-weight:900;margin-top:10px;">
                    ${{ $data['calculated_value'] ?? '0.00' }}
                </div>
            </div>

            {{-- PROFIT --}}
            <div style="padding:30px;background:#1e293b;color:white;">
                <div style="font-size:.75rem;text-transform:uppercase;color:#94a3b8;font-weight:700;">
                    Estimated Net Profit
                </div>

                <div style="font-size:2rem;font-weight:700;margin-top:10px;">
                    ${{ $data['calculated_profit'] ?? '0.00' }}
                </div>

                <div style="margin-top:10px;font-size:.8rem;color:#94a3b8;">
                    Based on {{ $data['payout_percentage'] ?? 70 }}% Margin
                </div>
            </div>

        </div>
    </div>

</x-filament-widgets::widget>