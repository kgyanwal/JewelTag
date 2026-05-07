<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'JewelTag.us | Ultimate Jewelry Management' }}</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Axios for API calls -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts (Playfair & Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-gold: #d97706;
            --secondary-gold: #fbbf24;
            --dark-gold: #b45309;
            --deep-sapphire: #0A2540;
            --onyx-black: #111827;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }

        .playfair { font-family: 'Playfair Display', serif; }

        /* Luxury Gradient for Header/Buttons */
        .gold-gradient {
            background: linear-gradient(135deg, var(--primary-gold), var(--dark-gold));
        }

        .gold-text {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold), var(--dark-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Animated entry for scanned items */
        .scan-row { 
            animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        /* Premium Card Styling */
        .luxury-card {
            background: white;
            border: 1px solid rgba(217, 119, 6, 0.1);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            border-radius: 1rem;
        }
    </style>
</head>

<body class="antialiased">
    <!-- Gold Top Accent -->
    <div class="w-full h-1 gold-gradient"></div>

    <nav class="bg-white border-b border-gray-100 py-4 mb-8 shadow-sm">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 gold-gradient rounded-lg flex items-center justify-center text-white shadow-md">
                    <i class="fas fa-gem"></i>
                </div>
                <span class="text-xl font-bold playfair text-deep-sapphire">JewelTag<span class="text-amber-600">.us</span></span>
            </div>
            
            <div class="flex items-center space-x-4">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Scanner Active</span>
                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-6 pb-12">
        @yield('content')
    </main>

    <footer class="text-center py-8 text-gray-400 text-xs">
        &copy; {{ date('Y') }} JewelTag Systems. All rights reserved.
    </footer>
</body>
</html>