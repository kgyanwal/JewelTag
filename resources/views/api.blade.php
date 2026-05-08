{{-- resources/views/api.blade.php --}}
@extends('layouts.legal')

@section('content')
<div class="min-h-screen">
    {{-- Hero Section --}}
    <div class="text-center mb-12">
        <div class="inline-flex items-center px-4 py-2 rounded-full bg-amber-50 border border-amber-200 mb-6">
            <i class="fas fa-code text-amber-600 text-sm mr-2"></i>
            <span class="text-amber-700 text-xs font-semibold tracking-wider">DEVELOPER RESOURCES</span>
        </div>
        <h1 class="text-5xl lg:text-6xl font-bold playfair mb-4">
            <span class="gold-gradient-text">API Reference</span>
        </h1>
        <div class="h-1 w-20 gold-gradient mx-auto rounded-full mb-6"></div>
        <p class="text-gray-600 text-lg max-w-2xl mx-auto">
            Integrate JewelTag's luxury inventory engine into your custom applications, POS systems, and e-commerce platforms.
        </p>
        <div class="flex items-center justify-center gap-4 mt-6">
            <div class="flex items-center text-gray-500 text-sm">
                <i class="fas fa-code-branch mr-2 text-amber-500"></i>
                <span>Version: <span class="text-gray-700 font-semibold">v1.0.0</span></span>
            </div>
            <div class="w-1 h-1 bg-gray-300 rounded-full"></div>
            <div class="flex items-center text-gray-500 text-sm">
                <i class="fas fa-clock mr-2 text-amber-500"></i>
                <span>Last Updated: <span class="text-gray-700 font-semibold">May 4, 2026</span></span>
            </div>
        </div>
    </div>

    {{-- Quick Links Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-12">
        <a href="#authentication" class="text-center p-4 bg-gray-50 rounded-xl border border-gray-100 hover:border-amber-200 hover:bg-amber-50/30 transition-all group">
            <i class="fas fa-key text-amber-500 text-xl mb-2 block"></i>
            <span class="text-xs font-medium text-gray-600 group-hover:text-amber-600">Authentication</span>
        </a>
        <a href="#endpoints" class="text-center p-4 bg-gray-50 rounded-xl border border-gray-100 hover:border-amber-200 hover:bg-amber-50/30 transition-all group">
            <i class="fas fa-server text-amber-500 text-xl mb-2 block"></i>
            <span class="text-xs font-medium text-gray-600 group-hover:text-amber-600">Endpoints</span>
        </a>
        <a href="#webhooks" class="text-center p-4 bg-gray-50 rounded-xl border border-gray-100 hover:border-amber-200 hover:bg-amber-50/30 transition-all group">
            <i class="fas fa-bolt text-amber-500 text-xl mb-2 block"></i>
            <span class="text-xs font-medium text-gray-600 group-hover:text-amber-600">Webhooks</span>
        </a>
        <a href="#rate-limits" class="text-center p-4 bg-gray-50 rounded-xl border border-gray-100 hover:border-amber-200 hover:bg-amber-50/30 transition-all group">
            <i class="fas fa-tachometer-alt text-amber-500 text-xl mb-2 block"></i>
            <span class="text-xs font-medium text-gray-600 group-hover:text-amber-600">Rate Limits</span>
        </a>
    </div>

    {{-- Authentication Section --}}
    <div id="authentication" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-key text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">Authentication</h2>
        </div>
        <p class="text-gray-600 leading-relaxed mb-4">
            All API requests must include your Bearer Token in the Authorization header.
            <a href="#get-api-key" class="text-amber-600 font-semibold hover:underline ml-1">Get your API key →</a>
        </p>
        <div class="bg-slate-900 rounded-xl overflow-hidden mb-4">
            <div class="flex items-center justify-between px-4 py-2 bg-slate-800 border-b border-slate-700">
                <span class="text-xs text-gray-400 font-mono">HTTP Request</span>
                <button onclick="copyToClipboard('auth-code')" class="text-gray-400 hover:text-white text-xs transition">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
            <div id="auth-code" class="p-4 font-mono text-sm">
                <span class="text-emerald-400">POST</span> <span class="text-amber-400">/api/v1/auth/token</span>
                <div class="mt-2 text-gray-300">
                    <span class="text-purple-400">Headers:</span>
                    <div class="ml-4">
                        <span class="text-blue-400">Content-Type</span>: <span class="text-green-400">application/json</span>
                    </div>
                </div>
                <div class="mt-2 text-gray-300">
                    <span class="text-purple-400">Body:</span>
                    <div class="ml-4 bg-slate-800 p-3 rounded-lg mt-1">
                        {<br>
                        &nbsp;&nbsp;"api_key": <span class="text-green-400">"your_api_key_here"</span>,<br>
                        &nbsp;&nbsp;"api_secret": <span class="text-green-400">"your_api_secret_here"</span><br>
                        }
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-green-50 rounded-xl p-4 border border-green-200">
            <div class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-0.5 mr-3"></i>
                <div>
                    <h4 class="font-bold text-green-800 text-sm">Success Response (200 OK)</h4>
                    <pre class="text-xs text-green-700 mt-2 font-mono overflow-x-auto">{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600
}</pre>
                </div>
            </div>
        </div>
    </div>

    {{-- Using the Token Section --}}
    <div class="mb-10">
        <div class="bg-amber-50 rounded-xl p-4 border border-amber-200 mb-6">
            <h4 class="font-bold text-amber-800 text-sm mb-2"><i class="fas fa-info-circle mr-2"></i> Using Your Token</h4>
            <p class="text-sm text-gray-700 mb-3">Include the token in all subsequent requests:</p>
            <div class="bg-slate-900 rounded-lg p-3">
                <code class="text-amber-400 text-xs">Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...</code>
            </div>
        </div>
    </div>

    {{-- Base URL Section --}}
    <div class="mb-10">
        <div class="bg-gray-100 rounded-xl p-4">
            <p class="text-sm text-gray-600 mb-2"><i class="fas fa-globe text-amber-500 mr-2"></i> <strong class="text-deep-sapphire">Base URL:</strong></p>
            <code class="text-sm font-mono text-amber-600 bg-white px-3 py-2 rounded-lg block">https://api.jeweltag.us/v1</code>
        </div>
    </div>

    {{-- API Endpoints Section --}}
    <div id="endpoints" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-server text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">API Endpoints</h2>
        </div>

        {{-- Get Inventory --}}
        <div class="mb-8">
            <div class="flex items-center gap-2 mb-3">
                <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold">GET</span>
                <h3 class="font-bold text-deep-sapphire">Retrieve Inventory</h3>
            </div>
            <div class="bg-slate-900 rounded-xl p-4 mb-3">
                <code class="text-amber-400 font-mono text-sm">/api/v1/inventory</code>
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-xl p-4">
                    <h4 class="font-semibold text-sm text-deep-sapphire mb-2">Query Parameters</h4>
                    <ul class="space-y-2 text-sm">
                        <li class="flex justify-between border-b border-gray-200 pb-1">
                            <span class="font-mono text-xs">limit</span>
                            <span class="text-gray-500 text-xs">Max items per page (default: 50, max: 200)</span>
                        </li>
                        <li class="flex justify-between border-b border-gray-200 pb-1">
                            <span class="font-mono text-xs">offset</span>
                            <span class="text-gray-500 text-xs">Pagination offset (default: 0)</span>
                        </li>
                        <li class="flex justify-between border-b border-gray-200 pb-1">
                            <span class="font-mono text-xs">category</span>
                            <span class="text-gray-500 text-xs">Filter by product category</span>
                        </li>
                        <li class="flex justify-between border-b border-gray-200 pb-1">
                            <span class="font-mono text-xs">status</span>
                            <span class="text-gray-500 text-xs">Filter by status (active, sold, repair)</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <h4 class="font-semibold text-sm text-deep-sapphire mb-2">Example Request</h4>
                    <pre class="text-xs font-mono bg-white p-2 rounded overflow-x-auto">curl -X GET "https://api.jeweltag.us/v1/inventory?limit=10&status=active" \
  -H "Authorization: Bearer YOUR_TOKEN"</pre>
                </div>
            </div>
        </div>

        {{-- Get Single Item --}}
        <div class="mb-8">
            <div class="flex items-center gap-2 mb-3">
                <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold">GET</span>
                <h3 class="font-bold text-deep-sapphire">Get Single Inventory Item</h3>
            </div>
            <div class="bg-slate-900 rounded-xl p-4 mb-3">
                <code class="text-amber-400 font-mono text-sm">/api/v1/inventory/{id}</code>
            </div>
            <div class="bg-gray-50 rounded-xl p-4">
                <h4 class="font-semibold text-sm text-deep-sapphire mb-2">Example Response</h4>
                <pre class="text-xs font-mono bg-white p-3 rounded overflow-x-auto">{
  "id": "JWL-12345",
  "sku": "DIA-001",
  "name": "Tiffany Diamond Ring",
  "category": "rings",
  "price": 12499.99,
  "certification": "GIA",
  "cert_number": "GIA-123456789",
  "status": "active",
  "created_at": "2026-01-15T10:30:00Z"
}</pre>
            </div>
        </div>

        {{-- Create Inventory --}}
        <div class="mb-8">
            <div class="flex items-center gap-2 mb-3">
                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-bold">POST</span>
                <h3 class="font-bold text-deep-sapphire">Create Inventory Item</h3>
            </div>
            <div class="bg-slate-900 rounded-xl p-4 mb-3">
                <code class="text-amber-400 font-mono text-sm">/api/v1/inventory</code>
            </div>
            <div class="bg-gray-50 rounded-xl p-4">
                <h4 class="font-semibold text-sm text-deep-sapphire mb-2">Request Body</h4>
                <pre class="text-xs font-mono bg-white p-3 rounded overflow-x-auto">{
  "sku": "DIA-002",
  "name": "Emerald Pendant",
  "category": "pendants",
  "price": 8499.99,
  "cost": 4200.00,
  "supplier_id": "SUP-789",
  "certification": "IGI",
  "description": "18K gold emerald pendant with diamonds"
}</pre>
            </div>
        </div>

        {{-- Update Inventory --}}
        <div class="mb-8">
            <div class="flex items-center gap-2 mb-3">
                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-lg text-xs font-bold">PUT</span>
                <h3 class="font-bold text-deep-sapphire">Update Inventory Item</h3>
            </div>
            <div class="bg-slate-900 rounded-xl p-4 mb-3">
                <code class="text-amber-400 font-mono text-sm">/api/v1/inventory/{id}</code>
            </div>
        </div>

        {{-- Delete Inventory --}}
        <div class="mb-8">
            <div class="flex items-center gap-2 mb-3">
                <span class="px-3 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-bold">DELETE</span>
                <h3 class="font-bold text-deep-sapphire">Delete Inventory Item</h3>
            </div>
            <div class="bg-slate-900 rounded-xl p-4 mb-3">
                <code class="text-amber-400 font-mono text-sm">/api/v1/inventory/{id}</code>
            </div>
        </div>

        {{-- Transactions Endpoint --}}
        <div class="mb-8">
            <div class="flex items-center gap-2 mb-3">
                <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold">GET</span>
                <h3 class="font-bold text-deep-sapphire">Get Sales Transactions</h3>
            </div>
            <div class="bg-slate-900 rounded-xl p-4">
                <code class="text-amber-400 font-mono text-sm">/api/v1/transactions</code>
            </div>
        </div>

        {{-- Customers Endpoint --}}
        <div class="mb-8">
            <div class="flex items-center gap-2 mb-3">
                <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold">GET</span>
                <h3 class="font-bold text-deep-sapphire">Get Customers</h3>
            </div>
            <div class="bg-slate-900 rounded-xl p-4">
                <code class="text-amber-400 font-mono text-sm">/api/v1/customers</code>
            </div>
        </div>
    </div>

    {{-- Webhooks Section --}}
    <div id="webhooks" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-bolt text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">Webhooks</h2>
        </div>
        <p class="text-gray-600 leading-relaxed mb-4">
            Receive real-time notifications for inventory changes, sales, and customer events.
        </p>
        <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-gray-50 rounded-xl p-4">
                <h4 class="font-semibold text-sm text-deep-sapphire mb-2">Available Events</h4>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center"><i class="fas fa-circle text-amber-500 text-[6px] mr-2"></i> <span class="font-mono text-xs">inventory.created</span></li>
                    <li class="flex items-center"><i class="fas fa-circle text-amber-500 text-[6px] mr-2"></i> <span class="font-mono text-xs">inventory.updated</span></li>
                    <li class="flex items-center"><i class="fas fa-circle text-amber-500 text-[6px] mr-2"></i> <span class="font-mono text-xs">inventory.sold</span></li>
                    <li class="flex items-center"><i class="fas fa-circle text-amber-500 text-[6px] mr-2"></i> <span class="font-mono text-xs">transaction.completed</span></li>
                    <li class="flex items-center"><i class="fas fa-circle text-amber-500 text-[6px] mr-2"></i> <span class="font-mono text-xs">customer.created</span></li>
                </ul>
            </div>
            <div class="bg-gray-50 rounded-xl p-4">
                <h4 class="font-semibold text-sm text-deep-sapphire mb-2">Register Webhook</h4>
                <div class="bg-slate-900 rounded-lg p-3">
                    <code class="text-amber-400 text-xs font-mono">POST /api/v1/webhooks</code>
                </div>
                <pre class="text-xs font-mono bg-white p-3 rounded mt-3 overflow-x-auto">{
  "url": "https://your-domain.com/webhook",
  "events": ["inventory.sold", "transaction.completed"]
}</pre>
            </div>
        </div>
    </div>

    {{-- Rate Limits Section --}}
    <div id="rate-limits" class="mb-10 scroll-mt-24">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-tachometer-alt text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">Rate Limits</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3 text-left font-semibold text-deep-sapphire border-b">Plan</th>
                        <th class="p-3 text-left font-semibold text-deep-sapphire border-b">Rate Limit</th>
                        <th class="p-3 text-left font-semibold text-deep-sapphire border-b">Burst Limit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td class="p-3 font-medium">Essential</td><td class="p-3">100 requests/min</td><td class="p-3">200 requests</td></tr>
                    <tr><td class="p-3 font-medium">Professional</td><td class="p-3">500 requests/min</td><td class="p-3">1000 requests</td></tr>
                    <tr><td class="p-3 font-medium">Enterprise</td><td class="p-3">Custom</td><td class="p-3">Custom</td></tr>
                </tbody>
            </table>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 mt-4 border border-yellow-200">
            <p class="text-sm text-yellow-800"><i class="fas fa-exclamation-triangle mr-2"></i> Rate limit headers are included in every response: <span class="font-mono text-xs">X-RateLimit-Limit</span>, <span class="font-mono text-xs">X-RateLimit-Remaining</span>, <span class="font-mono text-xs">X-RateLimit-Reset</span></p>
        </div>
    </div>

    {{-- Error Handling Section --}}
    <div class="mb-10">
        <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="w-8 h-8 gold-gradient rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-white text-xs"></i>
            </div>
            <h2 class="text-2xl font-bold playfair text-deep-sapphire">Error Handling</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3 text-left font-semibold text-deep-sapphire border-b">Status Code</th>
                        <th class="p-3 text-left font-semibold text-deep-sapphire border-b">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td class="p-3 font-mono text-xs">400</td><td class="p-3">Bad Request - Invalid parameters</td></tr>
                    <tr><td class="p-3 font-mono text-xs">401</td><td class="p-3">Unauthorized - Invalid or missing API key</td></tr>
                    <tr><td class="p-3 font-mono text-xs">403</td><td class="p-3">Forbidden - Insufficient permissions</td></tr>
                    <tr><td class="p-3 font-mono text-xs">404</td><td class="p-3">Not Found - Resource doesn't exist</td></tr>
                    <tr><td class="p-3 font-mono text-xs">429</td><td class="p-3">Too Many Requests - Rate limit exceeded</td></tr>
                    <tr><td class="p-3 font-mono text-xs">500</td><td class="p-3">Internal Server Error</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Get API Key Section --}}
    <div id="get-api-key" class="mb-10">
        <div class="bg-gradient-to-r from-deep-sapphire to-royal-blue rounded-2xl p-6 text-white">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-key text-amber-400 text-xl"></i>
                        <h3 class="text-xl font-bold">Ready to start building?</h3>
                    </div>
                    <p class="text-white/70 text-sm max-w-md">Get your API keys from your account dashboard.</p>
                </div>
                <a href="/master/settings/api" class="px-6 py-3 bg-white/10 backdrop-blur-sm rounded-xl text-sm font-semibold hover:bg-white/20 transition border border-white/20">
                    <i class="fas fa-arrow-right mr-2"></i> Go to API Settings
                </a>
            </div>
        </div>
    </div>

    {{-- SDKs Section --}}
    <div class="mb-8">
        <h3 class="text-lg font-bold text-deep-sapphire mb-3 flex items-center"><i class="fas fa-code-branch text-amber-500 mr-2"></i> Official SDKs</h3>
        <div class="flex flex-wrap gap-3">
            <a href="#" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-mono hover:bg-gray-700 transition"><i class="fab fa-js"></i> JavaScript</a>
            <a href="#" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-mono hover:bg-gray-700 transition"><i class="fab fa-python"></i> Python</a>
            <a href="#" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-mono hover:bg-gray-700 transition"><i class="fab fa-java"></i> Java</a>
            <a href="#" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-mono hover:bg-gray-700 transition"><i class="fab fa-php"></i> PHP</a>
            <a href="#" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-mono hover:bg-gray-700 transition"><i class="fas fa-gem"></i> Ruby</a>
        </div>
    </div>

    {{-- Support Section --}}
    <div class="bg-amber-50 rounded-xl p-6 border border-amber-200 mt-8">
        <div class="flex items-start">
            <div class="w-12 h-12 gold-gradient rounded-xl flex items-center justify-center flex-shrink-0 mr-4">
                <i class="fas fa-headset text-white text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-deep-sapphire text-lg mb-1">Developer Support</h3>
                <p class="text-gray-600 text-sm mb-3">Have questions about integrating with JewelTag? Our developer support team is here to help.</p>
                <div class="flex flex-wrap gap-3">
                    <a href="mailto:dev@jeweltag.us" class="text-amber-600 font-semibold text-sm hover:underline"><i class="fas fa-envelope mr-1"></i> dev@jeweltag.us</a>
                    <a href="/docs" class="text-amber-600 font-semibold text-sm hover:underline"><i class="fas fa-book mr-1"></i> Full Documentation</a>
                    <a href="https://status.jeweltag.us" class="text-amber-600 font-semibold text-sm hover:underline"><i class="fas fa-chart-line mr-1"></i> API Status</a>
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
    // Copy to clipboard function
    window.copyToClipboard = function(elementId) {
        const element = document.getElementById(elementId);
        const text = element.innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert('Copied to clipboard!');
        });
    }

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