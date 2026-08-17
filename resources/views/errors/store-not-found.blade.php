<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Store Not Found — JewelTag</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #F8F6F1;
            font-family: 'Inter', sans-serif;
        }
        .card {
            background: #fff;
            border: 1px solid rgba(11,61,60,0.1);
            border-radius: 16px;
            padding: 40px;
            max-width: 440px;
            text-align: center;
            box-shadow: 0 12px 32px rgba(11,61,60,0.08);
        }
        .icon {
            width: 56px; height: 56px;
            background: #FBEAE8;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
        }
        h1 { color: #0B3D3C; font-size: 20px; margin: 0 0 10px; }
        p { color: #5B6764; font-size: 14px; line-height: 1.6; margin: 0 0 24px; }
        code {
            background: #F8F6F1;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 13px;
            color: #B8463F;
        }
        a.btn {
            display: inline-block;
            background: #0B3D3C;
            color: #F8F6F1;
            padding: 10px 22px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#B8463F" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
        </div>
        <h1>Store Not Found</h1>
        <p>We couldn't find a store at <code>{{ $domain }}</code>. Please check the address, or if you're trying to create a new store, contact us to get set up.</p>
        <a href="https://jeweltag.us" class="btn">Go to JewelTag</a>
    </div>
</body>
</html>