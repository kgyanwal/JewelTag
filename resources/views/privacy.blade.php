{{-- resources/views/privacy.blade.php --}}
@extends('layouts.legal')

@section('content')
<div class="min-h-screen">
    {{-- Hero Section --}}
    <div class="text-center mb-12">
        <div class="inline-flex items-center px-4 py-2 rounded-full bg-amber-50 border border-amber-200 mb-6">
            <i class="fas fa-shield-alt text-amber-600 text-sm mr-2"></i>
            <span class="text-amber-700 text-xs font-semibold tracking-wider">YOUR PRIVACY MATTERS</span>
        </div>
        <h1 class="text-5xl lg:text-6xl font-bold playfair mb-4">
            <span class="gold-gradient-text">Privacy Policy</span>
        </h1>
        <div class="h-1 w-20 gold-gradient mx-auto rounded-full mb-6"></div>
        <p class="text-gray-600 text-lg max-w-2xl mx-auto">
            How we protect your data, respect your privacy, and maintain the highest standards of security for your jewelry business.
        </p>
        <div class="flex items-center justify-center gap-4 mt-6">
            <div class="flex items-center text-gray-500 text-sm">
                <i class="fas fa-calendar-alt mr-2 text-amber-500"></i>
                <span>Last Updated: <span class="text-gray-700 font-semibold">May 4, 2026</span></span>
            </div>
            <div class="w-1 h-1 bg-gray-300 rounded-full"></div>
            <div class="flex items-center text-gray-500 text-sm">
                <i class="fas fa-clock mr-2 text-amber-500"></i>
                <span>Effective: <span class="text-gray-700 font-semibold">Immediately</span></span>
            </div>
        </div>
    </div>

    {{-- Introduction Box --}}
    <div class="bg-amber-50 rounded-2xl p-6 mb-10 border-l-4 border-amber-500">
        <div class="flex items-start">
            <div class="w-10 h-10 gold-gradient rounded-xl flex items-center justify-center flex-shrink-0 mr-4">
                <i class="fas fa-gem text-white text-sm"></i>
            </div>
            <div>
                <h2 class="font-bold text-deep-sapphire text-lg mb-2">JewelTag Privacy Commitment</h2>
                <p class="text-gray-700 text-sm leading-relaxed">
                    At <span class="font-semibold text-amber-600">JewelTag.us</span>, we understand that your jewelry business handles sensitive client information, 
                    high-value inventory data, and financial transactions. This Privacy Policy explains how we collect, use, disclose, 
                    and safeguard your information when you use our platform, including our POS system, CRM, inventory management, and related services.
                </p>
                <p class="text-sm text-gray-600 mt-3">
                    <i class="fas fa-check-circle text-amber-500 mr-1"></i>
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
            <a href="#{{ $item['id'] }}" class="flex items-center p-4 bg-gray-50 rounded-xl border border-gray-100 hover:border-amber-200 hover:bg-amber-50/30 transition-all group">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-amber-200 transition">
                    <i class="fas {{ $item['icon'] }} text-amber-600 text-sm"></i>
                </div>
                <span class="text-gray-700 font-medium group-hover:text-amber-600">{{ $item['title'] }}</span>
                <i class="fas fa-chevron-right ml-auto text-gray-300 text-xs group-hover:text-amber-400"></i>
            </a>
        @endforeach
    </div>

    {{-- Section 1: Information We Collect --}}
    <div id="info-collect" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-database text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">1. Information We Collect</h2>
        </div>
        <p class="text-gray-600 leading-relaxed mb-4">
            We collect information that you provide directly to us, information generated through your use of our services, 
            and information from third-party sources as permitted by law.
        </p>
        <div class="grid md:grid-cols-2 gap-4 mt-4">
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                <div class="flex items-center mb-2">
                    <i class="fas fa-user-circle text-amber-500 text-lg mr-2"></i>
                    <h4 class="font-bold text-deep-sapphire">Account Information</h4>
                </div>
                <p class="text-sm text-gray-600">Business name, contact details (name, email, phone), billing information, tax ID, and user credentials.</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                <div class="flex items-center mb-2">
                    <i class="fas fa-gem text-amber-500 text-lg mr-2"></i>
                    <h4 class="font-bold text-deep-sapphire">Inventory Data</h4>
                </div>
                <p class="text-sm text-gray-600">Product details, certifications (GIA, IGI), supplier information, cost data, pricing, and images.</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                <div class="flex items-center mb-2">
                    <i class="fas fa-users text-amber-500 text-lg mr-2"></i>
                    <h4 class="font-bold text-deep-sapphire">Customer Information</h4>
                </div>
                <p class="text-sm text-gray-600">Client names, contact details, purchase history, preferences, appointments, and repair records.</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                <div class="flex items-center mb-2">
                    <i class="fas fa-chart-line text-amber-500 text-lg mr-2"></i>
                    <h4 class="font-bold text-deep-sapphire">Transaction Data</h4>
                </div>
                <p class="text-sm text-gray-600">Sales records, payment information (processed via secure PCI-compliant gateways), refunds, and layaway details.</p>
            </div>
        </div>
        <p class="mt-4 text-sm text-gray-500 italic bg-gray-50 p-3 rounded-lg">
            <i class="fas fa-info-circle text-amber-500 mr-1"></i>
            We do not store full credit card numbers or CVV codes. All payment processing is handled by our PCI Level 1 certified partners.
        </p>
    </div>

    {{-- Section 2: How We Use Your Information --}}
    <div id="use-info" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-chart-line text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">2. How We Use Your Information</h2>
        </div>
        <p class="text-gray-600 leading-relaxed mb-3">We use the information we collect to:</p>
        <ul class="grid md:grid-cols-2 gap-2 mb-4">
            <li class="flex items-start"><i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-2 text-sm"></i> <span class="text-gray-600">Operate, maintain, and improve our platform and features</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-2 text-sm"></i> <span class="text-gray-600">Process transactions and send related information (receipts, invoices)</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-2 text-sm"></i> <span class="text-gray-600">Generate analytics and business intelligence reports</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-2 text-sm"></i> <span class="text-gray-600">Provide customer support and respond to inquiries</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-2 text-sm"></i> <span class="text-gray-600">Send administrative communications (security alerts, policy updates)</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-2 text-sm"></i> <span class="text-gray-600">Prevent fraud, unauthorized transactions, and protect security</span></li>
        </ul>
    </div>

    {{-- Section 3: Information Sharing --}}
    <div id="share-info" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-share-alt text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">3. Information Sharing & Disclosure</h2>
        </div>
        <p class="text-gray-600 leading-relaxed mb-3">We do not sell your personal information. We may share information in the following circumstances:</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3 text-left font-semibold text-deep-sapphire border-b">Category</th>
                        <th class="p-3 text-left font-semibold text-deep-sapphire border-b">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="p-3 font-medium text-gray-700">Service Providers</td>
                        <td class="p-3 text-gray-600">Cloud hosting (AWS), payment processors (Stripe, PayPal), analytics (Google), and support tools.</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium text-gray-700">Legal Compliance</td>
                        <td class="p-3 text-gray-600">When required by law, court order, or to protect rights, property, or safety.</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium text-gray-700">Business Transfers</td>
                        <td class="p-3 text-gray-600">In connection with a merger, acquisition, or sale of assets (with notice).</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium text-gray-700">With Your Consent</td>
                        <td class="p-3 text-gray-600">When you explicitly authorize us to share information.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Section 4: Data Security --}}
    <div id="security" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-lock text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">4. Data Security</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-4 mb-4">
            <div class="text-center p-4 bg-gray-50 rounded-xl">
                <i class="fas fa-key text-2xl text-amber-500 mb-2 block"></i>
                <h4 class="font-bold text-sm text-deep-sapphire">256-bit Encryption</h4>
                <p class="text-xs text-gray-500">AES-256 for data at rest and TLS 1.3 for data in transit</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-xl">
                <i class="fas fa-database text-2xl text-amber-500 mb-2 block"></i>
                <h4 class="font-bold text-sm text-deep-sapphire">Daily Backups</h4>
                <p class="text-xs text-gray-500">Automated encrypted backups with 30-day retention</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-xl">
                <i class="fas fa-shield-virus text-2xl text-amber-500 mb-2 block"></i>
                <h4 class="font-bold text-sm text-deep-sapphire">SOC 2 Type II</h4>
                <p class="text-xs text-gray-500">Audited security controls and compliance</p>
            </div>
        </div>
        <p class="text-gray-600 text-sm">We implement industry-standard security measures, including regular security assessments, penetration testing, employee security training, and access controls. However, no method of transmission over the Internet is 100% secure.</p>
    </div>

    {{-- Section 5: Cookies --}}
    <div id="cookies" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-cookie-bite text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">5. Cookies & Tracking Technologies</h2>
        </div>
        <p class="text-gray-600 mb-3">We use cookies and similar technologies to enhance your experience, analyze usage, and personalize content. You can control cookies through your browser settings.</p>
        <div class="bg-gray-50 rounded-xl p-4">
            <div class="flex flex-wrap gap-2">
                <span class="px-3 py-1 bg-white rounded-full text-xs border">Essential Cookies (always active)</span>
                <span class="px-3 py-1 bg-white rounded-full text-xs border">Analytics Cookies (opt-out available)</span>
                <span class="px-3 py-1 bg-white rounded-full text-xs border">Preference Cookies</span>
            </div>
            <p class="text-xs text-gray-500 mt-3">You can manage your cookie preferences by clicking the "Cookie Settings" link in our footer.</p>
        </div>
    </div>

    {{-- Section 6: Your Rights --}}
    <div id="rights" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-user-check text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">6. Your Privacy Rights</h2>
        </div>
        <p class="text-gray-600 mb-3">Depending on your location, you may have the following rights:</p>
        <ul class="space-y-2 mb-4">
            <li class="flex items-start"><i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-2 text-sm"></i> <span class="text-gray-600"><strong>Access:</strong> Request a copy of your personal data</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-2 text-sm"></i> <span class="text-gray-600"><strong>Correction:</strong> Update inaccurate or incomplete information</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-2 text-sm"></i> <span class="text-gray-600"><strong>Deletion:</strong> Request deletion of your personal data (subject to legal retention)</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-2 text-sm"></i> <span class="text-gray-600"><strong>Opt-out:</strong> Decline sale of personal information (we don't sell it)</span></li>
            <li class="flex items-start"><i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-2 text-sm"></i> <span class="text-gray-600"><strong>Portability:</strong> Receive your data in a structured format</span></li>
        </ul>
        <div class="bg-amber-50 rounded-xl p-4">
            <p class="text-sm"><i class="fas fa-envelope text-amber-600 mr-2"></i> To exercise your rights, contact us at <a href="mailto:privacy@jeweltag.us" class="text-amber-600 font-semibold hover:underline">privacy@jeweltag.us</a> or call <strong class="text-deep-sapphire">1-800-JEWEL-TAG</strong>.</p>
        </div>
    </div>

    {{-- Section 7: Children's Privacy --}}
    <div id="children" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-child text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">7. Children's Privacy</h2>
        </div>
        <p class="text-gray-600">Our services are not directed to individuals under the age of 18. We do not knowingly collect personal information from children. If you become aware that a child has provided us with personal information, please contact us.</p>
    </div>

    {{-- Section 8: International Transfers --}}
    <div id="international" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-globe text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">8. International Data Transfers</h2>
        </div>
        <p class="text-gray-600">Your information may be transferred to and processed in the United States and other countries where we operate. We implement Standard Contractual Clauses (SCCs) and other safeguards to ensure adequate protection for international data transfers in compliance with applicable laws.</p>
    </div>

    {{-- Section 9: Policy Updates --}}
    <div id="updates" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-sync-alt text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">9. Updates to This Policy</h2>
        </div>
        <p class="text-gray-600">We may update this privacy policy periodically. We will notify you of material changes by posting the new policy on this page, updating the "Last Updated" date, and for significant changes, via email or in-app notification.</p>
    </div>

    {{-- Section 10: Contact Us --}}
    <div id="contact" class="scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-envelope text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">10. Contact Us</h2>
        </div>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-3">
                <p class="text-gray-600">If you have questions about this Privacy Policy or our data practices, please contact our Data Protection Officer:</p>
                <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                    <p><i class="fas fa-building w-6 text-amber-500"></i> <strong class="text-deep-sapphire">JewelTag Systems Inc.</strong></p>
                    <p><i class="fas fa-map-marker-alt w-6 text-amber-500"></i> 580 Fifth Avenue, Suite 2500<br>New York, NY 10036</p>
                    <p><i class="fas fa-envelope w-6 text-amber-500"></i> <a href="mailto:privacy@jeweltag.us" class="text-amber-600 hover:underline">privacy@jeweltag.us</a></p>
                    <p><i class="fas fa-phone w-6 text-amber-500"></i> <a href="tel:18005393582" class="text-amber-600 hover:underline">1-800-JEWEL-TAG</a></p>
                </div>
            </div>
            <div class="bg-amber-50 rounded-xl p-6 border border-amber-200">
                <div class="flex items-center mb-3">
                    <i class="fas fa-gavel text-2xl text-amber-600 mr-3"></i>
                    <h4 class="font-bold text-deep-sapphire">Legal Disclosures</h4>
                </div>
                <p class="text-sm text-gray-600 mb-2">For legal requests or to report a privacy concern, you may also contact our legal counsel at:</p>
                <p class="text-sm font-semibold text-amber-600">legal@jeweltag.us</p>
                <div class="mt-4 pt-3 border-t border-amber-200">
                    <p class="text-xs text-gray-500">For residents of the European Union, you have the right to lodge a complaint with your local supervisory authority.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Back to Top Button --}}
    <button id="back-to-top" class="fixed bottom-6 right-6 w-12 h-12 bg-white border border-gray-200 rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transition-all hidden z-40">
        <i class="fas fa-chevron-up text-gray-700"></i>
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