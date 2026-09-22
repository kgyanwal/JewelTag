<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JewelTag — Backup Codes</title>
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
        h1 { font-size: 22px; font-weight: 800; color: #0B3D3C; text-align: center; margin-bottom: 8px; }
        .subtitle { color: #64748b; text-align: center; font-size: 14px; margin-bottom: 24px; line-height: 1.6; }
        .warning-box {
            background: #fef3c7; border: 1.5px solid #fbbf24;
            border-left: 4px solid #f59e0b;
            border-radius: 10px; padding: 14px 16px;
            font-size: 13px; color: #92400e;
            margin-bottom: 24px; line-height: 1.6;
        }
        .warning-box strong { display: block; margin-bottom: 4px; font-size: 14px; }
        .codes-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 10px; margin-bottom: 24px;
        }
        .code-item {
            background: #f1f5f9; border: 1px solid #cbd5e1;
            border-radius: 8px; padding: 10px 14px;
            font-family: monospace; font-size: 15px;
            font-weight: 700; color: #0B3D3C;
            text-align: center; letter-spacing: 0.05em;
        }
        .no-codes {
            background: #f8fafc; border: 1px dashed #cbd5e1;
            border-radius: 10px; padding: 20px;
            text-align: center; color: #94a3b8;
            font-size: 14px; margin-bottom: 24px;
        }
        .disabled-box {
            background: #fef2f2; border: 1.5px solid #fecaca;
            border-left: 4px solid #dc2626;
            border-radius: 10px; padding: 14px 16px;
            font-size: 13px; color: #dc2626;
            margin-bottom: 24px;
        }
        .btn-copy {
            width: 100%; padding: 13px;
            background: transparent; color: #0B3D3C;
            border: 2px solid #C9A24B; border-radius: 12px;
            font-size: 14px; font-weight: 700;
            cursor: pointer; margin-bottom: 12px;
            transition: background 0.15s;
        }
        .btn-copy:hover { background: rgba(201,162,75,0.08); }
        .btn-continue {
            width: 100%; padding: 15px;
            background: linear-gradient(135deg, #0B3D3C, #3D6B63);
            color: white; border: none; border-radius: 12px;
            font-size: 16px; font-weight: 700; cursor: pointer;
            margin-bottom: 12px; transition: transform 0.15s, box-shadow 0.15s;
            text-decoration: none; display: block; text-align: center;
        }
        .btn-continue:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(11,61,60,0.35); }
        .btn-regenerate {
            width: 100%; padding: 12px;
            background: transparent; color: #dc2626;
            border: 2px solid #fecaca; border-radius: 12px;
            font-size: 13px; font-weight: 700;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-regenerate:hover { background: #fef2f2; }
        .remaining-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: #f0fdf4; border: 1.5px solid #bbf7d0;
            border-radius: 99px; padding: 5px 12px;
            font-size: 12px; font-weight: 700; color: #166534;
            margin: 0 auto 16px; display: flex; justify-content: center;
        }
        .alert-success {
            background: #f0fdf4; border-left: 4px solid #16a34a;
            color: #166534; padding: 12px 16px; border-radius: 10px;
            font-size: 13px; margin-bottom: 16px;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <img src="{{ asset('jeweltaglogo.png') }}" alt="JewelTag">
    </div>

    <h1>🔑 Backup Codes</h1>
    <p class="subtitle">
        Use these codes if you lose access to your<br>
        authenticator app or phone.
    </p>

    @if(session('status'))
        <div class="alert-success">✅ {{ session('status') }}</div>
    @endif

    @if(!$backupCodesEnabled)
        <div class="disabled-box">
            🚫 Backup codes have been disabled by your administrator for this store.
        </div>
    @elseif(!empty($newCodes))
        <div class="warning-box">
            <strong>⚠️ Save these codes now!</strong>
            Each code can only be used once. These will not be shown again.
            Store them somewhere safe — password manager, printed paper, etc.
        </div>

        <div class="codes-grid" id="codes-grid">
            @foreach($newCodes as $code)
                <div class="code-item">{{ $code }}</div>
            @endforeach
        </div>

        <button class="btn-copy" onclick="copyCodes()">📋 Copy All Codes</button>

    @else
        <div class="remaining-badge">
            🔑 {{ $remaining }} backup code(s) remaining
        </div>

        @if($remaining === 0)
            <div class="no-codes">
                All backup codes have been used.<br>
                Generate new ones below.
            </div>
        @else
            <div class="no-codes">
                Your backup codes are saved securely.<br>
                Generate new ones if you've lost them.
            </div>
        @endif
    @endif

    <a href="{{ filament()->getPanel('admin')->getUrl() }}" class="btn-continue">
        ✅ Continue to Dashboard
    </a>

    @if($backupCodesEnabled)
        <form action="{{ route('two-factor.backup-codes.regenerate') }}" method="POST">
            @csrf
            <button type="submit" class="btn-regenerate"
                    onclick="return confirm('Generate new backup codes? Your old codes will stop working.')">
                🔄 Generate New Backup Codes
            </button>
        </form>
    @endif
</div>

<script>
    function copyCodes() {
        const items = document.querySelectorAll('.code-item');
        const text  = Array.from(items).map(el => el.textContent.trim()).join('\n');

        navigator.clipboard.writeText(text).then(() => {
            const btn = document.querySelector('.btn-copy');
            btn.textContent = '✅ Copied!';
            setTimeout(() => btn.textContent = '📋 Copy All Codes', 2000);
        });
    }
</script>
</body>
</html>