<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JewelTag.us | Inventory, POS & CRM Built for Jewelers</title>
    <meta name="description" content="JewelTag is the inventory management, point-of-sale, and CRM platform built specifically for jewelry retailers — RFID tracking, repair orders, layaway, and more.">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700;9..144,800&family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --onyx:        #15120F;
            --onyx-soft:   #1F1A15;
            --case-felt:   #FAF6EE;
            --brass:       #B8863B;
            --brass-bright:#E0AE5C;
            --brass-dim:   #8A6428;
            --loupe:       #6FCF97;
            --loupe-dim:   #3E7C5A;
            --ink:         #211C16;
            --ink-soft:    #5C5346;
            --ease: cubic-bezier(0.16, 1, 0.3, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--ink);
            background: var(--case-felt);
            overflow-x: hidden;
        }

        .fraunces { font-family: 'Fraunces', serif; }
        .mono { font-family: 'JetBrains Mono', monospace; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.001ms !important; transition-duration: 0.001ms !important; }
        }

        .engraved-grid {
            background-image:
                linear-gradient(rgba(33,28,22,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(33,28,22,0.04) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        .nav-case {
            background: rgba(21,18,15,0.94);
            backdrop-filter: blur(14px) saturate(140%);
            border-bottom: 1px solid rgba(184,134,59,0.28);
        }
        .nav-link {
            position: relative;
            color: rgba(250,246,238,0.75);
            font-weight: 600;
            font-size: 0.84rem;
            letter-spacing: 0.02em;
            transition: color 200ms var(--ease);
        }
        .nav-link::after {
            content: '';
            position: absolute; left: 0; bottom: -6px;
            width: 0; height: 1.5px;
            background: var(--brass-bright);
            transition: width 220ms var(--ease);
        }
        .nav-link:hover { color: var(--brass-bright); }
        .nav-link:hover::after { width: 100%; }

        .btn-brass {
            background: linear-gradient(160deg, var(--brass-bright), var(--brass-dim));
            color: var(--onyx);
            font-weight: 700;
            font-size: 0.84rem;
            letter-spacing: 0.02em;
            padding: 12px 26px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 1px 0 rgba(255,255,255,0.4) inset, 0 6px 16px rgba(184,134,59,0.28);
            transition: transform 180ms var(--ease), box-shadow 180ms var(--ease);
        }
        .btn-brass:hover {
            transform: translateY(-2px);
            box-shadow: 0 1px 0 rgba(255,255,255,0.5) inset, 0 12px 28px rgba(184,134,59,0.4);
        }

        .btn-ghost-dark {
            border: 1.5px solid rgba(255,255,255,0.28);
            color: var(--case-felt);
            font-weight: 700;
            font-size: 0.84rem;
            letter-spacing: 0.02em;
            padding: 12px 26px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 200ms var(--ease);
        }
        .btn-ghost-dark:hover {
            border-color: var(--brass-bright);
            background: rgba(184,134,59,0.12);
            color: var(--brass-bright);
        }

        .hero-case {
            background:
                radial-gradient(ellipse 900px 600px at 80% -10%, rgba(184,134,59,0.18), transparent 60%),
                radial-gradient(ellipse 700px 500px at 0% 100%, rgba(184,134,59,0.10), transparent 60%),
                linear-gradient(180deg, var(--onyx) 0%, var(--onyx-soft) 100%);
            position: relative;
        }

        .facet-overlay {
            position: absolute; inset: 0; opacity: 0.5; pointer-events: none;
            background-image: repeating-linear-gradient(115deg, transparent 0 60px, rgba(255,255,255,0.015) 60px 61px);
        }

        .eyebrow-tag {
            display: inline-flex; align-items: center; gap: 8px;
            border: 1px solid rgba(184,134,59,0.4);
            background: rgba(184,134,59,0.08);
            border-radius: 999px;
            padding: 6px 16px 6px 10px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--brass-bright);
        }
        .eyebrow-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--loupe);
            box-shadow: 0 0 8px rgba(111,207,151,0.7);
            animation: pulse-dot 2.2s ease-in-out infinite;
        }
        @keyframes pulse-dot { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

        .loupe-stage {
            position: relative;
        }
        .loupe-frame {
            position: absolute;
            width: 168px; height: 168px;
            border-radius: 50%;
            border: 6px solid var(--brass-bright);
            box-shadow:
                0 0 0 3px var(--onyx),
                0 0 0 5px rgba(184,134,59,0.35),
                0 20px 50px rgba(0,0,0,0.55),
                inset 0 0 30px rgba(111,207,151,0.25);
            background: radial-gradient(circle at 35% 30%, rgba(255,255,255,0.18), transparent 55%), rgba(15,18,16,0.35);
            backdrop-filter: blur(0.5px);
            animation: loupe-drift 9s var(--ease) infinite;
            z-index: 30;
        }
        @keyframes loupe-drift {
            0%, 100% { transform: translate(0,0) scale(1); }
            25%      { transform: translate(6px,-10px) scale(1.03); }
            50%      { transform: translate(-4px,4px) scale(1); }
            75%      { transform: translate(8px,8px) scale(1.02); }
        }
        .loupe-handle {
            position: absolute;
            width: 14px; height: 70px;
            background: linear-gradient(90deg, var(--brass-dim), var(--brass-bright), var(--brass-dim));
            border-radius: 0 0 8px 8px;
            bottom: -58px; left: 50%;
            transform: translateX(-50%) rotate(28deg);
            transform-origin: top center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.4);
            z-index: 29;
        }
        .loupe-readout {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: var(--loupe);
            font-size: 0.62rem;
            text-align: center;
            line-height: 1.3;
        }

        .flip-stage {
            position: relative;
            perspective: 1800px;
        }

        .flip-frame {
            border-radius: 18px;
            border: 1px solid rgba(184,134,59,0.35);
            box-shadow: 0 30px 70px rgba(0,0,0,0.5), 0 1px 0 rgba(255,255,255,0.06) inset;
            overflow: hidden;
            position: relative;
            background: var(--onyx-soft);
        }

        .flip-aspect {
            position: relative;
            width: 100%;
            padding-top: 66%;
        }

        .flip-card {
            position: absolute;
            inset: 0;
            backface-visibility: hidden;
            transform-origin: top center;
            transform-style: preserve-3d;
        }

        .flip-card img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .flip-card::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(180deg, transparent 60%, rgba(21,18,15,0.5) 100%);
            pointer-events: none;
        }

        .flip-card-1 { z-index: 3; animation: flip-page-1 6s infinite; }
        .flip-card-2 { z-index: 2; animation: flip-page-2 6s infinite; }
        .flip-card-3 { z-index: 1; animation: flip-page-3 6s infinite; }

        @keyframes flip-page-1 {
            0%      { transform: rotateX(0deg); opacity: 1; }
            29%     { transform: rotateX(0deg); opacity: 1; }
            38%     { transform: rotateX(-130deg); opacity: 1; }
            38.5%   { transform: rotateX(-130deg); opacity: 0; }
            99.9%   { transform: rotateX(-130deg); opacity: 0; }
            100%    { transform: rotateX(0deg); opacity: 1; }
        }

        @keyframes flip-page-2 {
            0%      { transform: rotateX(0deg); opacity: 0; }
            29%     { transform: rotateX(0deg); opacity: 0; }
            29.5%   { transform: rotateX(0deg); opacity: 1; }
            62%     { transform: rotateX(0deg); opacity: 1; }
            71%     { transform: rotateX(-130deg); opacity: 1; }
            71.5%   { transform: rotateX(-130deg); opacity: 0; }
            100%    { transform: rotateX(-130deg); opacity: 0; }
        }

        @keyframes flip-page-3 {
            0%      { transform: rotateX(0deg); opacity: 0; }
            62%     { transform: rotateX(0deg); opacity: 0; }
            62.5%   { transform: rotateX(0deg); opacity: 1; }
            95%     { transform: rotateX(0deg); opacity: 1; }
            100%    { transform: rotateX(-130deg); opacity: 1; }
        }

        .flip-binding {
            position: absolute;
            top: -7px; left: 50%;
            transform: translateX(-50%);
            width: 86%;
            height: 14px;
            display: flex;
            justify-content: space-between;
            z-index: 25;
            pointer-events: none;
        }
        .flip-binding span {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: linear-gradient(160deg, var(--brass-bright), var(--brass-dim));
            box-shadow: 0 2px 6px rgba(0,0,0,0.4), 0 0 0 2px var(--onyx);
        }

        .flip-dots {
            display: flex;
            justify-content: center;
            gap: 7px;
            margin-top: 16px;
        }
        .flip-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: rgba(184,134,59,0.25);
            transition: width 0.3s, background 0.3s;
        }
        .flip-dot-1 { animation: dot-glow-1 6s infinite; }
        .flip-dot-2 { animation: dot-glow-2 6s infinite; }
        .flip-dot-3 { animation: dot-glow-3 6s infinite; }

        @keyframes dot-glow-1 {
            0%, 29% { background: var(--brass-bright); width: 18px; }
            38%, 100% { background: rgba(184,134,59,0.25); width: 6px; }
        }
        @keyframes dot-glow-2 {
            0%, 29.5% { background: rgba(184,134,59,0.25); width: 6px; }
            30%, 62% { background: var(--brass-bright); width: 18px; }
            71%, 100% { background: rgba(184,134,59,0.25); width: 6px; }
        }
        @keyframes dot-glow-3 {
            0%, 62.5% { background: rgba(184,134,59,0.25); width: 6px; }
            63%, 95% { background: var(--brass-bright); width: 18px; }
            100% { background: rgba(184,134,59,0.25); width: 6px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .flip-card-1, .flip-card-2, .flip-card-3,
            .flip-dot-1, .flip-dot-2, .flip-dot-3 {
                animation: none !important;
            }
            .flip-card-1 { opacity: 1; z-index: 3; }
            .flip-card-2, .flip-card-3 { opacity: 0; }
        }

        .stat-tray {
            background: var(--onyx-soft);
            border-top: 1px solid rgba(184,134,59,0.18);
            border-bottom: 1px solid rgba(184,134,59,0.18);
        }
        .stat-figure {
            font-family: 'Fraunces', serif;
            font-weight: 700;
            font-size: clamp(2.2rem, 4vw, 3.2rem);
            color: var(--brass-bright);
            line-height: 1;
        }
        .stat-label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(250,246,238,0.5);
        }

        .tray-card {
            background: #ffffff;
            border: 1px solid rgba(33,28,22,0.08);
            border-radius: 16px;
            padding: 32px;
            position: relative;
            transition: transform 260ms var(--ease), box-shadow 260ms var(--ease), border-color 260ms var(--ease);
            overflow: hidden;
        }
        .tray-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--brass-dim), var(--brass-bright));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 320ms var(--ease);
        }
        .tray-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 48px rgba(33,28,22,0.10);
            border-color: rgba(184,134,59,0.3);
        }
        .tray-card:hover::before { transform: scaleX(1); }

        .tray-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            background: linear-gradient(160deg, var(--onyx-soft), var(--onyx));
            color: var(--brass-bright);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(21,18,15,0.18);
        }

        .section-eyebrow {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--brass-dim);
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 14px;
        }
        .section-eyebrow::before {
            content: ''; width: 24px; height: 1.5px; background: var(--brass);
        }

        .price-card {
            background: #ffffff;
            border: 1.5px solid rgba(33,28,22,0.10);
            border-radius: 20px;
            padding: 40px 32px;
            position: relative;
            transition: transform 280ms var(--ease), box-shadow 280ms var(--ease);
        }
        .price-card:hover { transform: translateY(-6px); }
        .price-card.featured {
            background: linear-gradient(165deg, var(--onyx) 0%, var(--onyx-soft) 100%);
            border-color: var(--brass);
            box-shadow: 0 30px 60px rgba(184,134,59,0.22);
            transform: scale(1.04);
        }
        .price-card.featured:hover { transform: scale(1.04) translateY(-6px); }
        .featured-ribbon {
            position: absolute; top: 22px; right: -8px;
            background: linear-gradient(160deg, var(--brass-bright), var(--brass-dim));
            color: var(--onyx);
            font-size: 0.66rem; font-weight: 800; letter-spacing: 0.08em;
            padding: 6px 18px 6px 14px;
            border-radius: 3px 0 0 3px;
            box-shadow: -3px 4px 10px rgba(0,0,0,0.25);
        }
        .featured-ribbon::after {
            content: ''; position: absolute; right: 0; top: 100%;
            border-width: 4px 0 4px 8px;
            border-style: solid;
            border-color: transparent transparent transparent var(--brass-dim);
        }
        .price-check {
            color: var(--loupe-dim);
            margin-right: 10px;
            font-size: 0.78rem;
        }
        .price-check.dim { color: rgba(33,28,22,0.18); }
        .featured .price-check { color: var(--loupe); }
        .featured .price-check.dim { color: rgba(255,255,255,0.18); }

        .quote-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 32px;
            border: 1px solid rgba(33,28,22,0.08);
            position: relative;
        }
        .quote-mark {
            font-family: 'Fraunces', serif;
            font-size: 64px;
            color: rgba(184,134,59,0.18);
            line-height: 1;
            position: absolute;
            top: 14px; left: 20px;
        }

        .rail-step {
            position: relative;
            padding-left: 0;
        }
        .rail-num {
            font-family: 'Fraunces', serif;
            font-weight: 700;
            font-size: 0.95rem;
            width: 44px; height: 44px;
            border-radius: 50%;
            background: var(--onyx);
            color: var(--brass-bright);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 18px;
            border: 1.5px solid var(--brass);
        }
        .rail-track {
            position: absolute; top: 22px; left: 0; right: 0;
            height: 1.5px;
            background: repeating-linear-gradient(90deg, var(--brass) 0 6px, transparent 6px 12px);
            z-index: -1;
        }

        .input-felt {
            border: 1.5px solid rgba(33,28,22,0.14);
            border-radius: 10px;
            padding: 14px 16px;
            width: 100%;
            background: var(--case-felt);
            font-size: 0.92rem;
            transition: border-color 180ms var(--ease), box-shadow 180ms var(--ease);
        }
        .input-felt:focus {
            outline: none;
            border-color: var(--brass);
            box-shadow: 0 0 0 4px rgba(184,134,59,0.12);
            background: #ffffff;
        }

        .footer-onyx { background: var(--onyx); color: rgba(250,246,238,0.65); }
        .footer-link { color: rgba(250,246,238,0.55); transition: color 180ms var(--ease); }
        .footer-link:hover { color: var(--brass-bright); }

        .gold-underline {
            background: linear-gradient(transparent 65%, rgba(184,134,59,0.35) 65%);
        }

        @media (max-width: 1024px) {
            .loupe-frame { width: 120px; height: 120px; }
            .price-card.featured { transform: scale(1); }
            .price-card.featured:hover { transform: translateY(-6px); }
        }
        @media (max-width: 640px) {
            .loupe-frame { display: none; }
        }
    </style>
</head>

<body class="antialiased">

    <nav class="nav-case fixed w-full z-50">
        <div class="container mx-auto px-6 lg:px-10 py-4">
            <div class="flex justify-between items-center">
                <a href="/" class="flex items-center gap-3">
                    <img src="/jeweltaglogo.png" alt="JewelTag" class="h-10 w-auto">
                </a>

                <div class="hidden lg:flex items-center gap-10">
                    <a href="#features" class="nav-link">Features</a>
                    <a href="#solutions" class="nav-link">Implementation</a>
                    <a href="#pricing" class="nav-link">Pricing</a>
                    <a href="#testimonials" class="nav-link">Customers</a>
                    <div class="flex items-center gap-3 ml-4">
                        <a href="/master/login" class="text-sm font-semibold text-[var(--ink-soft)] hover:text-[var(--onyx)] transition-colors px-2">
                            <i class="fas fa-lock mr-1.5 text-xs"></i>Staff Login
                        </a>
                        <a href="#demo" class="btn-brass">
                            <i class="fas fa-arrow-right text-xs"></i> Start Free Trial
                        </a>
                    </div>
                </div>

                <button id="mobile-menu-button" class="lg:hidden text-[var(--onyx)] text-xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <div id="mobile-menu" class="lg:hidden hidden mt-5 pb-4 border-t border-[rgba(184,134,59,0.2)] pt-4">
                <div class="flex flex-col gap-4">
                    <a href="#features" class="text-sm font-semibold text-[var(--ink-soft)]">Features</a>
                    <a href="#solutions" class="text-sm font-semibold text-[var(--ink-soft)]">Implementation</a>
                    <a href="#pricing" class="text-sm font-semibold text-[var(--ink-soft)]">Pricing</a>
                    <a href="#testimonials" class="text-sm font-semibold text-[var(--ink-soft)]">Customers</a>
                    <a href="/master/login" class="text-sm font-semibold text-[var(--ink-soft)]">Staff Login</a>
                    <a href="#demo" class="btn-brass justify-center">Start Free Trial</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-case pt-36 pb-24 lg:pt-44 lg:pb-32">
        <div class="facet-overlay"></div>
        <div class="container mx-auto px-6 lg:px-10 relative z-10">
            <div class="grid lg:grid-cols-2 gap-16 items-center">

                <div data-aos="fade-up">
                    <div class="eyebrow-tag mb-7">
                        <span class="eyebrow-dot"></span>
                        Built specifically for jewelry retail
                    </div>

                    <h1 class="fraunces text-[2.6rem] leading-[1.08] sm:text-5xl lg:text-[3.6rem] font-semibold text-[var(--case-felt)] mb-7">
                        Every <span style="color: #B8863B;">piece,</span><br>
                        <span class="gold-underline" style="background-image: linear-gradient(transparent 70%, rgba(184,134,59,0.4) 70%); display:inline;">accounted for.</span>
                    </h1>

                    <p class="text-lg text-[rgba(250,246,238,0.68)] mb-10 leading-relaxed max-w-md">
                        Inventory, point-of-sale, repairs, and customer relationships — one system built around how a jewelry counter actually runs, from intake to sale to follow-up.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 mb-12">
                        <a href="#demo" class="btn-brass justify-center">
                            <i class="fas fa-play text-xs"></i> Start 30-Day Trial
                        </a>
                        <a href="/master" class="btn-ghost-dark justify-center">
                            <i class="fas fa-arrow-up-right-from-square text-xs"></i> Enter Master Portal
                        </a>
                    </div>

                    <div class="flex items-center gap-5 pt-2">
                        <div class="flex -space-x-3">
                            <div class="w-10 h-10 rounded-full bg-[var(--brass)] flex items-center justify-center text-[var(--onyx)] text-xs font-bold border-2 border-[var(--onyx)]">SC</div>
                            <div class="w-10 h-10 rounded-full bg-[var(--loupe-dim)] flex items-center justify-center text-white text-xs font-bold border-2 border-[var(--onyx)]">MR</div>
                            <div class="w-10 h-10 rounded-full bg-[var(--brass-dim)] flex items-center justify-center text-white text-xs font-bold border-2 border-[var(--onyx)]">JW</div>
                        </div>
                        <div class="text-sm text-[rgba(250,246,238,0.55)]">
                            <span class="text-[var(--case-felt)] font-bold">750+</span> jewelry retailers running on JewelTag
                        </div>
                    </div>
                </div>

                <div data-aos="fade-left" data-aos-delay="150">
                    <div class="loupe-stage flip-stage">

                        <div class="flip-binding">
                            <span></span><span></span><span></span><span></span><span></span>
                        </div>

                        <div class="flip-frame">
                            <div class="flip-aspect">
                                <div class="flip-card flip-card-1">
                                    <img src="/homephoto.png" alt="JewelTag — inventory dashboard">
                                </div>
                                <div class="flip-card flip-card-2">
                                    <img src="/homephoto2.png" alt="JewelTag — point of sale">
                                </div>
                                <div class="flip-card flip-card-3">
                                    <img src="/homephoto3.png" alt="JewelTag — repair tracking">
                                </div>
                            </div>
                        </div>

                        <div class="loupe-frame" style="top: -14px; right: -22px;">
                            <div class="loupe-handle"></div>
                            <div class="loupe-readout mono">
                                <div>
                                    <div style="font-size:1.1rem; font-weight:700;">2,847</div>
                                    <div style="opacity:0.7; letter-spacing:0.06em;">SKUs TRACKED</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flip-dots">
                        <span class="flip-dot flip-dot-1"></span>
                        <span class="flip-dot flip-dot-2"></span>
                        <span class="flip-dot flip-dot-3"></span>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="stat-tray py-14">
        <div class="container mx-auto px-6 lg:px-10">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-10">
                <div class="text-center" data-aos="fade-up" data-aos-delay="0">
                    <div class="stat-figure">50+</div>
                    <div class="stat-label mt-2">Jewelers on JewelTag</div>
                </div>
                <div class="text-center" data-aos="fade-up" data-aos-delay="80">
                    <div class="stat-figure">POS+CRM</div>
                    <div class="stat-label mt-2">POS CRM and SMM</div>
                </div>
                <div class="text-center" data-aos="fade-up" data-aos-delay="160">
                    <div class="stat-figure">99.5%</div>
                    <div class="stat-label mt-2">Target uptime SLA</div>
                </div>
                <div class="text-center" data-aos="fade-up" data-aos-delay="240">
                    <div class="stat-figure">256-bit</div>
                    <div class="stat-label mt-2">Encryption at rest</div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-24 engraved-grid">
        <div class="container mx-auto px-6 lg:px-10">
            <div class="max-w-2xl mb-16" data-aos="fade-up">
                <div class="section-eyebrow">The Workbench</div>
                <h2 class="fraunces text-4xl lg:text-5xl font-semibold text-[var(--onyx)] leading-tight">
                    Everything the counter touches, in one place.
                </h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="tray-card" data-aos="fade-up" data-aos-delay="0">
                    <div class="tray-icon"><i class="fas fa-tags"></i></div>
                    <h3 class="text-lg font-bold mb-2.5">Inventory &amp; RFID</h3>
                    <p class="text-[var(--ink-soft)] text-sm leading-relaxed mb-4">Barcode and RFID tracking down to metal, stone, and weight — with certification records attached to every piece.</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>GIA &amp; cert record storage</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Multi-store sync</li>
                    </ul>
                </div>

                <div class="tray-card" data-aos="fade-up" data-aos-delay="80">
                    <div class="tray-icon"><i class="fas fa-cash-register"></i></div>
                    <h3 class="text-lg font-bold mb-2.5">Point of Sale</h3>
                    <p class="text-[var(--ink-soft)] text-sm leading-relaxed mb-4">Built for the counter — layaway, financing, commission splits, and receipts that match how jewelers actually sell.</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Layaway &amp; financing</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Commission tracking</li>
                    </ul>
                </div>

                <div class="tray-card" data-aos="fade-up" data-aos-delay="160">
                    <div class="tray-icon"><i class="fas fa-screwdriver-wrench"></i></div>
                    <h3 class="text-lg font-bold mb-2.5">Repair Orders</h3>
                    <p class="text-[var(--ink-soft)] text-sm leading-relaxed mb-4">Digital work orders with photo documentation, status tracking, and automatic customer notifications.</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Photo documentation</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Warranty tracking</li>
                    </ul>
                </div>

                <div class="tray-card" data-aos="fade-up" data-aos-delay="0">
                    <div class="tray-icon"><i class="fas fa-chart-line"></i></div>
                    <h3 class="text-lg font-bold mb-2.5">Reporting</h3>
                    <p class="text-[var(--ink-soft)] text-sm leading-relaxed mb-4">Margin analysis, staff performance, and sales trends — pulled straight from the floor, not estimated after the fact.</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Margin &amp; staff reports</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>QuickBooks integration</li>
                    </ul>
                </div>

                <div class="tray-card" data-aos="fade-up" data-aos-delay="80">
                    <div class="tray-icon"><i class="fas fa-heart"></i></div>
                    <h3 class="text-lg font-bold mb-2.5">CRM_JewelTag</h3>
                    <p class="text-[var(--ink-soft)] text-sm leading-relaxed mb-4">Customer profiles, anniversaries, and wish lists — with marketing automation that knows what they bought before.</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Anniversary automation</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Wish list tracking</li>
                    </ul>
                </div>

                <div class="tray-card" data-aos="fade-up" data-aos-delay="160">
                    <div class="tray-icon"><i class="fas fa-shop"></i></div>
                    <h3 class="text-lg font-bold mb-2.5">E-Commerce Sync</h3>
                    <p class="text-[var(--ink-soft)] text-sm leading-relaxed mb-4">Connect Shopify or WooCommerce directly to your floor inventory — sell online without double-counting stock.</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Shopify &amp; WooCommerce</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Live stock sync</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="solutions" class="py-24 bg-white border-y border-[rgba(33,28,22,0.06)]">
        <div class="container mx-auto px-6 lg:px-10">
            <div class="max-w-2xl mx-auto text-center mb-16" data-aos="fade-up">
                <div class="section-eyebrow mx-auto" style="justify-content:center;">Getting Set Up</div>
                <h2 class="fraunces text-4xl lg:text-5xl font-semibold text-[var(--onyx)]">From signup to selling — four steps.</h2>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 max-w-5xl mx-auto relative">
                <div class="rail-track hidden lg:block"></div>

                <div class="rail-step text-center" data-aos="fade-up" data-aos-delay="0">
                    <div class="rail-num">01</div>
                    <h3 class="font-bold mb-1.5">Discovery</h3>
                    <p class="text-[var(--ink-soft)] text-sm">We map your current workflow and store setup</p>
                    <span class="text-xs text-[var(--brass-dim)] font-bold mt-2 inline-block">1–3 days</span>
                </div>
                <div class="rail-step text-center" data-aos="fade-up" data-aos-delay="80">
                    <div class="rail-num">02</div>
                    <h3 class="font-bold mb-1.5">Migration</h3>
                    <p class="text-[var(--ink-soft)] text-sm">White-glove transfer of inventory and customer data</p>
                    <span class="text-xs text-[var(--brass-dim)] font-bold mt-2 inline-block">3–7 days</span>
                </div>
                <div class="rail-step text-center" data-aos="fade-up" data-aos-delay="160">
                    <div class="rail-num">03</div>
                    <h3 class="font-bold mb-1.5">Training</h3>
                    <p class="text-[var(--ink-soft)] text-sm">Hands-on onboarding for every staff member</p>
                    <span class="text-xs text-[var(--brass-dim)] font-bold mt-2 inline-block">5–10 days</span>
                </div>
                <div class="rail-step text-center" data-aos="fade-up" data-aos-delay="240">
                    <div class="rail-num">04</div>
                    <h3 class="font-bold mb-1.5">Go Live</h3>
                    <p class="text-[var(--ink-soft)] text-sm">Launch with support watching your first weeks</p>
                    <span class="text-xs text-[var(--brass-dim)] font-bold mt-2 inline-block">Ongoing</span>
                </div>
            </div>
        </div>
    </section>

    <section id="testimonials" class="py-24 engraved-grid">
        <div class="container mx-auto px-6 lg:px-10">
            <div class="max-w-2xl mb-16" data-aos="fade-up">
                <div class="section-eyebrow">From the Counter</div>
                <h2 class="fraunces text-4xl lg:text-5xl font-semibold text-[var(--onyx)]">What changes when the system fits the work.</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <div class="quote-card" data-aos="fade-up" data-aos-delay="0">
                    <span class="quote-mark">"</span>
                    <div class="relative pt-8">
                        <p class="text-[var(--ink-soft)] text-sm leading-relaxed mb-5">Inventory reconciliation went from three weeks to two days. Stock accuracy is at 99.8% now.</p>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[var(--brass)] flex items-center justify-center text-[var(--onyx)] text-xs font-bold">SC</div>
                            <div>
                                <div class="font-bold text-sm">Sarah Chen</div>
                                <div class="text-xs text-[var(--ink-soft)]">Brilliance Diamonds</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="quote-card" data-aos="fade-up" data-aos-delay="80">
                    <span class="quote-mark">"</span>
                    <div class="relative pt-8">
                        <p class="text-[var(--ink-soft)] text-sm leading-relaxed mb-5">Repair admin time dropped 70%. Customers actually know where their piece is now — satisfaction went from 82% to 98%.</p>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[var(--loupe-dim)] flex items-center justify-center text-white text-xs font-bold">MR</div>
                            <div>
                                <div class="font-bold text-sm">Michael Rodriguez</div>
                                <div class="text-xs text-[var(--ink-soft)]">Gold Standard Jewelers</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="quote-card" data-aos="fade-up" data-aos-delay="160">
                    <span class="quote-mark">"</span>
                    <div class="relative pt-8">
                        <p class="text-[var(--ink-soft)] text-sm leading-relaxed mb-5">Sales are up 34% in six months, and our margins improved by eight points just from having real numbers to act on.</p>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[var(--brass-dim)] flex items-center justify-center text-white text-xs font-bold">JW</div>
                            <div>
                                <div class="font-bold text-sm">James Wilson</div>
                                <div class="text-xs text-[var(--ink-soft)]">Heritage Jewelers</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" class="py-24 bg-white border-t border-[rgba(33,28,22,0.06)]">
        <div class="container mx-auto px-6 lg:px-10">
            <div class="max-w-2xl mx-auto text-center mb-16" data-aos="fade-up">
                <div class="section-eyebrow mx-auto" style="justify-content:center;">Plans</div>
                <h2 class="fraunces text-4xl lg:text-5xl font-semibold text-[var(--onyx)] mb-4">Priced for the size of your floor.</h2>
                <p class="text-[var(--ink-soft)]">Every plan includes JewelTag's inventory core. Add CRM_JewelTag whenever you're ready for it.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-7 max-w-5xl mx-auto items-start">

                <div class="price-card" data-aos="fade-up" data-aos-delay="0">
                    <h3 class="text-lg font-bold mb-1">JewelTag Basic</h3>
                    <p class="text-sm text-[var(--ink-soft)] mb-6">For a single-location shop getting organized</p>
                    <div class="mb-7">
                        <span class="fraunces text-4xl font-bold">$499</span>
                        <span class="text-[var(--ink-soft)] text-sm">/month</span>
                    </div>
                    <ul class="space-y-3 text-sm mb-8">
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Inventory &amp; SKU management</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Barcode scanning &amp; labels</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Repair order tracking</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>QuickBooks integration</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Up to 3 users · 1 location</li>
                        <li class="flex items-center text-[rgba(33,28,22,0.4)]"><i class="fas fa-xmark price-check dim"></i>RFID &amp; multi-store sync</li>
                    </ul>
                    <a href="#demo" class="btn-ghost-dark w-full justify-center" style="color: var(--onyx); border-color: rgba(33,28,22,0.18);">Start Free Trial</a>
                </div>

                <div class="price-card featured" data-aos="fade-up" data-aos-delay="80">
                    <div class="featured-ribbon">MOST CHOSEN</div>
                    <h3 class="text-lg font-bold mb-1 text-[var(--case-felt)]">JewelTag Pro + CRM</h3>
                    <p class="text-sm text-[rgba(250,246,238,0.6)] mb-6">The full counter-to-customer system</p>
                    <div class="mb-7">
                        <span class="fraunces text-4xl font-bold text-[var(--brass-bright)]">$1,299</span>
                        <span class="text-[rgba(250,246,238,0.55)] text-sm">/month</span>
                    </div>
                    <ul class="space-y-3 text-sm mb-8 text-[rgba(250,246,238,0.85)]">
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Everything in Basic, unlimited items</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>RFID tracking &amp; live metal pricing</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Layaway, financing &amp; multi-store sync</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>CRM_JewelTag — loyalty, SMS &amp; email</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Advanced analytics &amp; API access</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Up to 15 users · 2 locations</li>
                    </ul>
                    <a href="#demo" class="btn-brass w-full justify-center">Start Free Trial</a>
                </div>

                <div class="price-card" data-aos="fade-up" data-aos-delay="160">
                    <h3 class="text-lg font-bold mb-1">Enterprise</h3>
                    <p class="text-sm text-[var(--ink-soft)] mb-6">For multi-store retailers &amp; custom needs</p>
                    <div class="mb-7">
                        <span class="fraunces text-4xl font-bold">Custom</span>
                    </div>
                    <ul class="space-y-3 text-sm mb-8">
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Unlimited locations &amp; users</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Custom integrations</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Dedicated account manager</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Priority phone support</li>
                        <li class="flex items-center"><i class="fas fa-check price-check"></i>Unlimited CRM customer records</li>
                    </ul>
                    <a href="#contact" class="btn-ghost-dark w-full justify-center" style="color: var(--onyx); border-color: rgba(33,28,22,0.18);">Contact Sales</a>
                </div>
            </div>

            <p class="text-center text-xs text-[var(--ink-soft)] mt-10">
                <i class="fas fa-shield-halved text-[var(--brass-dim)] mr-1.5"></i>
                256-bit encryption · Daily backups · 99.5% target uptime SLA · CRM_JewelTag requires an active JewelTag subscription
            </p>
        </div>
    </section>

    <section id="demo" class="py-24 hero-case relative">
        <div class="facet-overlay"></div>
        <div class="container mx-auto px-6 lg:px-10 relative z-10">
            <div class="max-w-xl mx-auto text-center mb-12" data-aos="fade-up">
                <h2 class="fraunces text-4xl lg:text-5xl font-semibold text-[var(--case-felt)] mb-4">See it on your own inventory.</h2>
                <p class="text-[rgba(250,246,238,0.65)]">30 days, full access, no card required.</p>
            </div>

            <div class="max-w-xl mx-auto bg-[var(--case-felt)] rounded-2xl p-8 shadow-2xl" data-aos="fade-up" data-aos-delay="100">
                <form id="demo-form" class="space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <input type="text" placeholder="First name" class="input-felt" required>
                        <input type="text" placeholder="Last name" class="input-felt" required>
                    </div>
                    <input type="email" placeholder="Work email" class="input-felt" required>
                    <input type="text" placeholder="Business name" class="input-felt" required>
                    <button type="submit" class="btn-brass w-full justify-center py-3.5">
                        <i class="fas fa-play text-xs"></i> Start 30-Day Trial
                    </button>
                    <p class="text-xs text-center text-[var(--ink-soft)]">
                        By signing up you agree to our <a href="#" class="font-semibold text-[var(--brass-dim)]">Terms</a> and <a href="#" class="font-semibold text-[var(--brass-dim)]">Privacy Policy</a>.
                    </p>
                </form>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white">
        <div class="container mx-auto px-6 lg:px-10">
            <div class="max-w-2xl mx-auto text-center mb-12" data-aos="fade-up">
                <div class="section-eyebrow mx-auto" style="justify-content:center;">Questions</div>
                <h2 class="fraunces text-3xl lg:text-4xl font-semibold text-[var(--onyx)]">Before you start the trial.</h2>
            </div>

            <div class="max-w-2xl mx-auto space-y-3">
                <div class="bg-[var(--case-felt)] rounded-xl p-5 border border-[rgba(33,28,22,0.08)]">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(1)">
                        <h3 class="font-bold text-sm">How long does implementation take?</h3>
                        <i class="fas fa-chevron-down text-[var(--brass-dim)] text-xs transition-transform"></i>
                    </button>
                    <div id="faq-1" class="mt-3 hidden text-sm text-[var(--ink-soft)] leading-relaxed">
                        Most shops are fully live within two to three weeks, covering discovery, data migration, staff training, and go-live support.
                    </div>
                </div>

                <div class="bg-[var(--case-felt)] rounded-xl p-5 border border-[rgba(33,28,22,0.08)]">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(2)">
                        <h3 class="font-bold text-sm">Can we bring over our existing data?</h3>
                        <i class="fas fa-chevron-down text-[var(--brass-dim)] text-xs transition-transform"></i>
                    </button>
                    <div id="faq-2" class="mt-3 hidden text-sm text-[var(--ink-soft)] leading-relaxed">
                        Yes — we migrate inventory, customer records, and transaction history from most existing jewelry systems and spreadsheets.
                    </div>
                </div>

                <div class="bg-[var(--case-felt)] rounded-xl p-5 border border-[rgba(33,28,22,0.08)]">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(3)">
                        <h3 class="font-bold text-sm">What happens to our data if we cancel?</h3>
                        <i class="fas fa-chevron-down text-[var(--brass-dim)] text-xs transition-transform"></i>
                    </button>
                    <div id="faq-3" class="mt-3 hidden text-sm text-[var(--ink-soft)] leading-relaxed">
                        You can export everything in CSV, JSON, or PDF at any time. After cancellation, you get a 30-day window to export before deletion.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="py-24 hero-case relative">
        <div class="facet-overlay"></div>
        <div class="container mx-auto px-6 lg:px-10 relative z-10">
            <div class="max-w-2xl mx-auto text-center mb-12" data-aos="fade-up">
                <h2 class="fraunces text-3xl lg:text-4xl font-semibold text-[var(--case-felt)] mb-4">Talk to the team.</h2>
            </div>

            <div class="grid sm:grid-cols-3 gap-6 max-w-2xl mx-auto mb-12">
                <div class="text-center">
                    <div class="tray-icon mx-auto"><i class="fas fa-phone"></i></div>
                    <p class="text-sm font-bold text-[var(--case-felt)]">1-800-JEWEL-TAG</p>
                    <p class="text-[rgba(250,246,238,0.5)] text-xs">Mon–Fri, 8am–8pm CT</p>
                </div>
                <div class="text-center">
                    <div class="tray-icon mx-auto"><i class="fas fa-envelope"></i></div>
                    <p class="text-sm font-bold text-[var(--case-felt)]">info@jeweltag.us</p>
                    <p class="text-[rgba(250,246,238,0.5)] text-xs">Reply within 1 business day</p>
                </div>
                <div class="text-center">
                    <div class="tray-icon mx-auto"><i class="fas fa-comments"></i></div>
                    <p class="text-sm font-bold text-[var(--case-felt)]">Live Chat</p>
                    <p class="text-[rgba(250,246,238,0.5)] text-xs">Bottom right corner</p>
                </div>
            </div>

            <div class="max-w-xl mx-auto bg-[var(--case-felt)] rounded-2xl p-8 shadow-2xl">
                <form id="contact-form" class="space-y-4">
                    <input type="text" placeholder="Your name" class="input-felt" required>
                    <input type="email" placeholder="Email address" class="input-felt" required>
                    <input type="text" placeholder="Business name" class="input-felt" required>
                    <select class="input-felt" required>
                        <option value="">Select inquiry type</option>
                        <option value="demo">Schedule a Demo</option>
                        <option value="pricing">Pricing Questions</option>
                        <option value="support">Technical Support</option>
                    </select>
                    <textarea placeholder="Your message..." class="input-felt h-28" required></textarea>
                    <button type="submit" class="btn-brass w-full justify-center py-3.5">
                        <i class="fas fa-paper-plane text-xs"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </section>

    <footer class="footer-onyx py-14">
        <div class="container mx-auto px-6 lg:px-10">
            <div class="grid md:grid-cols-4 gap-10 mb-10">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <img src="/jeweltaglogo.png" alt="JewelTag" class="h-9 w-auto">
                    </div>
                    <p class="text-xs leading-relaxed opacity-70">Inventory, POS, and CRM built specifically for jewelry retailers.</p>
                </div>
                <div>
                    <h4 class="text-[var(--case-felt)] font-bold text-sm mb-4">Platform</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#features" class="footer-link">Features</a></li>
                        <li><a href="#pricing" class="footer-link">Pricing</a></li>
                        <li><a href="#demo" class="footer-link">Free Trial</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-[var(--case-felt)] font-bold text-sm mb-4">Company</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#" class="footer-link">About</a></li>
                        <li><a href="#contact" class="footer-link">Contact</a></li>
                        <li><a href="#" class="footer-link">Blog</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-[var(--case-felt)] font-bold text-sm mb-4">Resources</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('docs') }}" class="footer-link">Documentation</a></li>
                        <li><a href="{{ route('api') }}" class="footer-link">API Reference</a></li>
                        <li><a href="{{ route('privacy') }}" class="footer-link">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-6 border-t border-[rgba(184,134,59,0.18)] text-center">
                <p class="text-xs opacity-50">© {{ date('Y') }} The Explorers USA DBA JewelTag. All rights reserved.</p>
                <div class="flex justify-center space-x-4 mt-4 opacity-40">
                    <i class="fab fa-cc-visa text-lg"></i>
                    <i class="fab fa-cc-mastercard text-lg"></i>
                    <i class="fab fa-cc-amex text-lg"></i>
                    <i class="fab fa-cc-paypal text-lg"></i>
                </div>
            </div>
        </div>
    </footer>

    <div class="fixed bottom-6 right-6 z-50">
        <button id="chat-toggle" class="w-14 h-14 rounded-full flex items-center justify-center text-[var(--onyx)] shadow-lg hover:shadow-xl transition-all" style="background: linear-gradient(160deg, var(--brass-bright), var(--brass-dim));">
            <i class="fas fa-comment text-xl"></i>
        </button>

        <div id="chat-window" class="absolute bottom-20 right-0 w-80 bg-white rounded-xl shadow-2xl hidden border border-[rgba(33,28,22,0.08)]">
            <div class="p-4 border-b border-[rgba(33,28,22,0.08)]">
                <div class="flex items-center">
                    <div class="tray-icon mr-3" style="width:40px;height:40px;margin-bottom:0;">
                        <i class="fas fa-headset text-sm"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm">JewelTag Support</h4>
                        <p class="text-xs text-[var(--ink-soft)]">We're online</p>
                    </div>
                </div>
            </div>
            <div class="p-4 h-64 overflow-y-auto">
                <div class="bg-[var(--case-felt)] rounded-lg p-3 max-w-xs mb-4 text-xs">
                    Hello! How can we help you today?
                </div>
                <div class="text-center">
                    <button class="px-3 py-2 rounded-lg text-xs font-semibold mr-2" style="background: rgba(184,134,59,0.1); color: var(--brass-dim);">Pricing</button>
                    <button class="px-3 py-2 rounded-lg text-xs font-semibold" style="background: rgba(184,134,59,0.1); color: var(--brass-dim);">Demo</button>
                </div>
            </div>
            <div class="p-4 border-t border-[rgba(33,28,22,0.08)]">
                <div class="flex">
                    <input type="text" placeholder="Type your message..." class="flex-1 border border-[rgba(33,28,22,0.14)] rounded-l-lg px-4 py-2 text-xs focus:outline-none">
                    <button class="text-[var(--onyx)] px-4 rounded-r-lg text-sm" style="background: var(--brass-bright);">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <button id="back-to-top" class="fixed bottom-24 right-6 w-12 h-12 bg-white border border-[rgba(33,28,22,0.1)] rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transition-all hidden z-40">
        <i class="fas fa-chevron-up text-[var(--ink-soft)]"></i>
    </button>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 700, once: true, offset: 40 });

        document.getElementById('mobile-menu-button').addEventListener('click', function () {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        document.getElementById('chat-toggle').addEventListener('click', function () {
            document.getElementById('chat-window').classList.toggle('hidden');
        });

        const backToTop = document.getElementById('back-to-top');
        window.addEventListener('scroll', function () {
            backToTop.classList.toggle('hidden', window.pageYOffset <= 300);
        });
        backToTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        window.toggleFAQ = function (number) {
            const faq = document.getElementById('faq-' + number);
            const icon = event.currentTarget.querySelector('i');
            faq.classList.toggle('hidden');
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        };

        document.getElementById('demo-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const inputs = this.querySelectorAll('input');
            const payload = new FormData();
            payload.append('name', inputs[0].value + ' ' + inputs[1].value);
            payload.append('email', inputs[2].value);
            payload.append('business', inputs[3].value);
            payload.append('type', 'Free Trial Request');
            payload.append('message', 'Requested a 30-day free trial.');
            payload.append('_token', '{{ csrf_token() }}');

            fetch('/contact', { method: 'POST', body: payload })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert("Thank you! We'll be in touch within 1 business day.");
                        this.reset();
                    } else {
                        alert('Something went wrong. Please email info@jeweltag.us directly.');
                    }
                });
        });

        document.getElementById('contact-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const inputs = this.querySelectorAll('input, select, textarea');
            const payload = new FormData();
            payload.append('name', inputs[0].value);
            payload.append('email', inputs[1].value);
            payload.append('business', inputs[2].value);
            payload.append('type', inputs[3].value);
            payload.append('message', inputs[4].value);
            payload.append('_token', '{{ csrf_token() }}');

            fetch('/contact', { method: 'POST', body: payload })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert("Message sent! We'll respond within 1 business day.");
                        this.reset();
                    } else {
                        alert('Something went wrong. Please email info@jeweltag.us directly.');
                    }
                });
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    document.getElementById('mobile-menu')?.classList.add('hidden');
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>

</html>