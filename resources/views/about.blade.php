{{-- resources/views/about.blade.php --}}
@extends('layouts.legal')

@section('content')
<div class="min-h-screen">

    {{-- ── HERO ── --}}
    <div class="relative rounded-2xl overflow-hidden mb-16" style="background:linear-gradient(135deg,#0A2540 0%,#1E3A8A 100%);min-height:420px;">
        {{-- Grid overlay --}}
        <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.03) 1px,transparent 1px);background-size:44px 44px;"></div>
        {{-- Glow orbs --}}
        <div style="position:absolute;width:500px;height:500px;background:radial-gradient(circle,rgba(217,119,6,0.18) 0%,transparent 70%);top:-250px;right:-150px;border-radius:50%;"></div>
        <div style="position:absolute;width:400px;height:400px;background:radial-gradient(circle,rgba(59,130,246,0.12) 0%,transparent 70%);bottom:-200px;left:-100px;border-radius:50%;"></div>

        <div class="relative z-10 flex flex-col items-center justify-center text-center px-8 py-24">
            <div class="inline-flex items-center px-4 py-2 rounded-full mb-6 border" style="background:rgba(255,255,255,0.08);border-color:rgba(217,119,6,0.4);">
                <span style="width:7px;height:7px;border-radius:50%;background:#fbbf24;margin-right:10px;animation:pulse 2s infinite;display:inline-block;"></span>
                <span style="color:rgba(255,255,255,0.85);font-size:0.72rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;">Built for Jewelry Retail</span>
            </div>
            <h1 class="fraunces mb-5" style="font-size:clamp(2.6rem,6vw,4rem);font-weight:800;color:#fff;line-height:1.08;max-width:700px;">
                The Story Behind <span style="background:linear-gradient(135deg,#fbbf24,#f59e0b,#d97706);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">JewelTag</span>
            </h1>
            <p style="color:rgba(255,255,255,0.65);font-size:1.1rem;max-width:560px;line-height:1.75;">
                We built the system we wished existed — one that actually understands how a jewelry counter runs, from the moment a piece arrives to the day a customer walks out with it.
            </p>
        </div>
    </div>

    {{-- ── MISSION ── --}}
    <div class="grid lg:grid-cols-2 gap-12 mb-20 items-center">
        <div>
            <div class="about-eyebrow">Our Mission</div>
            <h2 class="fraunces about-h2">Software that knows the difference between a diamond and a data entry.</h2>
            <p class="about-p mt-4">Most retail software treats jewelry like any other widget in a warehouse. JewelTag was built by people who spent time inside actual jewelry stores — watching how receipts were hand-written, how layaway plans were tracked on paper, how staff had no way to know which items had been sitting unsold for 14 months.</p>
            <p class="about-p mt-3">We set out to fix that — not with a generic POS ported from some other industry, but with a platform designed around the specific rhythms of jewelry retail: certifications, repairs, custom orders, trade-ins, memo stock, commission splits, and everything else that makes this business unlike any other.</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
            @foreach([
                ['fas fa-gem','Built for Jewelry','Every feature maps to a real counter workflow — nothing generic, nothing borrowed from other industries'],
                ['fas fa-shield-halved','Data You Own','Your inventory, customers, and history are yours. Export any time, in full, with no vendor lock-in'],
                ['fas fa-headset','Real Support','When something breaks at 11am on a Saturday — which is when it always breaks — we answer'],
                ['fas fa-code-branch','Always Improving','Every store that runs JewelTag teaches us something new. We ship updates every week'],
            ] as [$icon,$title,$desc])
            <div class="about-card">
                <div class="about-icon-box"><i class="{{ $icon }}"></i></div>
                <div class="text-sm font-bold text-[var(--onyx)] mt-3 mb-1.5">{{ $title }}</div>
                <div class="text-xs text-[var(--ink-soft)] leading-relaxed">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── NUMBERS ── --}}
    <div class="rounded-2xl mb-20 overflow-hidden" style="background:linear-gradient(135deg,#0A2540 0%,#1E3A8A 100%);">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-white/10">
            @foreach([
                ['50+','Jewelry stores actively using JewelTag today'],
                ['44','Database tables powering every feature — nothing bolted on'],
                ['99.5%','Target uptime — because downtime at noon on a Saturday is not acceptable'],
                ['0','Generic retail features that don\'t apply to jewelry — everything is purpose-built'],
            ] as [$num,$label])
            <div class="text-center px-8 py-10">
                <div class="fraunces font-bold mb-2" style="font-size:clamp(2rem,4vw,3rem);background:linear-gradient(135deg,#fbbf24,#f59e0b);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1;">{{ $num }}</div>
                <div style="font-size:0.78rem;color:rgba(255,255,255,0.55);line-height:1.55;">{{ $label }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── WHAT WE BUILT ── --}}
    <div class="mb-20">
        <div class="text-center mb-12">
            <div class="about-eyebrow" style="justify-content:center;">The Platform</div>
            <h2 class="fraunces about-h2 text-center">Everything the counter touches.</h2>
            <p class="about-p text-center mt-3 max-w-xl mx-auto">Eight years of counter experience distilled into one connected system.</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach([
                ['fas fa-tags','#B8863B','Inventory & RFID','62-field item records with RFID, GIA certification storage, Shopify sync, barcode printing, and memo/consignment tracking'],
                ['fas fa-cash-register','#1E3A8A','Point of Sale','Split payments, trade-ins, layaway (with automatic hold status), commission splits, deposit sales, and a 30-second duplicate guard'],
                ['fas fa-paint-brush','#3E7C5A','Custom Orders','Six-stage pipeline from draft to completed — with separate due dates for vendor delivery vs customer promise, and a Pipeline Report that shows everything stuck'],
                ['fas fa-screwdriver-wrench','#5C5346','Repairs','Sequential daily numbering (260609-1), photo intake, status notifications, and integration with Special Jobs in a sale'],
                ['fas fa-chart-bar','#8A6428','Reports','15+ reports including My Sales Report (per-associate commission splits), Stock Aging, Laybuy Health, Warranty, Deposit Sales, and the EOD closing'],
                ['fas fa-heart','#B8463F','CRM_JewelTag','Customer profiles, wishlists, follow-up scheduling, anniversary automation, and SMS/email marketing with 10DLC support'],
            ] as [$icon,$color,$title,$desc])
            <div class="about-feature-card">
                <div class="about-feature-icon" style="background:{{ $color }}20;color:{{ $color }};"><i class="{{ $icon }}"></i></div>
                <h3 class="text-sm font-bold text-[var(--onyx)] mt-3 mb-1.5">{{ $title }}</h3>
                <p class="text-xs text-[var(--ink-soft)] leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── TIMELINE ── --}}
    <div class="mb-20">
        <div class="text-center mb-12">
            <div class="about-eyebrow" style="justify-content:center;">How We Got Here</div>
            <h2 class="fraunces about-h2 text-center">From a spreadsheet problem to a full platform.</h2>
        </div>

        <div class="relative">
            {{-- Vertical line --}}
            <div class="hidden md:block absolute left-1/2 top-0 bottom-0 w-px" style="background:linear-gradient(180deg,rgba(184,134,59,0.4),rgba(184,134,59,0.1));transform:translateX(-50%);"></div>

            @php
            $timeline = [
                ['left', '2018', 'The Spreadsheet Era', 'A jewelry store owner in New Mexico was running inventory on a shared Google Sheet. Three staff members editing simultaneously, items showing as in-stock that had been sold months ago. We were called in to help.'],
                ['right', '2019', 'First Version', 'A basic Laravel CRUD app — inventory, simple sales, a barcode scanner. Deployed to one store. Immediately revealed everything that generic software gets wrong about jewelry: certifications, repairs, layaway.'],
                ['left', '2021', 'Multi-Tenant Architecture', 'The same problems existed in every store we visited. We rebuilt from scratch with Stancl Tenancy — one codebase, isolated databases per tenant, custom subdomains. JewelTag became a platform, not just an app.'],
                ['right', '2022', 'Custom Orders & Repairs', 'Added the six-stage custom order pipeline and the repair work order system with photo documentation. The repair number format (YYMMDD-N) was designed specifically to prevent the confusion that came from sequential numbering across months.'],
                ['left', '2023', 'RFID & Shopify', 'Integrated Zebra ZD621R label printing via ZPL and Browser Print. Built the Shopify push sync with base64 image encoding. Added the jeweltag-instore tag convention so synced items are always filterable.'],
                ['right', '2024', 'CRM_JewelTag', 'Launched the full CRM module — customer profiles with wishlist, anniversary automation, SMS via 10DLC, and the Upcoming Follow-ups report. Also added the Laybuy Health Report and Custom Order Pipeline Report.'],
                ['left', '2025–26', 'AI & Scale', 'Added Gemini AI for product description generation, expanded to 50+ active stores, and rebuilt the reporting layer with the My Sales Report (per-associate commission splits from sales_person_list JSON) and the complete 15+ report suite.'],
            ];
            @endphp

            <div class="space-y-10">
                @foreach($timeline as [$side,$year,$title,$desc])
                <div class="md:grid md:grid-cols-2 md:gap-12 items-center">
                    @if($side === 'left')
                    <div class="about-timeline-card">
                        <div class="about-timeline-year">{{ $year }}</div>
                        <h3 class="text-sm font-bold text-[var(--onyx)] mb-1.5">{{ $title }}</h3>
                        <p class="text-xs text-[var(--ink-soft)] leading-relaxed">{{ $desc }}</p>
                    </div>
                    <div class="hidden md:flex justify-start pl-6">
                        <div class="about-timeline-dot"></div>
                    </div>
                    @else
                    <div class="hidden md:flex justify-end pr-6">
                        <div class="about-timeline-dot"></div>
                    </div>
                    <div class="about-timeline-card">
                        <div class="about-timeline-year">{{ $year }}</div>
                        <h3 class="text-sm font-bold text-[var(--onyx)] mb-1.5">{{ $title }}</h3>
                        <p class="text-xs text-[var(--ink-soft)] leading-relaxed">{{ $desc }}</p>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── PARTNER ── --}}
    <div class="rounded-2xl mb-20 p-8 md:p-10" style="background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);border:1px solid rgba(59,130,246,0.3);">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-8">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#d97706,#b45309);">
                <i class="fas fa-brain text-white text-2xl"></i>
            </div>
            <div class="flex-1">
                <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:rgba(251,191,36,0.7);margin-bottom:6px;">Strategic Partner</div>
                <h3 style="font-size:1.2rem;font-weight:800;color:#fff;margin-bottom:8px;">Creative AI Network</h3>
                <p style="color:rgba(255,255,255,0.6);font-size:0.875rem;line-height:1.65;max-width:520px;">The technical architecture, AI integrations, and ongoing development of JewelTag is built and maintained in partnership with Creative AI Network — specialists in AI-driven software for retail, finance, and healthcare.</p>
            </div>
            <a href="https://creativeainetworks.com" target="_blank" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-bold flex-shrink-0" style="background:rgba(184,134,59,0.14);border:1px solid rgba(184,134,59,0.35);color:rgba(255,255,255,0.85);transition:background .2s;" onmouseover="this.style.background='rgba(184,134,59,0.28)'" onmouseout="this.style.background='rgba(184,134,59,0.14)'">
                Visit Partner <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
    </div>

    {{-- ── TEAM / VALUES ── --}}
    <div class="mb-20">
        <div class="text-center mb-12">
            <div class="about-eyebrow" style="justify-content:center;">How We Work</div>
            <h2 class="fraunces about-h2 text-center">What we believe about software for small business.</h2>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            @foreach([
                ['fas fa-magnifying-glass','You shouldn\'t need IT support to understand your own data','Every report in JewelTag is designed so the store owner can read it without asking anyone what it means. No pivot tables, no export-to-Excel, no consultant required.'],
                ['fas fa-clock','Speed matters at the counter','A customer is standing in front of you. The system should get out of the way. Every lookup, scan, and sale workflow in JewelTag is optimized for counter speed, not back-office completeness.'],
                ['fas fa-lock','Your data stays yours','You can export every record — inventory, customers, sales history, repairs — as CSV or JSON at any time. If you leave, you leave with everything. No data hostage situations.'],
                ['fas fa-comments','Support that knows the product','When you submit a support ticket from inside JewelTag, it reaches the people who built it. Not a call center. Not a tier-1 script. The people who wrote the code.'],
                ['fas fa-code','Built in public, updated weekly','We ship code every week. Bug fixes go out the day they\'re found. Features requested by stores in January are live by March. You\'re using software that is actively maintained, not finished.'],
                ['fas fa-store','Industry-specific, not industry-agnostic','We will never add a "hamburger counter mode" to JewelTag. Every decision we make is filtered through one question: does this make sense for a jewelry store? If not, it doesn\'t ship.'],
            ] as [$icon,$title,$desc])
            <div class="flex gap-5 p-5 rounded-xl border border-[rgba(33,28,22,0.07)] bg-white">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5" style="background:linear-gradient(160deg,#1F1A15,#15120F);color:#E0AE5C;font-size:15px;">
                    <i class="{{ $icon }}"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-[var(--onyx)] mb-1.5">{{ $title }}</h3>
                    <p class="text-xs text-[var(--ink-soft)] leading-relaxed">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── CTA ── --}}
    <div class="rounded-2xl p-8 md:p-12 text-center mb-6" style="background:linear-gradient(135deg,#0A2540 0%,#1E3A8A 100%);position:relative;overflow:hidden;">
        <div style="position:absolute;width:400px;height:400px;background:radial-gradient(circle,rgba(217,119,6,0.2) 0%,transparent 70%);top:-200px;right:-100px;border-radius:50%;pointer-events:none;"></div>
        <div class="relative z-10">
            <h2 class="fraunces mb-4" style="font-size:clamp(1.8rem,4vw,2.8rem);font-weight:800;color:#fff;">Ready to see it on your inventory?</h2>
            <p style="color:rgba(255,255,255,0.65);margin-bottom:28px;max-width:440px;margin-left:auto;margin-right:auto;font-size:0.95rem;line-height:1.7;">30 days, full access, no credit card. We'll migrate your existing data and be on a call with you for the first week.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/#demo" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-full text-sm font-bold" style="background:linear-gradient(135deg,#d97706,#b45309);color:#fff;box-shadow:0 8px 24px rgba(217,119,6,0.4);transition:transform .2s,box-shadow .2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 14px 32px rgba(217,119,6,0.55)'" onmouseout="this.style.transform='';this.style.boxShadow='0 8px 24px rgba(217,119,6,0.4)'">
                    <i class="fas fa-play text-xs"></i> Start Free Trial
                </a>
                <a href="/#contact" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-full text-sm font-bold" style="border:1.5px solid rgba(255,255,255,0.3);color:rgba(255,255,255,0.85);transition:all .2s;" onmouseover="this.style.borderColor='rgba(251,191,36,0.6)';this.style.color='#fbbf24'" onmouseout="this.style.borderColor='rgba(255,255,255,0.3)';this.style.color='rgba(255,255,255,0.85)'">
                    <i class="fas fa-envelope text-xs"></i> Talk to Us First
                </a>
            </div>
        </div>
    </div>

</div>

<style>
.about-eyebrow {
    display:flex; align-items:center; gap:10px;
    font-size:0.7rem; font-weight:700; letter-spacing:0.14em; text-transform:uppercase;
    color:var(--brass-dim); margin-bottom:12px;
}
.about-eyebrow::before { content:''; width:20px; height:1.5px; background:var(--brass); }

.about-h2 {
    font-family:'Fraunces',serif; font-size:clamp(1.5rem,3vw,2.2rem);
    font-weight:700; color:var(--onyx); line-height:1.18;
}

.about-p { color:var(--ink-soft); line-height:1.8; font-size:0.92rem; }

.about-card {
    background:#fff; border:1px solid rgba(33,28,22,0.08); border-radius:14px;
    padding:20px; transition:box-shadow .2s, transform .2s;
}
.about-card:hover { transform:translateY(-4px); box-shadow:0 16px 32px rgba(33,28,22,0.08); }

.about-icon-box {
    width:40px; height:40px; border-radius:10px;
    background:linear-gradient(160deg,#1F1A15,#15120F); color:#E0AE5C;
    display:flex; align-items:center; justify-content:center; font-size:16px;
}

.about-feature-card {
    background:#fff; border:1px solid rgba(33,28,22,0.08); border-radius:16px;
    padding:22px; position:relative; overflow:hidden;
    transition:transform .25s, box-shadow .25s;
}
.about-feature-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,var(--brass-dim),var(--brass-bright));
    transform:scaleX(0); transform-origin:left; transition:transform .3s;
}
.about-feature-card:hover { transform:translateY(-5px); box-shadow:0 20px 40px rgba(33,28,22,0.09); }
.about-feature-card:hover::before { transform:scaleX(1); }

.about-feature-icon {
    width:38px; height:38px; border-radius:10px;
    display:flex; align-items:center; justify-content:center; font-size:15px;
}

.about-timeline-card {
    background:#fff; border:1px solid rgba(33,28,22,0.08); border-radius:14px;
    padding:18px 20px; box-shadow:0 2px 8px rgba(33,28,22,0.04);
}
.about-timeline-year {
    font-family:'Fraunces',serif; font-weight:700;
    background:linear-gradient(135deg,#B8863B,#E0AE5C);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
    font-size:1.1rem; margin-bottom:6px;
}
.about-timeline-dot {
    width:14px; height:14px; border-radius:50%;
    background:var(--brass-bright); border:3px solid #fff;
    box-shadow:0 0 0 3px var(--brass); flex-shrink:0;
    margin-top:22px;
}

@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.4;} }
</style>

@endsection