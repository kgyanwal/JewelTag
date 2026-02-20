<x-filament-widgets::widget>
    @php
        $data = $this->data ?? [];
    @endphp

    <div class="gold-exchange-widget">
        {{-- HEADER --}}
        <div class="gold-exchange-header">
            <div class="header-left">
                <div class="header-badge">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h2>Gold Exchange</h2>
                    <p>Live Trading Terminal</p>
                </div>
            </div>
            <div class="live-status">
                <span class="live-dot"></span>
                <span>LIVE USD</span>
            </div>
        </div>

        {{-- FORM --}}
        <div class="gold-exchange-form">
            {{ $this->form }}
        </div>

        {{-- FOOTER --}}
        <div class="gold-exchange-footer">
            {{-- CASH OFFER --}}
            <div class="cash-offer-card">
                <div class="offer-label">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Customer Cash Payout</span>
                </div>
                <div class="offer-amount">
                    ${{ $data['calculated_value'] ?? '0.00' }}
                </div>
            </div>

            {{-- PROFIT --}}
            <div class="profit-card">
                <div class="profit-label">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <span>Estimated Net Profit</span>
                </div>
                <div class="profit-amount">
                    ${{ $data['calculated_profit'] ?? '0.00' }}
                </div>
                <div class="profit-meta">
                    Based on {{ $data['payout_percentage'] ?? 70 }}% Margin
                </div>
            </div>
        </div>
    </div>

    <style>
        .gold-exchange-widget {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .gold-exchange-widget:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        /* Header */
        .gold-exchange-header {
            background: #0f172a;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #1e293b;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-badge {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #fbbf24, #d97706);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .header-left h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: white;
            letter-spacing: 0.3px;
            line-height: 1.2;
        }

        .header-left p {
            margin: 2px 0 0;
            font-size: 11px;
            color: #94a3b8;
        }

        .live-status {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, 0.15);
            padding: 6px 12px;
            border-radius: 30px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .live-status .live-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .live-status span {
            font-size: 11px;
            font-weight: 600;
            color: #10b981;
            letter-spacing: 0.5px;
        }

        /* Form Section */
        .gold-exchange-form {
            background: #f8fafc;
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* Compact form styles */
        .gold-exchange-form .fi-fo-field {
            margin-bottom: 12px;
        }

        .gold-exchange-form .fi-input {
            border-radius: 10px !important;
            border-color: #e2e8f0 !important;
            padding: 10px 14px !important;
            font-size: 13px !important;
            transition: all 0.2s ease;
        }

        .gold-exchange-form .fi-input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        .gold-exchange-form .fi-fo-field-label label {
            font-size: 12px !important;
            font-weight: 600 !important;
            color: #475569 !important;
            margin-bottom: 4px !important;
        }

        /* Footer */
        .gold-exchange-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* Cash Offer Card */
        .cash-offer-card {
            background: linear-gradient(135deg, #064e3b, #065f46);
            padding: 24px;
            color: white;
            transition: all 0.3s ease;
        }

        .cash-offer-card:hover {
            background: linear-gradient(135deg, #065f46, #047857);
        }

        .offer-label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
        }

        .offer-label svg {
            color: #6ee7b7;
        }

        .offer-label span {
            font-size: 11px;
            text-transform: uppercase;
            color: #6ee7b7;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .offer-amount {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        /* Profit Card */
        .profit-card {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            padding: 24px;
            color: white;
            transition: all 0.3s ease;
        }

        .profit-card:hover {
            background: linear-gradient(135deg, #334155, #1e293b);
        }

        .profit-label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
        }

        .profit-label svg {
            color: #94a3b8;
        }

        .profit-label span {
            font-size: 11px;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .profit-amount {
            font-size: 28px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .profit-meta {
            font-size: 11px;
            color: #94a3b8;
        }

        /* Animations */
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 0.8; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .gold-exchange-footer {
                grid-template-columns: 1fr;
            }
            
            .cash-offer-card,
            .profit-card {
                padding: 20px;
            }
            
            .offer-amount {
                font-size: 28px;
            }
            
            .profit-amount {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .gold-exchange-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .header-left {
                width: 100%;
            }
            
            .live-status {
                align-self: flex-start;
            }
            
            .gold-exchange-form {
                padding: 16px;
            }
            
            .cash-offer-card,
            .profit-card {
                padding: 16px;
            }
            
            .offer-amount {
                font-size: 24px;
            }
            
            .profit-amount {
                font-size: 22px;
            }
        }
    </style>

    <script>
        // Optional: Add subtle animation on value change
        function highlightChange(element) {
            if (!element) return;
            element.style.transition = 'background-color 0.3s ease';
            element.style.backgroundColor = 'rgba(52, 211, 153, 0.1)';
            setTimeout(() => {
                element.style.backgroundColor = '';
            }, 300);
        }

        // Watch for form updates (if using Livewire)
        document.addEventListener('livewire:load', function() {
            Livewire.hook('message.processed', (message, component) => {
                const cashOffer = document.querySelector('.offer-amount');
                const profitAmount = document.querySelector('.profit-amount');
                
                if (cashOffer) highlightChange(cashOffer);
                if (profitAmount) highlightChange(profitAmount);
            });
        });
    </script>
</x-filament-widgets::widget>