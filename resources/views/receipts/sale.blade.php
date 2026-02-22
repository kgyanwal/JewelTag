<!DOCTYPE html>
<html lang="en">

<head>
    @php
    // Initialize variables
    $totalSavings = 0;
    $hasRepair = false;
    $hasCustom = false;

    // Check receipt type
    foreach($sale->items as $item) {
        if($item->discount_percent > 0) {
            $originalPrice = $item->sold_price / (1 - ($item->discount_percent / 100));
            $totalSavings += ($originalPrice - $item->sold_price) * $item->qty;
        }
        
        if($item->repair_id || $item->repair) $hasRepair = true;
        if($item->custom_order_id || $item->customOrder) $hasCustom = true;
    }

    // Determine receipt type with colors
    if ($hasRepair) {
        $receiptType = 'repair';
        $receiptTitle = 'REPAIR RECEIPT';
        $receiptTypeLabel = 'Service/Repair';
        $receiptColor = '#249E94'; // REPAIR COLOR
        $receiptDarkColor = '#1a7a72';
        $receiptAccent = '#4fc1b7';
        $receiptBgLight = '#f0f9f8';
    } elseif ($hasCustom) {
        $receiptType = 'custom';
        $receiptTitle = 'CUSTOM ORDER RECEIPT';
        $receiptTypeLabel = 'Custom Design';
        $receiptColor = '#BB8ED0'; // CUSTOM ORDER COLOR
        $receiptDarkColor = '#9a6fb3';
        $receiptAccent = '#d4b3e6';
        $receiptBgLight = '#f9f5fc';
    } else {
        $receiptType = 'normal';
        $receiptTitle = 'SALES RECEIPT';
        $receiptTypeLabel = 'Product Sale';
        $receiptColor = '#1a6b8c'; // Blue remains same
        $receiptDarkColor = '#12506b';
        $receiptAccent = '#d4af37';
        $receiptBgLight = '#eff6ff';
    }
    @endphp
    
    <meta charset="utf-8">
    <title>{{ $receiptTitle }}: {{ $sale->invoice_number }} | Diamond Square</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap');

        /* Base Resets for Email */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-size: 11px;
        }

        body {
            font-family: 'Inter', Helvetica, Arial, sans-serif;
            color: #2c3e50;
            line-height: 1.4;
            background: #f0f9ff;
            padding: 20px;
        }

        /* Print Button */
        .print-button-container {
            max-width: 850px;
            margin: 0 auto 15px;
            display: flex;
            justify-content: flex-end;
        }

        .print-button {
            background: {{ $receiptColor }};
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none !important;
                padding: 20px !important;
                max-width: 100% !important;
                border-top: none !important;
            }
            .no-print {
                display: none !important;
            }
            .invoice-header-card, .totals-card, .items-table thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page {
                margin: 0.3in;
                size: auto;
            }
            a[href]:after {
                content: none !important;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    @if(!isset($is_pdf) && !isset($is_email))
    <div class="print-button-container no-print">
        <button class="print-button" onclick="printReceipt()">
            <i class="fas fa-print"></i> Print Receipt
        </button>
    </div>
    @endif

    <div class="invoice-container" id="receipt-content" style="max-width: 850px; margin: 0 auto; padding: 30px; background: #ffffff; border-radius: 10px; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1); border-top: 6px solid {{ $receiptColor }};">
        
        @if($receiptType !== 'normal')
        <div style="margin: 10px 0 15px; padding: 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase; text-align: center; letter-spacing: 1px; background: {{ $receiptBgLight }}; color: {{ $receiptColor }}; border: 1px solid {{ $receiptColor }}; font-size: 11px;">
            <i class="fas {{ $receiptType == 'repair' ? 'fa-wrench' : 'fa-palette' }}"></i>
            {{ $receiptTitle }}
        </div>
        @endif
        
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px; border-bottom: 2px solid #e0e7ee; padding-bottom: 20px;">
            <tr>
                <td width="60%" valign="top">
                    <h1 style="font-family: 'Playfair Display', serif; font-size: 24px; color: {{ $receiptColor }}; margin-bottom: 5px;">
                        {{ optional($sale->store)->name ?? 'Diamond Square' }}
                    </h1>
                    <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: {{ $receiptAccent }}; letter-spacing: 1px; margin-bottom: 10px;">
                        Premium Jewelry & Luxury Timepieces
                    </div>
                    <div style="font-size: 10px; background: #f8fafc; padding: 10px; border-radius: 6px; border-left: 3px solid {{ $receiptAccent }}; color: #546e7a; line-height: 1.5;">
                        <div style="margin-bottom: 3px;">
                            <i class="fas fa-map-marker-alt"></i> {{ optional($sale->store)->location ?? 'Your Location(insert from store)' }}
                        </div>
                        <div style="margin-bottom: 3px;">
                            <i class="fas fa-phone"></i> {{ optional($sale->store)->phone ?? '505-810-7222' }} &nbsp; | &nbsp; 
                            <i class="fas fa-envelope"></i> {{ optional($sale->store)->email ?? 'info@example.com' }}
                        </div>
                        <div>
                            <i class="fas fa-globe"></i> {{ str_replace(['http://', 'https://'], '', optional($sale->store)->domain_url ?? 'thedsq.jeweltag.us') }}
                        </div>
                    </div>
                </td>
                <td width="40%" valign="top" align="right">
                    <div class="invoice-header-card" style="background-color: {{ $receiptColor }}; color: white; padding: 15px 20px; border-radius: 8px; text-align: right;">
                        <span style="display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">{{ $receiptTitle }}</span>
                        <span style="display: block; font-size: 20px; font-weight: 700; margin: 5px 0;">{{ $receiptType == 'normal' ? $sale->invoice_number : strtoupper($receiptType).'-'.$sale->invoice_number }}</span>
                        <div style="font-size: 10px; line-height: 1.4; margin-top: 8px;">
                            <div><i class="fas fa-calendar-day"></i> <b>Date:</b> {{ $sale->created_at->format('m/d/Y') }}</div>
                            <div><i class="fas fa-clock"></i> <b>Time:</b> {{ $sale->created_at->format('h:i A') }}</div>
                            <div><i class="fas fa-user-tie"></i> <b>Associate:</b> {{ is_array($sale->sales_person_list) ? implode(', ', $sale->sales_person_list) : $sale->sales_person_list }}</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
            <tr>
                <td width="48%" valign="top" style="background: #f9fbfc; border-radius: 6px; padding: 15px; border: 1px solid #e0e7ee; border-left: 3px solid {{ $receiptColor }};">
                    <div style="font-size: 9px; color: {{ $receiptColor }}; text-transform: uppercase; font-weight: 700; margin-bottom: 8px; letter-spacing: 0.5px;">
                        <i class="fas fa-user-circle"></i> Customer Information
                    </div>
                    <div style="font-size: 15px; font-weight: 700; color: {{ $receiptColor }}; margin-bottom: 5px;">
                        {{ $sale->customer->name ?? 'Valued Customer' }}
                    </div>
                    <div style="color: #546e7a; line-height: 1.5; font-size: 10px;">
                        <div><i class="fas fa-phone"></i> {{ $sale->customer->phone ?? 'N/A' }}</div>
                        <div><i class="fas fa-envelope"></i> {{ $sale->customer->email ?? 'N/A' }}</div>
                    </div>
                </td>
                
                <td width="4%"></td>

                <td width="48%" valign="top" style="background: #f9fbfc; border-radius: 6px; padding: 15px; border: 1px solid #e0e7ee; border-left: 3px solid {{ $receiptColor }};">
                    <div style="font-size: 9px; color: {{ $receiptColor }}; text-transform: uppercase; font-weight: 700; margin-bottom: 8px; letter-spacing: 0.5px;">
                        <i class="fas fa-credit-card"></i> Payment Summary
                    </div>
                    
                    <table width="100%" cellpadding="0" cellspacing="0" style="line-height: 1.6; font-size: 10px;">
                        <tr>
                            <td valign="top" style="color: #2c3e50;">Payment Method:</td>
                            <td valign="top" align="right">
                                @if($sale->is_split_payment)
                                    @if($sale->payment_amount_1 > 0)
                                    <div style="margin-bottom: 2px;">
                                        <span style="font-size: 9px; color: #666;">{{ strtoupper($sale->payment_method_1) }}:</span> 
                                        <strong style="color: {{ $receiptColor }};">${{ number_format($sale->payment_amount_1, 2) }}</strong>
                                    </div>
                                    @endif
                                    @if($sale->payment_amount_2 > 0)
                                    <div style="margin-bottom: 2px;">
                                        <span style="font-size: 9px; color: #666;">{{ strtoupper($sale->payment_method_2) }}:</span> 
                                        <strong style="color: {{ $receiptColor }};">${{ number_format($sale->payment_amount_2, 2) }}</strong>
                                    </div>
                                    @endif
                                    @if($sale->payment_amount_3 > 0)
                                    <div>
                                        <span style="font-size: 9px; color: #666;">{{ strtoupper($sale->payment_method_3) }}:</span> 
                                        <strong style="color: {{ $receiptColor }};">${{ number_format($sale->payment_amount_3, 2) }}</strong>
                                    </div>
                                    @endif
                                @else
                                    <strong style="color: {{ $receiptColor }};">{{ strtoupper(str_replace('_', ' ', $sale->payment_method)) }}</strong>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="border-top: 1px dashed #eee; height: 5px;"></td>
                        </tr>
                        <tr>
                            <td valign="top" style="padding-top: 5px; color: #2c3e50;">Status:</td>
                            <td valign="top" align="right" style="padding-top: 5px;">
                                @if($sale->payment_method === 'laybuy' && ($sale->laybuy->balance_due ?? 1) > 0)
                                    <strong style="color: #f59e0b;">LAYBY ACTIVE</strong>
                                @else
                                    <strong style="color: #10b981;">PAID IN FULL</strong>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div style="font-size: 14px; font-weight: 700; color: {{ $receiptColor }}; margin-bottom: 10px;">
            <i class="fas {{ $receiptType == 'repair' ? 'fa-wrench' : ($receiptType == 'custom' ? 'fa-palette' : 'fa-gem') }}"></i> 
            {{ $receiptType == 'repair' ? 'Repair Services' : ($receiptType == 'custom' ? 'Custom Order Items' : 'Purchased Items') }}
        </div>

        <table class="items-table" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px; border: 1px solid #e0e7ee; border-radius: 6px; border-collapse: collapse; font-size: 10px;">
            <thead>
                <tr>
                    <th style="background-color: {{ $receiptColor }}; color: white; padding: 10px 8px; text-align: left; font-size: 9px; text-transform: uppercase; border: 1px solid #e0e7ee;">Reference #</th>
                    <th style="background-color: {{ $receiptColor }}; color: white; padding: 10px 8px; text-align: left; font-size: 9px; text-transform: uppercase; border: 1px solid #e0e7ee;">Item Description</th>
                    <th style="background-color: {{ $receiptColor }}; color: white; padding: 10px 8px; text-align: center; width: 10%; font-size: 9px; text-transform: uppercase; border: 1px solid #e0e7ee;">Qty</th>
                    <th style="background-color: {{ $receiptColor }}; color: white; padding: 10px 8px; text-align: center; width: 10%; font-size: 9px; text-transform: uppercase; border: 1px solid #e0e7ee;">Disc %</th>
                    <th style="background-color: {{ $receiptColor }}; color: white; padding: 10px 8px; text-align: right; width: 20%; font-size: 9px; text-transform: uppercase; border: 1px solid #e0e7ee;">Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td style="padding: 10px 8px; border-bottom: 1px solid #eee; background: #fff;">
                        <span style="background: #f0f7fa; color: {{ $receiptColor }}; padding: 3px 6px; border-radius: 3px; font-family: monospace; font-size: 9px; border: 1px dashed #c2e0ee; font-weight: 700;">
                            @if ($item->productItem) {{ $item->productItem->barcode }}
                            @elseif ($item->repair) REPAIR #{{ $item->repair->repair_no }}
                            @elseif ($item->customOrder) CUSTOM #{{ $item->customOrder->order_no }}
                            @else N/A @endif
                        </span>
                    </td>
                    <td style="padding: 10px 8px; border-bottom: 1px solid #eee; background: #fff;">
                        @if($item->repair_id || $item->repair)
                            <div style="font-size: 8px; text-transform: uppercase; padding: 2px 4px; border-radius: 2px; margin-bottom: 3px; display: inline-block; font-weight: 600; color: white; background-color: {{ $receiptColor }};">Service/Repair</div>
                        @elseif($item->custom_order_id || $item->customOrder)
                            <div style="font-size: 8px; text-transform: uppercase; padding: 2px 4px; border-radius: 2px; margin-bottom: 3px; display: inline-block; font-weight: 600; color: white; background-color: {{ $receiptColor }};">Custom Design</div>
                        @endif
                        <div style="font-weight: 700; color: #2c3e50; font-size: 12px;">{{ $item->custom_description }}</div>
                    </td>
                    <td style="text-align: center; font-weight: 700; padding: 10px 8px; border-bottom: 1px solid #eee; background: #fff;">{{ $item->qty }}</td>
                    <td style="text-align: center; padding: 10px 8px; border-bottom: 1px solid #eee; background: #fff;">
                        @if($item->discount_percent > 0)
                        <span style="color: #10b981; font-weight: 700;">{{ number_format($item->discount_percent, 0) }}%</span>
                        @else
                        <span style="color: #546e7a;">-</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: 700; color: {{ $receiptColor }}; padding: 10px 8px; border-bottom: 1px solid #eee; background: #fff;">
                        ${{ number_format($item->sold_price * $item->qty, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="55%" valign="top" style="padding-right: 20px;">
                    <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e0e7ee; margin-bottom: 15px;">
                        <div style="color: {{ $receiptColor }}; font-size: 9px; text-transform: uppercase; font-weight: 700; margin-bottom: 5px; letter-spacing: 0.5px;">
                            <i class="fas fa-file-contract"></i> TERMS & CONDITIONS
                        </div>
                        <p style="font-size: 8px; color: #546e7a; line-height: 1.4; margin: 0;">
                            @if($receiptType == 'repair')
                            All repair services guaranteed for 90 days. Returns not accepted for completed repairs. Additional issues must be reported within warranty period. By signing, you acknowledge receipt of items and agree to terms.
                            @elseif($receiptType == 'custom')
                            Custom orders are final sale - no returns/exchanges. All designs created to customer specifications. By signing, you approve the final design and acknowledge the final sale nature.
                            @else
                            All sales final. Returns accepted within 14 days for exchange/store credit only, item unworn with original receipt. By signing, you agree to store policy.
                            @endif
                        </p>
                    </div>

                    @if(!isset($is_pdf) && !isset($is_email))
                    <div class="no-print" style="background: #f8fafc; border: 1px solid #e0e7ee; border-radius: 6px; padding: 12px; margin-bottom: 15px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="80" valign="middle">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://thedsq.jeweltag.us" alt="QR Code" width="70" height="70" style="border: 2px solid white; border-radius: 4px;">
                                </td>
                                <td valign="middle" style="padding-left: 10px;">
                                    <div style="font-weight: 600; color: {{ $receiptColor }}; margin-bottom: 4px; font-size: 9px;">Follow Us Online</div>
                                    <a href="{{ optional($sale->store)->domain_url ?? 'https://thedsq.jeweltag.us' }}" target="_blank" style="text-decoration: none; color: #2c3e50; font-weight: 500; font-size: 9px; display: block; margin-bottom: 3px;">
                                        <i class="fas fa-globe" style="color: {{ $receiptColor }}; width: 14px;"></i> {{ str_replace(['http://', 'https://'], '', optional($sale->store)->domain_url ?? 'thedsq.jeweltag.us') }}
                                    </a>
                                    @if(!empty(optional($sale->store)->facebook_link))
                                    <a href="{{ $sale->store->facebook_link }}" target="_blank" style="text-decoration: none; color: #2c3e50; font-weight: 500; font-size: 9px; display: block; margin-bottom: 3px;">
                                        <i class="fab fa-facebook-square" style="color: {{ $receiptColor }}; width: 14px;"></i> Facebook
                                    </a>
                                    @endif
                                     @if(!empty(optional($sale->store)->instagram_link))
                        <a href="{{ $sale->store->instagram_link }}"style="text-decoration: none; color: #2c3e50; font-weight: 500; font-size: 9px; display: block; margin-bottom: 3px;" target="_blank">
                            <i class="fab fa-instagram"></i> Instagram
                        </a>
                        @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    @endif

                    <div style="margin-top: 15px; border-top: 1px solid #2c3e50; width: 200px; padding-top: 5px; font-weight: 600; font-size: 8px; text-transform: uppercase; color: {{ $receiptColor }};">
                        @if($receiptType == 'repair')
                        Your Trusted Jewelry Repair Experts
                        @elseif($receiptType == 'custom')
                        Master Custom Jewelry Designers
                        @else
                        Your Trusted Jewelry Store In Town
                        @endif
                    </div>
                </td>

                <td width="45%" valign="top">
                    <div style="background-color: {{ $receiptColor }}; border-radius: 6px; padding: 15px; color: white;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="color: white; font-size: 11px;">
                            <tr>
                                <td style="padding: 5px 0; border-bottom: 1px dashed rgba(255, 255, 255, 0.2);">Subtotal</td>
                                <td align="right" style="padding: 5px 0; border-bottom: 1px dashed rgba(255, 255, 255, 0.2);">${{ number_format($sale->subtotal, 2) }}</td>
                            </tr>
                            @if($totalSavings > 0)
                            <tr>
                                <td style="padding: 5px 0; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); color: #4ade80; font-weight: 600;">Total Savings</td>
                                <td align="right" style="padding: 5px 0; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); color: #4ade80; font-weight: 600;">-${{ number_format($totalSavings, 2) }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td style="padding: 5px 0; border-bottom: 1px dashed rgba(255, 255, 255, 0.2);">Sales Tax</td>
                                <td align="right" style="padding: 5px 0; border-bottom: 1px dashed rgba(255, 255, 255, 0.2);">${{ number_format($sale->tax_amount, 2) }}</td>
                            </tr>
                            @if($sale->has_trade_in)
                            <tr>
                                <td style="padding: 5px 0; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); color: #ffeb3b;">Trade-In Credit</td>
                                <td align="right" style="padding: 5px 0; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); color: #ffeb3b;">-${{ number_format($sale->trade_in_value, 2) }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td style="padding-top: 10px; margin-top: 5px; font-size: 16px; font-weight: 700;">TOTAL</td>
                                <td align="right" style="padding-top: 10px; margin-top: 5px; font-size: 16px; font-weight: 700;">${{ number_format($sale->final_total, 2) }}</td>
                            </tr>
                        </table>

                        @if($sale->payment_method === 'laybuy')
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 10px; background: rgba(0,0,0,0.2); border-radius: 4px;">
                            <tr>
                                <td style="padding: 8px; font-weight: 600; font-size: 11px;">BALANCE DUE</td>
                                <td align="right" style="padding: 8px; font-size: 14px; font-weight: 700; color: {{ $receiptAccent }};">${{ number_format($sale->laybuy->balance_due ?? $sale->final_total, 2) }}</td>
                            </tr>
                        </table>
                        @endif
                    </div>

                    @if($sale->has_warranty)
                    <div style="background: #f9fbfc; border-radius: 6px; padding: 15px; border: 1px solid #e0e7ee; border-left: 3px solid {{ $receiptColor }}; margin-top: 15px;">
                        <div style="font-size: 9px; color: {{ $receiptColor }}; text-transform: uppercase; font-weight: 700; margin-bottom: 8px; letter-spacing: 0.5px;">
                            <i class="fas fa-shield-alt"></i> Warranty Coverage
                        </div>
                        <table width="100%" cellpadding="0" cellspacing="0" style="line-height: 1.5; font-size: 9px;">
                            <tr>
                                <td valign="top" style="color: #2c3e50;">Coverage Status:</td>
                                <td valign="top" align="right"><strong style="color: #10b981;"><i class="fas fa-check-circle"></i> INCLUDED</strong></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="border-top: 1px dashed #eee; height: 4px; padding-top: 4px;"></td>
                            </tr>
                            <tr>
                                <td valign="top" style="color: #2c3e50;">Duration:</td>
                                <td valign="top" align="right"><strong style="color: {{ $receiptColor }};">{{ $sale->warranty_period }}</strong></td>
                            </tr>
                        </table>
                        <div style="font-size: 8px; color: #999; margin-top: 5px;">
                            *Covers manufacturing defects. See store policy.
                        </div>
                    </div>
                    @endif
                    
                    <div style="text-align: center; margin-top: 15px; color: {{ $receiptAccent }}; font-weight: 700; letter-spacing: 0.5px; font-size: 9px;">
                        THANK YOU FOR YOUR BUSINESS!
                    </div>
                </td>
            </tr>
        </table>

    </div>

    @if(!isset($is_pdf) && !isset($is_email))
    <script>
        function printReceipt() {
            const originalTitle = document.title;
            document.title = '{{ $receiptTitle }} - {{ $sale->invoice_number }}';
            window.print();
            setTimeout(() => {
                document.title = originalTitle;
            }, 1000);
        }
        
        window.addEventListener('beforeprint', function() {
            document.body.classList.add('is-printing');
        });
        
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('is-printing');
        });
    </script>
    @endif
</body>

</html>