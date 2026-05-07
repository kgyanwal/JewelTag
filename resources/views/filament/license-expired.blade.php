<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Expired | JewelTag</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center border border-slate-200">
            <div class="w-16 h-16 bg-rose-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-5V9m0 0V7m0 2h2M12 7H10M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Access Restricted</h1>
            <p class="text-slate-500 mb-6">{{ $reason }}</p>
            
            <div class="bg-slate-50 rounded-xl p-4 mb-6 text-left border border-slate-100">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-2">Store Profile</p>
                <p class="text-sm text-slate-700">Tenant ID: <span class="font-bold">{{ $tenant }}</span></p>
                @isset($plan)
                <p class="text-sm text-slate-700">Current Plan: <span class="font-bold capitalize">{{ $plan }}</span></p>
                @endisset
            </div>
            
            <a href="mailto:info@jeweltag.us" class="block w-full py-3 px-6 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-all">
                Contact Support to Renew
            </a>
        </div>
    </div>
</body>
</html>