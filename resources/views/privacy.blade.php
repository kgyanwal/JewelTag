{{-- resources/views/privacy.blade.php --}}
@extends('layouts.legal')

@section('content')
<div class="min-h-screen">
    {{-- Hero Section --}}
    <div class="text-center mb-12">
        <div class="inline-flex items-center px-4 py-2 rounded-full bg-[rgba(184,134,59,0.06)] border border-[rgba(184,134,59,0.25)] mb-6">
            <i class="fas fa-shield-alt text-[var(--brass-dim)] text-sm mr-2"></i>
            <span class="text-[var(--brass-dim)] text-xs font-semibold tracking-wider">YOUR PRIVACY MATTERS</span>
        </div>
        <h1 class="text-5xl lg:text-6xl font-bold fraunces mb-4">
            <span class="gold-gradient-text">Privacy Policy</span>
        </h1>
        <div class="h-1 w-20 gold-gradient mx-auto rounded-full mb-6"></div>
        <p class="text-[var(--ink-soft)] text-lg max-w-2xl mx-auto">
            How we protect your data, respect your privacy, and maintain the highest standards of security for your jewelry business.
        </p>
        <div class="flex items-center justify-center gap-4 mt-6">
            <div class="flex items-center text-[var(--ink-soft)] text-sm">
                <i class="fas fa-calendar-alt mr-2 text-[var(--brass)]"></i>
                <span>Last Updated: <span class="text-[var(--ink)] font-semibold">May 4, 2026</span></span>
            </div>
            <div class="w-1 h-1 bg-[rgba(33,28,22,0.2)] rounded-full"></div>
            <div class="flex items-center text-[var(--ink-soft)] text-sm">
                <i class="fas fa-clock mr-2 text-[var(--brass)]"></i>
                <span>Effective: <span class="text-[var(--ink)] font-semibold">Immediately</span></span>
            </div>
        </div>
    </div>

    {{-- Introduction Box --}}
    <div class="bg-[rgba(184,134,59,0.06)] rounded-2xl p-6 mb-10 border-l-4 border-[var(--brass)]">
        <div class="flex items-start">
            <div class="w-10 h-10 gold-gradient rounded-xl flex items-center justify-center flex-shrink-0 mr-4">
                <i class="fas fa-gem text-[var(--onyx)] text-sm"></i>
            </div>
            <div>
                <h2 class="font-bold text-[var(--onyx)] text-lg mb-2">JewelTag Privacy Commitment</h2>
                <p class="text-[var(--ink-soft)] text-sm leading-relaxed">
                    At <span class="font-semibold text-[var(--brass-dim)]">JewelTag.us</span>, we understand that your jewelry business handles sensitive client information,
                    high-value inventory data, and financial transactions. This Privacy Policy explains how we collect, use, disclose,
                    and safeguard your information when you use our platform, including our POS system, CRM, inventory management, and related services.
                </p>
                <p class="text-sm text-[var(--ink-soft)] mt-3">
                    <i class="fas fa-check-circle text-[var(--brass)] mr-1"></i>
                    <strong>Our Promise:</strong> We never sell your personal data or your customers' information to third parties.
                    We are GDPR, CCPA, and PCI DSS compliant.
                </p>
            </div>
        </div>
    </div>

    {{-- Table of Contents Grid --}}
    <div class="grid md:grid-cols-2 gap-4 mb-12">
        @php
            $tocItems = [
                ['id' => 'info-collect', 'icon' => 'fa-database', 'title' => 'Information We Collect'],
                ['id' => 'use-info', 'icon' => 'fa-chart-line', 'title' => 'How We Use Your Information'],
                ['id' => 'share-info', 'icon' => 'fa-share-alt', 'title' => 'Information Sharing'],
                ['id' => 'security', 'icon' => 'fa-lock', 'title' => 'Data Security'],
                ['id' => 'cookies', 'icon' => 'fa-cookie-bite', 'title' => 'Cookies & Tracking'],
                ['id' => 'rights', 'icon' => 'fa-user-check', 'title' => 'Your Rights'],
                ['id' => 'children', 'icon' => 'fa-child', 'title' => "Children's Privacy"],
                ['id' => 'international', 'icon' => 'fa-globe', 'title' => 'International Transfers'],
                ['id' => 'updates', 'icon' => 'fa-sync-alt', 'title' => 'Policy Updates'],
                ['id' => 'contact', 'icon' => 'fa-envelope', 'title' => 'Contact Us']
            ];
        @endphp
        @foreach($tocItems as $item)
            <a href="#{{ $item['id'] }}" class="flex items-center p-4 bg-[var(--case-felt)] rounded-xl border border-[rgba(33,28,22,0.08)] hover:border-[rgba(184,134,59,0.4)] hover:bg-[rgba(184,134,59,0.05)] transition-all group">
                <div class="w-10 h-10 bg-[rgba(184,134,59,0.12)] rounded-lg flex items-center justify-center mr-3 group-hover:bg-[rgba(184,134,59,0.22)] transition">
                    <i class="fas {{ $item['icon'] }} text-[var(--brass-dim)] text-sm"></i>
                </div>
                <span class="text-[var(--ink-soft)] font-medium group-hover:text-[var(--brass-dim)]">{{ $item['title'] }}</span>
                <i class="fas fa-chevron-right ml-auto text-[rgba(33,28,22,0.25)] text-xs group-hover:text-[var(--brass)]"></i>
            </a>
        @endforeach
    </div>

    {{-- Section 1: Information We Collect --}}
    <div id="info-collect" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-[rgba(33,28,22,0.08)]">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-database text-[var(--onyx)] text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold fraunces text-[var(--onyx)]">1. Information We Collect</h2>
        </div>
        <p class="text-[var(--ink-soft)] leading-relaxed mb-4">
            We collect information that you provide directly to us, information generated through your use of our services,
            and information from third-party sources as permitted by law.
        </p>
        <div class="grid md:grid-cols-2 gap-4 mt-4">
            <div class="bg-[var(--case-felt)] rounded-xl p-4 border border-[rgba(33,28,22,0.08)]">
                <div class="flex items-center mb-2">
                    <i class="fas fa-user-circle text-[var(--brass)] text-lg mr-2"></i>
                    <h4 class="font-bold text-[var(--onyx)]">Account Information</h4>
                </div>
                <p class="text-sm text-[var(--ink-soft)]">Business name, contact details (name, email, phone), billing information, tax ID, and user credentials.</p>
            </div>
            <div class="bg-[var(--case-felt)] rounded-xl p-4 border border-[rgba(33,28,22,0.08)]">
                <div class="flex items-center mb-2">
                    <i class="fas fa-gem text-[var(--brass)] text-lg mr-2"></i>
                    <h4 class="font-bold text-[var(--onyx)]">Inventory Data</h4>
                </div>
                <p class="text-sm text-[var(--ink-soft)]">Product details, certifications (GIA, IGI), supplier information, cost data, pricing, and images.</p>
            </div>
            <div class="bg-[var(--case-felt)] rounded-xl p-4 border border-[rgba(33,28,22,0.08)]">
                <div class="flex items-center mb-2">
                    <i class="fas fa-users text-[var(--brass)] text-lg mr-2"></i>
                    <h4 class="font-bold text-[var(--onyx)]">Customer Information</h4>
                </div>
                <p class="text-sm text-[var(--ink-soft)]">Client names, contact details, purchase history, preferences, appointments, and repair records.</p>
            </div>
            <div class="bg-[var(--case-felt)] rounded-xl p-4 border border-[rgba(33,28,22,0.08)]">
                <div class="flex items-center mb-2">
                    <i class="fas fa-chart-line text-[var(--brass)] text-lg mr-2"></i>
                    <h4 class="font-bold text-[var(--onyx)]">Transaction Data</h4>
                </div>
                <p class="text-sm text-[var(--ink-soft)]">Sales records, payment information (processed via secure PCI-compliant gateways), refunds, and layaway details.</p>
            </div>
        </div>
        <p class="mt-4 text-sm text-[var(--ink-soft)] italic bg-[var(--case-felt)] p-3 rounded-lg border border-[rgba(33,28,22,0.06)]">
            <i class="fas fa-info-circle text-[var(--brass)] mr-1"></i>
            We do not store full credit card numbers or CVV codes. All payment processing is handled by our PCI Level 1 certified partners.
        </p>
    </div>

    {{-- Section 2: How We Use Your Information --}}
    <div id="use-info" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-[rgba(33,28,22,0.08)]">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-chart-line text-[var(--onyx)] text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold fraunces text-[var(--onyx)]">2. How We Use Your Information</h2>
        </div>
        <p class="text-[var(--ink-soft)] leading-relaxed mb-3">We use the information we collect to:</p>
        <ul class="grid md:grid-cols-2 gap-2 mb-4">
            <li class="flex items-start"><i class="fas fa-check-circle text-[var(--loupe-dim,#3E7C5A)] mt-0.5 mr-2 text-sm"></i> <span class="text-[var(--ink-soft)]">Operate, maintain, and improve our platform and features</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-[var(--loupe-dim,#3E7C5A)] mt-0.5 mr-2 text-sm"></i> <span class="text-[var(--ink-soft)]">Process transactions and send related information (receipts, invoices)</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-[var(--loupe-dim,#3E7C5A)] mt-0.5 mr-2 text-sm"></i> <span class="text-[var(--ink-soft)]">Generate analytics and business intelligence reports</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-[var(--loupe-dim,#3E7C5A)] mt-0.5 mr-2 text-sm"></i> <span class="text-[var(--ink-soft)]">Provide customer support and respond to inquiries</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-[var(--loupe-dim,#3E7C5A)] mt-0.5 mr-2 text-sm"></i> <span class="text-[var(--ink-soft)]">Send administrative communications (security alerts, policy updates)</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-[var(--loupe-dim,#3E7C5A)] mt-0.5 mr-2 text-sm"></i> <span class="text-[var(--ink-soft)]">Prevent fraud, unauthorized transactions, and protect security</span></li>
        </ul>
    </div>

    {{-- Section 3: Information Sharing --}}
    <div id="share-info" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-[rgba(33,28,22,0.08)]">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-share-alt text-[var(--onyx)] text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold fraunces text-[var(--onyx)]">3. Information Sharing &amp; Disclosure</h2>
        </div>
        <p class="text-[var(--ink-soft)] leading-relaxed mb-3">We do not sell your personal information. We may share information in the following circumstances:</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-[var(--case-felt)]">
                    <tr>
                        <th class="p-3 text-left font-semibold text-[var(--onyx)] border-b border-[rgba(33,28,22,0.1)]">Category</th>
                        <th class="p-3 text-left font-semibold text-[var(--onyx)] border-b border-[rgba(33,28,22,0.1)]">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgba(33,28,22,0.06)]">
                    <tr>
                        <td class="p-3 font-medium text-[var(--ink)]">Service Providers</td>
                        <td class="p-3 text-[var(--ink-soft)]">Cloud hosting (AWS), payment processors (Stripe, PayPal), analytics (Google), and support tools.</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium text-[var(--ink)]">Legal Compliance</td>
                        <td class="p-3 text-[var(--ink-soft)]">When required by law, court order, or to protect rights, property, or safety.</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium text-[var(--ink)]">Business Transfers</td>
                        <td class="p-3 text-[var(--ink-soft)]">In connection with a merger, acquisition, or sale of assets (with notice).</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium text-[var(--ink)]">With Your Consent</td>
                        <td class="p-3 text-[var(--ink-soft)]">When you explicitly authorize us to share information.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Section 4: Data Security --}}
    <div id="security" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-[rgba(33,28,22,0.08)]">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-lock text-[var(--onyx)] text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold fraunces text-[var(--onyx)]">4. Data Security</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-4 mb-4">
            <div class="text-center p-4 bg-[var(--case-felt)] rounded-xl border border-[rgba(33,28,22,0.06)]">
                <i class="fas fa-key text-2xl text-[var(--brass)] mb-2 block"></i>
                <h4 class="font-bold text-sm text-[var(--onyx)]">256-bit Encryption</h4>
                <p class="text-xs text-[var(--ink-soft)]">AES-256 for data at rest and TLS 1.3 for data in transit</p>
            </div>
            <div class="text-center p-4 bg-[var(--case-felt)] rounded-xl border border-[rgba(33,28,22,0.06)]">
                <i class="fas fa-database text-2xl text-[var(--brass)] mb-2 block"></i>
                <h4 class="font-bold text-sm text-[var(--onyx)]">Daily Backups</h4>
                <p class="text-xs text-[var(--ink-soft)]">Automated encrypted backups with 30-day retention</p>
            </div>
            <div class="text-center p-4 bg-[var(--case-felt)] rounded-xl border border-[rgba(33,28,22,0.06)]">
                <i class="fas fa-shield-virus text-2xl text-[var(--brass)] mb-2 block"></i>
                <h4 class="font-bold text-sm text-[var(--onyx)]">SOC 2 Type II</h4>
                <p class="text-xs text-[var(--ink-soft)]">Audited security controls and compliance</p>
            </div>
        </div>
        <p class="text-[var(--ink-soft)] text-sm">We implement industry-standard security measures, including regular security assessments, penetration testing, employee security training, and access controls. However, no method of transmission over the Internet is 100% secure.</p>
    </div>

    {{-- Section 5: Cookies --}}
    <div id="cookies" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-[rgba(33,28,22,0.08)]">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-cookie-bite text-[var(--onyx)] text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold fraunces text-[var(--onyx)]">5. Cookies &amp; Tracking Technologies</h2>
        </div>
        <p class="text-[var(--ink-soft)] mb-3">We use cookies and similar technologies to enhance your experience, analyze usage, and personalize content. You can control cookies through your browser settings.</p>
        <div class="bg-[var(--case-felt)] rounded-xl p-4 border border-[rgba(33,28,22,0.06)]">
            <div class="flex flex-wrap gap-2">
                <span class="px-3 py-1 bg-white rounded-full text-xs border border-[rgba(33,28,22,0.1)]">Essential Cookies (always active)</span>
                <span class="px-3 py-1 bg-white rounded-full text-xs border border-[rgba(33,28,22,0.1)]">Analytics Cookies (opt-out available)</span>
                <span class="px-3 py-1 bg-white rounded-full text-xs border border-[rgba(33,28,22,0.1)]">Preference Cookies</span>
            </div>
            <p class="text-xs text-[var(--ink-soft)] mt-3">You can manage your cookie preferences by clicking the "Cookie Settings" link in our footer.</p>
        </div>
    </div>

    {{-- Section 6: Your Rights --}}
    <div id="rights" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-[rgba(33,28,22,0.08)]">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-user-check text-[var(--onyx)] text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold fraunces text-[var(--onyx)]">6. Your Privacy Rights</h2>
        </div>
        <p class="text-[var(--ink-soft)] mb-3">Depending on your location, you may have the following rights:</p>
        <ul class="space-y-2 mb-4">
            <li class="flex items-start"><i class="fas fa-check-circle text-[var(--loupe-dim,#3E7C5A)] mt-0.5 mr-2 text-sm"></i> <span class="text-[var(--ink-soft)]"><strong>Access:</strong> Request a copy of your personal data</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-[var(--loupe-dim,#3E7C5A)] mt-0.5 mr-2 text-sm"></i> <span class="text-[var(--ink-soft)]"><strong>Correction:</strong> Update inaccurate or incomplete information</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-[var(--loupe-dim,#3E7C5A)] mt-0.5 mr-2 text-sm"></i> <span class="text-[var(--ink-soft)]"><strong>Deletion:</strong> Request deletion of your personal data (subject to legal retention)</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-[var(--loupe-dim,#3E7C5A)] mt-0.5 mr-2 text-sm"></i> <span class="text-[var(--ink-soft)]"><strong>Opt-out:</strong> Decline sale of personal information (we don't sell it)</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-[var(--loupe-dim,#3E7C5A)] mt-0.5 mr-2 text-sm"></i> <span class="text-[var(--ink-soft)]"><strong>Portability:</strong> Receive your data in a structured format</span></li>
        </ul>
        <div class="bg-[rgba(184,134,59,0.06)] rounded-xl p-4 border border-[rgba(184,134,59,0.18)]">
            <p class="text-sm"><i class="fas fa-envelope text-[var(--brass-dim)] mr-2"></i> To exercise your rights, contact us at <a href="mailto:privacy@jeweltag.us" class="text-[var(--brass-dim)] font-semibold hover:underline">privacy@jeweltag.us</a> or call <strong class="text-[var(--onyx)]">+1 +1 505-810-7222</strong>.</p>
        </div>
    </div>

    {{-- Section 7: Children's Privacy --}}
    <div id="children" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-[rgba(33,28,22,0.08)]">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-child text-[var(--onyx)] text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold fraunces text-[var(--onyx)]">7. Children's Privacy</h2>
        </div>
        <p class="text-[var(--ink-soft)]">Our services are not directed to individuals under the age of 18. We do not knowingly collect personal information from children. If you become aware that a child has provided us with personal information, please contact us.</p>
    </div>

    {{-- Section 8: International Transfers --}}
    <div id="international" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-[rgba(33,28,22,0.08)]">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-globe text-[var(--onyx)] text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold fraunces text-[var(--onyx)]">8. International Data Transfers</h2>
        </div>
        <p class="text-[var(--ink-soft)]">Your information may be transferred to and processed in the United States and other countries where we operate. We implement Standard Contractual Clauses (SCCs) and other safeguards to ensure adequate protection for international data transfers in compliance with applicable laws.</p>
    </div>

    {{-- Section 9: Policy Updates --}}
    <div id="updates" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-[rgba(33,28,22,0.08)]">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-sync-alt text-[var(--onyx)] text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold fraunces text-[var(--onyx)]">9. Updates to This Policy</h2>
        </div>
        <p class="text-[var(--ink-soft)]">We may update this privacy policy periodically. We will notify you of material changes by posting the new policy on this page, updating the "Last Updated" date, and for significant changes, via email or in-app notification.</p>
    </div>

    {{-- Section 10: Contact Us --}}
    <div id="contact" class="scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-[rgba(33,28,22,0.08)]">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-envelope text-[var(--onyx)] text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold fraunces text-[var(--onyx)]">10. Contact Us</h2>
        </div>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-3">
                <p class="text-[var(--ink-soft)]">If you have questions about this Privacy Policy or our data practices, please contact our Data Protection Officer:</p>
                <div class="bg-[var(--case-felt)] rounded-xl p-4 space-y-2 border border-[rgba(33,28,22,0.06)]">
                    <p><i class="fas fa-building w-6 text-[var(--brass)]"></i> <strong class="text-[var(--onyx)]">JewelTag Systems Inc.</strong></p>
                    <p><i class="fas fa-map-marker-alt w-6 text-[var(--brass)]"></i>6600 Menaul Blvd NE Suite 6508<br>Albuquerque NM 87110</p>
                    <p><i class="fas fa-envelope w-6 text-[var(--brass)]"></i> <a href="mailto:privacy@jeweltag.us" class="text-[var(--brass-dim)] hover:underline">privacy@jeweltag.us</a></p>
                    <p><i class="fas fa-phone w-6 text-[var(--brass)]"></i> <a href="tel:18005393582" class="text-[var(--brass-dim)] hover:underline">+1 +1 505-810-7222</a></p>
                </div>
            </div>
            <div class="bg-[rgba(184,134,59,0.06)] rounded-xl p-6 border border-[rgba(184,134,59,0.22)]">
                <div class="flex items-center mb-3">
                    <i class="fas fa-gavel text-2xl text-[var(--brass-dim)] mr-3"></i>
                    <h4 class="font-bold text-[var(--onyx)]">Legal Disclosures</h4>
                </div>
                <p class="text-sm text-[var(--ink-soft)] mb-2">For legal requests or to report a privacy concern, you may also contact our legal counsel at:</p>
                <p class="text-sm font-semibold text-[var(--brass-dim)]">legal@jeweltag.us</p>
                <div class="mt-4 pt-3 border-t border-[rgba(184,134,59,0.2)]">
                    <p class="text-xs text-[var(--ink-soft)]">For residents of the European Union, you have the right to lodge a complaint with your local supervisory authority.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Back to Top Button --}}
    <button id="back-to-top" class="fixed bottom-6 right-6 w-12 h-12 bg-white border border-[rgba(33,28,22,0.12)] rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transition-all hidden z-40">
        <i class="fas fa-chevron-up text-[var(--ink-soft)]"></i>
    </button>
</div>

<script>
    // Back to Top functionality
    const backToTopBtn = document.getElementById('back-to-top');

    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 500) {
            backToTopBtn.classList.remove('hidden');
        } else {
            backToTopBtn.classList.add('hidden');
        }
    });

    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });
</script>
@endsection