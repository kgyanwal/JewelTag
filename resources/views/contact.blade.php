{{-- resources/views/contact.blade.php --}}
@extends('layouts.legal')

@section('content')
<div class="min-h-screen">

    {{-- ── HERO ── --}}
    <div class="relative rounded-2xl overflow-hidden mb-16" style="background:linear-gradient(135deg,#0A2540 0%,#1E3A8A 100%);min-height:360px;">
        <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.03) 1px,transparent 1px);background-size:44px 44px;"></div>
        <div style="position:absolute;width:500px;height:500px;background:radial-gradient(circle,rgba(217,119,6,0.2) 0%,transparent 70%);top:-250px;right:-100px;border-radius:50%;"></div>
        <div style="position:absolute;width:400px;height:400px;background:radial-gradient(circle,rgba(59,130,246,0.12) 0%,transparent 70%);bottom:-200px;left:-100px;border-radius:50%;"></div>

        <div class="relative z-10 flex flex-col items-center justify-center text-center px-8 py-20">
            <div class="inline-flex items-center px-4 py-2 rounded-full mb-6 border" style="background:rgba(255,255,255,0.08);border-color:rgba(217,119,6,0.4);">
                <span style="width:7px;height:7px;border-radius:50%;background:#4ade80;margin-right:10px;display:inline-block;animation:ct-pulse 2s ease-in-out infinite;"></span>
                <span style="color:rgba(255,255,255,0.85);font-size:0.72rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;">We're Online — Typical Reply Within 1 Business Day</span>
            </div>
            <h1 class="fraunces mb-4" style="font-size:clamp(2.4rem,6vw,3.8rem);font-weight:800;color:#fff;line-height:1.08;max-width:640px;">
                Let's Talk About
                <span style="background:linear-gradient(135deg,#fbbf24,#f59e0b,#d97706);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"> Your Store</span>
            </h1>
            <p style="color:rgba(255,255,255,0.62);font-size:1rem;max-width:480px;line-height:1.75;">
                Whether you're ready to start a trial, have pricing questions, or just want to see how JewelTag handles your specific workflow — we're here.
            </p>
        </div>
    </div>

    {{-- ── THREE CONTACT CHANNELS ── --}}
    <div class="grid sm:grid-cols-3 gap-5 mb-16">
        @foreach([
            ['fas fa-phone','Call Us',' +1 505-810-7222','Mon–Fri, 10AM–6PM CST','rgba(184,134,59,0.12)','var(--brass-bright)','tel:+18005839524'],
            ['fas fa-envelope','Email Us','info@jeweltag.us','Reply within 1 business day','rgba(59,130,246,0.1)','#60a5fa','mailto:info@jeweltag.us'],
            ['fas fa-comment-dots','Developer Support','info@jeweltag.us','For API & integration questions','rgba(111,207,151,0.1)','#4ade80','mailto:dev@jeweltag.us'],
        ] as [$icon,$label,$value,$sub,$bg,$color,$href])
        <a href="{{ $href }}" class="ct-channel-card group">
            <div class="ct-channel-icon" style="background:{{ $bg }};color:{{ $color }};"><i class="{{ $icon }}"></i></div>
            <div class="ct-channel-label">{{ $label }}</div>
            <div class="ct-channel-value">{{ $value }}</div>
            <div class="ct-channel-sub">{{ $sub }}</div>
            <div class="ct-channel-arrow group-hover:translate-x-1"><i class="fas fa-arrow-right text-xs"></i></div>
        </a>
        @endforeach
    </div>

    {{-- ── MAIN GRID: form + info ── --}}
    <div class="grid lg:grid-cols-[1fr_400px] gap-10 mb-16">

        {{-- FORM --}}
        <div class="bg-white rounded-2xl border border-[rgba(33,28,22,0.08)] shadow-sm overflow-hidden">
            <div class="px-8 py-6 border-b border-[rgba(33,28,22,0.07)]" style="background:linear-gradient(135deg,#0A2540,#1E3A8A);">
                <h2 class="fraunces text-white font-bold" style="font-size:1.35rem;">Send Us a Message</h2>
                <p style="color:rgba(255,255,255,0.6);font-size:0.82rem;margin-top:4px;">Fill in the form and we'll route it to the right person.</p>
            </div>

            <form id="contact-page-form" class="p-8 space-y-5">
                @csrf
                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <label class="ct-label">First Name <span class="text-red-400">*</span></label>
                        <input type="text" name="first_name" placeholder="Sarah" class="ct-input" required>
                    </div>
                    <div>
                        <label class="ct-label">Last Name <span class="text-red-400">*</span></label>
                        <input type="text" name="last_name" placeholder="Chen" class="ct-input" required>
                    </div>
                </div>

                <div>
                    <label class="ct-label">Work Email <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-[var(--ink-soft)] text-sm"></i>
                        <input type="email" name="email" placeholder="sarah@brilliancediamonds.com" class="ct-input pl-11" required>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <label class="ct-label">Business Name <span class="text-red-400">*</span></label>
                        <input type="text" name="business" placeholder="Brilliance Diamonds" class="ct-input" required>
                    </div>
                    <div>
                        <label class="ct-label">Phone Number</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-4 rounded-l-xl border-2 border-r-0 border-[rgba(33,28,22,0.14)] bg-[var(--case-felt)] text-[var(--ink-soft)] text-sm font-bold">+1</span>
                            <input type="tel" name="phone" placeholder="505-555-0100" class="ct-input rounded-l-none border-l-0" style="border-radius:0 10px 10px 0;">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="ct-label">Inquiry Type <span class="text-red-400">*</span></label>
                    <select name="type" class="ct-input" required>
                        <option value="">— Select one —</option>
                        <option value="trial">Start a 30-Day Free Trial</option>
                        <option value="demo">Schedule a Live Demo</option>
                        <option value="pricing">Pricing & Plan Questions</option>
                        <option value="migration">Data Migration Help</option>
                        <option value="support">Technical Support</option>
                        <option value="api">API & Integration Questions</option>
                        <option value="enterprise">Enterprise / Multi-Store</option>
                        <option value="other">Something Else</option>
                    </select>
                </div>

                <div>
                    <label class="ct-label">How Many Items Are in Your Inventory?</label>
                    <select name="inventory_size" class="ct-input">
                        <option value="">— Optional —</option>
                        <option value="<500">Under 500 items</option>
                        <option value="500-2000">500 – 2,000 items</option>
                        <option value="2000-10000">2,000 – 10,000 items</option>
                        <option value=">10000">Over 10,000 items</option>
                    </select>
                </div>

                <div>
                    <label class="ct-label">Your Message <span class="text-red-400">*</span></label>
                    <textarea name="message" rows="5" placeholder="Tell us about your store, what you're running now, and what's not working — the more detail the better." class="ct-input resize-none" required></textarea>
                </div>

                <div class="flex items-start gap-3">
                    <input type="checkbox" id="agree" name="agree" class="mt-1 accent-amber-600" required>
                    <label for="agree" class="text-xs text-[var(--ink-soft)] leading-relaxed">
                        I agree to the <a href="{{ route('privacy') }}" class="text-[var(--brass-dim)] font-semibold hover:underline">Privacy Policy</a> and consent to JewelTag contacting me about my inquiry.
                    </label>
                </div>

                <button type="submit" class="ct-submit-btn" id="ct-submit">
                    <i class="fas fa-paper-plane text-xs"></i>
                    <span id="ct-btn-text">Send Message</span>
                    <i class="fas fa-spinner fa-spin text-xs hidden" id="ct-spinner"></i>
                </button>

                <div id="ct-success" class="hidden rounded-xl p-4 text-center" style="background:rgba(111,207,151,0.1);border:1px solid rgba(111,207,151,0.3);">
                    <i class="fas fa-circle-check text-emerald-600 text-xl mb-2 block"></i>
                    <p class="text-sm font-bold text-emerald-800">Message sent — we'll be in touch within 1 business day.</p>
                </div>
                <div id="ct-error" class="hidden rounded-xl p-4 text-center" style="background:rgba(184,70,63,0.05);border:1px solid rgba(184,70,63,0.2);">
                    <p class="text-sm font-bold" style="color:#7A2B26;">Something went wrong. Please email <a href="mailto:info@jeweltag.us" class="underline">info@jeweltag.us</a> directly.</p>
                </div>
            </form>
        </div>

        {{-- RIGHT SIDEBAR --}}
        <div class="space-y-6">

            {{-- What happens next --}}
            <div class="bg-white rounded-2xl border border-[rgba(33,28,22,0.08)] p-6 shadow-sm">
                <h3 class="fraunces font-bold text-[var(--onyx)] mb-5" style="font-size:1rem;">What happens after you send?</h3>
                <div class="space-y-4">
                    @foreach([
                        ['1','Within 1 business day','A real person on our team reads your message and replies — not a bot, not a template.'],
                        ['2','Discovery call (optional)','If your inquiry is complex, we\'ll suggest a 20-minute call to understand your store setup.'],
                        ['3','Trial or demo','We set up your tenant environment and walk you through your first week, data migration included.'],
                    ] as [$n,$title,$desc])
                    <div class="flex gap-4 items-start">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background:var(--onyx);color:var(--brass-bright);border:1.5px solid var(--brass);font-family:'Fraunces',serif;">{{ $n }}</div>
                        <div>
                            <div class="text-sm font-bold text-[var(--onyx)]">{{ $title }}</div>
                            <div class="text-xs text-[var(--ink-soft)] mt-0.5 leading-relaxed">{{ $desc }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Common questions --}}
            <div class="bg-white rounded-2xl border border-[rgba(33,28,22,0.08)] p-6 shadow-sm">
                <h3 class="fraunces font-bold text-[var(--onyx)] mb-4" style="font-size:1rem;">Quick answers</h3>
                <div class="space-y-3">
                    @foreach([
                        ['Is the trial really free?','Yes — 30 days, full access, no credit card. We migrate your data and support you through the first week at no charge.'],
                        ['How long does setup take?','Most stores are live within 2–3 weeks: 1–3 days discovery, 3–7 days migration, 5–10 days training.'],
                        ['Can we import existing inventory?','Yes. We support CSV import and have helped migrate from spreadsheets, other POS systems, and custom databases.'],
                    ] as [$q,$a])
                    <div class="border-b border-[rgba(33,28,22,0.06)] pb-3 last:border-0 last:pb-0">
                        <div class="text-xs font-bold text-[var(--onyx)] mb-1">{{ $q }}</div>
                        <div class="text-xs text-[var(--ink-soft)] leading-relaxed">{{ $a }}</div>
                    </div>
                    @endforeach
                </div>
                <a href="{{ route('docs') }}" class="inline-flex items-center gap-2 text-xs font-bold text-[var(--brass-dim)] mt-4 hover:underline">
                    <i class="fas fa-book text-xs"></i> Full Documentation
                </a>
            </div>

            {{-- Trust signals --}}
            <div class="rounded-2xl p-6" style="background:linear-gradient(135deg,#0A2540,#1E3A8A);">
                <div class="text-center mb-5">
                    <div style="font-size:0.68rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:rgba(251,191,36,0.7);margin-bottom:6px;">Trusted by</div>
                    <div class="fraunces font-bold text-white" style="font-size:2rem;line-height:1;">50+</div>
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.55);">jewelry retailers across the US</div>
                </div>
                <div class="space-y-3">
                    @foreach([
                        ['"info@jeweltag.us"','General inquiries'],
                        ['"dev@jeweltag.us"','API & developer support'],
                        ['"+1 +1 505-810-7222"','Phone support'],
                    ] as [$val,$label])
                    <div class="flex items-center justify-between py-2 border-b border-white/10 last:border-0">
                        <span style="font-family:'JetBrains Mono',monospace;font-size:0.72rem;color:rgba(251,191,36,0.85);">{{ $val }}</span>
                        <span style="font-size:0.68rem;color:rgba(255,255,255,0.45);">{{ $label }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    {{-- ── MAP / LOCATION STRIP ── --}}
    <div class="grid sm:grid-cols-3 gap-5 mb-16">
        @foreach([
            ['fas fa-map-pin','Headquartered in','New Mexico, USA','The Explorers USA DBA JewelTag'],
            ['fas fa-clock','Business Hours','Mon – Fri, 8AM – 8PM CT','Emergency support available for active tenants'],
            ['fas fa-shield-halved','Security','256-bit encryption','All data encrypted at rest and in transit'],
        ] as [$icon,$label,$value,$sub])
        <div class="flex gap-4 items-start p-5 bg-white rounded-xl border border-[rgba(33,28,22,0.07)]">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background:linear-gradient(160deg,#1F1A15,#15120F);color:#E0AE5C;font-size:15px;">
                <i class="{{ $icon }}"></i>
            </div>
            <div>
                <div style="font-size:0.68rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--ink-soft);margin-bottom:3px;">{{ $label }}</div>
                <div class="text-sm font-bold text-[var(--onyx)]">{{ $value }}</div>
                <div style="font-size:0.72rem;color:var(--ink-soft);margin-top:2px;">{{ $sub }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── TESTIMONIAL STRIP ── --}}
    <div class="rounded-2xl p-8 mb-6" style="background:linear-gradient(135deg,rgba(184,134,59,0.06),rgba(184,134,59,0.02));border:1px solid rgba(184,134,59,0.18);">
        <div class="grid md:grid-cols-3 gap-6">
            @foreach([
                ['"We were running on a shared Google Sheet. JewelTag took us from chaos to 99.8% stock accuracy in the first month."','Sarah B.','Manager, Texas'],
                ['"Repair admin time dropped 70%. Customers stopped calling to ask where their piece was — the system tells them automatically."','Michael R.','Manager, New Mexico'],
                ['"The commission split tracking alone justified the subscription. My staff trusts their pay now because they can see every number."','James W.','Owner, Colorado'],
            ] as [$quote,$name,$role])
            <div class="relative pl-5" style="border-left:3px solid rgba(184,134,59,0.4);">
                <p class="text-sm text-[var(--ink-soft)] leading-relaxed italic mb-3">{{ $quote }}</p>
                <div class="text-xs font-bold text-[var(--onyx)]">{{ $name }}</div>
                <div class="text-xs text-[var(--ink-soft)]">{{ $role }}</div>
            </div>
            @endforeach
        </div>
    </div>

</div>

<style>
@keyframes ct-pulse { 0%,100%{opacity:1;} 50%{opacity:.35;} }

.ct-label { display:block; font-size:0.75rem; font-weight:700; color:var(--ink-soft); margin-bottom:7px; letter-spacing:0.03em; }

.ct-input {
    width:100%; padding:13px 16px;
    border:1.5px solid rgba(33,28,22,0.14); border-radius:10px;
    background:var(--case-felt); font-size:0.88rem; color:var(--ink);
    transition:border-color 150ms, box-shadow 150ms, background 150ms;
    display:block;
}
.ct-input:focus {
    outline:none; border-color:var(--brass);
    box-shadow:0 0 0 4px rgba(184,134,59,0.12);
    background:#ffffff;
}
.ct-input::placeholder { color:rgba(33,28,22,0.3); }

.ct-submit-btn {
    width:100%; padding:15px; border-radius:50px;
    background:linear-gradient(135deg,#d97706,#b45309);
    color:#fff; font-weight:800; font-size:0.9rem; letter-spacing:0.04em; text-transform:uppercase;
    display:flex; align-items:center; justify-content:center; gap:10px;
    box-shadow:0 8px 24px rgba(217,119,6,0.35);
    transition:transform .2s, box-shadow .2s;
    cursor:pointer; border:none;
}
.ct-submit-btn:hover { transform:translateY(-2px); box-shadow:0 14px 32px rgba(217,119,6,0.5); }
.ct-submit-btn:active { transform:translateY(0); }

.ct-channel-card {
    display:flex; flex-direction:column; align-items:center; text-align:center;
    padding:28px 20px; background:#fff; border-radius:18px;
    border:1px solid rgba(33,28,22,0.08); text-decoration:none;
    transition:transform .25s, box-shadow .25s, border-color .25s;
    position:relative; overflow:hidden;
}
.ct-channel-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,var(--brass-dim),var(--brass-bright));
    transform:scaleX(0); transform-origin:left; transition:transform .3s;
}
.ct-channel-card:hover { transform:translateY(-6px); box-shadow:0 20px 40px rgba(33,28,22,0.09); border-color:rgba(184,134,59,0.25); }
.ct-channel-card:hover::before { transform:scaleX(1); }
.ct-channel-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:20px; margin-bottom:14px; }
.ct-channel-label { font-size:0.68rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--ink-soft); margin-bottom:5px; }
.ct-channel-value { font-size:0.9rem; font-weight:800; color:var(--onyx); margin-bottom:3px; }
.ct-channel-sub { font-size:0.72rem; color:var(--ink-soft); }
.ct-channel-arrow { margin-top:12px; color:var(--brass-dim); transition:transform .2s; }
</style>

<script>
document.getElementById('contact-page-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const btn       = document.getElementById('ct-submit');
    const btnText   = document.getElementById('ct-btn-text');
    const spinner   = document.getElementById('ct-spinner');
    const success   = document.getElementById('ct-success');
    const error     = document.getElementById('ct-error');

    // Loading state
    btn.disabled    = true;
    btnText.textContent = 'Sending…';
    spinner.classList.remove('hidden');
    success.classList.add('hidden');
    error.classList.add('hidden');

    const data = new FormData(this);
    // Combine first + last name for the /contact endpoint
    data.append('name', data.get('first_name') + ' ' + data.get('last_name'));

    fetch('/contact', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                success.classList.remove('hidden');
                this.reset();
            } else {
                error.classList.remove('hidden');
            }
        })
        .catch(() => error.classList.remove('hidden'))
        .finally(() => {
            btn.disabled    = false;
            btnText.textContent = 'Send Message';
            spinner.classList.add('hidden');
        });
});
</script>

@endsection