<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>JewelTag.us | Ultimate Jewelry Management</title>
  <meta name="description" content="JewelTag is the inventory management, point-of-sale, and CRM platform built specifically for jewelry retailers — RFID tracking, repair orders, layaway, and more.">
  
  <!-- Tailwind + Fonts + Icons -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
  <style>
    :root {
      --primary-gold: #d97706;
      --secondary-gold: #fbbf24;
      --dark-gold: #b45309;
      --deep-sapphire: #0A2540;
      --royal-blue: #1E3A8A;
      --navy-nav: #06111F;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Outfit', sans-serif; color:#1e293b; overflow-x:hidden; background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%); }
    .playfair { font-family:'Playfair Display',serif; }
    .mono { font-family:'Space Mono', monospace; }

    .gold-gradient { background:linear-gradient(135deg,var(--primary-gold),var(--dark-gold)); }
    .gold-gradient-text {
      background:linear-gradient(135deg,var(--primary-gold),var(--secondary-gold),var(--dark-gold));
      -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
    }
    .diamond-text {
      background:linear-gradient(135deg,#ffffff,#e2e8f0,#cbd5e1);
      -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
    }

    /* ── NAV ── */
    .nav-luxury {
      background: rgba(6,17,31,0.96);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(217,119,6,0.3);
      box-shadow: 0 4px 24px rgba(0,0,0,0.35);
    }
    .nav-link { color:rgba(255,255,255,0.75); font-weight:600; font-size:0.84rem; letter-spacing:0.02em; transition:color 200ms; position:relative; }
    .nav-link::after { content:''; position:absolute; left:0; bottom:-4px; width:0; height:1.5px; background:var(--primary-gold); transition:width 220ms; }
    .nav-link:hover { color:#fbbf24; }
    .nav-link:hover::after { width:100%; }

    /* ── HERO ── */
    .hero-luxury {
      background:linear-gradient(135deg,var(--deep-sapphire) 0%,var(--royal-blue) 100%);
      position:relative; overflow:hidden; min-height:100vh; display:flex; align-items:center; width:100%;
    }
    .hero-luxury::before {
      content:''; position:absolute; width:600px; height:600px;
      background:radial-gradient(circle,rgba(217,119,6,0.18) 0%,transparent 70%);
      top:-300px; right:-300px; border-radius:50%;
    }
    .hero-luxury::after {
      content:''; position:absolute; width:500px; height:500px;
      background:radial-gradient(circle,rgba(59,130,246,0.15) 0%,transparent 70%);
      bottom:-250px; left:-250px; border-radius:50%;
    }
    .diamond-grid {
      position:absolute; width:100%; height:100%;
      background-image:linear-gradient(rgba(255,255,255,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.03) 1px,transparent 1px);
      background-size:50px 50px; z-index:0;
    }

    /* ── SAAS FLOATING DASHBOARD ANIMATIONS ── */
    .dashboard-container {
      perspective: 1000px;
      transform-style: preserve-3d;
    }

    .main-dashboard {
      animation: heroSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      opacity: 0;
      transform: translateY(40px) scale(0.95) rotateX(5deg);
      box-shadow: 0 30px 60px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.1);
    }

    @keyframes heroSlideUp {
      to { opacity: 1; transform: translateY(0) scale(1) rotateX(0deg); }
    }

    /* ── LIVE PRODUCT DEMO VIDEO (replaces the guessed screenshot zoom) ──
       Real footage of the app, framed like a live browser window, with a
       progress bar and timed caption chips that pop up in sync with what's
       actually happening on screen — no more guessed coordinates. */
    .video-stage { position:absolute; inset:0; overflow:hidden; background:#0b1220; }
    .pos-video {
      position:absolute; inset:0; width:100%; height:100%;
      object-fit: contain; background:#0b1220;
    }
    .video-vignette {
      position:absolute; inset:0; z-index:4; pointer-events:none;
      background:linear-gradient(180deg, rgba(0,0,0,0.55) 0%, transparent 20%, transparent 78%, rgba(0,0,0,0.45) 100%);
    }
    .video-topbar {
      position:absolute; top:0; left:0; right:0; z-index:6;
      display:flex; align-items:center; gap:8px;
      padding:12px 14px;
      background:linear-gradient(180deg, rgba(6,17,31,0.85), transparent);
    }
    .video-dot { width:9px; height:9px; border-radius:50%; }
    .video-url {
      margin-left:8px; font-family:'JetBrains Mono',monospace; font-size:10.5px;
      color:rgba(255,255,255,0.55); background:rgba(255,255,255,0.08);
      padding:4px 10px; border-radius:6px; letter-spacing:.02em;
    }
    .video-live {
      margin-left:auto; display:flex; align-items:center; gap:6px;
      font-size:10.5px; font-weight:700; color:#f87171; letter-spacing:.05em; text-transform:uppercase;
    }
    .video-live-dot { width:6px; height:6px; border-radius:50%; background:#f87171; animation: liveBlink 1.6s ease-in-out infinite; }
    @keyframes liveBlink { 0%,100% { opacity:1; } 50% { opacity:.25; } }
    .video-mute-btn {
      margin-left:10px; width:28px; height:28px; border-radius:50%;
      background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2);
      color:#fff; font-size:11.5px; display:flex; align-items:center; justify-content:center;
      cursor:pointer; transition:background .2s ease;
    }
    .video-mute-btn:hover { background:rgba(255,255,255,0.24); }

    .video-caption {
      position:absolute; left:16px; bottom:32px; z-index:6; max-width:82%;
      opacity:0; transform:translateY(8px);
      transition: opacity 0.35s ease, transform 0.35s ease;
    }
    .video-caption.show { opacity:1; transform:translateY(0); }
    .video-caption-text {
      display:inline-block; background:#0f172a; color:#fbbf24;
      font-size:12px; font-weight:700; letter-spacing:.01em;
      padding:7px 13px; border-radius:8px;
      border:1px solid rgba(251,191,36,0.4);
      box-shadow:0 10px 24px rgba(0,0,0,0.45);
    }

    .video-progress { position:absolute; left:0; right:0; bottom:0; height:3px; background:rgba(255,255,255,0.15); z-index:6; }
    .video-progress-fill { height:100%; width:0%; background:linear-gradient(90deg, var(--secondary-gold), var(--primary-gold)); }

    .video-playfallback {
      position:absolute; inset:0; z-index:8; display:none;
      align-items:center; justify-content:center; background:rgba(6,17,31,0.55); cursor:pointer;
    }
    .video-playfallback.show { display:flex; }
    .video-playfallback .btn-circle {
      width:64px; height:64px; border-radius:50%;
      background:linear-gradient(135deg, var(--primary-gold), var(--dark-gold));
      color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px;
      box-shadow:0 12px 30px rgba(217,119,6,0.5);
    }

    @media (prefers-reduced-motion:reduce) {
      .main-dashboard { animation:none; opacity:1; transform:none; }
      .video-caption { transition:none; }
      .video-live-dot { animation:none; }
    }

    /* Buttons */
    .btn-luxury {
      background:linear-gradient(135deg,var(--primary-gold),var(--dark-gold)); color:white;
      padding:16px 36px; border-radius:50px; font-weight:700; font-size:15px; letter-spacing:.5px;
      transition:all .4s cubic-bezier(.4,0,.2,1); border:none; position:relative; overflow:hidden;
      display:inline-flex; align-items:center; justify-content:center;
    }
    .btn-luxury::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent); transition:left .7s; }
    .btn-luxury:hover { transform:translateY(-4px) scale(1.04); box-shadow:0 20px 40px rgba(217,119,6,0.35); }
    .btn-luxury:hover::before { left:100%; }
    .btn-outline-light { border:2px solid rgba(255,255,255,0.5); color:white; padding:16px 36px; border-radius:50px; font-weight:700; font-size:15px; transition:all .4s; display:inline-flex; align-items:center; justify-content:center; }
    .btn-outline-light:hover { background:rgba(255,255,255,0.12); border-color:rgba(255,255,255,0.9); transform:translateY(-3px); }

    /* Stats */
    .stat-number { font-size:4rem; font-weight:900; background:linear-gradient(135deg,var(--primary-gold),var(--secondary-gold)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; line-height:1; }

    /* Cards */
    .luxury-card {
      background:rgba(255,255,255,0.96); backdrop-filter:blur(20px);
      border:1px solid rgba(217,119,6,0.18);
      box-shadow:0 20px 40px rgba(0,0,0,0.06),0 1px 0 rgba(255,255,255,0.8) inset;
      transition:all .45s cubic-bezier(.4,0,.2,1); border-radius:24px; position:relative; overflow:hidden;
    }
    .luxury-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg,var(--primary-gold),var(--secondary-gold)); border-radius:24px 24px 0 0; }
    .luxury-card:hover { transform:translateY(-14px) scale(1.02); box-shadow:0 40px 80px rgba(0,0,0,0.1),0 0 0 1px rgba(217,119,6,0.3),0 0 40px rgba(217,119,6,0.1); }

    /* Pricing */
    .pricing-luxury { background:white; border-radius:28px; padding:44px 36px; border:2px solid rgba(217,119,6,0.2); transition:all .4s; position:relative; overflow:hidden; }
    .pricing-luxury.featured { border-color:var(--primary-gold); transform:scale(1.04); box-shadow:0 40px 80px rgba(217,119,6,0.15); }
    .pricing-luxury.featured::before { content:'MOST POPULAR'; position:absolute; top:26px; right:-34px; background:linear-gradient(135deg,var(--primary-gold),var(--dark-gold)); color:white; padding:8px 50px; font-size:12px; font-weight:800; letter-spacing:1px; transform:rotate(45deg); }

    /* Patterns & Footer */
    .jewel-pattern-bg { background-color:#f8fafc; background-image:radial-gradient(circle at 10% 20%,rgba(217,119,6,0.05) 0%,transparent 20%),radial-gradient(circle at 90% 80%,rgba(59,130,246,0.05) 0%,transparent 20%); }
    .partner-card { background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%); border-radius:24px; padding:40px; color:white; border:1px solid rgba(59,130,246,0.3); box-shadow:0 20px 40px rgba(0,0,0,0.2); transition:all .4s; }
    .partner-card:hover { transform:translateY(-10px); border-color:rgba(59,130,246,0.6); box-shadow:0 30px 60px rgba(0,0,0,0.3),0 0 30px rgba(59,130,246,0.2); }
    .footer-luxury { background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%); color:white; position:relative; overflow:hidden; }
    .footer-luxury::before { content:''; position:absolute; width:400%; height:400%; background:radial-gradient(circle,rgba(217,119,6,0.05) 1px,transparent 1px); background-size:40px 40px; transform:rotate(45deg); top:-150%; left:-150%; z-index:0; opacity:0.4; }

    /* Form */
    .luxury-gradient-bg { background:linear-gradient(135deg,var(--deep-sapphire) 0%,var(--royal-blue) 100%); }
    .form-luxury { border:2px solid rgba(217,119,6,0.2); border-radius:16px; padding:16px 20px; transition:all .3s; width:100%; background:white; font-size:15px; }
    .form-luxury:focus { outline:none; border-color:var(--primary-gold); box-shadow:0 0 0 4px rgba(217,119,6,0.1); }

    @media (max-width:1024px) { 
      .pricing-luxury.featured { transform:scale(1); }
    }
  </style>
</head>
<body class="antialiased relative">

  <!-- Floating background diamonds -->
  <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
    <div class="floating-diamond" style="width:80px;height:80px;top:10%;left:5%;animation-delay:0s;"></div>
    <div class="floating-diamond" style="width:60px;height:60px;top:20%;right:10%;animation-delay:1s;"></div>
    <div class="floating-diamond" style="width:100px;height:100px;bottom:30%;left:15%;animation-delay:2s;"></div>
    <div class="floating-diamond" style="width:70px;height:70px;bottom:15%;right:5%;animation-delay:3s;"></div>
  </div>

  <!-- ═══ NAV ═══ -->
  <nav class="nav-luxury fixed w-full z-50 py-4">
    <div class="container mx-auto px-6 lg:px-8">
      <div class="flex justify-between items-center">
        <a href="/" class="flex items-center">
          <img src="/jeweltaglogo.png" alt="JewelTag" class="h-10 w-auto" onerror="this.src='https://via.placeholder.com/120x40?text=JewelTag'">
        </a>
        <div class="hidden lg:flex items-center space-x-8">
          <a href="#features" class="nav-link">Features</a>
          <a href="#gallery" class="nav-link">Gallery</a>
          <a href="#solutions" class="nav-link">Solutions</a>
          <a href="#pricing" class="nav-link">Pricing</a>
          <a href="#testimonials" class="nav-link">Success Stories</a>
          <a href="/admin/login" class="px-5 py-2.5 border border-white/30 text-white/80 font-bold rounded-xl hover:border-yellow-400/60 hover:text-yellow-300 transition-all text-sm uppercase tracking-wider">
            <i class="fas fa-lock mr-2 text-xs"></i>Staff Login
          </a>
          <a href="#demo" class="px-6 py-3 btn-luxury text-sm uppercase tracking-wider">
            <i class="fas fa-crown mr-2"></i> Free Demo
          </a>
        </div>
        <button id="mobile-menu-button" class="lg:hidden text-white text-2xl">
          <i class="fas fa-bars"></i>
        </button>
      </div>
      <div id="mobile-menu" class="lg:hidden hidden mt-6 pb-4 border-t border-white/10 pt-4">
        <div class="flex flex-col space-y-4">
          <a href="#features" class="text-white/70 font-semibold py-2 border-b border-white/10">Features</a>
          <a href="#gallery" class="text-white/70 font-semibold py-2 border-b border-white/10">Gallery</a>
          <a href="#solutions" class="text-white/70 font-semibold py-2 border-b border-white/10">Solutions</a>
          <a href="#pricing" class="text-white/70 font-semibold py-2 border-b border-white/10">Pricing</a>
          <a href="#testimonials" class="text-white/70 font-semibold py-2 border-b border-white/10">Success Stories</a>
          <a href="/admin/login" class="w-full px-6 py-3 border border-white/30 text-white font-bold rounded-xl text-center mt-2">Staff Login</a>
          <a href="#demo" class="w-full py-3 btn-luxury text-center text-sm">Free Demo</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- ═══ HERO ═══ -->
  <section class="hero-luxury pt-32 pb-20 lg:pt-40 lg:pb-32 w-full">
    <div class="diamond-grid"></div>
    <div class="container mx-auto px-6 lg:px-8 relative z-10 w-full">
      <div class="flex flex-col lg:flex-row items-center gap-10 lg:gap-16 w-full">

        <!-- Left: copy -->
        <div class="lg:w-1/2 mb-10 lg:mb-0 w-full" data-aos="fade-right">
          <div class="inline-flex items-center px-5 py-2 rounded-full bg-white/10 backdrop-blur-sm mb-6 border border-white/20">
            <span class="w-2 h-2 rounded-full bg-amber-400 mr-2 animate-pulse"></span>
            <span class="text-white/90 text-sm font-semibold tracking-wider">A Leading Jewelry POS & CRM Application</span>
          </div>
          <h1 class="text-5xl lg:text-7xl font-black text-white mb-6 playfair leading-tight">
            The <span class="gold-gradient-text">Ultimate Platform</span> for Jewelry Retails
          </h1>
          <p class="text-xl lg:text-2xl text-white/80 mb-8 leading-relaxed">
            Transform your jewelry business with the world's most advanced inventory management, POS, CRM, and business intelligence platform designed exclusively for Jewelry retailers.
          </p>
          <div class="flex flex-col sm:flex-row gap-4">
            <a href="#demo" class="btn-luxury text-center"><i class="fas fa-play-circle mr-3"></i> Start 30-Day Trial</a>
            <a href="/admin" class="btn-outline-light text-center"><i class="fas fa-gem mr-3"></i> Enter Platform</a>
          </div>
          <div class="mt-10 flex items-center">
            <div class="flex -space-x-3">
              <div class="w-12 h-12 rounded-full gold-gradient flex items-center justify-center text-white text-sm font-bold border-2 border-white">JD</div>
              <div class="w-12 h-12 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-bold border-2 border-white">SR</div>
              <div class="w-12 h-12 rounded-full bg-emerald-600 flex items-center justify-center text-white text-sm font-bold border-2 border-white">MK</div>
              <div class="w-12 h-12 rounded-full bg-amber-600 flex items-center justify-center text-white text-sm font-bold border-2 border-white">+</div>
            </div>
            <div class="ml-4">
              <p class="text-white/80 text-sm"><span class="text-white font-bold">750+</span> jewelers transformed</p>
              <div class="flex items-center mt-1">
                <i class="fas fa-star text-yellow-400 text-sm"></i><i class="fas fa-star text-yellow-400 text-sm"></i><i class="fas fa-star text-yellow-400 text-sm"></i><i class="fas fa-star text-yellow-400 text-sm"></i><i class="fas fa-star text-yellow-400 text-sm"></i>
                <span class="text-white/60 text-xs ml-2">4.95/5 (428 reviews)</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Live product demo video, framed like a live app window -->
        <div class="lg:w-1/2 w-full relative flex justify-center items-center mt-10 lg:mt-0 px-4 sm:px-10 lg:px-0">
          
          <!-- Subtle glowing background aura -->
          <div class="absolute inset-0 bg-blue-500/20 blur-[100px] rounded-full pointer-events-none"></div>

          <div class="dashboard-container relative w-full max-w-2xl mx-auto">
            
            <div class="main-dashboard relative rounded-2xl overflow-hidden bg-gray-900 z-10 w-full pb-[60%]">
               <div class="video-stage" id="video-stage">
                  <video id="pos-video" class="pos-video" autoplay muted loop playsinline preload="metadata">
                    <source src="/jewelrypos.mp4" type="video/mp4">
                  </video>

                  <div class="video-vignette"></div>

                  <div class="video-topbar">
                    <span class="video-dot" style="background:#f87171;"></span>
                    <span class="video-dot" style="background:#fbbf24;"></span>
                    <span class="video-dot" style="background:#4ade80;"></span>
                    <span class="video-url">app.jeweltag.us</span>
                    <span class="video-live"><span class="video-live-dot"></span>Live Demo</span>
                    <button class="video-mute-btn" id="video-mute-btn" aria-label="Toggle sound" type="button">
                      <i class="fas fa-volume-xmark" id="mute-icon"></i>
                    </button>
                  </div>

                  <div class="video-caption" id="video-caption">
                    <span class="video-caption-text" id="video-caption-text"></span>
                  </div>

                  <div class="video-progress"><div class="video-progress-fill" id="video-progress-fill"></div></div>

                  <!-- Shown only if the browser blocks autoplay -->
                  <div class="video-playfallback" id="video-playfallback">
                    <div class="btn-circle"><i class="fas fa-play"></i></div>
                  </div>
               </div>
               <!-- Dark overlay border for sleek framing -->
               <div class="absolute inset-0 border-[3px] border-white/10 rounded-2xl pointer-events-none mix-blend-overlay z-20"></div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ═══ STATS ═══ -->
  <section class="py-16 bg-white">
    <div class="container mx-auto px-6 lg:px-8">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="text-center"><div class="stat-number">50+</div><p class="text-gray-600 mt-2 text-sm font-semibold uppercase tracking-wider">Premium Jewelers</p></div>
        <div class="text-center">
            <div class="stat-number" style="font-size: 3rem;">POS+CRM</div>
            <p class="text-gray-600 mt-2 text-sm font-semibold uppercase tracking-wider">POS, CRM and SMM</p>
        </div>
        <div class="text-center"><div class="stat-number">99.5%</div><p class="text-gray-600 mt-2 text-sm font-semibold uppercase tracking-wider">Target Uptime</p></div>
        <div class="text-center"><div class="stat-number">24/7</div><p class="text-gray-600 mt-2 text-sm font-semibold uppercase tracking-wider">Premium Support</p></div>
      </div>
    </div>
  </section>

  <!-- ═══ FEATURES ═══ -->
  <section id="features" class="py-20 jewel-pattern-bg">
    <div class="container mx-auto px-6 lg:px-8">
      <div class="text-center mb-12" data-aos="fade-up">
        <h2 class="text-4xl lg:text-5xl font-bold mb-4 playfair"><span style="color:var(--deep-sapphire)">Unparalleled Features for</span> <span class="gold-gradient-text">Luxury Retail</span></h2>
        <p class="text-lg text-gray-600 max-w-3xl mx-auto">Designed exclusively for jewelry businesses, our comprehensive platform transforms every aspect of your operations.</p>
      </div>
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="luxury-card p-7" data-aos="fade-up" data-aos-delay="0">
          <div class="w-14 h-14 gold-gradient rounded-xl flex items-center justify-center text-white text-2xl mb-5"><i class="fas fa-boxes-stacked"></i></div>
          <h3 class="text-xl font-bold mb-3">Intelligent Inventory</h3>
          <p class="text-gray-600 text-sm mb-4">AI-powered tracking with RFID, barcode scanning, and multi-location management.</p>
          <ul class="space-y-2 text-sm"><li class="flex items-center"><i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>GIA Certificate Integration</li><li class="flex items-center"><i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>Automated Reordering</li></ul>
        </div>
        <div class="luxury-card p-7" data-aos="fade-up" data-aos-delay="80">
          <div class="w-14 h-14 gold-gradient rounded-xl flex items-center justify-center text-white text-2xl mb-5"><i class="fas fa-cash-register"></i></div>
          <h3 class="text-xl font-bold mb-3">Amazing Jewelry POS Suite</h3>
          <p class="text-gray-600 text-sm mb-4">Complete point-of-sale with integrated payments, layaway, and commission tracking.</p>
          <ul class="space-y-2 text-sm"><li class="flex items-center"><i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>Custom Invoice Templates</li><li class="flex items-center"><i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>Multiple stores Support</li></ul>
        </div>
        <div class="luxury-card p-7" data-aos="fade-up" data-aos-delay="160">
          <div class="w-14 h-14 gold-gradient rounded-xl flex items-center justify-center text-white text-2xl mb-5"><i class="fas fa-chart-line"></i></div>
          <h3 class="text-xl font-bold mb-3">Business Intelligence & Reporting</h3>
          <p class="text-gray-600 text-sm mb-4">Real-time dashboards, sales forecasting, and profit margin analysis.</p>
          <ul class="space-y-2 text-sm"><li class="flex items-center"><i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>Predictive Analytics</li><li class="flex items-center"><i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>ROI Optimization</li></ul>
        </div>
        <div class="luxury-card p-7" data-aos="fade-up" data-aos-delay="0">
          <div class="w-14 h-14 gold-gradient rounded-xl flex items-center justify-center text-white text-2xl mb-5"><i class="fas fa-users"></i></div>
          <h3 class="text-xl font-bold mb-3">AI Enabled CRM</h3>
          <p class="text-gray-600 text-sm mb-4">Client profiles, purchase history, and automated marketing, image generation, text generation.</p>
          <ul class="space-y-2 text-sm"><li class="flex items-center"><i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>Anniversary Automation</li><li class="flex items-center"><i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>Wishlist Management</li></ul>
        </div>
        <div class="luxury-card p-7" data-aos="fade-up" data-aos-delay="80">
          <div class="w-14 h-14 gold-gradient rounded-xl flex items-center justify-center text-white text-2xl mb-5"><i class="fas fa-screwdriver-wrench"></i></div>
          <h3 class="text-xl font-bold mb-3">Repair Management</h3>
          <p class="text-gray-600 text-sm mb-4">Digital work orders, status tracking, and customer notifications.</p>
          <ul class="space-y-2 text-sm"><li class="flex items-center"><i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>Photo Documentation</li><li class="flex items-center"><i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>Warranty Tracking</li></ul>
        </div>
        <div class="luxury-card p-7" data-aos="fade-up" data-aos-delay="160">
          <div class="w-14 h-14 gold-gradient rounded-xl flex items-center justify-center text-white text-2xl mb-5"><i class="fas fa-shop"></i></div>
          <h3 class="text-xl font-bold mb-3">E-Commerce Sync</h3>
          <p class="text-gray-600 text-sm mb-4">Connect Shopify or WooCommerce directly to your floor inventory without double-counting stock.</p>
          <ul class="space-y-2 text-sm"><li class="flex items-center"><i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>Shopify &amp; WooCommerce</li><li class="flex items-center"><i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>Live Stock Sync</li></ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══ GALLERY ═══ -->
  <section id="gallery" class="py-20 bg-white">
    <div class="container mx-auto px-6 lg:px-8">
      <div class="text-center mb-12" data-aos="fade-up">
        <h2 class="text-4xl lg:text-5xl font-bold mb-4 playfair"><span style="color:var(--deep-sapphire)">All In</span> <span class="gold-gradient-text">One</span></h2>
        <p class="text-lg text-gray-600 max-w-3xl mx-auto">A Complete Tech Solution for Jewelry Retailers.</p>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4 lg:gap-6">
        <div class="group relative overflow-hidden rounded-2xl aspect-square shadow-lg" data-aos="zoom-in" data-aos-delay="0">
          <img src="/jeweltag1.png?q=80&w=800&auto=format&fit=crop" alt="Diamond Ring" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
            <span class="text-amber-400 text-xs font-bold uppercase tracking-wider mb-1">Technical Variation</span>
            <span class="text-white font-semibold tracking-wider text-lg">POS, CRM, SMM, Repair, RFID</span>
          </div>
        </div>
        <div class="group relative overflow-hidden rounded-2xl aspect-square shadow-lg" data-aos="zoom-in" data-aos-delay="100">
          <img src="/jeweltag2.png?q=80&w=800&auto=format&fit=crop" alt="Luxury Necklace" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
            <span class="text-amber-400 text-xs font-bold uppercase tracking-wider mb-1">We will take care of your product</span>
            <span class="text-white font-semibold tracking-wider text-lg">You can focus on Sell With Feeling</span>
          </div>
        </div>
        <div class="group relative overflow-hidden rounded-2xl aspect-square shadow-lg" data-aos="zoom-in" data-aos-delay="200">
          <img src="/jeweltag3.png?q=80&w=800&auto=format&fit=crop" alt="Luxury Watch" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
            <span class="text-amber-400 text-xs font-bold uppercase tracking-wider mb-1">Application that shines</span>
            <span class="text-white font-semibold tracking-wider text-lg">Like The Diamond's Sparkle</span>
          </div>
        </div>
        <div class="group relative overflow-hidden rounded-2xl aspect-square shadow-lg" data-aos="zoom-in" data-aos-delay="0">
         <img src="/jeweltag4.png?q=80&w=800&auto=format&fit=crop" alt="Diamond Earrings" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
            <span class="text-amber-400 text-xs font-bold uppercase tracking-wider mb-1">Product Categorization</span>
            <span class="text-white font-semibold tracking-wider text-lg">Department, type, weight, color</span>
          </div>
        </div>
        <div class="group relative overflow-hidden rounded-2xl aspect-square shadow-lg" data-aos="zoom-in" data-aos-delay="100">
          <img src="/jeweltag5.png?q=80&w=800&auto=format&fit=crop" alt="Precious Stones" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
            <span class="text-amber-400 text-xs font-bold uppercase tracking-wider mb-1">JewelTag gives you a Smile</span>
            <span class="text-white font-semibold tracking-wider text-lg">We are your technical problem solver</span>
          </div>
        </div>
        <div class="group relative overflow-hidden rounded-2xl aspect-square shadow-lg" data-aos="zoom-in" data-aos-delay="200">
          <img src="/jeweltag6.png?q=80&w=800&auto=format&fit=crop" alt="Vintage Rings" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
            <span class="text-amber-400 text-xs font-bold uppercase tracking-wider mb-1">Third Party App Integration</span>
            <span class="text-white font-semibold tracking-wider text-lg">Peace of Mind</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══ PARTNER ═══ -->
  <section class="py-20 jewel-pattern-bg">
    <div class="container mx-auto px-6 lg:px-8">
      <div class="partner-card max-w-4xl mx-auto text-center">
        <div class="w-20 h-20 gold-gradient rounded-2xl flex items-center justify-center mx-auto mb-6"><i class="fas fa-brain text-white text-3xl"></i></div>
        <h3 class="text-2xl font-bold mb-3">Strategic Partner: Creative AI Network</h3>
        <p class="text-white/80 mb-6 max-w-2xl mx-auto">Enhancing JewelTag with cutting-edge AI, custom software development, and digital transformation expertise.</p>
        <div class="flex flex-wrap justify-center gap-3 mb-6">
          <span class="px-4 py-2 bg-white/10 rounded-full text-white/90 text-sm">Finance</span>
          <span class="px-4 py-2 bg-white/10 rounded-full text-white/90 text-sm">Healthcare</span>
          <span class="px-4 py-2 bg-white/10 rounded-full text-white/90 text-sm">E-commerce</span>
          <span class="px-4 py-2 bg-white/10 rounded-full text-white/90 text-sm">Luxury Retail</span>
        </div>
        <a href="https://creativeainetworks.com" target="_blank" class="inline-flex items-center text-amber-400 hover:text-amber-300 font-semibold">Visit Partner Website <i class="fas fa-arrow-right ml-2 text-sm"></i></a>
      </div>
    </div>
  </section>

  <!-- ═══ IMPLEMENTATION ═══ -->
  <section id="solutions" class="py-20 bg-white border-y border-gray-100">
    <div class="container mx-auto px-6 lg:px-8">
      <div class="text-center mb-12">
        <h2 class="text-4xl lg:text-5xl font-bold mb-4 playfair"><span style="color:var(--deep-sapphire)">Implementation</span> <span class="gold-gradient-text">Excellence</span></h2>
        <p class="text-lg text-gray-600">Your journey to jewelry management excellence in 4 simple steps.</p>
      </div>
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 max-w-5xl mx-auto">
        <div class="text-center" data-aos="fade-up" data-aos-delay="0"><div class="w-16 h-16 gold-gradient rounded-full flex items-center justify-center text-white text-xl font-bold mx-auto mb-4">01</div><h3 class="font-bold mb-2">Discovery</h3><p class="text-gray-600 text-sm">In-depth analysis of your business needs</p><span class="text-xs text-amber-600 font-semibold mt-2 inline-block">1–3 Days</span></div>
        <div class="text-center" data-aos="fade-up" data-aos-delay="80"><div class="w-16 h-16 gold-gradient rounded-full flex items-center justify-center text-white text-xl font-bold mx-auto mb-4">02</div><h3 class="font-bold mb-2">Migration</h3><p class="text-gray-600 text-sm">White-glove data transfer &amp; setup</p><span class="text-xs text-amber-600 font-semibold mt-2 inline-block">3–7 Days</span></div>
        <div class="text-center" data-aos="fade-up" data-aos-delay="160"><div class="w-16 h-16 gold-gradient rounded-full flex items-center justify-center text-white text-xl font-bold mx-auto mb-4">03</div><h3 class="font-bold mb-2">Training</h3><p class="text-gray-600 text-sm">Personalized team onboarding</p><span class="text-xs text-amber-600 font-semibold mt-2 inline-block">5–10 Days</span></div>
        <div class="text-center" data-aos="fade-up" data-aos-delay="240"><div class="w-16 h-16 gold-gradient rounded-full flex items-center justify-center text-white text-xl font-bold mx-auto mb-4">04</div><h3 class="font-bold mb-2">Go Live</h3><p class="text-gray-600 text-sm">Launch with dedicated support</p><span class="text-xs text-amber-600 font-semibold mt-2 inline-block">Ongoing</span></div>
      </div>
    </div>
  </section>

  <!-- ═══ TESTIMONIALS ═══ -->
  <section id="testimonials" class="py-20 jewel-pattern-bg">
    <div class="container mx-auto px-6 lg:px-8">
      <div class="text-center mb-12"><h2 class="text-4xl lg:text-5xl font-bold mb-4 playfair"><span class="gold-gradient-text">Success Stories</span></h2><p class="text-lg text-gray-600">Trusted by leading jewelers worldwide</p></div>
      <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
        <div class="bg-white p-7 rounded-2xl shadow-sm border border-amber-100" data-aos="fade-up" data-aos-delay="0">
          <div class="flex items-center mb-4"><div class="w-12 h-12 gold-gradient rounded-full flex items-center justify-center text-white mr-3 font-bold">SC</div><div><h4 class="font-bold">Sarah Bull</h4><p class="text-xs text-gray-500">Manager, Jewelry Store in Texas</p></div></div>
          <p class="text-gray-600 text-sm italic">"Inventory reconciliation reduced from 3 weeks to 2 days. Easy to learn and detailed oriented application"</p>
          <div class="mt-3 text-amber-600 font-bold text-sm">Time Saving</div>
        </div>
        <div class="bg-white p-7 rounded-2xl shadow-sm border border-amber-100" data-aos="fade-up" data-aos-delay="80">
          <div class="flex items-center mb-4"><div class="w-12 h-12 gold-gradient rounded-full flex items-center justify-center text-white mr-3 font-bold">MR</div><div><h4 class="font-bold">Michael Rodriguez</h4><p class="text-xs text-gray-500">Manager, Jewelry Store in New Mexico</p></div></div>
          <p class="text-gray-600 text-sm italic">"Repair admin time reduced by 70%. Customer satisfaction increased from 82% to 98%."</p>
          <div class="mt-3 text-amber-600 font-bold text-sm">Easy Repair Tracking</div>
        </div>
        <div class="bg-white p-7 rounded-2xl shadow-sm border border-amber-100" data-aos="fade-up" data-aos-delay="160">
          <div class="flex items-center mb-4"><div class="w-12 h-12 gold-gradient rounded-full flex items-center justify-center text-white mr-3 font-bold">JW</div><div><h4 class="font-bold">James Wilson</h4><p class="text-xs text-gray-500">Staff, Diamond Square</p></div></div>
          <p class="text-gray-600 text-sm italic">"Easy UI. Great CRM and more customer support feature available. Social Media Post and Marketing is in-built with CRM"</p>
          <div class="mt-3 text-amber-600 font-bold text-sm">The Best UI</div>
        </div>
      </div>
    </div>
  </section>

<!-- ═══ PRICING ═══ -->
<section id="pricing" class="py-20 bg-white border-t border-gray-200">
  <div class="container mx-auto px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-4xl lg:text-5xl font-bold mb-4 playfair"><span style="color:var(--deep-sapphire)">Premium Plans for</span> <span class="gold-gradient-text">Every Business</span></h2>
      <p class="text-lg text-gray-600">Transparent pricing, no hidden fees, no long-term contracts.</p>
    </div>
    <div class="grid md:grid-cols-3 gap-7 max-w-5xl mx-auto items-start">
      <!-- Basic -->
      <div class="pricing-luxury" data-aos="fade-up" data-aos-delay="0">
        <h3 class="text-xl font-bold mb-2">JewelTag Basic</h3>
        <p class="text-sm text-gray-500 mb-5">For small jewelry shops</p>
        <div class="mb-5">
          <span class="text-4xl font-bold gold-gradient-text">$299</span>
          <span class="text-gray-500 text-sm">/month</span>
          <div class="mt-1">
            <span class="text-sm text-gray-400 line-through">$499</span>
            <span class="ml-2 inline-block bg-amber-100 text-amber-700 text-xs font-bold px-2 py-0.5 rounded-full">Save $200</span>
          </div>
        </div>
        <ul class="space-y-2 text-sm mb-7">
          <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Up to 1,000 items</li>
          <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Basic POS</li>
          <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Customer management</li>
          <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Repair order tracking</li>
          <li class="flex items-center text-gray-400"><i class="fas fa-times text-gray-300 mr-2 text-xs"></i>RFID &amp; multi-store</li>
        </ul>
        <a href="#demo" class="block text-center border-2 border-amber-500 text-amber-600 font-bold py-3 rounded-full hover:bg-amber-500 hover:text-white transition-all text-sm">Start Free Trial</a>
      </div>

      <!-- Pro -->
      <div class="pricing-luxury featured" data-aos="fade-up" data-aos-delay="80" style="
        background: linear-gradient(145deg, #0A2540 0%, #1E3A8A 60%, #0A2540 100%);
        border: 2px solid #d97706;
        transform: scale(1.06);
        box-shadow: 0 0 0 1px rgba(217,119,6,0.3), 0 40px 80px rgba(10,37,64,0.55), 0 0 60px rgba(217,119,6,0.18);
        position: relative; overflow: hidden; border-radius: 28px; padding: 44px 36px;
      ">
        <div style="position:absolute;inset:0;overflow:hidden;border-radius:26px;pointer-events:none;z-index:0;">
          <div style="position:absolute;top:-50%;left:-80%;width:60%;height:200%;background:linear-gradient(105deg,transparent,rgba(217,119,6,0.1),transparent);animation:card-shimmer 4s ease-in-out 2s infinite;"></div>
        </div>
        <style>
          @keyframes card-shimmer{0%,100%{left:-80%;opacity:0}10%{opacity:1}40%{left:130%;opacity:0}41%,100%{left:130%;opacity:0}}
        </style>
        <div style="position:absolute;top:26px;right:-34px;background:linear-gradient(135deg,#d97706,#b45309);color:white;padding:8px 50px;font-size:12px;font-weight:800;letter-spacing:1px;transform:rotate(45deg);z-index:10;">MOST POPULAR</div>
        <div style="position:absolute;top:16px;left:18px;font-size:18px;opacity:0.5;z-index:1;">✦</div>
        <div style="position:relative;z-index:1;">
          <h3 class="text-xl font-bold mb-1" style="color:#ffffff;">JewelTag Pro + CRM</h3>
          <p class="text-sm mb-5" style="color:rgba(255,255,255,0.55);">The full counter-to-customer system</p>
          <div class="mb-2">
            <span class="text-5xl font-black" style="font-family:'Playfair Display',serif;background:linear-gradient(135deg,#fbbf24,#f59e0b,#d97706);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">$499</span>
            <span style="color:rgba(255,255,255,0.45);font-size:0.9rem;">/month</span>
            <div class="mt-1">
              <span style="color:rgba(255,255,255,0.4);font-size:1rem;text-decoration:line-through;">$799</span>
              <span style="display:inline-block;background:rgba(251,191,36,0.2);color:#fbbf24;font-size:0.7rem;font-weight:800;padding:0.15rem 0.6rem;border-radius:20px;margin-left:0.5rem;">Save $300</span>
            </div>
          </div>
          <p style="font-size:11px;color:rgba(251,191,36,0.75);font-weight:700;letter-spacing:0.06em;text-transform:uppercase;margin-bottom:22px;">Limited time offer — 40% off</p>
          <ul class="space-y-3 text-sm mb-8" style="color:rgba(255,255,255,0.82);">
            <li class="flex items-center"><i class="fas fa-check text-yellow-400 mr-3 text-xs"></i>Unlimited items &amp; SKUs</li>
            <li class="flex items-center"><i class="fas fa-check text-yellow-400 mr-3 text-xs"></i>Advanced POS + Layaway + Financing</li>
            <li class="flex items-center"><i class="fas fa-check text-yellow-400 mr-3 text-xs"></i>CRM — loyalty, SMS &amp; email marketing</li>
            <li class="flex items-center"><i class="fas fa-check text-yellow-400 mr-3 text-xs"></i>RFID tracking &amp; live metal pricing</li>
            <li class="flex items-center"><i class="fas fa-check text-yellow-400 mr-3 text-xs"></i>Multi-store sync &amp; advanced analytics</li>
            <li class="flex items-center"><i class="fas fa-check text-yellow-400 mr-3 text-xs"></i>API access · Up to 15 users · 2 locations</li>
          </ul>
          <a href="#demo" style="display:block;text-align:center;background:linear-gradient(135deg,#d97706,#b45309);color:white;font-weight:800;padding:16px;border-radius:50px;font-size:0.95rem;letter-spacing:0.04em;text-transform:uppercase;box-shadow:0 8px 28px rgba(217,119,6,0.45);transition:all .35s;" onmouseover="this.style.transform='translateY(-3px) scale(1.03)';this.style.boxShadow='0 16px 40px rgba(217,119,6,0.55)'" onmouseout="this.style.transform='';this.style.boxShadow='0 8px 28px rgba(217,119,6,0.45)'">
            <i class="fas fa-crown mr-2"></i> Start Free Trial
          </a>
        </div>
      </div>

      <!-- Enterprise -->
      <div class="pricing-luxury" data-aos="fade-up" data-aos-delay="160">
        <h3 class="text-xl font-bold mb-2">Enterprise</h3>
        <p class="text-sm text-gray-500 mb-5">For multi-store retailers</p>
        <div class="mb-5">
          <span class="text-4xl font-bold gold-gradient-text">Custom</span>
        </div>
        <ul class="space-y-2 text-sm mb-7">
          <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Multi-store management</li>
          <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Custom integrations</li>
          <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Dedicated account manager</li>
          <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>24/7 premium support</li>
        </ul>
        <a href="#contact" class="block text-center border-2 border-amber-500 text-amber-600 font-bold py-3 rounded-full hover:bg-amber-500 hover:text-white transition-all text-sm">Contact Sales</a>
      </div>
    </div>
    <div class="mt-8 text-center">
      <p class="text-xs text-gray-500"><i class="fas fa-shield-alt text-amber-600 mr-1"></i> All plans: 256-bit encryption · Daily backups · 99.5% uptime SLA · GDPR compliant</p>
    </div>
  </div>
</section>

  <!-- ═══ DEMO ═══ -->
  <section id="demo" class="py-20 luxury-gradient-bg">
    <div class="container mx-auto px-6 lg:px-8">
      <div class="max-w-4xl mx-auto text-center text-white mb-10">
        <h2 class="text-4xl lg:text-5xl font-bold mb-4 playfair"><span class="diamond-text">Experience JewelTag</span> <span class="gold-gradient-text">Firsthand</span></h2>
        <p class="text-white/80">Start your 30-day premium trial today. No credit card required.</p>
      </div>
      <div class="max-w-2xl mx-auto bg-white rounded-2xl p-8">
        <form id="demo-form" class="space-y-4">
          <div class="grid sm:grid-cols-2 gap-4">
            <input type="text" placeholder="First Name" class="form-luxury" required>
            <input type="text" placeholder="Last Name" class="form-luxury" required>
          </div>
          <input type="email" placeholder="Email Address" class="form-luxury" required>
          <div class="flex">
            <span class="inline-flex items-center px-4 rounded-l-lg border-2 border-r-0 border-amber-500 bg-amber-50 text-amber-700 font-bold text-sm">+1</span>
            <input type="tel" placeholder="Phone Number" class="form-luxury rounded-l-none" required>
          </div>
          <input type="text" placeholder="Business Name" class="form-luxury" required>
          <button type="submit" class="btn-luxury w-full py-4 text-sm"><i class="fas fa-play-circle mr-2"></i> Start 30-Day Premium Trial</button>
          <p class="text-xs text-center text-gray-500">By signing up, you agree to our <a href="#" class="text-amber-600">Terms</a> and <a href="#" class="text-amber-600">Privacy Policy</a>.</p>
        </form>
      </div>
    </div>
  </section>

  <!-- ═══ FAQ ═══ -->
  <section class="py-20 bg-gray-50">
    <div class="container mx-auto px-6 lg:px-8">
      <div class="text-center mb-10"><h2 class="text-3xl lg:text-4xl font-bold mb-3 playfair"><span style="color:var(--deep-sapphire)">Frequently Asked</span> <span class="gold-gradient-text">Questions</span></h2></div>
      <div class="max-w-3xl mx-auto space-y-4">
        <div class="bg-white rounded-xl p-5 border border-gray-200">
          <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(1)"><h3 class="font-bold text-sm">How long does implementation take?</h3><i class="fas fa-chevron-down text-amber-600 text-xs"></i></button>
          <div id="faq-1" class="mt-3 hidden text-sm text-gray-600">Most customers are fully live within 2–3 weeks, including discovery, migration, training, and go-live support.</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200">
          <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(2)"><h3 class="font-bold text-sm">Can we import existing data?</h3><i class="fas fa-chevron-down text-amber-600 text-xs"></i></button>
          <div id="faq-2" class="mt-3 hidden text-sm text-gray-600">Yes! We support data imports from all major jewelry management systems, spreadsheets, and custom databases.</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200">
          <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(3)"><h3 class="font-bold text-sm">What security measures do you have?</h3><i class="fas fa-chevron-down text-amber-600 text-xs"></i></button>
          <div id="faq-3" class="mt-3 hidden text-sm text-gray-600">Bank-level 256-bit encryption, daily backups, intrusion detection, and compliance with GDPR, CCPA, and PCI DSS.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══ CONTACT ═══ -->
  <section id="contact" class="py-20 luxury-gradient-bg">
    <div class="container mx-auto px-6 lg:px-8">
      <div class="max-w-4xl mx-auto text-center text-white mb-10"><h2 class="text-3xl lg:text-4xl font-bold mb-3 playfair"><span class="diamond-text">Connect With Our</span> <span class="gold-gradient-text">Expert Team</span></h2></div>
      <div class="grid sm:grid-cols-3 gap-6 max-w-3xl mx-auto mb-10">
        <div class="text-center text-white"><div class="w-12 h-12 gold-gradient rounded-xl flex items-center justify-center mx-auto mb-3"><i class="fas fa-phone"></i></div><p class="text-sm font-bold">1-800-JEWEL-TAG</p><p class="text-white/60 text-xs">Mon–Fri, 8AM–8PM CT</p></div>
        <div class="text-center text-white"><div class="w-12 h-12 gold-gradient rounded-xl flex items-center justify-center mx-auto mb-3"><i class="fas fa-envelope"></i></div><p class="text-sm font-bold">info@jeweltag.us</p><p class="text-white/60 text-xs">Reply within 1 business day</p></div>
        <div class="text-center text-white"><div class="w-12 h-12 gold-gradient rounded-xl flex items-center justify-center mx-auto mb-3"><i class="fas fa-comments"></i></div><p class="text-sm font-bold">Live Chat</p><p class="text-white/60 text-xs">Click the chat icon below</p></div>
      </div>
      <div class="max-w-2xl mx-auto bg-white rounded-2xl p-8">
        <form id="contact-form" class="space-y-4">
          <input type="text" placeholder="Your Name" class="form-luxury" required>
          <input type="email" placeholder="Email Address" class="form-luxury" required>
          <input type="text" placeholder="Business Name" class="form-luxury" required>
          <select class="form-luxury" required><option value="">Select inquiry type</option><option value="demo">Schedule a Demo</option><option value="pricing">Pricing Questions</option><option value="support">Technical Support</option></select>
          <textarea placeholder="Your message..." class="form-luxury h-32" required></textarea>
          <button type="submit" class="btn-luxury w-full py-4 text-sm"><i class="fas fa-paper-plane mr-2"></i> Send Message</button>
        </form>
      </div>
    </div>
  </section>

    <footer class="footer-luxury py-14">
        <div class="container mx-auto px-6 lg:px-10">
            <div class="grid md:grid-cols-4 gap-10 mb-10 relative z-10">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <img src="/jeweltaglogo.png" alt="JewelTag" class="h-9 w-auto">
                    </div>
                    <p class="text-xs leading-relaxed opacity-70 text-white">Inventory, POS, and CRM built specifically for luxury jewelry retailers.</p>
                </div>
                <div>
                    <h4 class="text-white font-bold text-sm mb-4">Platform</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#features" class="text-white/70 hover:text-amber-400 transition-colors">Features</a></li>
                        <li><a href="#pricing" class="text-white/70 hover:text-amber-400 transition-colors">Pricing</a></li>
                        <li><a href="#demo" class="text-white/70 hover:text-amber-400 transition-colors">Free Trial</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold text-sm mb-4">Company</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#" class="text-white/70 hover:text-amber-400 transition-colors">About Us</a></li>
                        <li><a href="#contact" class="text-white/70 hover:text-amber-400 transition-colors">Contact</a></li>
                        <li><a href="#" class="text-white/70 hover:text-amber-400 transition-colors">Blog</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold text-sm mb-4">Resources</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('docs') ?? '#' }}" class="text-white/70 hover:text-amber-400 transition-colors">Documentation</a></li>
                        <li><a href="{{ route('api') ?? '#' }}" class="text-white/70 hover:text-amber-400 transition-colors">API Reference</a></li>
                        <li><a href="{{ route('privacy') ?? '#' }}" class="text-white/70 hover:text-amber-400 transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-6 border-t border-white/10 text-center relative z-10">
                <p class="text-xs opacity-50 text-white">© {{ date('Y') }} The Explorers USA DBA JewelTag. All rights reserved.</p>
                <div class="flex justify-center space-x-4 mt-4 opacity-40 text-white">
                    <i class="fab fa-cc-visa text-lg"></i>
                    <i class="fab fa-cc-mastercard text-lg"></i>
                    <i class="fab fa-cc-amex text-lg"></i>
                    <i class="fab fa-cc-paypal text-lg"></i>
                </div>
            </div>
        </div>
    </footer>

  <!-- ═══ CHAT WIDGET ═══ -->
  <div class="fixed bottom-6 right-6 z-50">
    <button id="chat-toggle" class="w-14 h-14 gold-gradient rounded-full flex items-center justify-center text-white shadow-lg hover:shadow-xl transition-all">
      <i class="fas fa-comment text-xl"></i>
    </button>
    <div id="chat-window" class="absolute bottom-20 right-0 w-80 bg-white rounded-xl shadow-2xl hidden">
      <div class="p-4 border-b border-gray-200"><div class="flex items-center"><div class="w-10 h-10 gold-gradient rounded-lg flex items-center justify-center mr-3"><i class="fas fa-headset text-white"></i></div><div><h4 class="font-bold text-sm text-gray-800">JewelTag Support</h4><p class="text-xs text-gray-500">We're online</p></div></div></div>
      <div class="p-4 h-64 overflow-y-auto"><div class="bg-gray-100 rounded-lg p-3 max-w-xs mb-4 text-xs text-gray-800">Hello! How can we help you today?</div><div class="text-center"><button class="px-3 py-2 bg-amber-50 text-amber-700 rounded-lg text-xs font-semibold mr-2">Pricing</button><button class="px-3 py-2 bg-amber-50 text-amber-700 rounded-lg text-xs font-semibold">Demo</button></div></div>
      <div class="p-4 border-t border-gray-200"><div class="flex"><input type="text" placeholder="Type your message..." class="flex-1 border border-gray-300 rounded-l-lg px-4 py-2 text-xs focus:outline-none focus:border-amber-500 text-gray-800"><button class="gold-gradient text-white px-4 rounded-r-lg text-sm"><i class="fas fa-paper-plane"></i></button></div></div>
    </div>
  </div>

  <!-- Back to top -->
  <button id="back-to-top" class="fixed bottom-24 right-6 w-12 h-12 bg-white border border-gray-200 rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transition-all hidden z-40">
    <i class="fas fa-chevron-up text-gray-700"></i>
  </button>

  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    // Initialize Scroll Animations
    AOS.init({ duration:800, once:true, offset:50 });

    // Mobile menu
    document.getElementById('mobile-menu-button')?.addEventListener('click', () => {
      const menu = document.getElementById('mobile-menu');
      menu?.classList.toggle('hidden');
    });

    document.querySelectorAll('#mobile-menu a').forEach(link => {
      link.addEventListener('click', () => {
        document.getElementById('mobile-menu')?.classList.add('hidden');
      });
    });

    // Chat
    document.getElementById('chat-toggle').addEventListener('click', function(){ document.getElementById('chat-window').classList.toggle('hidden'); });

    // Back to top
    const btt = document.getElementById('back-to-top');
    window.addEventListener('scroll', function(){ btt.classList.toggle('hidden', window.pageYOffset <= 300); });
    btt.addEventListener('click', function(){ window.scrollTo({top:0,behavior:'smooth'}); });

    // FAQ
    window.toggleFAQ = function(n){ const f=document.getElementById('faq-'+n),i=event.currentTarget.querySelector('i'); f.classList.toggle('hidden'); i.classList.toggle('fa-chevron-down'); i.classList.toggle('fa-chevron-up'); };

    /* ══════════════════════════════════════════════════════════════════ */
    /* HERO DEMO VIDEO — real footage, synced captions instead of guessed  */
    /* highlights. Edit the `captions` array below: `time` and `duration`  */
    /* are seconds into jewelrypos.mp4, `label` is what to show while     */
    /* that segment plays. Set these to match what's actually on screen   */
    /* at those timestamps in your video.                                  */
    /* ══════════════════════════════════════════════════════════════════ */
    document.addEventListener('DOMContentLoaded', () => {
        const video = document.getElementById('pos-video');
        if (!video) return;

        const muteBtn = document.getElementById('video-mute-btn');
        const muteIcon = document.getElementById('mute-icon');
        const progressFill = document.getElementById('video-progress-fill');
        const caption = document.getElementById('video-caption');
        const captionText = document.getElementById('video-caption-text');
        const playFallback = document.getElementById('video-playfallback');

        // EDIT THESE to match your actual video's timeline
        const captions = [
            { time: 0,  duration: 4, label: 'Live Inventory Dashboard' },
            { time: 5,  duration: 4, label: 'Point of Sale Checkout' },
            { time: 10, duration: 4, label: 'RFID Item Scanning' },
            { time: 15, duration: 4, label: 'Customer & Repair Tracking' }
        ];
        let activeCaption = null;

        video.addEventListener('timeupdate', () => {
            if (video.duration) {
                progressFill.style.width = (video.currentTime / video.duration * 100) + '%';
            }
            const match = captions.find(c => video.currentTime >= c.time && video.currentTime < c.time + c.duration);
            if (match !== activeCaption) {
                activeCaption = match;
                if (match) {
                    captionText.textContent = match.label;
                    caption.classList.add('show');
                } else {
                    caption.classList.remove('show');
                }
            }
        });

        muteBtn?.addEventListener('click', () => {
            video.muted = !video.muted;
            muteIcon.className = video.muted ? 'fas fa-volume-xmark' : 'fas fa-volume-high';
        });

        // Most browsers allow muted autoplay; if it's still blocked, show a play button
        const tryPlay = () => video.play().catch(() => {
            playFallback.classList.add('show');
        });
        tryPlay();

        playFallback?.addEventListener('click', () => {
            playFallback.classList.remove('show');
            video.muted = true;
            muteIcon.className = 'fas fa-volume-xmark';
            video.play().catch(() => {});
        });
    });

    // Dynamic Form Submission for Demo
    document.getElementById('demo-form').addEventListener('submit', function(e){
        e.preventDefault();
        const inputs = this.querySelectorAll('input');
        const payload = new FormData();
        payload.append('name', inputs[0].value + ' ' + inputs[1].value);
        payload.append('email', inputs[2].value);
        // Automatically append +1 to the phone number per rules
        payload.append('phone', '+1 ' + inputs[3].value);
        payload.append('business', inputs[4].value);
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
            }).catch(err => {
                alert('Something went wrong. Please email info@jeweltag.us directly.');
            });
    });

    // Dynamic Form Submission for Contact
    document.getElementById('contact-form').addEventListener('submit', function(e){
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
            }).catch(err => {
                alert('Something went wrong. Please email info@jeweltag.us directly.');
            });
    });

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(a=>{
      a.addEventListener('click',function(e){ e.preventDefault(); const t=document.querySelector(this.getAttribute('href')); if(t){document.getElementById('mobile-menu')?.classList.add('hidden');t.scrollIntoView({behavior:'smooth',block:'start'});} });
    });
  </script>
</body>
</html>