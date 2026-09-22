<?php

namespace App\Filament\Master\Resources;

use App\Filament\Master\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Billing & Legal';
    protected static ?string $navigationLabel = 'Subscriptions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                    // ── COLUMN 1 & 2: Plan Details ──
                    Grid::make(1)->columnSpan(2)->schema([
                        Section::make('Subscription Details')
                            ->icon('heroicon-o-currency-dollar')
                            ->columns(2)
                            ->schema([
                                Select::make('tenant_id')
                                    ->label('Store / Tenant')
                                    ->options(Tenant::pluck('id', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->disabled(fn($record) => $record !== null),

                                Select::make('plan_tier')
                                    ->label('Plan Tier')
                                    ->options([
                                        'starter' => 'Starter ($99/mo)',
                                        'professional' => 'Professional ($199/mo)',
                                        'enterprise' => 'Enterprise (Custom)',
                                    ])
                                    ->required()
                                    ->live(),

                                Select::make('billing_cycle')
                                    ->options([
                                        'monthly' => 'Monthly',
                                        'annually' => 'Annually (Save 20%)',
                                    ])
                                    ->required()
                                    ->default('monthly'),

                                Select::make('status')
                                    ->label('Account Status')
                                    ->options([
                                        'trialing' => 'Trialing',
                                        'active' => 'Active',
                                        'past_due' => 'Past Due (Warning)',
                                        'canceled' => 'Canceled',
                                        'unpaid' => 'Unpaid (Suspended)',
                                    ])
                                    ->required()
                                    ->default('active'),
                            ]),

                        Section::make('Billing Cycle Dates')
                            ->icon('heroicon-o-calendar-days')
                            ->columns(2)
                            ->schema([
                                DatePicker::make('current_period_start')
                                    ->label('Cycle Start Date')
                                    ->required(),
                                DatePicker::make('current_period_end')
                                    ->label('Next Renewal / Due Date')
                                    ->required(),
                                DatePicker::make('trial_ends_at')
                                    ->label('Trial Ends At (Optional)'),
                                DatePicker::make('canceled_at')
                                    ->label('Canceled At (Read Only)')
                                    ->disabled(),
                            ]),
                    ]),

                    // ── COLUMN 3: Legal & Paperwork ──
                    Grid::make(1)->columnSpan(1)->schema([
                        Section::make('Legal & Paperwork')
                            ->icon('heroicon-o-document-text')
                            ->description('Master Subscription Agreement details.')
                            ->schema([
                                TextInput::make('msa_version')
                                    ->label('MSA Version Agreed To')
                                    ->default('v1.0-2026')
                                    ->required(),

                                // 🚀 Signature status indicator, live-updating.
                                Forms\Components\Placeholder::make('msa_signature_status')
                                    ->hiddenLabel()
                                    ->live()
                                    ->content(function (\Filament\Forms\Get $get) {
                                        $agreedAt = $get('msa_agreed_at');
                                        if ($agreedAt) {
                                            return new HtmlString("
                                                <div style='background:#f0fdf4;border:1.5px solid #86efac;border-radius:8px;padding:10px 14px;font-size:12px;'>
                                                    <strong style='color:#166534;'>✅ Agreement Signed</strong>
                                                    <div style='color:#15803d;margin-top:2px;'>on " . \Illuminate\Support\Str::of($agreedAt)->limit(16, '') . "</div>
                                                </div>
                                            ");
                                        }
                                        return new HtmlString("
                                            <div style='background:#fef2f2;border:1.5px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:12px;'>
                                                <strong style='color:#991b1b;'>⚠️ Not Yet Signed</strong>
                                                <div style='color:#7f1d1d;margin-top:2px;'>Customer has not agreed to the current MSA.</div>
                                            </div>
                                        ");
                                    }),

                                // 🚀 Opens the full license agreement in a scrollable modal.
                                // Now also captures the signer's Full Legal Name and
                                // Title/Position, matching the docx's "Print Name / Title"
                                // signature line under CUSTOMER.
                                Forms\Components\Actions::make([
                                    \Filament\Forms\Components\Actions\Action::make('view_and_sign_agreement')
                                        ->label(fn(\Filament\Forms\Get $get) => $get('msa_agreed_at') ? '📄 View Signed Agreement' : '📄 View & Sign Agreement')
                                        ->color(fn(\Filament\Forms\Get $get) => $get('msa_agreed_at') ? 'gray' : 'warning')
                                        ->button()
                                        ->extraAttributes(['style' => 'width:100%;'])
                                        ->modalHeading('JewelTag Software License and Subscription Agreement')
                                        ->modalWidth('4xl')
                                        ->modalSubmitActionLabel('I Agree — Sign Agreement')
                                        ->modalCancelActionLabel('Close')
                                        ->fillForm(fn(\Filament\Forms\Get $get) => [
                                            'agreement_checkbox' => (bool) $get('msa_agreed_at'),
                                            'signer_name'         => $get('msa_signer_name'),
                                            'signer_title'        => $get('msa_signer_title'),
                                        ])
                                        ->form([
                                            Forms\Components\Placeholder::make('agreement_scroll_hint')
                                                ->hiddenLabel()
                                                ->content(new HtmlString("
                                                    <div style='display:flex;align-items:center;justify-content:center;gap:6px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:8px 12px;margin-bottom:8px;font-size:12px;color:#92400e;font-weight:700;'>
                                                        ⬇ Scroll down to read the full agreement before signing ⬇
                                                    </div>
                                                ")),

                                            Forms\Components\Placeholder::make('agreement_full_text')
                                                ->hiddenLabel()
                                                ->content(fn() => new HtmlString(self::getAgreementHtml())),

                                            Forms\Components\Checkbox::make('agreement_checkbox')
                                                ->label('I have read and agree to the terms of this Software License and Subscription Agreement, including all Exhibits.')
                                                ->required()
                                                ->accepted()
                                                ->live()
                                                ->extraAttributes(['style' => 'margin-top:12px;']),

                                            // 🚀 NEW — digital signature block: name + title of the
                                            // person signing on the customer's behalf.
                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('signer_name')
                                                        ->label('Full Legal Name')
                                                        ->placeholder('e.g. Jane Smith')
                                                        ->required()
                                                        ->extraAttributes(['style' => 'font-family:cursive;font-size:16px;']),
                                                    TextInput::make('signer_title')
                                                        ->label('Title / Position')
                                                        ->placeholder('e.g. Owner, General Manager')
                                                        ->required(),
                                                ])
                                                ->columnSpanFull(),
                                        ])
                                        ->action(function (array $data, \Filament\Forms\Set $set) {
                                            if (empty($data['agreement_checkbox'])) return;

                                            // 🚀 Stamp the signature fields on the parent form —
                                            // real IP + real timestamp + signer name/title.
                                            $set('msa_agreed_at', now()->toDateTimeString());
                                            $set('msa_agreed_ip', request()->ip());
                                            $set('msa_signer_name', $data['signer_name']);
                                            $set('msa_signer_title', $data['signer_title']);

                                            Notification::make()
                                                ->title('Agreement Signed')
                                                ->body("Signed by {$data['signer_name']} ({$data['signer_title']}). Save the record to persist it.")
                                                ->success()
                                                ->send();
                                        }),
                                ])->columnSpanFull(),

                                // 🚀 NEW — renders the actual signature block once signed,
                                // styled like a signed document (cursive name, title, date).
                                Forms\Components\Placeholder::make('signature_block_display')
                                    ->hiddenLabel()
                                    ->live()
                                    ->visible(fn(\Filament\Forms\Get $get) => (bool) $get('msa_agreed_at') && $get('msa_signer_name'))
                                    ->content(function (\Filament\Forms\Get $get) {
                                        return new HtmlString(
                                            self::getSignedSignatureHtml(
                                                $get('msa_signer_name'),
                                                $get('msa_signer_title'),
                                                $get('msa_agreed_at')
                                            )
                                        );
                                    }),

                                TextInput::make('msa_signer_name')
                                    ->label('Signer Name')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Captured at signing — see signature block above.'),

                                TextInput::make('msa_signer_title')
                                    ->label('Signer Title')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Captured at signing.'),

                                TextInput::make('msa_agreed_ip')
                                    ->label('IP Address at Signing')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Logged automatically when "I Agree" is clicked above.'),

                                DatePicker::make('msa_agreed_at')
                                    ->label('Date Signed')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Set automatically — cannot be backdated manually.'),

                                FileUpload::make('contract_pdf_path')
                                    ->label('Custom Contract Upload')
                                    ->directory('contracts')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->helperText('Upload signed physical contracts here (for Enterprise accounts).'),
                            ]),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant_id')
                    ->label('Store')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('plan_tier')
                    ->label('Plan')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        'canceled', 'unpaid' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucfirst($state))),

                TextColumn::make('current_period_end')
                    ->label('Next Invoice')
                    ->date()
                    ->sortable()
                    ->description(function ($record) {
                        if ($record->status === 'past_due') {
                            return new HtmlString("<span class='text-danger-600 font-bold'>Payment Overdue!</span>");
                        }
                        return ucfirst($record->billing_cycle);
                    }),

                TextColumn::make('msa_signer_name')
                    ->label('Signed By')
                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} ({$record->msa_signer_title})" : '—')
                    ->toggleable(),

                TextColumn::make('msa_agreed_at')
                    ->label('Legal')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? '✅ Signed' : '⚠️ Unsigned')
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'canceled' => 'Canceled',
                        'unpaid' => 'Unpaid',
                    ]),
                Tables\Filters\SelectFilter::make('plan_tier')
                    ->options([
                        'starter' => 'Starter',
                        'professional' => 'Professional',
                        'enterprise' => 'Enterprise',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('view_contract')
                    ->label('View Contract')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('gray')
                    ->url(fn ($record) => $record->contract_pdf_path ? asset('storage/' . $record->contract_pdf_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->contract_pdf_path !== null),

                Tables\Actions\Action::make('cancel_subscription')
                    ->label('Cancel Subs')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Subscription')
                    ->modalDescription('Are you sure? This will mark the subscription to cancel at the end of the current billing cycle.')
                    ->visible(fn ($record) => in_array($record->status, ['active', 'past_due']))
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'canceled',
                            'canceled_at' => now(),
                        ]);
                        Notification::make()->title('Subscription Canceled')->success()->send();
                    }),
            ]);
    }

    // 🚀 Renders an actual signed signature block (cursive name, title, date)
    // in place of blank "Print Name / Title" underscores. Public so
    // TenantResource can reuse it during tenant creation.
    public static function getSignedSignatureHtml(?string $name, ?string $title, ?string $date): string
    {
        if (!$name) {
            return "<div style='margin-top:10px;font-size:12px;color:#78716c;'>Signature: ______________________ &nbsp;&nbsp;Date: ___________<br>Print Name / Title: ______________________</div>";
        }

        $formattedDate = $date ? \Illuminate\Support\Str::of($date)->limit(16, '') : '—';

        return "
            <div style='background:#fffef7;border:1.5px solid #d6d3d1;border-radius:10px;padding:14px 18px;margin-top:6px;'>
                <div style='font-size:10px;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;'>Digital Signature — CUSTOMER</div>
                <div style='font-family:\"Brush Script MT\",cursive;font-size:26px;color:#1e293b;border-bottom:1.5px solid #1e293b;display:inline-block;padding-bottom:4px;margin-bottom:4px;'>" . e($name) . "</div>
                <div style='display:flex;justify-content:space-between;font-size:12px;color:#57534e;margin-top:4px;'>
                    <span><strong>" . e($title) . "</strong></span>
                    <span>Signed: {$formattedDate}</span>
                </div>
            </div>
        ";
    }

    // 🚀 Full JewelTag Software License and Subscription Agreement text,
    // rendered inline inside the modal's scrollable body. Public so
    // TenantResource can reuse the exact same text when creating new tenants.
    public static function getAgreementHtml(): string
    {
        $sections = [
            ['1. Parties and Acceptance', "This Software License and Subscription Agreement (\"Agreement\") is entered into as of the date of electronic or physical signature below (the \"Effective Date\") by and between The Explorers USA, LLC, d/b/a JewelTag, a Texas limited liability company (\"Licensor,\" \"Company,\" \"we,\" \"us,\" or \"our\"), and the retailer or business entity identified in Exhibit A (\"Licensee,\" \"Customer,\" \"you,\" or \"your\"). Licensor and Customer may each be referred to individually as a \"Party\" and collectively as the \"Parties.\"<br><br>By (a) signing this Agreement, (b) accessing or using the Software, or (c) clicking \"I Agree\" through Licensor's digital signature portal, Customer represents that it has the authority to bind the entity on whose behalf it is acting, and agrees to be bound by this Agreement, including all Exhibits, which are incorporated by reference and form an integral part of this Agreement."],
            ['2. Definitions', "\"Affiliate\" means any entity that controls, is controlled by, or is under common control with a Party.<br>\"Confidential Information\" has the meaning given in Section 14.<br>\"Customer Data\" means all data, records, and content that Customer or its Users input into, or that is generated through Customer's use of, the Software, including inventory records, CRM/customer records, transaction records, and financing-related data.<br>\"Documentation\" means Licensor's user guides and technical documentation for the Software, as updated from time to time.<br>\"Financing Partners\" means third-party consumer financing/lending companies integrated with the Software, including but not limited to Acima, Progressive Leasing, Snap Finance, Uown, Synchrony, and Wells Fargo, as may be updated from time to time.<br>\"Fees\" means the subscription and any usage-based fees payable by Customer as set forth in Exhibit A.<br>\"Force Majeure Event\" has the meaning given in Section 25.<br>\"Intellectual Property Rights\" means all patent, copyright, trademark, trade secret, and other proprietary rights, worldwide.<br>\"Order Form\" means Exhibit A or any subsequent ordering document referencing this Agreement.<br>\"Software\" means the JewelTag software-as-a-service platform, including the Inventory Management Module, CRM Module, and, if licensed, the Financing Integration Module, together with all updates, Documentation, and related services made available by Licensor.<br>\"Store Location(s)\" means each physical retail location operated by Customer and authorized to access the Software, as listed in Exhibit A. This Agreement applies uniformly regardless of the number of Store Locations or the states in which they are located.<br>\"Subscription Term\" means the initial and any renewal term of Customer's subscription, as set forth in Exhibit A.<br>\"Users\" means Customer's employees, contractors, and agents authorized by Customer to access the Software under Customer's account."],
            ['3. Software as a Service; License Grant', "3.1 License Grant. Subject to Customer's compliance with this Agreement and timely payment of Fees, Licensor grants Customer a non-exclusive, non-transferable, non-sublicensable, revocable right to access and use the Software during the Subscription Term, solely for Customer's internal business operations at the Store Locations identified in Exhibit A.<br><br>3.2 Subscription Tiers. The Basic Tier includes the Inventory Management and CRM Modules. The Pro Tier includes the Basic Tier features plus the Financing Integration Module, advanced reporting, and any additional features designated by Licensor as Pro-Tier features from time to time. Feature availability, user seat limits, and Store Location limits per tier are set forth in Exhibit A.<br><br>3.3 Reservation of Rights. Licensor reserves all rights not expressly granted to Customer in this Agreement. No implied licenses are granted.<br><br>3.4 Modifications to the Software. Licensor may modify, update, or discontinue features of the Software from time to time, provided that Licensor will use commercially reasonable efforts not to materially reduce the core functionality of a Subscription Tier during a paid Subscription Term without reasonable notice.<br><br>3.5 Third-Party Services. The Software may interoperate with third-party services, including Financing Partners, payment processors, and POS hardware providers. Licensor is not responsible for the acts, omissions, availability, or terms of any third-party service."],
            ['4. Customer Responsibilities and Acceptable Use', "4.1 Accurate Information. Customer shall provide accurate business, billing, and Store Location information and promptly update Licensor of any changes.<br><br>4.2 Account Security. Customer is responsible for maintaining the confidentiality of login credentials and API keys issued to it, and for all activity occurring under its account, whether or not authorized by Customer. Customer shall notify Licensor promptly of any suspected unauthorized access.<br><br>4.3 Compliance with Law. Customer shall use the Software in compliance with all applicable federal, state, and local laws, including those referenced in Sections 8 through 11.<br><br>4.4 Acceptable Use Policy. Customer and its Users shall comply with the Acceptable Use Policy set forth in Exhibit C. Violation of Exhibit C constitutes a material breach of this Agreement.<br><br>4.5 Restrictions. Customer shall not, and shall not permit any third party to: (a) reverse engineer, decompile, or disassemble the Software or attempt to derive its source code; (b) resell, sublicense, lease, or provide third-party access to the Software without Licensor's prior written consent; (c) use the Software to process data unrelated to Customer's retail jewelry business operations; (d) exceed usage limits tied to its Subscription Tier; (e) use the Software to build a competing product; or (f) remove or alter any proprietary notices in the Software or Documentation."],
            ['5. Fees, Invoicing, and Taxes', "5.1 Fees. Customer shall pay the Fees set forth in Exhibit A. Except as expressly stated in this Agreement, Fees are non-refundable and non-cancelable once paid.<br><br>5.2 Invoicing and Payment Terms. Fees are invoiced monthly in advance and are due within 30 days of the invoice date, unless otherwise stated in Exhibit A.<br><br>5.3 Late Payment. Amounts not paid when due accrue interest at the lesser of 1.5% per month or the maximum rate permitted by applicable law, and Licensor may suspend access under Section 20 for accounts more than 30 days past due.<br><br>5.4 Taxes. Fees are exclusive of applicable sales, use, and similar taxes. Customer is responsible for all such taxes other than taxes on Licensor's net income.<br><br>5.5 Fee Changes. Licensor may adjust Fees for any renewal term upon at least 30 days' written notice prior to the start of the renewal term."],
            ['6. Term, Renewal, and Termination', "6.1 Term. This Agreement begins on the Effective Date and continues for the Subscription Term stated in Exhibit A, automatically renewing for successive terms of equal length unless either Party provides written notice of non-renewal at least 30 days before the end of the then-current term.<br><br>6.2 Termination for Cause. Either Party may terminate this Agreement if the other Party materially breaches this Agreement and fails to cure such breach within 30 days of written notice. Licensor may terminate or suspend immediately, without opportunity to cure, for breaches of Section 4.4 (Acceptable Use), Section 4.5 (Restrictions), or non-payment continuing more than 30 days past due.<br><br>6.3 Effect of Termination. Upon termination or expiration: (a) all licenses granted to Customer immediately terminate; (b) Customer shall pay all Fees accrued through the effective date of termination; (c) Licensor will make Customer Data available for export in a standard format for 30 days following termination, after which Licensor may delete Customer Data in accordance with its data retention practices; and (d) each Party shall return or destroy the other Party's Confidential Information upon request.<br><br>6.4 Termination for Convenience. Either Party may terminate this Agreement for convenience upon 60 days' written notice; provided that Fees already paid for the then-current Subscription Term are non-refundable absent a separate written agreement."],
            ['7. Customer Data and Data Protection', "7.1 Ownership. As between the Parties, Customer retains all right, title, and interest in Customer Data. Customer grants Licensor a limited, non-exclusive license to host, process, and use Customer Data solely to provide, maintain, secure, and improve the Software, and as otherwise permitted under Exhibit B.<br><br>7.2 Data Processing Addendum. The Data Processing Addendum attached as Exhibit B governs the technical and organizational security measures applied to Customer Data and is incorporated into this Agreement by reference.<br><br>7.3 Security Measures. Licensor shall maintain administrative, technical, and physical safeguards designed to protect Customer Data consistent with industry standards for SaaS platforms handling retail transaction and financing-related data.<br><br>7.4 Data Breach Notification. In the event of a confirmed security incident resulting in unauthorized access to or disclosure of Customer Data, Licensor will notify Customer without undue delay, and in any event within the timeframe required to enable Customer to satisfy its own notification obligations under applicable law, and will provide reasonably available information regarding the nature and scope of the incident.<br><br>7.5 Subprocessors. Licensor may engage subprocessors (e.g., cloud hosting providers) to provide the Software, provided that Licensor remains responsible for such subprocessors' compliance with the data protection obligations in this Agreement."],
            ['8. Multi-State and Multi-Jurisdiction Compliance', "8.1 General. Because the Software is licensed to retailers operating in multiple states, and Licensor anticipates licensing the Software to additional retail customers beyond Customer over time, Customer is solely responsible for complying with the data privacy, breach notification, consumer protection, and financial services laws of each state in which Customer operates Store Locations or has end consumers, including but not limited to:<br>Texas: the Texas Data Privacy and Security Act (TDPSA) and the Texas Identity Theft Enforcement and Protection Act (breach notification).<br>New Mexico: the New Mexico Data Breach Notification Act (NMSA 57-12C-1 et seq.), and any comprehensive consumer privacy law subsequently enacted by the New Mexico Legislature during the Term.<br>Any additional state in which Customer later operates a Store Location: the applicable state's consumer data privacy, breach notification, and consumer protection statutes then in effect.<br><br>8.2 No Legal Advice. Licensor will provide reasonable technical cooperation to help Customer meet its notification obligations under Section 7.4, but does not provide legal advice, and this Agreement does not substitute for Customer obtaining its own state-specific legal counsel, particularly before expanding to new states.<br><br>8.3 Future Retailer Customers. This Agreement is drafted to apply on substantially the same terms to other retailer customers licensing the Software in the future, regardless of the state(s) in which such customers operate, subject to jurisdiction-specific terms added in a customer's Exhibit A where required by local law."],
            ['9. Payment Card Data (PCI-DSS)', "To the extent the Software transmits, processes, or facilitates payment card data through POS integrations, each Party shall comply with the Payment Card Industry Data Security Standard (PCI-DSS) requirements applicable to its respective role. Licensor does not store full payment card numbers within the core Software database. Customer is responsible for the PCI-DSS compliance of its own POS hardware and any card-present payment environment at its Store Locations."],
            ['10. Financing Integration Module', "10.1 Role of Licensor. The Financing Integration Module (available under the Pro Tier) facilitates a technical connection between Customer's point-of-sale transactions and Financing Partners. Licensor is not a lender, broker, or credit-decisioning entity, and makes no representation regarding approval odds, financing terms, or the suitability of any Financing Partner for a given consumer.<br><br>10.2 GLBA and FCRA. Where the Financing Integration Module transmits nonpublic personal financial information, each Party shall comply with its respective obligations under the Gramm-Leach-Bliley Act (GLBA) and, where consumer credit reporting data is involved, the Fair Credit Reporting Act (FCRA). Customer is solely responsible for obtaining all consumer consents and providing all disclosures required by Financing Partners and applicable law prior to submitting any consumer's information for financing.<br><br>10.3 Per-Store Lender Credentials. Customer is solely responsible for safeguarding Financing Partner API credentials configured within the Software for each Store Location, and for promptly revoking or rotating credentials upon staff turnover, store closure, or suspected compromise. Licensor is not liable for losses arising from Customer's failure to safeguard such credentials.<br><br>10.4 No Guarantee. Licensor does not guarantee the continued availability of any specific Financing Partner integration and may add, remove, or modify Financing Partner integrations upon reasonable notice."],
            ['11. SMS and Electronic Communications Compliance', "If Customer uses the Software's SMS/text messaging features to contact end consumers, Customer represents and warrants that it has obtained proper prior express written consent from each recipient in accordance with the Telephone Consumer Protection Act (TCPA), will maintain records of such consent for the period required by law, and will honor opt-out requests promptly. If Customer uses email marketing features, Customer shall comply with the CAN-SPAM Act. Customer is solely responsible for the content of all messages sent through the Software and for maintaining consent records; Licensor's responsibility is limited to providing the technical mechanism for opt-out processing."],
            ['12. Jewelry-Specific Disclaimers', "No Appraisal Warranty: the Software's inventory valuation, pricing, and reporting tools are provided for internal business management purposes only and do not constitute a professional appraisal, grading certification, or guarantee of the authenticity, grade, weight, or value of any item.<br><br>Data Accuracy: Customer is solely responsible for the accuracy of item descriptions, valuations, weights, and inventory records entered into the Software, and for maintaining appropriate insurance coverage for its physical inventory independent of the Software.<br><br>No Insurance: the Software is not a substitute for jewelers block insurance or other insurance coverage appropriate to Customer's business, and Licensor makes no representation regarding the adequacy of Customer's insurance coverage."],
            ['13. Intellectual Property', "13.1 Licensor IP. The Software, and all Intellectual Property Rights therein, including all underlying source code, architecture, and Documentation, are and will remain the sole and exclusive property of Licensor and its licensors. No rights are granted to Customer except the limited license expressly set forth in Section 3.<br><br>13.2 Feedback. If Customer provides suggestions, feedback, or feature requests regarding the Software, Licensor may use such feedback without restriction or compensation to Customer.<br><br>13.3 Customer Trademarks. Licensor may reference Customer's name and logo solely to identify Customer as a JewelTag customer in marketing materials, unless Customer opts out in writing."],
            ['14. Confidentiality', "14.1 Definition. \"Confidential Information\" means non-public information disclosed by either Party that is designated as confidential or that a reasonable person would understand to be confidential given the nature of the information and circumstances of disclosure, including pricing, Customer Data, and the non-public terms of this Agreement.<br><br>14.2 Obligations. Each Party shall (a) protect the other Party's Confidential Information using at least the same degree of care it uses for its own confidential information of similar nature, and not less than reasonable care, and (b) use such Confidential Information solely to perform its obligations or exercise its rights under this Agreement.<br><br>14.3 Exceptions. Confidential Information does not include information that (a) is or becomes publicly available through no fault of the receiving Party, (b) was rightfully known to the receiving Party without restriction prior to disclosure, (c) is independently developed without use of the disclosing Party's Confidential Information, or (d) is rightfully obtained from a third party without restriction.<br><br>14.4 Compelled Disclosure. A Party may disclose Confidential Information if required by law or court order, provided it gives the other Party prompt notice (where legally permitted) to allow it to seek a protective order.<br><br>14.5 Duration. Confidentiality obligations survive for three years after termination of this Agreement, except that obligations with respect to trade secrets survive for as long as the information qualifies as a trade secret under applicable law."],
            ['15. Representations and Warranties; Disclaimer', "15.1 Mutual Representations. Each Party represents that it has the authority to enter into this Agreement and that doing so does not violate any other agreement to which it is a party.<br><br>15.2 Customer Representations. Customer represents that it has obtained, and will maintain throughout the Term, all consents, licenses, and authorizations necessary to lawfully input Customer Data into the Software and to use the Software's SMS, financing, and CRM features as described in Sections 10 and 11.<br><br>15.3 DISCLAIMER. EXCEPT AS EXPRESSLY STATED IN THIS AGREEMENT, THE SOFTWARE IS PROVIDED \"AS IS\" AND \"AS AVAILABLE,\" WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION THE IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, TITLE, AND NON-INFRINGEMENT. LICENSOR DOES NOT WARRANT THAT THE SOFTWARE WILL BE UNINTERRUPTED, ERROR-FREE, OR SECURE FROM ALL UNAUTHORIZED ACCESS."],
            ['16. Indemnification', "16.1 By Licensor. Licensor shall defend Customer against any third-party claim alleging that the Software, as provided by Licensor and used in accordance with this Agreement, infringes such third party's U.S. patent, copyright, or trademark, and shall indemnify Customer for damages finally awarded, provided Customer promptly notifies Licensor of the claim, gives Licensor sole control of the defense and settlement, and provides reasonable cooperation. This obligation does not apply to claims arising from (a) modification of the Software by anyone other than Licensor, (b) combination of the Software with products not provided by Licensor, or (c) Customer Data.<br><br>16.2 By Customer. Customer shall defend, indemnify, and hold harmless Licensor and its Affiliates, officers, and employees from and against any third-party claims, damages, liabilities, and reasonable expenses (including attorneys' fees) arising out of or related to: (a) Customer Data; (b) Customer's or its Users' violation of this Agreement, including the Acceptable Use Policy; (c) Customer's violation of applicable law, including the TCPA, GLBA, FCRA, CAN-SPAM, or state privacy and breach notification laws referenced in Section 8; (d) disputes between Customer and any Financing Partner or end consumer relating to financing transactions facilitated through the Software; or (e) Customer's use of the Software at any Store Location in a manner not authorized by this Agreement.<br><br>16.3 Procedure. The indemnified Party shall provide prompt written notice of any claim, and the indemnifying Party shall have control of the defense and settlement, provided that no settlement admitting fault on behalf of the indemnified Party may be made without its consent."],
            ['17. Limitation of Liability', "17.1 Exclusion of Consequential Damages. EXCEPT FOR THE CARVE-OUTS IN SECTION 17.3, NEITHER PARTY WILL BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS, REVENUE, DATA, OR GOODWILL, ARISING OUT OF OR RELATED TO THIS AGREEMENT, REGARDLESS OF THE THEORY OF LIABILITY AND EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.<br><br>17.2 Cap on Liability. EXCEPT FOR THE CARVE-OUTS IN SECTION 17.3, EACH PARTY'S TOTAL AGGREGATE LIABILITY ARISING OUT OF OR RELATED TO THIS AGREEMENT WILL NOT EXCEED THE FEES PAID OR PAYABLE BY CUSTOMER TO LICENSOR IN THE TWELVE (12) MONTHS PRECEDING THE EVENT GIVING RISE TO THE CLAIM.<br><br>17.3 Carve-Outs. The limitations in Sections 17.1 and 17.2 do not apply to: (a) a Party's indemnification obligations under Section 16; (b) a Party's breach of its confidentiality obligations under Section 14; (c) Customer's payment obligations under Section 5; (d) either Party's gross negligence or willful misconduct; or (e) damages arising from a Party's violation of applicable law that cannot be limited as a matter of law."],
            ['18. Insurance', "During the Term, Customer shall maintain, at its own expense: (a) commercial general liability insurance with limits of at least \$1,000,000 per occurrence; and (b) cyber liability insurance with limits of at least \$1,000,000 per occurrence, covering data breach and privacy liability arising from Customer's retail operations. Customer shall provide evidence of such coverage to Licensor upon reasonable request."],
            ['19. Audit Rights', "Licensor may, upon at least 10 business days' written notice and no more than once per 12-month period (or at any time following a reasonably suspected breach of Section 4.5), audit Customer's use of the Software to verify compliance with the Subscription Tier limits and Acceptable Use Policy. Audits will be conducted during normal business hours in a manner that minimizes disruption to Customer's operations."],
            ['20. Suspension of Service', "Licensor may suspend Customer's access to the Software, in whole or in part, without liability, if: (a) Customer's account is more than 30 days past due; (b) Licensor reasonably believes Customer's use poses a security risk to the Software or other customers; (c) suspension is required to comply with applicable law or a court order; or (d) Customer materially breaches Section 4.4 or 4.5. Licensor will provide notice of suspension where reasonably practicable and will restore access promptly upon resolution of the underlying issue."],
            ['21. Support and Service Availability', "Licensor will provide reasonable commercial support during standard business hours as further described in the Documentation or a separate support policy referenced in Exhibit A. Licensor does not guarantee any specific uptime percentage under this Agreement unless a separate Service Level Agreement is executed by the Parties."],
            ['22. Non-Solicitation', "During the Term and for 12 months thereafter, neither Party shall solicit for hire any employee of the other Party who was directly involved in the performance of this Agreement, without the other Party's prior written consent, except through general public job postings not targeted at such employee."],
            ['23. Assignment and Change of Control', "Neither Party may assign this Agreement without the other Party's prior written consent, except that either Party may assign this Agreement without consent in connection with a merger, acquisition, or sale of substantially all of its assets, provided the assignee agrees in writing to be bound by this Agreement. Any attempted assignment in violation of this Section is void."],
            ['24. Export Control and Sanctions Compliance', "Customer shall not use, export, or re-export the Software in violation of any applicable U.S. export control or sanctions laws, and represents that it is not located in, or ordinarily resident in, a country or region subject to comprehensive U.S. sanctions, and is not identified on any U.S. government restricted party list."],
            ['25. Force Majeure', "Neither Party will be liable for any delay or failure to perform (other than payment obligations) resulting from causes beyond its reasonable control, including acts of God, natural disaster, war, terrorism, labor disputes, internet or utility failures, or governmental action (\"Force Majeure Event\"), provided the affected Party gives prompt notice and uses commercially reasonable efforts to mitigate the impact."],
            ['26. Dispute Resolution; Governing Law; Arbitration', "26.1 Governing Law. This Agreement is governed by the laws of the State of Texas, without regard to conflict-of-law principles. Venue for any court proceeding permitted under this Agreement (e.g., injunctive relief under Section 26.3) shall lie in Denton County, Texas.<br><br>26.2 Informal Resolution. The Parties shall first attempt in good faith to resolve any dispute through negotiation between authorized representatives within 30 days of written notice of the dispute.<br><br>26.3 Arbitration. Any dispute not resolved informally shall be resolved by binding arbitration administered by the American Arbitration Association under its Commercial Arbitration Rules, seated in Denton County, Texas, before a single arbitrator, except that either Party may seek injunctive relief in court to protect its Intellectual Property Rights or Confidential Information.<br><br>26.4 Class Action Waiver. Any arbitration or proceeding will be conducted on an individual basis only, and not as part of a class, consolidated, or representative action, to the fullest extent permitted by law.<br><br>26.5 Attorneys' Fees. In any action to enforce this Agreement, the prevailing Party is entitled to recover its reasonable attorneys' fees and costs."],
            ['27. Notices', "Notices under this Agreement must be in writing and delivered by email (with confirmation of receipt), overnight courier, or certified mail to the addresses on file in Exhibit A, or such other address as a Party designates in writing."],
            ['28. Relationship of the Parties', "The Parties are independent contractors. Nothing in this Agreement creates a partnership, joint venture, agency, or employment relationship between the Parties."],
            ['29. Entire Agreement; Amendment; Order of Precedence', "This Agreement, including all Exhibits and any Order Forms, constitutes the entire agreement between the Parties regarding its subject matter and supersedes all prior or contemporaneous agreements, whether written or oral. This Agreement may only be amended by a written instrument signed by both Parties, except that Licensor may update Exhibit C (Acceptable Use Policy) upon reasonable notice to reflect legal or security requirements. In the event of a conflict, the body of this Agreement controls over the Exhibits unless an Exhibit expressly states otherwise."],
            ['30. Severability; Waiver; No Third-Party Beneficiaries', "If any provision of this Agreement is held unenforceable, the remaining provisions will remain in full force and effect, and the unenforceable provision will be modified to the minimum extent necessary to make it enforceable. No waiver of any provision is effective unless in writing and signed by the waiving Party. This Agreement does not create any rights for the benefit of any third party."],
            ['31. Survival', "Sections 5 (as to accrued Fees), 6.3, 10.3, 13, 14, 15.3, 16, 17, 22, 26, 27, and 30 survive termination or expiration of this Agreement, along with any other provision that by its nature is intended to survive."],
            ['32. Counterparts and Electronic Signature', "This Agreement may be executed in counterparts, each of which is deemed an original. This Agreement may be executed electronically through Licensor's digital signature portal or other electronic means, and such electronic signature has the same legal effect as a handwritten signature under the Texas Uniform Electronic Transactions Act."],
            ['Exhibit A: Subscription Tier, Fees, and Store Locations', "Customer Legal Name: ______________________<br>Notice Address / Email: ______________________<br>Subscription Tier Selected: ☐ Basic &nbsp;&nbsp;☐ Pro<br>Subscription Term: ______________________ &nbsp;&nbsp;Renewal Term: ______________________<br>Fees: \$______ per month, billed monthly in advance<br>Payment Terms: Net 30 days<br><br><strong>Store Locations Covered Under This Agreement</strong><br>Store 1 — Name / Address / State: ______________________ &nbsp;&nbsp;Financing Partners Enabled: ______________________<br>Store 2 — Name / Address / State: ______________________ &nbsp;&nbsp;Financing Partners Enabled: ______________________<br>[Add additional rows as needed for additional Store Locations]<br>User Seat Limit: ______________________"],
            ['Exhibit B: Data Processing Addendum', "This Data Processing Addendum (\"DPA\") supplements the Agreement and applies to Licensor's processing of Customer Data that includes personal information of Customer's end consumers.<br><br><strong>B.1 Roles of the Parties</strong> — For purposes of this DPA, Customer is the data controller/business and Licensor acts as a data processor/service provider with respect to Customer Data containing personal information, processing such data solely on Customer's documented instructions as set forth in this Agreement.<br><br><strong>B.2 Scope of Processing</strong> — Licensor processes Customer Data solely to provide, secure, support, and improve the Software, and for no other purpose, except as required by law.<br><br><strong>B.3 Security Measures</strong> — Licensor shall implement administrative, technical, and physical safeguards designed to protect Customer Data against unauthorized access, disclosure, alteration, or destruction, including access controls, encryption of data in transit, and regular security review of the Software.<br><br><strong>B.4 Subprocessors</strong> — Licensor may engage subprocessors (such as cloud infrastructure and hosting providers) to process Customer Data, provided Licensor imposes data protection obligations on such subprocessors materially consistent with this DPA and remains responsible for their performance.<br><br><strong>B.5 Breach Notification</strong> — Licensor shall notify Customer without undue delay, and in no event later than 72 hours after becoming aware, of any confirmed breach involving unauthorized access to or disclosure of Customer Data, and shall provide reasonably available details of the nature and scope of the incident and remediation steps taken.<br><br><strong>B.6 Assistance with Data Subject Requests</strong> — Licensor shall provide reasonable technical assistance to enable Customer to respond to verified requests from end consumers to access, correct, or delete their personal information, to the extent required by applicable law.<br><br><strong>B.7 Deletion or Return of Data</strong> — Upon termination of the Agreement and expiration of the export period described in Section 6.3, Licensor shall delete or, at Customer's written request made before such expiration, return Customer Data, except to the extent retention is required by applicable law.<br><br><strong>B.8 Audit Assistance</strong> — Licensor shall make available information reasonably necessary to demonstrate compliance with this DPA and shall allow for audits as described in Section 19 of the Agreement."],
            ['Exhibit C: Acceptable Use Policy', "Customer and its Users shall not use the Software to:<br>• Upload or transmit any unlawful, defamatory, or fraudulent content;<br>• Store data unrelated to Customer's retail jewelry business operations, including sensitive categories of personal information not required for such operations;<br>• Send SMS or email communications without the consent required under Section 11 of the Agreement;<br>• Attempt to gain unauthorized access to the Software, other customers' data, or Licensor's systems;<br>• Interfere with or disrupt the integrity or performance of the Software, including through introduction of malware;<br>• Use the Software in a manner that violates the rights of any third party, including Intellectual Property Rights or privacy rights;<br>• Use the Financing Integration Module to submit fraudulent or materially inaccurate consumer information to a Financing Partner;<br>• Circumvent Subscription Tier limits, including user seat or Store Location limits, without upgrading the applicable subscription.<br><br>Licensor may update this Acceptable Use Policy from time to time upon reasonable notice to reflect legal, security, or operational requirements."],
        ];

        $html = "<div style='max-height:480px;overflow-y:auto;padding:16px 20px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;font-size:13px;line-height:1.6;color:#1f2937;'>";
        $html .= "<h2 style='text-align:center;font-size:16px;font-weight:900;color:#0B3D3C;margin-bottom:4px;'>JewelTag Software License and Subscription Agreement</h2>";
        $html .= "<p style='text-align:center;font-size:11px;color:#6b7280;margin-bottom:20px;'>The Explorers USA, LLC, d/b/a JewelTag</p>";

        foreach ($sections as [$title, $body]) {
            $html .= "<div style='margin-bottom:18px;'>";
            $html .= "<h3 style='font-size:13px;font-weight:800;color:#0B3D3C;margin-bottom:6px;border-bottom:1px solid #e5e7eb;padding-bottom:4px;'>{$title}</h3>";
            $html .= "<div style='color:#374151;'>{$body}</div>";
            $html .= "</div>";
        }

        $html .= "<div style='margin-top:20px;padding-top:14px;border-top:2px solid #0B3D3C;font-size:11px;color:#6b7280;'>";
        $html .= "By checking the box below and clicking \"I Agree — Sign Agreement,\" you acknowledge that you have read and understood this Agreement, including all Exhibits, and agree to be bound by its terms.";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}