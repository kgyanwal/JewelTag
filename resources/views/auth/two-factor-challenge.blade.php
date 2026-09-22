<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JewelTag — Security Verification</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <style>
        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, 'Inter', sans-serif;
            background: #0B3D3C;
            background-image:
                linear-gradient(rgba(201, 162, 75, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(201, 162, 75, 0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            padding: 48px 40px 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 32px 64px rgba(0, 0, 0, 0.4);
            border-top: 4px solid #C9A24B;
        }

        .logo {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo img {
            height: 44px;
        }

        .shield {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #0B3D3C, #3D6B63);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 24px rgba(11, 61, 60, 0.3);
        }

        h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0B3D3C;
            text-align: center;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            text-align: center;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .method-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #f0fdf4;
            border: 1.5px solid #bbf7d0;
            border-radius: 99px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 700;
            color: #166534;
            margin-bottom: 16px;
        }

        .phone-badge {
            background: #eff6ff;
            border: 1.5px solid #bfdbfe;
            border-radius: 10px;
            padding: 11px 16px;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 16px;
        }

        .totp-hint {
            background: #fefce8;
            border: 1px solid #fef08a;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: #854d0e;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .alert-error {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #f0fdf4;
            border-left: 4px solid #16a34a;
            color: #166534;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .otp-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #0B3D3C;
            margin-bottom: 10px;
        }

        .otp-row {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .otp-digit {
            width: 54px;
            height: 66px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            text-align: center;
            font-size: 28px;
            font-weight: 900;
            color: #0B3D3C;
            outline: none;
            transition: all 0.15s;
            background: #f8fafc;
            caret-color: transparent;
        }

        .otp-digit:focus {
            border-color: #C9A24B;
            box-shadow: 0 0 0 3px rgba(201, 162, 75, 0.25);
            background: #fffdf5;
        }

        .otp-digit.filled {
            border-color: #0B3D3C;
            background: #f0fdf4;
        }

        .backup-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            color: #0B3D3C;
            text-align: center;
            outline: none;
            font-family: monospace;
            letter-spacing: 0.1em;
            background: #f8fafc;
            margin-bottom: 20px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .backup-input:focus {
            border-color: #C9A24B;
            box-shadow: 0 0 0 3px rgba(201, 162, 75, 0.25);
        }

        /* Trust device section */
        .trust-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 20px;
        }

        .trust-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            margin-bottom: 0;
        }

        .trust-toggle input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #0B3D3C;
            cursor: pointer;
            flex-shrink: 0;
        }

        .trust-toggle label {
            font-size: 13px;
            color: #374151;
            cursor: pointer;
            line-height: 1.4;
        }

        .trust-toggle label span {
            font-weight: 700;
            color: #0B3D3C;
        }

        .trust-duration {
            display: none;
            margin-top: 12px;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
        }

        .trust-duration label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            display: block;
            margin-bottom: 8px;
        }

        .duration-options {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .duration-btn {
            flex: 1;
            min-width: 70px;
            padding: 8px 4px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: #374151;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: all 0.15s;
        }

        .duration-btn.active {
            border-color: #0B3D3C;
            background: #0B3D3C;
            color: white;
        }

        .duration-btn:hover:not(.active) {
            border-color: #C9A24B;
            color: #0B3D3C;
        }

        .btn-verify {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #0B3D3C, #3D6B63);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-bottom: 12px;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .btn-verify:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(11, 61, 60, 0.35);
        }

        .btn-secondary {
            width: 100%;
            padding: 13px;
            background: transparent;
            color: #0B3D3C;
            border: 2px solid #C9A24B;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            margin-bottom: 10px;
            transition: background 0.15s;
        }

        .btn-secondary:hover {
            background: rgba(201, 162, 75, 0.08);
        }

        .btn-backup {
            width: 100%;
            padding: 11px;
            background: transparent;
            color: #64748b;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13px;
            cursor: pointer;
            margin-bottom: 12px;
            transition: all 0.15s;
        }

        .btn-backup:hover {
            border-color: #94a3b8;
            color: #374151;
            background: #f8fafc;
        }

        .timer-wrap {
            text-align: center;
            margin-top: 16px;
            font-size: 12px;
            color: #94a3b8;
        }

        .timer-wrap #countdown {
            font-weight: 700;
            color: #0B3D3C;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 16px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .divider span {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 600;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="logo" style="background-color: #0B3D3C;">
            <img src="{{ asset('jeweltaglogo.png') }}" alt="JewelTag">
        </div>

        <div class="shield">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        </div>

        <h1>Verify Your Identity</h1>

        {{-- Normal verification mode --}}
        <div id="normal-mode">
            @if($method === 'totp')
            <p class="subtitle">Open your authenticator app and enter the 6-digit code.</p>
            <div class="method-badge">🔐 Google Authenticator / Authy</div>
            <div class="totp-hint">
                Open <strong>Google Authenticator</strong> or <strong>Authy</strong> and enter the code for <strong>JewelTag</strong>.
            </div>
            @else
            <p class="subtitle">A 6-digit code was sent to your phone.</p>
            @if($maskedPhone)
            <div class="phone-badge">📱 Code sent to {{ $maskedPhone }}</div>
            @endif
            @endif

            @if($errors->any())
            <div class="alert-error">⚠️ {{ $errors->first() }}</div>
            @endif

            @if(session('status'))
            <div class="alert-success">✅ {{ session('status') }}</div>
            @endif

            <form action="{{ route('two-factor.verify') }}" method="POST" id="otp-form">
                @csrf
                <input type="hidden" name="code" id="otp-hidden">
                <input type="hidden" name="is_backup_code" value="0">
                <input type="hidden" name="trust_device" id="trust-device-hidden" value="0">
                <input type="hidden" name="trust_hours" id="trust-hours-hidden" value="24">

                <div class="otp-label">
                    {{ $method === 'totp' ? 'Authenticator App Code' : '6-digit SMS Code' }}
                </div>
                <div class="otp-row">
                    @for($i = 0; $i < 6; $i++)
                        <input type="text" inputmode="numeric" maxlength="1"
                        pattern="[0-9]" class="otp-digit"
                        autocomplete="off" id="digit-{{ $i }}">
                        @endfor
                </div>

                {{-- Trust device section with duration picker --}}
                <div class="trust-section">
                    <div class="trust-toggle">
                        <input type="checkbox" id="trust_device_check" onchange="toggleTrust(this)">
                        <label for="trust_device_check" onclick="event.preventDefault(); document.getElementById('trust_device_check').click();">
                            <span>Remember this device</span><br>
                            Skip 2FA on this computer for a set period
                        </label>
                    </div>

                    <div class="trust-duration" id="trust-duration">
                        <label>Remember for how long?</label>
                        <div class="duration-options">
                            <button type="button" class="duration-btn active" onclick="setDuration(24, this)">24 hrs</button>
                            <button type="button" class="duration-btn" onclick="setDuration(72, this)">3 days</button>
                            <button type="button" class="duration-btn" onclick="setDuration(168, this)">1 week</button>
                            <button type="button" class="duration-btn" onclick="setDuration(720, this)">30 days</button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-verify">🔐 Verify &amp; Continue</button>
            </form>

            @if($method === 'sms')
            <form action="{{ route('two-factor.resend') }}" method="POST">
                @csrf
                <button type="submit" class="btn-secondary">📨 Resend SMS Code</button>
            </form>
            <div class="timer-wrap">Code expires in <span id="countdown">10:00</span></div>
            @else
            <div class="timer-wrap">Codes refresh every <span id="countdown">30</span>s in your app</div>
            @endif

            {{-- Backup code option --}}
            @if($backupCodesEnabled && $backupCodesRemaining > 0)
            <div class="divider">
                <span>can't access your {{ $method === 'totp' ? 'auth app' : 'phone' }}?</span>
            </div>
            <button class="btn-backup" onclick="switchToBackup()">
                🔑 Use a store backup code ({{ $backupCodesRemaining }} attempt{{ $backupCodesRemaining !== 1 ? 's' : '' }} left today)
            </button>
            @endif
        </div>

        {{-- Backup code entry mode --}}
        <div id="backup-mode" style="display:none;">
            <p class="subtitle">Enter one of your store backup codes.<br>Each code works multiple times until expiry.</p>

            @if($errors->any())
            <div class="alert-error">⚠️ {{ $errors->first() }}</div>
            @endif

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
                <button type="submit" class="btn-verify">🔑 Use Backup Code</button>
            </form>

            <button class="btn-secondary" onclick="switchToNormal()">
                ← Back to {{ $method === 'totp' ? 'Auth App' : 'SMS' }} Code
            </button>
        </div>
        <div style="text-align:center;margin-top:16px;padding-top:16px;border-top:1px solid #f1f5f9;">
            <a href="{{ route('two-factor.reset') }}"
                onclick="return confirm('Switch your 2FA method? You will need to re-verify with the new method.')"
                style="font-size:12px;color:#94a3b8;text-decoration:none;">
                🔄 Switch to {{ $method === 'totp' ? '💬 SMS Code' : '📱 Auth App' }} instead
            </a>
        </div>

    </div>

    <script>
        const digits = Array.from(document.querySelectorAll('.otp-digit'));
        const hidden = document.getElementById('otp-hidden');
        const form = document.getElementById('otp-form');
        const method = '{{ $method }}';

        function syncHidden() {
            hidden.value = digits.map(d => d.value).join('');
            digits.forEach(d => d.classList.toggle('filled', d.value !== ''));
        }

        digits.forEach((input, i) => {
            input.addEventListener('input', e => {
                e.target.value = e.target.value.replace(/\D/g, '').slice(-1);
                syncHidden();
                if (e.target.value && i < digits.length - 1) digits[i + 1].focus();
                if (hidden.value.length === 6) setTimeout(() => form.submit(), 120);
            });
            input.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !input.value && i > 0) {
                    digits[i - 1].focus();
                    digits[i - 1].value = '';
                    syncHidden();
                }
            });
            input.addEventListener('paste', e => {
                e.preventDefault();
                const p = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                p.split('').forEach((c, j) => {
                    if (digits[j]) digits[j].value = c;
                });
                syncHidden();
                if (p.length === 6) setTimeout(() => form.submit(), 120);
                else if (digits[p.length]) digits[p.length].focus();
            });
        });

        if (digits[0]) digits[0].focus();

        // Trust device toggle
        function toggleTrust(checkbox) {
            const checked = checkbox ? checkbox.checked : document.getElementById('trust_device_check').checked;
            document.getElementById('trust-device-hidden').value = checked ? '1' : '0';
            document.getElementById('trust-duration').style.display = checked ? 'block' : 'none';
        }
        // Duration selection
        function setDuration(hours, btn) {
            document.querySelectorAll('.duration-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('trust-hours-hidden').value = hours;
        }

        function switchToBackup() {
            document.getElementById('normal-mode').style.display = 'none';
            document.getElementById('backup-mode').style.display = 'block';
            document.querySelector('.backup-input')?.focus();
        }

        function switchToNormal() {
            document.getElementById('backup-mode').style.display = 'none';
            document.getElementById('normal-mode').style.display = 'block';
            if (digits[0]) digits[0].focus();
        }

        // Timer
        const el = document.getElementById('countdown');
        if (el) {
            if (method === 'sms') {
                let secs = 600;
                const t = setInterval(() => {
                    secs--;
                    const m = Math.floor(secs / 60),
                        s = secs % 60;
                    el.textContent = `${m}:${s.toString().padStart(2, '0')}`;
                    if (secs <= 60) el.style.color = '#dc2626';
                    if (secs <= 0) {
                        clearInterval(t);
                        el.textContent = 'EXPIRED';
                    }
                }, 1000);
            } else {
                function updateTotp() {
                    const s = 30 - (Math.floor(Date.now() / 1000) % 30);
                    el.textContent = s;
                    el.style.color = s <= 5 ? '#dc2626' : '#0B3D3C';
                }
                updateTotp();
                setInterval(updateTotp, 1000);
            }
        }
    </script>
</body>

</html>