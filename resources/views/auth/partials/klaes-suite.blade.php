{{-- The text that scrolls beside the sign-in form. The top speaks to KAMMA (briefing: the welcome text "is now going to be
     KAMMA for the person logging in"); from the KLAES 2.0 backbone paragraph down it is word for word the KLAES-CORE login page
     (klas resources/views/auth/login.blade.php), and the five suites stay as they are there. --}}
<div class="mx-auto w-full max-w-3xl px-12 py-8">
    <div class="mb-8 text-center">
        <h1 class="mb-2 text-4xl font-bold text-gray-900 dark:text-gray-100">KLAES-METRO</h1>
        <h2 class="mb-3 text-2xl font-semibold text-gray-800 dark:text-gray-200">Kano State Metropolitan Area Management Agency (KAMMA)</h2>
        <p class="text-lg font-medium italic text-gray-700 dark:text-gray-300">
            The Urban &amp; Field Operations Engine of KLAES 2.0: Planning Recommendation, Site Inspection and Betterment
            for Metropolitan Kano and its Urban Centres.
        </p>
    </div>

    <div class="mb-6 rounded-lg bg-white p-6 shadow-sm dark:bg-slate-900">
        <p class="leading-relaxed text-gray-700 dark:text-gray-300">
            KLAES-METRO is KAMMA's own workspace in the Kano State Land Administration Enterprise System. It handles
            applications for land inside the 10 km buffer around Metropolitan Kano and the LGA urban centres: Planning
            Recommendation and Advice, KAMMA Site Inspection (KSI), betterment bills and payments, and the shadow files,
            scanning and file tracking that go with them.
        </p>
        <p class="mt-3 leading-relaxed text-gray-700 dark:text-gray-300">
            Files reach KAMMA from the Permanent Secretary, move through KAMMA's own workflow to the Managing Director,
            and return to the Ministry, all on the same land records as KLAES-CORE.
        </p>
    </div>

    <div class="mb-6 rounded-lg border border-blue-100 bg-blue-50 p-6 shadow-sm dark:border-blue-900/60 dark:bg-blue-950/40">
        <p class="leading-relaxed text-gray-700 dark:text-gray-300">
            Built on a modern, modular architecture, KLAES 2.0 serves as the unified digital backbone for the Ministry
            of Land and Physical Planning, KAMMA (Kano State Metropolitan Area Management Agency), KADCA (Kano State
            Development Control Agency), and KANGIS (Kano State Geographic Information System). By bringing together
            land administration, geospatial intelligence, development control, and revenue collection, KLAES 2.0 is not
            just a software—it is a statewide digital governance ecosystem enabling smarter workflows, greater
            transparency, and seamless service delivery.
        </p>
        <p class="mt-3 leading-relaxed text-gray-700 dark:text-gray-300">
            Whether it's registering a title, approving a building plan, performing a legal search, managing land
            disputes, or collecting state revenues, KLAES 2.0 ensures every department works in lockstep with
            synchronized data and automated processes.
        </p>
    </div>

    @php
        $klaesSuites = [
            [
                'title' => '1. KLAES-CORE (The Foundational Land & Registry Engine)',
                'intro' => 'The centralized hub for the Ministry of Land and Physical Planning, managing the end-to-end lifecycle of land files and deeds.',
                'items' => [
                    'e-Registry' => 'A digitally centralized, secure repository of all title records enabling instant retrieval, search, and registration with strict data integrity and audit trails.',
                    'Sectional Titling (ST) Module' => 'Allows original owners to apply for property fragmentation, supporting full workflows including betterment billing, planning approvals, and final conveyancing via a nested application system.',
                    'Systematic Land Titling & Registration (SLTR)' => 'Empowers the mass titling of informal settlements by integrating community-based land identification with formal parcel-based mapping.',
                    'Electronic Document Management System (EDMS)' => 'Manages scanned documents, file archiving, and indexing, integrated with RFID tracking for physical files and bulk document workflows.',
                    'Customer Relationship Management (CRM)' => 'Streamlines interactions between the Ministry and stakeholders, tracking requests, complaints, service timelines, and feedback.',
                ],
            ],
            [
                'title' => '2. KLAES-GIS (The Geospatial & Title Foundation – KANGIS)',
                'intro' => 'The spatial intelligence engine powering accurate mapping and land information.',
                'items' => [
                    'Enterprise GIS Integration' => 'Provides multi-user access to live spatial databases of parcels, layouts, infrastructure, and encumbrances.',
                    'Advanced Spatial Tools' => 'Supports spatial analysis, parcel querying, site planning, and seamless survey integration.',
                    'KANGIS Synchronization' => 'Fully integrated with the Parcel Fabric, COGO tools, and the KANGIS Land Information System (LIS) for real-time, bi-directional data syncing.',
                ],
            ],
            [
                'title' => '3. KLAES-METRO (The Urban & Field Operations Engine – KAMMA)',
                'intro' => 'The urban planning engine automating building permits, zoning, and field operations.',
                'items' => [
                    'Automated Planning Approvals' => 'Streamlines the submission, review, and approval of building plans and zoning permits.',
                    'Field Operations & Inspections' => 'Empowers field agents with mobile tools for joint site inspections, cadastral mapping, and special assignments.',
                    'Spatial Buffer Routing' => 'Automatically routes applications to KAMMA or Physical Planning based on geospatial city-radius parameters.',
                ],
            ],
            [
                'title' => '4. KLAES-CONTROL (The Regulatory & Security Shield – KADCA)',
                'intro' => 'The centralized governance, compliance, and security hub for development control.',
                'items' => [
                    'Development Control & Enforcement' => 'Provides a digital framework for site inspections, compliance monitoring, and enforcement of approved building plans.',
                    'Advanced Security Protocols' => 'Enforces system-wide compliance through 2-Factor Authentication (2FA), biometric face identification, and Role-Based Access Control (RBAC).',
                    'Immutable Audit Trails' => 'Tracks every user action, file movement, and system modification to ensure absolute accountability.',
                ],
            ],
            [
                'title' => '5. KLAES-Rev M (The Unified Billing & Revenue Engine)',
                'intro' => 'The financial engine ensuring zero revenue leakages and seamless state treasury integration.',
                'items' => [
                    'Automated Billing Workflow' => 'Generates dynamic, automated invoices based on parcel size, land use, and instrument type.',
                    'Real-Time Payment Integration' => 'Direct API-based integration with INTERSWITCH and KIRMAS (Kano State Internal Revenue Service) ensures all payments are tracked, validated, and reconciled in real-time.',
                    'Customer Portal & Digital Invoicing' => "Applicants can view/download invoices, make payments via multiple channels (Bank, POS, USSD, Mobile), and monitor balances. Receipts are auto-generated and digitally attached to the customer's profile.",
                ],
            ],
        ];

        $klaesTech = [
            'Blockchain Technology' => 'Used in our Digital Legal Search (available both On-Premise for official use and Online for commercial use) to guarantee record immutability. It generates complete land histories from ownership to encumbrances, making titles fraud-proof.',
            'Artificial Intelligence (AI)' => 'Integrated into the core engine to automatically detect anomalies, flag duplicate land transactions, and predict processing bottlenecks before they occur.',
            'Multi-Tiered Approvals & Alerts' => 'Automated routing of files through various approval stages with real-time SMS and email alerts to applicants and officers, drastically reducing approval times.',
        ];
    @endphp

    <div class="mb-6 rounded-lg bg-white p-6 shadow-sm dark:bg-slate-900">
        <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">The KLAES 2.0 Suite Ecosystem</h3>
        <p class="mb-5 leading-relaxed text-gray-700 dark:text-gray-300">
            KLAES 2.0 is powered by five specialized, interoperable suites designed to handle the specific mandates of
            Kano State's land and urban governance agencies:
        </p>

        <div class="space-y-6">
            @foreach ($klaesSuites as $suite)
                <div>
                    <h4 class="mb-1 flex items-center text-lg font-medium">
                        <span class="mr-2 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/60">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </span>
                        {{ $suite['title'] }}
                    </h4>
                    <p class="mb-2 pl-8 text-sm italic text-gray-600 dark:text-gray-400">{{ $suite['intro'] }}</p>
                    <ul class="space-y-1.5 pl-8">
                        @foreach ($suite['items'] as $label => $text)
                            <li class="flex items-start">
                                <svg class="mr-2 mt-1 h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                <span class="text-gray-700 dark:text-gray-300"><span class="font-semibold">{{ $label }}:</span> {{ $text }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mb-6 rounded-lg border border-blue-100 bg-blue-50 p-6 shadow-sm dark:border-blue-900/60 dark:bg-blue-950/40">
        <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">Powered by Next-Gen Technologies</h3>
        <p class="mb-4 leading-relaxed text-gray-700 dark:text-gray-300">
            KLAES 2.0 leverages cutting-edge technology to guarantee trust, speed, and accuracy:
        </p>
        <ul class="space-y-2">
            @foreach ($klaesTech as $label => $text)
                <li class="flex items-start">
                    <svg class="mr-2 mt-1 h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span class="text-gray-700 dark:text-gray-300"><span class="font-semibold">{{ $label }}:</span> {{ $text }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="mb-8 text-center">
        <p class="text-xl font-semibold italic">
            "KLAES 2.0: Interoperable Land, Urban, and Revenue Governance for a Smarter Kano."
        </p>
    </div>

    <div class="mb-8 mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
        © {{ now()->year }} LAAD-Sys - Land Admin System. All rights reserved.
    </div>
</div>
