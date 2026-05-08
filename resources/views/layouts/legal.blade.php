{{-- resources/views/layouts/legal.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Legal Information | JewelTag.us' }}</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts (Playfair & Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-gold: #d97706;
            --secondary-gold: #fbbf24;
            --dark-gold: #b45309;
            --deep-sapphire: #0A2540;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .playfair { font-family: 'Playfair Display', serif; }

        .gold-gradient {
            background: linear-gradient(135deg, var(--primary-gold), var(--dark-gold));
        }

        .gold-gradient-text {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold), var(--dark-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Luxury Content Card */
        .luxury-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(217, 119, 6, 0.15);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            border-radius: 24px;
            position: relative;
            overflow: hidden;
        }

        .luxury-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-gold), var(--secondary-gold));
        }

        .scroll-mt-24 {
            scroll-margin-top: 6rem;
        }
    </style>
</head>

<body class="antialiased">
    <!-- Gold Top Accent -->
    <div class="fixed top-0 left-0 w-full h-1 gold-gradient z-50"></div>

    <!-- Navigation -->
    <nav class="bg-white/80 backdrop-blur-md border-b border-gray-100 py-4 fixed w-full top-0 z-40">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <a href="/" class="flex items-center space-x-3">
                    <div class="w-10 h-10 gold-gradient rounded-lg flex items-center justify-center text-white shadow-md">
                        <i class="fas fa-gem"></i>
                    </div>
                    <span class="text-xl font-bold playfair text-deep-sapphire">JewelTag<span class="text-amber-600">.us</span></span>
                </a>
            </div>
            
            <div class="hidden md:flex space-x-6">
                <a href="/#features" class="text-xs font-bold uppercase tracking-widest text-gray-500 hover:text-amber-600 transition">Features</a>
                <a href="/#pricing" class="text-xs font-bold uppercase tracking-widest text-gray-500 hover:text-amber-600 transition">Pricing</a>
                <a href="/master/login" class="text-xs font-bold uppercase tracking-widest text-gray-500 hover:text-amber-600 transition">Staff Login</a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-6 pt-32 pb-20">
        <div class="max-w-4xl mx-auto">
            <div class="luxury-card p-8 md:p-12">
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="text-center py-12 text-gray-400 text-xs border-t border-gray-100">
        <div class="flex justify-center space-x-4 mb-4">
            <a href="{{ route('privacy') }}" class="hover:text-amber-600 transition">Privacy Policy</a>
            <a href="{{ route('docs') }}" class="hover:text-amber-600 transition">Documentation</a>
            <a href="{{ route('api') }}" class="hover:text-amber-600 transition">API Reference</a>
        </div>
        <p>&copy; {{ date('Y') }} JewelTag Systems. All rights reserved.</p>
    </footer>
</body>
</html>