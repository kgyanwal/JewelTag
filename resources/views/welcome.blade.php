<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JewelTag.us | Ultimate Jewelry Management & Inventory Software</title>
    <meta name="description" content="World's most advanced jewelry inventory management, POS, CRM, and business intelligence platform for jewelers, retailers, and luxury brands.">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=Cormorant+Garamond:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gold: #d97706;
            --secondary-gold: #fbbf24;
            --dark-gold: #b45309;
            --gold-soft: #fffbeb;
            --platinum: #E5E4E2;
            --diamond-white: #F8FAFC;
            --deep-sapphire: #0A2540;
            --royal-blue: #1E3A8A;
            --emerald-green: #047857;
            --ruby-red: #DC2626;
            --amethyst-purple: #7C3AED;
            --onyx-black: #111827;
            --text-main: #1e293b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            overflow-x: hidden;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .playfair {
            font-family: 'Playfair Display', serif;
        }
        
        .cormorant {
            font-family: 'Cormorant Garamond', serif;
        }
        
        /* Gold Gradients */
        .gold-gradient { 
            background: linear-gradient(135deg, var(--primary-gold), var(--dark-gold)); 
        }
        
        .gold-text { 
            color: var(--primary-gold); 
        }
        
        .gold-gradient-text {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold), var(--dark-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Advanced Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(3deg); }
            66% { transform: translateY(-10px) rotate(-3deg); }
        }
        
        @keyframes diamond-sparkle {
            0%, 100% { opacity: 0.3; transform: scale(0.8) rotate(0deg); }
            50% { opacity: 1; transform: scale(1.2) rotate(180deg); }
        }
        
        @keyframes shimmer {
            0% { background-position: -2000px 0; }
            100% { background-position: 2000px 0; }
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(217, 119, 6, 0.3); }
            50% { box-shadow: 0 0 40px rgba(217, 119, 6, 0.6); }
        }
        
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .animate-float {
            animation: float 8s ease-in-out infinite;
        }
        
        .animate-sparkle {
            animation: diamond-sparkle 3s infinite;
        }
        
        .animate-shimmer {
            animation: shimmer 3s infinite linear;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            background-size: 1000px 100%;
        }
        
        .animate-pulse-glow {
            animation: pulse-glow 2s infinite;
        }
        
        .animate-gradient-shift {
            background-size: 200% 200%;
            animation: gradient-shift 5s ease infinite;
        }
        
        /* Premium Backgrounds */
        .luxury-gradient-bg {
            background: linear-gradient(135deg, var(--deep-sapphire) 0%, var(--royal-blue) 100%);
        }
        
        .gold-gradient-bg {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
        }
        
        .diamond-mesh-bg {
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(217, 119, 6, 0.1) 2px, transparent 2px),
                radial-gradient(circle at 75% 75%, rgba(59, 130, 246, 0.1) 2px, transparent 2px);
            background-size: 60px 60px;
        }
        
        .jewel-pattern-bg {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(217, 119, 6, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.05) 0%, transparent 20%);
        }
        
        .diamond-text {
            background: linear-gradient(135deg, #ffffff, #e2e8f0, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Premium Cards */
        .luxury-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(217, 119, 6, 0.2);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.05),
                0 1px 0 rgba(255, 255, 255, 0.8) inset;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
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
            border-radius: 24px 24px 0 0;
        }
        
        .luxury-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 
                0 40px 80px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(217, 119, 6, 0.3),
                0 0 40px rgba(217, 119, 6, 0.1);
        }
        
        .jewel-icon-card {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            margin-bottom: 24px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        .jewel-icon-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .jewel-icon-card:hover::after {
            opacity: 1;
        }
        
        /* Navigation */
        .nav-luxury {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(217, 119, 6, 0.1);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }
        
        /* Buttons */
        .btn-luxury {
            background: linear-gradient(135deg, var(--primary-gold), var(--dark-gold));
            color: white;
            padding: 16px 36px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 0.5px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
        }
        
        .btn-luxury::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.7s;
        }
        
        .btn-luxury:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 40px rgba(217, 119, 6, 0.3);
        }
        
        .btn-luxury:hover::before {
            left: 100%;
        }
        
        .btn-outline-luxury {
            border: 2px solid var(--primary-gold);
            color: var(--primary-gold);
            padding: 16px 36px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 0.5px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-outline-luxury:hover {
            background: linear-gradient(135deg, var(--primary-gold), var(--dark-gold));
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(217, 119, 6, 0.2);
        }
        
        /* Stats */
        .stat-number {
            font-size: 4.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            text-shadow: 0 5px 15px rgba(217, 119, 6, 0.2);
        }
        
        /* Partner Card */
        .partner-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 24px;
            padding: 40px;
            color: white;
            border: 1px solid rgba(59, 130, 246, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            transition: all 0.4s ease;
        }
        
        .partner-card:hover {
            transform: translateY(-10px);
            border-color: rgba(59, 130, 246, 0.6);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3), 0 0 30px rgba(59, 130, 246, 0.2);
        }
        
        /* Testimonials */
        .testimonial-luxury {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(217, 119, 6, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .testimonial-luxury::before {
            content: '"';
            position: absolute;
            top: 20px;
            left: 30px;
            font-size: 120px;
            font-family: 'Playfair Display', serif;
            color: rgba(217, 119, 6, 0.1);
            line-height: 1;
        }
        
        /* Pricing */
        .pricing-luxury {
            background: white;
            border-radius: 28px;
            padding: 48px;
            border: 2px solid rgba(217, 119, 6, 0.2);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .pricing-luxury.featured {
            border-color: var(--primary-gold);
            transform: scale(1.05);
            box-shadow: 0 40px 80px rgba(217, 119, 6, 0.15);
        }
        
        .pricing-luxury.featured::before {
            content: 'MOST POPULAR';
            position: absolute;
            top: 25px;
            right: -35px;
            background: linear-gradient(135deg, var(--primary-gold), var(--dark-gold));
            color: white;
            padding: 8px 50px;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 1px;
            transform: rotate(45deg);
        }
        
        /* Forms */
        .form-luxury {
            border: 2px solid rgba(217, 119, 6, 0.2);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s ease;
            width: 100%;
            background: white;
            font-size: 16px;
        }
        
        .form-luxury:focus {
            outline: none;
            border-color: var(--primary-gold);
            box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.1);
        }
        
        /* Footer */
        .footer-luxury {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .footer-luxury::before {
            content: '';
            position: absolute;
            width: 400%;
            height: 400%;
            background: radial-gradient(circle, rgba(217, 119, 6, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
            transform: rotate(45deg);
            top: -150%;
            left: -150%;
            z-index: 0;
            opacity: 0.4;
        }
        
        /* Hero Section */
        .hero-luxury {
            background: linear-gradient(135deg, #0A2540 0%, #1E3A8A 100%);
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .hero-luxury::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(217, 119, 6, 0.15) 0%, transparent 70%);
            top: -300px;
            right: -300px;
            border-radius: 50%;
        }
        
        .hero-luxury::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
            bottom: -250px;
            left: -250px;
            border-radius: 50%;
        }
        
        .diamond-grid {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 0;
        }
        
        /* Floating Elements */
        .floating-diamond {
            position: absolute;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            transform: rotate(45deg);
            z-index: 1;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(217, 119, 6, 0.1);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .stat-number {
                font-size: 3.5rem;
            }
            
            .pricing-luxury.featured {
                transform: scale(1);
            }
            
            .hero-luxury::before,
            .hero-luxury::after {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .stat-number {
                font-size: 2.8rem;
            }
            
            .btn-luxury, .btn-outline-luxury {
                padding: 14px 28px;
                font-size: 14px;
            }
            
            .jewel-icon-card {
                width: 64px;
                height: 64px;
                font-size: 24px;
            }
        }
    </style>
</head>
<body class="antialiased relative">
    <!-- Gold Top Bar -->
    <div class="fixed top-0 left-0 w-full h-1 gold-gradient z-50"></div>
    
    <!-- Animated Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="floating-diamond animate-float" style="top:10%; left:5%; animation-delay:0s;"></div>
        <div class="floating-diamond animate-float" style="top:20%; right:10%; animation-delay:1s; width:60px; height:60px;"></div>
        <div class="floating-diamond animate-float" style="bottom:30%; left:15%; animation-delay:2s; width:100px; height:100px;"></div>
        <div class="floating-diamond animate-float" style="bottom:15%; right:5%; animation-delay:3s; width:70px; height:70px;"></div>
    </div>
    
    <!-- Navigation -->
    <nav class="nav-luxury fixed w-full z-50 py-4">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 gold-gradient rounded-xl flex items-center justify-center text-white text-xl shadow-lg">
                        <i class="fas fa-gem"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold playfair gold-gradient-text">JewelTag<span class="text-deep-sapphire">.us</span></h1>
                    </div>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden lg:flex items-center space-x-8">
                    <a href="#features" class="text-gray-700 hover:text-amber-600 font-semibold text-sm uppercase tracking-wider transition duration-300">Features</a>
                    <a href="#solutions" class="text-gray-700 hover:text-amber-600 font-semibold text-sm uppercase tracking-wider transition duration-300">Solutions</a>
                    <a href="#pricing" class="text-gray-700 hover:text-amber-600 font-semibold text-sm uppercase tracking-wider transition duration-300">Pricing</a>
                    <a href="#testimonials" class="text-gray-700 hover:text-amber-600 font-semibold text-sm uppercase tracking-wider transition duration-300">Success Stories</a>
                    <a href="/master/login" class="px-6 py-3 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 transition-all text-sm uppercase tracking-wider">
                        <i class="fas fa-lock mr-2"></i> Staff Login
                    </a>
                    <a href="#demo" class="px-6 py-3 gold-gradient text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm uppercase tracking-wider">
                        <i class="fas fa-crown mr-2"></i> Free Demo
                    </a>
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-button" class="lg:hidden text-gray-700">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="lg:hidden hidden mt-6 pb-4">
                <div class="flex flex-col space-y-4">
                    <a href="#features" class="text-gray-700 hover:text-amber-600 font-semibold py-2 border-b border-gray-100">Features</a>
                    <a href="#solutions" class="text-gray-700 hover:text-amber-600 font-semibold py-2 border-b border-gray-100">Solutions</a>
                    <a href="#pricing" class="text-gray-700 hover:text-amber-600 font-semibold py-2 border-b border-gray-100">Pricing</a>
                    <a href="#testimonials" class="text-gray-700 hover:text-amber-600 font-semibold py-2 border-b border-gray-100">Success Stories</a>
                    <a href="/master/login" class="w-full px-6 py-3 bg-slate-900 text-white font-bold rounded-xl text-center mt-4">Staff Login</a>
                    <a href="#demo" class="w-full px-6 py-3 gold-gradient text-white font-bold rounded-xl text-center">Free Demo</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-luxury pt-32 pb-20 lg:pt-40 lg:pb-32">
        <div class="diamond-grid"></div>
        <div class="container mx-auto px-6 lg:px-8 relative z-10">
            <div class="flex flex-col lg:flex-row items-center">
                <div class="lg:w-1/2 mb-16 lg:mb-0" data-aos="fade-right">
                    <div class="inline-flex items-center px-5 py-2 rounded-full bg-white/10 backdrop-blur-sm mb-6 border border-white/20">
                        <span class="w-2 h-2 rounded-full bg-amber-500 mr-2"></span>
                        <span class="text-white/90 text-sm font-semibold tracking-wider">TRUSTED BY 750+ LUXURY JEWELERS WORLDWIDE</span>
                    </div>
                    
                    <h1 class="text-5xl lg:text-7xl font-black text-white mb-6 playfair leading-tight">
                        The <span class="gold-gradient-text">Ultimate Platform</span> for Jewelry Excellence
                    </h1>
                    
                    <p class="text-xl lg:text-2xl text-white/80 mb-8 leading-relaxed">
                        Transform your jewelry business with the world's most advanced inventory management, POS, CRM, and business intelligence platform designed exclusively for luxury retailers.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="#demo" class="btn-luxury text-center">
                            <i class="fas fa-play-circle mr-3"></i> Start 30-Day Trial
                        </a>
                        <a href="/master" class="btn-outline-luxury text-white border-white/40 hover:border-white text-center">
                            <i class="fas fa-gem mr-3"></i> Enter Master Portal
                        </a>
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
                                <i class="fas fa-star text-yellow-400 text-sm"></i>
                                <i class="fas fa-star text-yellow-400 text-sm"></i>
                                <i class="fas fa-star text-yellow-400 text-sm"></i>
                                <i class="fas fa-star text-yellow-400 text-sm"></i>
                                <i class="fas fa-star text-yellow-400 text-sm"></i>
                                <span class="text-white/60 text-xs ml-2">4.95/5 (428 reviews)</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="lg:w-1/2 relative" data-aos="fade-left">
                    <!-- Dashboard Preview -->
                    <div class="glass-card rounded-2xl p-4 shadow-2xl animate-float" style="animation-delay: 1s;">
                        <div class="gold-gradient rounded-xl p-6 text-white">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-gem text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-white/80 text-xs">Today's Revenue</p>
                                        <p class="text-2xl font-bold">$24,847</p>
                                    </div>
                                </div>
                                <div class="px-2 py-1 bg-white/20 rounded-full text-xs">
                                    +32.5%
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-white/10 rounded-lg p-3">
                                    <p class="text-white/80 text-xs">Inventory</p>
                                    <p class="text-xl font-bold">2,847</p>
                                </div>
                                <div class="bg-white/10 rounded-lg p-3">
                                    <p class="text-white/80 text-xs">Avg. Sale</p>
                                    <p class="text-xl font-bold">$1,249</p>
                                </div>
                            </div>
                            
                            <!-- Live Activity -->
                            <div class="mt-4 pt-4 border-t border-white/20">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-white/80">Live Activity Feed</span>
                                    <span class="flex items-center">
                                        <span class="w-2 h-2 bg-green-400 rounded-full mr-1 animate-pulse"></span>
                                        <span class="text-white/60">3 active</span>
                                    </span>
                                </div>
                                <div class="mt-3 space-y-2">
                                    <div class="flex items-center text-xs">
                                        <div class="w-6 h-6 bg-green-500/20 rounded flex items-center justify-center mr-2">
                                            <i class="fas fa-shopping-cart text-green-300 text-xs"></i>
                                        </div>
                                        <span class="text-white/80">Premium Sale: $8,499</span>
                                    </div>
                                    <div class="flex items-center text-xs">
                                        <div class="w-6 h-6 bg-purple-500/20 rounded flex items-center justify-center mr-2">
                                            <i class="fas fa-box text-purple-300 text-xs"></i>
                                        </div>
                                        <span class="text-white/80">New Inventory Added</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Floating Elements -->
                    <div class="absolute -top-6 -right-6 w-24 h-24 gold-gradient rounded-2xl opacity-20 blur-xl"></div>
                    <div class="absolute -bottom-6 -left-6 w-32 h-32 bg-blue-600 rounded-2xl opacity-10 blur-xl"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="stat-number text-4xl lg:text-5xl">750+</div>
                    <p class="text-gray-600 mt-2 text-sm font-medium uppercase tracking-wider">Premium Jewelers</p>
                </div>
                <div class="text-center">
                    <div class="stat-number text-4xl lg:text-5xl">$4.2B+</div>
                    <p class="text-gray-600 mt-2 text-sm font-medium uppercase tracking-wider">Inventory Managed</p>
                </div>
                <div class="text-center">
                    <div class="stat-number text-4xl lg:text-5xl">99.95%</div>
                    <p class="text-gray-600 mt-2 text-sm font-medium uppercase tracking-wider">System Uptime</p>
                </div>
                <div class="text-center">
                    <div class="stat-number text-4xl lg:text-5xl">24/7</div>
                    <p class="text-gray-600 mt-2 text-sm font-medium uppercase tracking-wider">Premium Support</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 jewel-pattern-bg">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="text-center mb-12" data-aos="fade-up">
                <h2 class="text-4xl lg:text-5xl font-bold mb-4 playfair">
                    <span class="text-deep-sapphire">Unparalleled Features for</span> <span class="gold-gradient-text">Luxury Retail</span>
                </h2>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                    Designed exclusively for jewelry businesses, our comprehensive platform transforms every aspect of your operations.
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="luxury-card p-6">
                    <div class="w-14 h-14 gold-gradient rounded-xl flex items-center justify-center text-white text-xl mb-4">
                        <i class="fas fa-boxes-stacked"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Intelligent Inventory</h3>
                    <p class="text-gray-600 text-sm mb-4">AI-powered tracking with RFID, barcode scanning, and multi-location management.</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>
                            <span>GIA Certificate Integration</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>
                            <span>Automated Reordering</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Feature 2 -->
                <div class="luxury-card p-6">
                    <div class="w-14 h-14 gold-gradient rounded-xl flex items-center justify-center text-white text-xl mb-4">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Luxury POS Suite</h3>
                    <p class="text-gray-600 text-sm mb-4">Complete point-of-sale with integrated payments, layaway, and commission tracking.</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>
                            <span>Custom Invoice Templates</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>
                            <span>Multi-store Support</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Feature 3 -->
                <div class="luxury-card p-6">
                    <div class="w-14 h-14 gold-gradient rounded-xl flex items-center justify-center text-white text-xl mb-4">
                        <i class="fas fa-chart-network"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Business Intelligence</h3>
                    <p class="text-gray-600 text-sm mb-4">Real-time dashboards, sales forecasting, and profit margin analysis.</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>
                            <span>Predictive Analytics</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>
                            <span>ROI Optimization</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Feature 4 -->
                <div class="luxury-card p-6">
                    <div class="w-14 h-14 gold-gradient rounded-xl flex items-center justify-center text-white text-xl mb-4">
                        <i class="fas fa-users-crown"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">VIP CRM</h3>
                    <p class="text-gray-600 text-sm mb-4">Client profiles, purchase history, and automated marketing for VIP customers.</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>
                            <span>Anniversary Automation</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>
                            <span>Wishlist Management</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Feature 5 -->
                <div class="luxury-card p-6">
                    <div class="w-14 h-14 gold-gradient rounded-xl flex items-center justify-center text-white text-xl mb-4">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Repair Management</h3>
                    <p class="text-gray-600 text-sm mb-4">Digital work orders, status tracking, and customer notifications.</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>
                            <span>Photo Documentation</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>
                            <span>Warranty Tracking</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Feature 6 -->
                <div class="luxury-card p-6">
                    <div class="w-14 h-14 gold-gradient rounded-xl flex items-center justify-center text-white text-xl mb-4">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Mobile Platform</h3>
                    <p class="text-gray-600 text-sm mb-4">Native iOS & Android apps with offline mode and bank-level encryption.</p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>
                            <span>Offline Auto-Sync</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-2 text-xs"></i>
                            <span>256-bit Encryption</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Partner Section -->
    <section class="py-20">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="partner-card max-w-4xl mx-auto text-center">
                <div class="w-20 h-20 gold-gradient rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-brain text-white text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold mb-3">Strategic Partner: Creative AI Network</h3>
                <p class="text-white/80 mb-6 max-w-2xl mx-auto">
                    Enhancing JewelTag with cutting-edge AI, custom software development, and digital transformation expertise.
                </p>
                <div class="flex flex-wrap justify-center gap-3 mb-6">
                    <span class="px-4 py-2 bg-white/10 rounded-full text-white/90 text-sm">Finance</span>
                    <span class="px-4 py-2 bg-white/10 rounded-full text-white/90 text-sm">Healthcare</span>
                    <span class="px-4 py-2 bg-white/10 rounded-full text-white/90 text-sm">E-commerce</span>
                    <span class="px-4 py-2 bg-white/10 rounded-full text-white/90 text-sm">Luxury Retail</span>
                </div>
                <a href="https://creativeainetworks.com" target="_blank" class="inline-flex items-center text-amber-400 hover:text-amber-300 font-semibold">
                    Visit Partner Website <i class="fas fa-arrow-right ml-2 text-sm"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="solutions" class="py-20 bg-white">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl lg:text-5xl font-bold mb-4 playfair">
                    <span class="text-deep-sapphire">Implementation</span> <span class="gold-gradient-text">Excellence</span>
                </h2>
                <p class="text-lg text-gray-600">Your journey to jewelry management excellence in 4 simple steps.</p>
            </div>
            
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-5xl mx-auto">
                <div class="text-center">
                    <div class="w-16 h-16 gold-gradient rounded-full flex items-center justify-center text-white text-xl font-bold mx-auto mb-4">01</div>
                    <h3 class="font-bold mb-2">Discovery</h3>
                    <p class="text-gray-600 text-sm">In-depth analysis of your business needs</p>
                    <span class="text-xs text-amber-600 font-semibold mt-2 inline-block">1-3 Days</span>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 gold-gradient rounded-full flex items-center justify-center text-white text-xl font-bold mx-auto mb-4">02</div>
                    <h3 class="font-bold mb-2">Migration</h3>
                    <p class="text-gray-600 text-sm">White-glove data transfer & setup</p>
                    <span class="text-xs text-amber-600 font-semibold mt-2 inline-block">3-7 Days</span>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 gold-gradient rounded-full flex items-center justify-center text-white text-xl font-bold mx-auto mb-4">03</div>
                    <h3 class="font-bold mb-2">Training</h3>
                    <p class="text-gray-600 text-sm">Personalized team onboarding</p>
                    <span class="text-xs text-amber-600 font-semibold mt-2 inline-block">5-10 Days</span>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 gold-gradient rounded-full flex items-center justify-center text-white text-xl font-bold mx-auto mb-4">04</div>
                    <h3 class="font-bold mb-2">Go Live</h3>
                    <p class="text-gray-600 text-sm">Launch with dedicated support</p>
                    <span class="text-xs text-amber-600 font-semibold mt-2 inline-block">Ongoing</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section id="testimonials" class="py-20 jewel-pattern-bg">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl lg:text-5xl font-bold mb-4 playfair">
                    <span class="gold-gradient-text">Success Stories</span>
                </h2>
                <p class="text-lg text-gray-600">Trusted by leading jewelers worldwide</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-amber-100">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 gold-gradient rounded-full flex items-center justify-center text-white mr-3">SC</div>
                        <div>
                            <h4 class="font-bold">Sarah Chen</h4>
                            <p class="text-xs text-gray-500">CEO, Brilliance Diamonds</p>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm italic">"Inventory reconciliation reduced from 3 weeks to 2 days. Stock accuracy increased to 99.8%."</p>
                    <div class="mt-3 text-amber-600 font-bold text-sm">+42% Revenue</div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-amber-100">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 gold-gradient rounded-full flex items-center justify-center text-white mr-3">MR</div>
                        <div>
                            <h4 class="font-bold">Michael Rodriguez</h4>
                            <p class="text-xs text-gray-500">Gold Standard Jewelers</p>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm italic">"Repair admin time reduced by 70%. Customer satisfaction increased from 82% to 98%."</p>
                    <div class="mt-3 text-amber-600 font-bold text-sm">+156% Repair Revenue</div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-amber-100">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 gold-gradient rounded-full flex items-center justify-center text-white mr-3">JW</div>
                        <div>
                            <h4 class="font-bold">James Wilson</h4>
                            <p class="text-xs text-gray-500">Heritage Jewelers</p>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm italic">"Sales increased 34% in 6 months. Profit margins improved by 8 percentage points."</p>
                    <div class="mt-3 text-amber-600 font-bold text-sm">+34% Sales Growth</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-white">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl lg:text-5xl font-bold mb-4 playfair">
                    <span class="text-deep-sapphire">Premium Plans for</span> <span class="gold-gradient-text">Every Business</span>
                </h2>
                <p class="text-lg text-gray-600">Transparent pricing, no hidden fees, no long-term contracts.</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                <!-- Essential Plan -->
                <div class="pricing-luxury p-6">
                    <h3 class="text-xl font-bold mb-2">Essential</h3>
                    <p class="text-sm text-gray-500 mb-4">For small jewelry shops</p>
                    <div class="mb-4">
                        <span class="text-3xl font-bold gold-gradient-text">$149</span>
                        <span class="text-gray-500 text-sm">/month</span>
                    </div>
                    <ul class="space-y-2 text-sm mb-6">
                        <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Up to 1,000 items</li>
                        <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Basic POS</li>
                        <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Customer management</li>
                        <li class="flex items-center text-gray-400"><i class="fas fa-times text-gray-400 mr-2 text-xs"></i>Advanced analytics</li>
                    </ul>
                    <a href="#demo" class="btn-outline-luxury w-full text-center py-3 text-sm">Start Free Trial</a>
                </div>
                
                <!-- Professional Plan -->
                <div class="pricing-luxury featured p-6">
                    <h3 class="text-xl font-bold mb-2">Professional</h3>
                    <p class="text-sm text-gray-500 mb-4">Most popular choice</p>
                    <div class="mb-4">
                        <span class="text-3xl font-bold gold-gradient-text">$299</span>
                        <span class="text-gray-500 text-sm">/month</span>
                    </div>
                    <ul class="space-y-2 text-sm mb-6">
                        <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Unlimited items</li>
                        <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Advanced POS</li>
                        <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>CRM & marketing</li>
                        <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Repair tracking</li>
                    </ul>
                    <a href="#demo" class="btn-luxury w-full text-center py-3 text-sm">Start Free Trial</a>
                </div>
                
                <!-- Enterprise Plan -->
                <div class="pricing-luxury p-6">
                    <h3 class="text-xl font-bold mb-2">Enterprise</h3>
                    <p class="text-sm text-gray-500 mb-4">For multi-store retailers</p>
                    <div class="mb-4">
                        <span class="text-3xl font-bold gold-gradient-text">Custom</span>
                    </div>
                    <ul class="space-y-2 text-sm mb-6">
                        <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Multi-store management</li>
                        <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Custom integrations</li>
                        <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>Dedicated account manager</li>
                        <li class="flex items-center"><i class="fas fa-check text-emerald-600 mr-2 text-xs"></i>24/7 premium support</li>
                    </ul>
                    <a href="#contact" class="btn-outline-luxury w-full text-center py-3 text-sm">Contact Sales</a>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-shield-alt text-amber-600 mr-1"></i> All plans include: 256-bit encryption • Daily backups • 99.95% uptime SLA • GDPR compliance
                </p>
            </div>
        </div>
    </section>

    <!-- Demo Section -->
    <section id="demo" class="py-20 luxury-gradient-bg">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center text-white mb-10">
                <h2 class="text-4xl lg:text-5xl font-bold mb-4 playfair">
                    <span class="diamond-text">Experience JewelTag</span> <span class="gold-gradient-text">Firsthand</span>
                </h2>
                <p class="text-white/80">Start your 30-day premium trial today. No credit card required.</p>
            </div>
            
            <div class="max-w-2xl mx-auto bg-white rounded-2xl p-8">
                <form id="demo-form" class="space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <input type="text" placeholder="First Name" class="form-luxury py-3 text-sm" required>
                        <input type="text" placeholder="Last Name" class="form-luxury py-3 text-sm" required>
                    </div>
                    <input type="email" placeholder="Email Address" class="form-luxury py-3 text-sm" required>
                    <input type="text" placeholder="Business Name" class="form-luxury py-3 text-sm" required>
                    <button type="submit" class="btn-luxury w-full py-4 text-sm">
                        <i class="fas fa-play-circle mr-2"></i> Start 30-Day Premium Trial
                    </button>
                    <p class="text-xs text-center text-gray-500">
                        By signing up, you agree to our <a href="#" class="text-amber-600">Terms</a> and <a href="#" class="text-amber-600">Privacy Policy</a>.
                    </p>
                </form>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 bg-gray-50">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2 class="text-3xl lg:text-4xl font-bold mb-3 playfair">
                    <span class="text-deep-sapphire">Frequently Asked</span> <span class="gold-gradient-text">Questions</span>
                </h2>
            </div>
            
            <div class="max-w-3xl mx-auto space-y-4">
                <div class="bg-white rounded-xl p-5 border border-gray-200">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(1)">
                        <h3 class="font-bold text-sm">How long does implementation take?</h3>
                        <i class="fas fa-chevron-down text-amber-600 text-xs"></i>
                    </button>
                    <div id="faq-1" class="mt-3 hidden text-sm text-gray-600">
                        Most customers complete implementation within 2-3 weeks, including discovery, migration, training, and go-live support.
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-5 border border-gray-200">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(2)">
                        <h3 class="font-bold text-sm">Can we import existing data?</h3>
                        <i class="fas fa-chevron-down text-amber-600 text-xs"></i>
                    </button>
                    <div id="faq-2" class="mt-3 hidden text-sm text-gray-600">
                        Yes! We support data imports from all major jewelry management systems, spreadsheets, and custom databases.
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-5 border border-gray-200">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(3)">
                        <h3 class="font-bold text-sm">What security measures do you have?</h3>
                        <i class="fas fa-chevron-down text-amber-600 text-xs"></i>
                    </button>
                    <div id="faq-3" class="mt-3 hidden text-sm text-gray-600">
                        Bank-level 256-bit encryption, daily backups, intrusion detection, and compliance with GDPR, CCPA, and PCI DSS.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 luxury-gradient-bg">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center text-white mb-10">
                <h2 class="text-3xl lg:text-4xl font-bold mb-3 playfair">
                    <span class="diamond-text">Connect With Our</span> <span class="gold-gradient-text">Expert Team</span>
                </h2>
            </div>
            
            <div class="grid sm:grid-cols-3 gap-6 max-w-3xl mx-auto mb-10">
                <div class="text-center text-white">
                    <div class="w-12 h-12 gold-gradient rounded-xl flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-phone"></i>
                    </div>
                    <p class="text-sm font-bold">1-800-JEWEL-TAG</p>
                    <p class="text-white/60 text-xs">Mon-Fri, 8AM-8PM</p>
                </div>
                <div class="text-center text-white">
                    <div class="w-12 h-12 gold-gradient rounded-xl flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <p class="text-sm font-bold">sales@jeweltag.us</p>
                    <p class="text-white/60 text-xs">24hr response</p>
                </div>
                <div class="text-center text-white">
                    <div class="w-12 h-12 gold-gradient rounded-xl flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-comments"></i>
                    </div>
                    <p class="text-sm font-bold">Live Chat</p>
                    <p class="text-white/60 text-xs">Click chat icon below</p>
                </div>
            </div>
            
            <div class="max-w-2xl mx-auto bg-white rounded-2xl p-8">
                <form id="contact-form" class="space-y-4">
                    <input type="text" placeholder="Your Name" class="form-luxury py-3 text-sm" required>
                    <input type="email" placeholder="Email Address" class="form-luxury py-3 text-sm" required>
                    <input type="text" placeholder="Business Name" class="form-luxury py-3 text-sm" required>
                    <select class="form-luxury py-3 text-sm" required>
                        <option value="">Select inquiry type</option>
                        <option value="demo">Schedule a Demo</option>
                        <option value="pricing">Pricing Questions</option>
                        <option value="support">Technical Support</option>
                    </select>
                    <textarea placeholder="Your message..." class="form-luxury py-3 text-sm h-32" required></textarea>
                    <button type="submit" class="btn-luxury w-full py-4 text-sm">
                        <i class="fas fa-paper-plane mr-2"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-luxury py-12">
        <div class="container mx-auto px-6 lg:px-8 relative z-10">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 gold-gradient rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-gem text-white text-sm"></i>
                        </div>
                        <h3 class="text-xl font-bold playfair gold-gradient-text">JewelTag</h3>
                    </div>
                    <p class="text-white/60 text-xs leading-relaxed">
                        The ultimate software solution for luxury jewelry businesses.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-bold text-sm mb-4">Platform</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#features" class="text-white/60 hover:text-white">Features</a></li>
                        <li><a href="#pricing" class="text-white/60 hover:text-white">Pricing</a></li>
                        <li><a href="#demo" class="text-white/60 hover:text-white">Free Trial</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold text-sm mb-4">Company</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#" class="text-white/60 hover:text-white">About</a></li>
                        <li><a href="#contact" class="text-white/60 hover:text-white">Contact</a></li>
                        <li><a href="#" class="text-white/60 hover:text-white">Blog</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold text-sm mb-4">Resources</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#" class="text-white/60 hover:text-white">Documentation</a></li>
                        <li><a href="#" class="text-white/60 hover:text-white">API Reference</a></li>
                        <li><a href="#" class="text-white/60 hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="pt-6 border-t border-white/10 text-center">
                <p class="text-white/40 text-xs">
                    © {{ date('Y') }} JewelTag Systems. All rights reserved. | ISO 27001 Certified • SOC 2 Compliant
                </p>
                <div class="flex justify-center space-x-4 mt-4">
                    <i class="fab fa-cc-visa text-white/40 text-lg"></i>
                    <i class="fab fa-cc-mastercard text-white/40 text-lg"></i>
                    <i class="fab fa-cc-amex text-white/40 text-lg"></i>
                    <i class="fab fa-cc-paypal text-white/40 text-lg"></i>
                </div>
            </div>
        </div>
    </footer>

    <!-- Chat Widget -->
    <div class="fixed bottom-6 right-6 z-50">
        <button id="chat-toggle" class="w-14 h-14 gold-gradient rounded-full flex items-center justify-center text-white shadow-lg hover:shadow-xl transition-all">
            <i class="fas fa-comment text-xl"></i>
        </button>
        
        <div id="chat-window" class="absolute bottom-20 right-0 w-80 bg-white rounded-xl shadow-2xl hidden">
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 gold-gradient rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-headset text-white"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm">JewelTag Support</h4>
                        <p class="text-xs text-gray-500">We're online</p>
                    </div>
                </div>
            </div>
            <div class="p-4 h-64 overflow-y-auto">
                <div class="bg-gray-100 rounded-lg p-3 max-w-xs mb-4">
                    <p class="text-xs">Hello! How can we help you today?</p>
                </div>
                <div class="text-center">
                    <button class="px-3 py-2 bg-amber-50 text-amber-700 rounded-lg text-xs font-semibold mr-2">Pricing</button>
                    <button class="px-3 py-2 bg-amber-50 text-amber-700 rounded-lg text-xs font-semibold">Demo</button>
                </div>
            </div>
            <div class="p-4 border-t border-gray-200">
                <div class="flex">
                    <input type="text" placeholder="Type your message..." class="flex-1 border border-gray-300 rounded-l-lg px-4 py-2 text-xs focus:outline-none focus:border-amber-500">
                    <button class="gold-gradient text-white px-4 rounded-r-lg text-sm">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="back-to-top" class="fixed bottom-24 right-6 w-12 h-12 bg-white border border-gray-200 rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transition-all hidden z-40">
        <i class="fas fa-chevron-up text-gray-700"></i>
    </button>

    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });
        
        // Mobile Menu Toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // Chat Toggle
        document.getElementById('chat-toggle').addEventListener('click', function() {
            document.getElementById('chat-window').classList.toggle('hidden');
        });

        // Back to Top
        const backToTop = document.getElementById('back-to-top');
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.classList.remove('hidden');
            } else {
                backToTop.classList.add('hidden');
            }
        });

        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // FAQ Toggle
        window.toggleFAQ = function(number) {
            const faq = document.getElementById('faq-' + number);
            const icon = event.currentTarget.querySelector('i');
            faq.classList.toggle('hidden');
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        }

        // Form Submissions
        document.getElementById('demo-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you! Your trial request has been submitted. Our team will contact you within 1 hour.');
            this.reset();
        });

        document.getElementById('contact-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for your message! We\'ll get back to you within 1 hour.');
            this.reset();
        });

        // Smooth Scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
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