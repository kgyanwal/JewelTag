<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JewelTag — Set Up Two-Factor Authentication</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, 'Inter', sans-serif;
            background: #0B3D3C;
            background-image:
                linear-gradient(rgba(201,162,75,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(201,162,75,0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }
        .card {
            background: #fff; border-radius: 20px;
            padding: 48px 40px 40px; width: 100%; max-width: 500px;
            box-shadow: 0 32px 64px rgba(0,0,0,0.4);
            border-top: 4px solid #C9A24B;
        }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo img { height: 40px; }
        h1 { font-size: 22px; font-weight: 800; color: #0B3D3C; text-align: center; margin-bottom: 6px; }
        .subtitle { color: #64748b; text-align: center; font-size: 14px; margin-bottom: 28px; line-height: 1.6; }
        .tabs { display: flex; gap: 8px; margin-bottom: 28px; }
        .tab {
            flex: 1; padding: 10px 6px; text-align: center;
            border: 2px solid #e2e8f0; border-radius: 12px;
            cursor: pointer; transition: all 0.15s;
            font-size: 13px; font-weight: 600; color: #64748b;
        }
        .tab.active { border-color: #0B3D3C; background: #0B3D3C; color: white; }
        .tab:not(.active):hover { border-color: #C9A24B; color: #0B3D3C; }
        .panel { display: none; }
        .panel.active { display: block; }
        .qr-wrap {
            text-align: center; margin-bottom: 20px;
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 12px; padding: 20px;
        }
        .qr-wrap svg { width: 180px; height: 180px; }
        .secret-box {
            background: #f1f5f9; border: 1px solid #cbd5e1;
            border-radius: 8px; padding: 10px 14px;
            font-family: monospace; font-size: 14px;
            letter-spacing: 0.08em; text-align: center;
            color: #0B3D3C; font-weight: 700;
            margin-bottom: 16px; word-break: break-all;
        }
        .steps {
            background: #f8fafc; border-radius: 10px;
            padding: 14px 16px; margin-bottom: 20px;
            font-size: 13px; color: #374151; line-height: 1.8;
        }
        .steps ol { padding-left: 18px; }
        .steps li { margin-bottom: 4px; }
        .steps strong { color: #0B3D3C; }
        .sms-info {
            background: #eff6ff; border: 1.5px solid #bfdbfe;
            border-radius: 10px; padding: 14px 16px;
            font-size: 14px; color: #1e40af; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .btn-send-sms {
            width: 100%; padding: 13px;
            background: transparent; color: #0B3D3C;
            border: 2px solid #C9A24B; border-radius: 12px;
            font-size: 14px; font-weight: 700;
            cursor: pointer; margin-bottom: 16px; transition: background 0.15s;
        }
        .btn-send-sms:hover { background: rgba(201,162,75,0.08); }
        .otp-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #0B3D3C; margin-bottom: 10px; }
        .otp-row { display: flex; gap: 8px; justify-content: center; margin-bottom: 20px; }
        .otp-digit {
            width: 52px; height: 62px; border: 2px solid #e2e8f0;
            border-radius: 12px; text-align: center;
            font-size: 26px; font-weight: 900; color: #0B3D3C;
            outline: none; transition: all 0.15s; background: #f8fafc;
            caret-color: transparent;
        }
        .otp-digit:focus { border-color: #C9A24B; box-shadow: 0 0 0 3px rgba(201,162,75,0.25); background: #fffdf5; }
        .otp-digit.filled { border-color: #0B3D3C; background: #f0fdf4; }
        .btn-confirm {
            width: 100%; padding: 15px;
            background: linear-gradient(135deg, #0B3D3C, #3D6B63);
            color: white; border: none; border-radius: 12px;
            font-size: 16px; font-weight: 700; cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-confirm:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(11,61,60,0.35); }
        .alert-error {
            background: #fef2f2; border-left: 4px solid #dc2626;
            color: #dc2626; padding: 12px 16px; border-radius: 10px;
            font-size: 13px; font-weight: 600; margin-bottom: 16px;
        }
        .alert-success {
            background: #f0fdf4; border-left: 4px solid #16a34a;
            color: #166534; padding: 12px 16px; border-radius: 10px;
            font-size: 13px; font-weight: 600; margin-bottom: 16px;
        }
        /* Backup code panel */
        .backup-info {
            background: #fef3c7; border: 1.5px solid #fbbf24;
            border-left: 4px solid #f59e0b;
            border-radius: 10px; padding: 14px 16px;
            font-size: 13px; color: #92400e;
            margin-bottom: 20px; line-height: 1.6;
        }
        .backup-info strong { color: #78350f; }
        .backup-input {
            width: 100%; padding: 14px 16px;
            border: 2px solid #e2e8f0; border-radius: 12px;
            font-size: 18px; font-weight: 700; color: #0B3D3C;
            text-align: center; outline: none;
            font-family: monospace; letter-spacing: 0.1em;
            background: #f8fafc; margin-bottom: 20px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .backup-input:focus { border-color: #C9A24B; box-shadow: 0 0 0 3px rgba(201,162,75,0.25); }
        .backup-remaining {
            display: flex; align-items: center; justify-content: center;
            gap: 6px; margin-bottom: 16px;
            background: #f0fdf4; border: 1px solid #bbf7d0;
            border-radius: 8px; padding: 8px 14px;
            font-size: 13px; font-weight: 700; color: #166534;
        }
        .no-backup {
            background: #fef2f2; border: 1px solid #fecaca;
            border-radius: 10px; padding: 16px;
            text-align: center; color: #dc2626; font-size: 13px;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo" style="background-color: #0B3D3C;">
        <img src="{{ asset('jeweltaglogo.png') }}" alt="JewelTag">
    </div>

    <h1>🔐 Set Up Two-Factor Auth</h1>
    <p class="subtitle">
        Choose your verification method or use a store backup code.
    </p>

    @if($errors->any())
        <div class="alert-error">⚠️ {{ $errors->first() }}</div>
    @endif
    @if(session('sms_status'))
        <div class="alert-success">✅ {{ session('sms_status') }}</div>
    @endif
    @if(session('sms_error'))
        <div class="alert-error">❌ {{ session('sms_error') }}</div>
    @endif
    @if(session('status'))
        <div class="alert-success">✅ {{ session('status') }}</div>
    @endif

    {{-- Method tabs --}}
    <div class="tabs">
        <div class="tab active" id="tab-totp" onclick="switchTab('totp')">
            📱 Auth App<br><small style="font-weight:400;font-size:11px;">Google Authenticator</small>
        </div>
        <div class="tab" id="tab-sms" onclick="switchTab('sms')">
            💬 SMS Code<br><small style="font-weight:400;font-size:11px;">Text message</small>
        </div>
        <div class="tab" id="tab-backup" onclick="switchTab('backup')">
            🔑 Backup Code<br><small style="font-weight:400;font-size:11px;">Store code</small>
        </div>
    </div>

    {{-- TOTP Panel --}}
    <div class="panel active" id="panel-totp">
        <div class="qr-wrap">
            {!! $qrCode !!}
        </div>

        <div class="steps">
            <ol>
                <li>Install <strong>Google Authenticator</strong> or <strong>Authy</strong> on your phone</li>
                <li>Tap the <strong>+</strong> button → Scan QR code</li>
                <li>Point your camera at the QR code above</li>
                <li>Enter the 6-digit code shown in the app below</li>
            </ol>
        </div>

        <!-- <p style="font-size:11px;color:#94a3b8;text-align:center;margin-bottom:8px;">
            Can't scan? Enter this key manually:
        </p>
        <div class="secret-box">{{ chunk_split($secret, 4, ' ') }}</div> -->

        <form action="{{ route('two-factor.setup.confirm') }}" method="POST" id="totp-form">
            @csrf
            <input type="hidden" name="method" value="totp">
            <input type="hidden" name="code" id="totp-hidden">
            <div class="otp-label">Enter code from your auth app</div>
            <div class="otp-row" id="totp-digits">
                @for($i = 0; $i < 6; $i++)
                    <input type="text" inputmode="numeric" maxlength="1"
                           pattern="[0-9]" class="otp-digit totp-digit" autocomplete="off">
                @endfor
            </div>
            <button type="submit" class="btn-confirm">✅ Confirm &amp; Enable Auth App</button>
        </form>
    </div>

    {{-- SMS Panel --}}
    <div class="panel" id="panel-sms">
        @if($maskedPhone)
            <div class="sms-info">
                📱 We'll send codes to <strong>{{ $maskedPhone }}</strong>
            </div>
        @else
            <div class="alert-error">
                ⚠️ No phone number configured for this store.<br>
                <small>Go to <strong>Admin → Store Settings</strong> and add the store phone number.</small>
            </div>
        @endif

        <form action="{{ route('two-factor.setup.send-sms') }}" method="POST">
            @csrf
            <button type="submit" class="btn-send-sms">📨 Send Verification Code</button>
        </form>

        <form action="{{ route('two-factor.setup.confirm') }}" method="POST" id="sms-form">
            @csrf
            <input type="hidden" name="method" value="sms">
            <input type="hidden" name="code" id="sms-hidden">
            <div class="otp-label">Enter SMS code</div>
            <div class="otp-row" id="sms-digits">
                @for($i = 0; $i < 6; $i++)
                    <input type="text" inputmode="numeric" maxlength="1"
                           pattern="[0-9]" class="otp-digit sms-digit" autocomplete="off">
                @endfor
            </div>
            <button type="submit" class="btn-confirm">✅ Confirm &amp; Enable SMS</button>
        </form>
    </div>

    {{-- BACKUP CODE Panel --}}
    <div class="panel" id="panel-backup">
        @php
            // Get backup codes from tenant
            $setupBackupEnabled   = false;
            $setupBackupRemaining = 0;
            $setupBackupExpired   = false;

            if (function_exists('tenant') && tenant()) {
                $tenantData = \Illuminate\Support\Facades\DB::connection('mysql')
                    ->table('tenants')->where('id', tenant()->id)->first();

                $setupBackupEnabled = (bool) ($tenantData->backup_codes_enabled ?? false);

                if ($setupBackupEnabled) {
                    if (!empty($tenantData->backup_codes_expires_at) && now()->isAfter($tenantData->backup_codes_expires_at)) {
                        $setupBackupExpired   = true;
                        $setupBackupEnabled   = false;
                        $setupBackupRemaining = 0;
                    } else {
                        $stored               = json_decode($tenantData->two_factor_backup_codes ?? '[]', true);
                        $attemptKey           = 'backup_code_attempts_' . tenant()->id . '_' . auth()->id() . '_' . now()->format('Y-m-d');
                        $usedToday            = \Illuminate\Support\Facades\Cache::get($attemptKey, 0);
                        $setupBackupRemaining = max(0, 8 - $usedToday);
                    }
                }
            }
        @endphp

        @if(!$setupBackupEnabled)
            <div class="no-backup">
                🚫 Backup codes are not available for this store.<br>
                <small style="opacity:0.7;">Contact your administrator to enable backup codes.</small>
            </div>
        @elseif($setupBackupExpired)
            <div class="no-backup">
                ⏰ Store backup codes have expired.<br>
                <small style="opacity:0.7;">Contact your administrator to generate new codes.</small>
            </div>
        @elseif($setupBackupRemaining <= 0)
            <div class="no-backup">
                🔒 No backup code attempts remaining today.<br>
                <small style="opacity:0.7;">Try again tomorrow or contact your administrator.</small>
            </div>
        @else
            <div class="backup-info">
                <strong>⚠️ Emergency access only</strong><br>
                Use a store backup code if you can't set up Auth App or SMS right now.
                This will skip setup and grant immediate access.
                Each code works multiple times — <strong>{{ $setupBackupRemaining }} attempt(s) left today</strong>.
            </div>

            <div class="backup-remaining">
                🔑 {{ $setupBackupRemaining }} attempt(s) remaining today
            </div>

            <form action="{{ route('two-factor.verify') }}" method="POST">
                @csrf
                <input type="hidden" name="is_backup_code" value="1">
                <div class="otp-label">Store Backup Code</div>
                <input type="text"
                       name="code"
                       class="backup-input"
                       placeholder="Enter backup code"
                       autocomplete="off"
                       maxlength="20">
                <button type="submit" class="btn-confirm">🔑 Use Backup Code &amp; Enter</button>
            </form>
        @endif
    </div>

    {{-- Back to login --}}
    <form action="{{ route('filament.admin.auth.logout') }}" method="POST" style="text-align:center;margin-top:20px;">
        @csrf
        <button type="submit" style="background:none;border:none;color:#94a3b8;font-size:13px;cursor:pointer;">
            ← Back to login
        </button>
    </form>
</div>

<script>
    function switchTab(method) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + method).classList.add('active');
        document.getElementById('panel-' + method).classList.add('active');

        // Focus first digit when switching to totp or sms
        if (method === 'totp') {
            setTimeout(() => document.querySelector('#totp-digits .otp-digit')?.focus(), 50);
        } else if (method === 'sms') {
            setTimeout(() => document.querySelector('#sms-digits .otp-digit')?.focus(), 50);
        } else if (method === 'backup') {
            setTimeout(() => document.querySelector('.backup-input')?.focus(), 50);
        }
    }

    function setupDigits(containerSelector, hiddenId, formId) {
        const digits = Array.from(document.querySelectorAll(containerSelector + ' .otp-digit'));
        const hidden = document.getElementById(hiddenId);
        const form   = document.getElementById(formId);

        function sync() {
            hidden.value = digits.map(d => d.value).join('');
            digits.forEach(d => d.classList.toggle('filled', d.value !== ''));
        }

        digits.forEach((input, i) => {
            input.addEventListener('input', e => {
                e.target.value = e.target.value.replace(/\D/g, '').slice(-1);
                sync();
                if (e.target.value && i < digits.length - 1) digits[i + 1].focus();
                if (hidden.value.length === 6) setTimeout(() => form.submit(), 120);
            });
            input.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !input.value && i > 0) {
                    digits[i - 1].focus(); digits[i - 1].value = ''; sync();
                }
            });
            input.addEventListener('paste', e => {
                e.preventDefault();
                const p = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                p.split('').forEach((c, j) => { if (digits[j]) digits[j].value = c; });
                sync();
                if (p.length === 6) setTimeout(() => form.submit(), 120);
            });
        });

        if (digits[0]) digits[0].focus();
    }

    setupDigits('#totp-digits', 'totp-hidden', 'totp-form');
    setupDigits('#sms-digits',  'sms-hidden',  'sms-form');

    @if(session('sms_status') || session('sms_error'))
        switchTab('sms');
    @endif
</script>
</body>
</html>