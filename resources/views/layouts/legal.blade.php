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
    <!-- Google Fonts (Fraunces & Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700;9..144,800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            /* ── JEWELER'S WORKBENCH PALETTE ── matches the main site theme */
            --onyx:         #15120F;
            --onyx-soft:    #1F1A15;
            --case-felt:    #FAF6EE;
            --brass:        #B8863B;
            --brass-bright: #E0AE5C;
            --brass-dim:    #8A6428;
            --loupe:        #6FCF97;
            --loupe-dim:    #3E7C5A;
            --ink:          #211C16;
            --ink-soft:     #5C5346;
            --ease: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--ink);
            background: var(--case-felt);
        }

        .playfair, .fraunces { font-family: 'Fraunces', serif; }

        .gold-gradient {
            background: linear-gradient(160deg, var(--brass-bright), var(--brass-dim));
        }

        .gold-gradient-text {
            background: linear-gradient(135deg, var(--brass-bright), var(--brass), var(--brass-dim));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Content card */
        .luxury-card {
            background: rgba(255, 255, 255, 0.97);
            border: 1px solid rgba(33, 28, 22, 0.08);
            box-shadow: 0 20px 50px rgba(21, 18, 15, 0.08);
            border-radius: 20px;
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
            background: linear-gradient(90deg, var(--brass-dim), var(--brass-bright));
        }

        .scroll-mt-24 {
            scroll-margin-top: 6rem;
        }

        /* Nav */
        .legal-nav {
            background: rgba(21,18,15,0.94);
            backdrop-filter: blur(14px) saturate(140%);
            border-bottom: 1px solid rgba(184,134,59,0.28);
        }
        .legal-nav-link {
            position: relative;
            color: rgba(250,246,238,0.75);
            transition: color 200ms var(--ease);
        }
        .legal-nav-link:hover { color: var(--brass-bright); }

        /* Footer */
        .legal-footer {
            background: var(--onyx);
            color: rgba(250,246,238,0.55);
        }
        .legal-footer a { color: rgba(250,246,238,0.65); transition: color 200ms var(--ease); }
        .legal-footer a:hover { color: var(--brass-bright); }
    </style>
</head>

<body class="antialiased">
    <!-- Brass Top Accent -->
    <div class="fixed top-0 left-0 w-full h-1 gold-gradient z-50"></div>

    <!-- Navigation -->
    <nav class="legal-nav py-4 fixed w-full top-0 z-40">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <a href="/" class="flex items-center space-x-3">
                    <img src="/jeweltaglogo.png" alt="JewelTag" class="h-9 w-auto">
                </a>
            </div>

            <div class="hidden md:flex space-x-6">
                <a href="/#features" class="legal-nav-link text-xs font-bold uppercase tracking-widest">Features</a>
                <a href="/#pricing" class="legal-nav-link text-xs font-bold uppercase tracking-widest">Pricing</a>
                <a href="/master/login" class="legal-nav-link text-xs font-bold uppercase tracking-widest">Staff Login</a>
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

    <footer class="legal-footer text-center py-12 text-xs">
        <div class="flex justify-center space-x-4 mb-4">
            <a href="{{ route('privacy') }}" class="transition">Privacy Policy</a>
            <a href="{{ route('docs') }}" class="transition">Documentation</a>
            <a href="{{ route('api') }}" class="transition">API Reference</a>
        </div>
        <p class="opacity-60">&copy; {{ date('Y') }} The Explorers USA DBA JewelTag. All rights reserved.</p>
    </footer>
</body>
</html>