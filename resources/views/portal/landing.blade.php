{{-- Applicant portal landing page (user 2026-11-23: a landing page first, with Sign in and Create account, not straight
     to the login). Copied from the KADCA portal landing, whose structure follows the KLAES LAAS portal landing (gov strip,
     masthead, hero, how it moves, before you begin, closing call, footer) and the MLPP-SG surveyor landing (photo hero),
     with the Kano photos of the app's own landing (welcome.blade.php). KLAES-METRO green. Self-contained CSS, so it needs
     no asset build; light and dark follow the saved theme choice. --}}
@php
    $agency = 'KLAES-METRO';
    $agencyName = 'Kano State Metropolitan Area Management Agency';
    $short = 'KAMMA';
    $photos = ['kano-1.webp', 'kano-2.webp', 'kano-3.webp'];

    $services = [
        ['file-plus-2', 'Ask for a Planning Recommendation', 'On a land application (Conversion, Sectional Titling, SLTR) or a Post-CofO application: Sub-Division, Merger, Extension or Change of Purpose.'],
        ['copy', 'Shadow File, not your land file', 'KAMMA works on a Shadow File: its own copy of your land file. Your land file is never changed.'],
        ['receipt', 'Pay your bills', 'See the processing fee and the betterment bill, what to pay, the reference to quote and when payment is confirmed.'],
        ['route', 'Track progress', 'Follow your request from the site visit and the zonal KSI to the Planning Recommendation, the MD KAMMA and the Ministry.'],
        ['bell', 'Get every update', 'Each step reaches you on the portal and by SMS from KLAES-METRO, so you never have to call to ask.'],
    ];

    // PLACEHOLDER until KAMMA sends its office address, phone and email.
    $contact = [
        ['map-pin', 'Visit', 'Kano State Metropolitan Area Management Agency, Kano. Office address to be confirmed.'],
        ['clock', 'Office hours', 'Monday to Friday, 8:00 am to 4:00 pm.'],
        ['message-square', 'Updates', 'Every update on your request reaches you on this portal and by SMS from KLAES-METRO.'],
    ];

    $faqs = [
        ['Is my land inside KAMMA\'s area?', 'KAMMA covers land within 10 km of Metropolitan Kano and of every urban centre (10 km from each LGA headquarters). Outside that buffer the Planning Recommendation comes from Physical Planning at the Ministry.'],
        ['What is a Shadow File?', 'A digital copy of your land file that KAMMA works on. It keeps your land file number as its reference, holds the site photos, the KSI and the bills, and never changes your land file.'],
        ['What is the KSI?', 'The KAMMA Site Inspection: the Zonal Office inspects and measures the site. The betterment bill is worked out on the area the KSI recommends.'],
        ['Why would my betterment bill be four times higher?', 'If the KSI finds a building on the land that has no Building Plan Approval, the betterment fee is the standard fee × '.$penalty.'. Upload your Building Plan Approval if you have one.'],
        ['How do I pay?', 'Quote your bill number as the reference and pay through an official channel. KAMMA confirms the payment against your receipt, then moves your request on.'],
        ['What happens at the end?', 'The MD KAMMA approves the Planning Recommendation and your land file goes back to the Ministry of Land for the next stage.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <x-theme-init />
    <title>Applicant Portal &mdash; {{ $agency }}</title>
    <meta name="description" content="{{ $agency }} Applicant Portal: ask for a Planning Recommendation with the {{ $agencyName }}, pay bills and track progress.">
    <meta name="theme-color" content="#065f46">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #047857; --brand-strong: #065f46; --brand-deep: #052e1f; --brand-tint: #ecfdf5; --brand-line: #a7f3d0;
            --accent: #16a34a; --gold: #ca8a04;
            --surface: #f8fafc; --surface-card: #ffffff; --surface-alt: #ecfdf5;
            --ink: #0f172a; --ink-soft: #475569; --ink-faint: #94a3b8; --border: #e2e8f0;
            --on-deep: #ffffff; --on-deep-soft: #a7f3d0;
            --shadow: 0 1px 2px rgba(15, 23, 42, .06), 0 8px 24px rgba(15, 23, 42, .06);
        }
        .dark {
            --brand: #34d399; --brand-strong: #6ee7b7; --brand-deep: #03140d; --brand-tint: rgba(16, 185, 129, .14); --brand-line: rgba(52, 211, 153, .35);
            --surface: #020617; --surface-card: #0f172a; --surface-alt: #0b1124;
            --ink: #f1f5f9; --ink-soft: #94a3b8; --ink-faint: #64748b; --border: #1e293b;
            --on-deep-soft: #6ee7b7;
            --shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 8px 24px rgba(0, 0, 0, .35);
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }
        body { margin: 0; background: var(--surface); color: var(--ink); font-family: Figtree, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; line-height: 1.5; -webkit-font-smoothing: antialiased; }
        img { max-width: 100%; display: block; }
        a { color: inherit; }
        .wrap { width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 16px; }
        @media (min-width: 640px) { .wrap { padding: 0 24px; } }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0; }
        .icon { width: 1.15rem; height: 1.15rem; flex-shrink: 0; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; border-radius: .6rem; padding: .7rem 1.2rem; font-weight: 700; font-size: .95rem; text-decoration: none; border: 2px solid transparent; cursor: pointer; transition: background .2s, color .2s, border-color .2s, transform .2s; white-space: nowrap; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: var(--brand-strong); }
        .dark .btn-primary { color: #03140d; }
        .btn-outline { border-color: var(--brand-line); color: var(--brand); background: transparent; }
        .btn-outline:hover { background: var(--brand-tint); }
        .btn-invert { background: #fff; color: #052e1f; }
        .btn-invert:hover { background: #ecfdf5; }
        .btn-ghost { border-color: rgba(255, 255, 255, .45); color: #fff; background: rgba(255, 255, 255, .06); }
        .btn-ghost:hover { background: rgba(255, 255, 255, .16); }
        .btn-lg { padding: .95rem 1.6rem; font-size: 1rem; }

        /* Government strip + masthead (LAAS gov-header) */
        .gov { background: var(--brand-deep); color: var(--on-deep-soft); font-size: 11px; font-weight: 600; letter-spacing: .02em; }
        .gov .wrap { display: flex; justify-content: space-between; align-items: center; gap: .5rem; padding-top: .45rem; padding-bottom: .45rem; }
        .gov b { color: #fff; font-weight: 600; }
        .gov .secure { display: none; align-items: center; gap: .35rem; }
        @media (min-width: 640px) { .gov .secure { display: inline-flex; } }
        .masthead { position: sticky; top: 0; z-index: 40; background: color-mix(in srgb, var(--surface-card) 94%, transparent); backdrop-filter: blur(8px); border-bottom: 1px solid var(--border); }
        .masthead .wrap { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-top: .6rem; padding-bottom: .6rem; }
        .brand { display: flex; align-items: center; gap: .75rem; text-decoration: none; min-width: 0; }
        .brand img { width: 3rem; height: 3rem; object-fit: contain; }
        .brand strong { display: block; font-size: 1rem; font-weight: 800; letter-spacing: .04em; color: var(--ink); }
        .brand small { display: block; font-size: .72rem; font-weight: 600; color: var(--ink-soft); }
        .nav { display: none; align-items: center; gap: .25rem; }
        .nav a.link { padding: .5rem .7rem; border-radius: .5rem; font-size: .9rem; font-weight: 600; color: var(--ink-soft); text-decoration: none; }
        .nav a.link:hover { background: var(--brand-tint); color: var(--ink); }
        .nav .sep { width: 1px; height: 1.5rem; background: var(--border); margin: 0 .4rem; }
        .head-actions { display: flex; align-items: center; gap: .4rem; }
        .icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 2.4rem; height: 2.4rem; border-radius: .55rem; border: 0; background: transparent; color: var(--ink-soft); cursor: pointer; }
        .icon-btn:hover { background: var(--brand-tint); color: var(--ink); }
        .dark .only-light, .only-dark { display: none; }
        .dark .only-dark { display: inline; }
        .head-cta { display: none; }
        @media (min-width: 640px) { .head-cta { display: inline-flex; } }
        .menu-btn { display: inline-flex; }
        @media (min-width: 1024px) { .nav { display: flex; } .menu-btn { display: none; } }
        .mobile-nav { display: none; border-top: 1px solid var(--border); background: var(--surface-card); }
        .mobile-nav.open { display: block; }
        .mobile-nav .wrap { display: grid; gap: .25rem; padding-top: .75rem; padding-bottom: 1rem; }
        .mobile-nav a.link { padding: .65rem .5rem; border-radius: .5rem; font-weight: 600; color: var(--ink-soft); text-decoration: none; }
        .mobile-nav .row { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-top: .5rem; }
        @media (min-width: 1024px) { .mobile-nav, .mobile-nav.open { display: none; } }

        /* Hero: the Kano photos sliding under a brand overlay */
        .hero { position: relative; overflow: hidden; background: var(--brand-deep); color: #fff; }
        .hero .slides { position: absolute; inset: 0; }
        .hero .slide { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 1.2s ease; }
        .hero .slide.active { opacity: 1; }
        .hero .shade { position: absolute; inset: 0; background: linear-gradient(100deg, rgba(5, 46, 31, .94) 0%, rgba(5, 46, 31, .82) 45%, rgba(5, 46, 31, .45) 100%); }
        .dark .hero .shade { background: linear-gradient(100deg, rgba(2, 6, 23, .96) 0%, rgba(3, 20, 13, .86) 45%, rgba(3, 20, 13, .5) 100%); }
        .hero .wrap { position: relative; display: grid; gap: 2.5rem; padding-top: 3.5rem; padding-bottom: 4rem; align-items: center; }
        @media (min-width: 1024px) { .hero .wrap { grid-template-columns: 1.15fr .85fr; padding-top: 5.5rem; padding-bottom: 6rem; } }
        .badge { display: inline-flex; align-items: center; gap: .6rem; padding: .35rem 1rem .35rem .35rem; border-radius: 999px; border: 1px solid rgba(255, 255, 255, .25); background: rgba(255, 255, 255, .08); font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #d1fae5; }
        .badge img { width: 1.6rem; height: 1.6rem; object-fit: contain; background: #fff; border-radius: 999px; padding: 2px; }
        .hero h1 { margin: 1.4rem 0 0; font-size: clamp(2.1rem, 6vw, 3.6rem); line-height: 1.06; font-weight: 900; letter-spacing: -.02em; }
        .hero h1 span { color: #6ee7b7; }
        .hero p.lead { margin: 1.25rem 0 0; max-width: 36rem; font-size: 1.08rem; line-height: 1.75; color: #d1fae5; }
        .hero .ctas { margin-top: 2rem; display: flex; flex-direction: column; gap: .75rem; }
        @media (min-width: 640px) { .hero .ctas { flex-direction: row; } }
        .trust { margin: 2.25rem 0 0; padding: 1.5rem 0 0; list-style: none; border-top: 1px solid rgba(255, 255, 255, .16); display: flex; flex-wrap: wrap; gap: .75rem 1.75rem; font-size: .875rem; font-weight: 600; color: #d1fae5; }
        .trust li { display: flex; align-items: center; gap: .5rem; }
        .hero-card { display: none; border-radius: 1.25rem; border: 1px solid rgba(255, 255, 255, .18); background: rgba(15, 23, 42, .55); backdrop-filter: blur(10px); padding: 1.5rem; box-shadow: 0 20px 50px rgba(0, 0, 0, .35); }
        @media (min-width: 1024px) { .hero-card { display: block; } }
        .hero-card h2 { margin: 0 0 1rem; font-size: .8rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: #a7f3d0; }
        .mini { list-style: none; margin: 0; padding: 0; }
        .mini li { display: flex; gap: .8rem; padding-bottom: .9rem; position: relative; }
        .mini li:not(:last-child)::after { content: ""; position: absolute; left: .8rem; top: 1.75rem; bottom: .1rem; width: 2px; background: rgba(255, 255, 255, .15); }
        .mini .dot { width: 1.6rem; height: 1.6rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 800; flex-shrink: 0; background: rgba(255, 255, 255, .12); color: #d1fae5; }
        .mini .dot.done { background: #22c55e; color: #fff; }
        .mini .dot.now { background: #fff; color: #065f46; }
        .mini b { display: block; font-size: .9rem; }
        .mini small { display: block; font-size: .78rem; color: #a7f3d0; }
        .dots { position: absolute; left: 50%; bottom: 1rem; transform: translateX(-50%); display: flex; gap: .4rem; z-index: 2; }
        .dots button { width: .55rem; height: .55rem; border-radius: 999px; border: 0; background: rgba(255, 255, 255, .4); cursor: pointer; padding: 0; }
        .dots button.active { width: 1.4rem; background: #fff; }

        /* Sections */
        section.block { padding: 4.5rem 0; scroll-margin-top: 4.5rem; }
        section.alt { background: var(--surface-alt); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
        .head { max-width: 42rem; margin: 0 auto 2.75rem; text-align: center; }
        .eyebrow { margin: 0 0 .6rem; font-size: .75rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: var(--brand); }
        .head h2 { margin: 0; font-size: clamp(1.7rem, 4vw, 2.4rem); font-weight: 800; letter-spacing: -.01em; color: var(--ink); }
        .head p { margin: .9rem 0 0; color: var(--ink-soft); font-size: 1rem; line-height: 1.7; }
        .grid { display: grid; gap: 1.1rem; }
        @media (min-width: 640px) { .grid-2, .grid-3, .grid-5 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } .grid-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
        .card { background: var(--surface-card); border: 1px solid var(--border); border-radius: 1rem; padding: 1.5rem; box-shadow: var(--shadow); min-width: 0; }
        .card h3 { margin: 0; font-size: 1.02rem; font-weight: 800; color: var(--ink); }
        .card p { margin: .5rem 0 0; font-size: .9rem; line-height: 1.6; color: var(--ink-soft); }
        .tile { width: 3rem; height: 3rem; border-radius: .85rem; display: flex; align-items: center; justify-content: center; background: var(--brand-tint); color: var(--brand); margin-bottom: 1rem; }
        .tile.solid { background: var(--brand); color: #fff; }
        .dark .tile.solid { color: #03140d; }

        /* Steps */
        .steps { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; counter-reset: step; }
        @media (min-width: 640px) { .steps { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .steps { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .steps li { display: flex; gap: 1rem; align-items: flex-start; }
        .num { width: 2.6rem; height: 2.6rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-weight: 900; color: #fff; background: var(--brand); flex-shrink: 0; box-shadow: 0 0 0 4px var(--surface-card); }
        .dark .num { color: #03140d; }

        /* Requirements */
        .checklist { list-style: none; margin: 1rem 0 0; padding: 0; display: grid; gap: .55rem; }
        .checklist li { display: flex; gap: .6rem; align-items: flex-start; font-size: .92rem; color: var(--ink-soft); }
        .checklist li i, .checklist li svg { color: var(--accent); margin-top: .15rem; }
        .chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: 1rem; }
        .chip { display: inline-flex; padding: .3rem .65rem; border-radius: 999px; background: var(--brand-tint); color: var(--brand); font-size: .75rem; font-weight: 800; letter-spacing: .03em; }

        /* Fees */
        .fee { display: flex; flex-wrap: wrap; align-items: baseline; gap: .35rem .75rem; margin-top: .25rem; }
        .fee .amount { font-size: clamp(2rem, 5vw, 2.6rem); font-weight: 900; color: var(--ink); letter-spacing: -.02em; }
        .fee .per { font-weight: 700; color: var(--ink-soft); }
        .feelist { list-style: none; margin: 1.25rem 0 0; padding: 0; border-top: 1px solid var(--border); }
        .feelist li { display: flex; justify-content: space-between; gap: 1rem; padding: .8rem 0; border-bottom: 1px solid var(--border); font-size: .92rem; color: var(--ink-soft); }
        .feelist li b { color: var(--ink); text-align: right; }
        .note { margin-top: 1rem; display: flex; gap: .6rem; align-items: flex-start; padding: .85rem 1rem; border-radius: .75rem; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-size: .85rem; line-height: 1.55; }
        .dark .note { background: rgba(245, 158, 11, .1); border-color: rgba(245, 158, 11, .3); color: #fcd34d; }

        /* FAQ */
        .faq { max-width: 52rem; margin: 0 auto; display: grid; gap: .75rem; }
        .faq details { background: var(--surface-card); border: 1px solid var(--border); border-radius: .85rem; padding: 0 1.15rem; }
        .faq summary { list-style: none; cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 1rem 0; font-weight: 700; color: var(--ink); }
        .faq summary::-webkit-details-marker { display: none; }
        .faq summary .chev { transition: transform .2s; color: var(--ink-faint); }
        .faq details[open] summary .chev { transform: rotate(180deg); }
        .faq details p { margin: 0 0 1.1rem; color: var(--ink-soft); font-size: .93rem; line-height: 1.65; }

        /* Closing call + footer */
        .cta { position: relative; overflow: hidden; border-radius: 1.5rem; background: var(--brand-deep); color: #fff; text-align: center; padding: 3.25rem 1.25rem; }
        @media (min-width: 640px) { .cta { padding: 3.75rem 3rem; } }
        .cta img.bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .18; }
        .cta > div { position: relative; }
        .cta h2 { margin: 0; font-size: clamp(1.7rem, 4vw, 2.3rem); font-weight: 800; }
        .cta p { margin: 1rem auto 0; max-width: 36rem; color: #d1fae5; line-height: 1.7; }
        .cta .ctas { margin-top: 2rem; display: flex; flex-direction: column; gap: .75rem; justify-content: center; }
        @media (min-width: 640px) { .cta .ctas { flex-direction: row; } }
        footer.site { background: var(--brand-deep); color: var(--on-deep-soft); }
        footer.site .wrap { padding-top: 3.25rem; padding-bottom: 2rem; }
        .fgrid { display: grid; gap: 2.25rem; }
        @media (min-width: 768px) { .fgrid { grid-template-columns: 1.4fr 1fr 1fr; } }
        footer.site h4 { margin: 0 0 .9rem; color: #fff; font-size: .75rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        footer.site ul { list-style: none; margin: 0; padding: 0; display: grid; gap: .55rem; }
        footer.site a { color: var(--on-deep-soft); text-decoration: none; font-size: .9rem; font-weight: 500; }
        footer.site a:hover { color: #fff; text-decoration: underline; }
        footer.site p { font-size: .88rem; line-height: 1.65; margin: 0; }
        .fbase { margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, .15); display: flex; flex-direction: column; gap: 1rem; align-items: center; justify-content: space-between; font-size: .78rem; }
        @media (min-width: 640px) { .fbase { flex-direction: row; } }
        .fbase img { height: 2.6rem; width: auto; opacity: .9; }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } .hero .slide, .btn { transition: none; } }
    </style>
</head>
<body>
    <x-layout.preloader :delay="350" />

    {{-- Government identification strip (LAAS gov-header) --}}
    <div class="gov">
        <div class="wrap">
            <span>An official portal of the <b>{{ $agencyName }} ({{ $short }})</b>, Kano State</span>
            <span class="secure"><i data-lucide="lock" class="icon" style="width:12px;height:12px" aria-hidden="true"></i> Secure connection</span>
        </div>
    </div>

    {{-- Masthead --}}
    <header class="masthead">
        <div class="wrap">
            <a href="{{ route('portal.home') }}" class="brand">
                <img src="{{ asset('images/logo.png') }}" alt="{{ $short }} {{ $agency }} logo">
                <span><strong>{{ $agency }}</strong><small>Applicant Portal</small></span>
            </a>

            <nav class="nav" aria-label="Primary">
                <a class="link" href="#services">What you can do</a>
                <a class="link" href="#how-it-works">How it works</a>
                <a class="link" href="#requirements">Requirements</a>
                <a class="link" href="#fees">Fees</a>
                <a class="link" href="#faq">FAQ</a>
                <a class="link" href="#contact">Contact</a>
                <span class="sep"></span>
            </nav>

            <div class="head-actions">
                <button type="button" class="icon-btn" data-theme-toggle aria-label="Switch theme" title="Switch theme">
                    <i data-lucide="moon" class="icon only-light" aria-hidden="true"></i>
                    <i data-lucide="sun" class="icon only-dark" aria-hidden="true"></i>
                </button>
                <a href="{{ route('portal.login') }}" class="btn btn-outline head-cta" data-cta="sign-in">Sign in</a>
                <a href="{{ route('portal.register') }}" class="btn btn-primary head-cta" data-cta="sign-up">Create account</a>
                <button type="button" class="icon-btn menu-btn" data-menu-toggle aria-expanded="false" aria-controls="mobile-nav" aria-label="Open menu">
                    <i data-lucide="menu" class="icon" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="mobile-nav" id="mobile-nav">
            <div class="wrap">
                <a class="link" href="#services">What you can do</a>
                <a class="link" href="#how-it-works">How it works</a>
                <a class="link" href="#requirements">Requirements</a>
                <a class="link" href="#fees">Fees</a>
                <a class="link" href="#faq">FAQ</a>
                <a class="link" href="#contact">Contact</a>
                <div class="row">
                    <a href="{{ route('portal.login') }}" class="btn btn-outline">Sign in</a>
                    <a href="{{ route('portal.register') }}" class="btn btn-primary">Create account</a>
                </div>
            </div>
        </div>
    </header>

    <main id="portal-landing">
        {{-- Hero --}}
        <section class="hero" aria-label="{{ $agency }} Applicant Portal">
            <div class="slides" id="slides">
                @foreach ($photos as $photo)
                    <img src="{{ asset('images/landing/'.$photo) }}" alt="" aria-hidden="true" class="slide {{ $loop->first ? 'active' : '' }}" @unless ($loop->first) loading="lazy" @endunless>
                @endforeach
            </div>
            <div class="shade" aria-hidden="true"></div>

            <div class="wrap">
                <div>
                    <p class="badge"><img src="{{ asset('images/logo.png') }}" alt="" aria-hidden="true"> Official {{ $short }} Portal &middot; Planning Recommendation</p>
                    <h1>Your Planning Recommendation, <span>online</span></h1>
                    <p class="lead">
                        Ask the {{ $agencyName }} for a Planning Recommendation on your land application, or on a Sub-Division,
                        Merger, Extension or Change of Purpose inside the 10 km metropolitan buffer. Pay your bills and follow your
                        Shadow File desk by desk, back to the Ministry.
                    </p>
                    <div class="ctas">
                        <a href="{{ route('portal.register') }}" class="btn btn-invert btn-lg" data-cta="sign-up">
                            <i data-lucide="user-plus" class="icon" aria-hidden="true"></i> Create account
                        </a>
                        <a href="{{ route('portal.login') }}" class="btn btn-ghost btn-lg" data-cta="sign-in">
                            <i data-lucide="log-in" class="icon" aria-hidden="true"></i> Sign in
                        </a>
                    </div>
                    <ul class="trust">
                        <li><i data-lucide="shield-check" class="icon" aria-hidden="true"></i> Linked to your KLAES land file</li>
                        <li><i data-lucide="message-square-check" class="icon" aria-hidden="true"></i> SMS at every step</li>
                        <li><i data-lucide="copy" class="icon" aria-hidden="true"></i> Your land file is never changed</li>
                    </ul>
                </div>

                <aside class="hero-card" aria-label="Example application progress">
                    <h2>Your application, step by step</h2>
                    <ol class="mini">
                        @foreach (array_slice($steps, 0, 6, true) as $key => [$title, $hint])
                            <li>
                                <span class="dot {{ $loop->index < 4 ? 'done' : ($loop->index === 4 ? 'now' : '') }}">
                                    @if ($loop->index < 4) &#10003; @else {{ $loop->iteration }} @endif
                                </span>
                                <span><b>{{ $title }}</b>@if ($loop->index === 4)<small>{{ $hint }}</small>@endif</span>
                            </li>
                        @endforeach
                    </ol>
                </aside>
            </div>

            <div class="dots" role="tablist" aria-label="Photos">
                @foreach ($photos as $photo)
                    <button type="button" class="{{ $loop->first ? 'active' : '' }}" data-slide="{{ $loop->index }}" aria-label="Photo {{ $loop->iteration }}"></button>
                @endforeach
            </div>
        </section>

        {{-- What the portal does --}}
        <section class="block" id="services">
            <div class="wrap">
                <div class="head">
                    <p class="eyebrow">The portal</p>
                    <h2>Everything for your request, in one place</h2>
                    <p>One account for all your requests with {{ $short }}. No queues to ask where your file is.</p>
                </div>
                <div class="grid grid-5">
                    @foreach ($services as [$icon, $title, $desc])
                        <div class="card">
                            <span class="tile"><i data-lucide="{{ $icon }}" class="icon" style="width:1.4rem;height:1.4rem" aria-hidden="true"></i></span>
                            <h3>{{ $title }}</h3>
                            <p>{{ $desc }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Steps of the process (the portal timeline, App\Support\Portal\PortalTimeline) --}}
        <section class="block alt" id="how-it-works">
            <div class="wrap">
                <div class="head">
                    <p class="eyebrow">The process</p>
                    <h2>How your application moves</h2>
                    <p>{{ count($steps) }} steps from your request to the file going back to the Ministry. You are told when each one completes.</p>
                </div>
                <ol class="steps">
                    @foreach ($steps as $key => [$title, $hint])
                        <li class="card">
                            <span class="num">{{ $loop->iteration }}</span>
                            <div>
                                <h3>{{ $title }}</h3>
                                <p>{{ $hint }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- Requirements --}}
        <section class="block" id="requirements">
            <div class="wrap">
                <div class="head">
                    <p class="eyebrow">Before you begin</p>
                    <h2>What you need</h2>
                    <p>Have these ready. The form is in short steps and checks each one before you move on.</p>
                </div>
                <div class="grid grid-3">
                    <div class="card">
                        <span class="tile solid"><i data-lucide="folder-check" class="icon" style="width:1.4rem;height:1.4rem" aria-hidden="true"></i></span>
                        <h3>Your land file and phone</h3>
                        <ul class="checklist">
                            <li><i data-lucide="check" class="icon" aria-hidden="true"></i> The file number of your land file in KLAES (from your title documents)</li>
                            <li><i data-lucide="check" class="icon" aria-hidden="true"></i> For a Merger, the file numbers of the other plots</li>
                            <li><i data-lucide="check" class="icon" aria-hidden="true"></i> A Nigerian mobile number that receives SMS</li>
                        </ul>
                    </div>
                    <div class="card">
                        <span class="tile solid"><i data-lucide="files" class="icon" style="width:1.4rem;height:1.4rem" aria-hidden="true"></i></span>
                        <h3>Documents</h3>
                        <ul class="checklist">
                            @foreach ($documents as $doc)
                                <li><i data-lucide="check" class="icon" aria-hidden="true"></i> {{ $doc }}</li>
                            @endforeach
                        </ul>
                        <p style="margin-top:.8rem;font-size:.8rem">Send the ones that apply to your request. Upload as {{ implode(', ', $fileTypes) }}, up to {{ $uploadMaxMb }} MB each.</p>
                    </div>
                    <div class="card">
                        <span class="tile solid"><i data-lucide="list-checks" class="icon" style="width:1.4rem;height:1.4rem" aria-hidden="true"></i></span>
                        <h3>Types of request</h3>
                        @foreach ($types as $category => $list)
                            <p style="margin-top:.9rem;font-weight:800;color:var(--ink)">{{ \App\Support\Portal\PortalForm::CATEGORIES[$category] }}</p>
                            <div class="chips" style="margin-top:.45rem">
                                @foreach ($list as $type => $label)
                                    <span class="chip">{{ $type }}</span>
                                @endforeach
                            </div>
                        @endforeach
                        <p style="margin-top:.9rem;font-size:.8rem">Inside the 10 km buffer around Metropolitan Kano and each urban centre.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Fees (config/kamma.php bills) --}}
        <section class="block alt" id="fees">
            <div class="wrap">
                <div class="head">
                    <p class="eyebrow">Fees</p>
                    <h2>What it costs</h2>
                    <p>Two bills: the processing fee when your Shadow File is opened, and the betterment bill after the KSI.</p>
                </div>
                <div class="grid grid-3" style="max-width:72rem;margin:0 auto">
                    <div class="card">
                        <span class="tile"><i data-lucide="receipt" class="icon" style="width:1.4rem;height:1.4rem" aria-hidden="true"></i></span>
                        <h3>Application processing fee</h3>
                        @if ($processingFee !== null)
                            <div class="fee"><span class="amount" data-fee>₦{{ number_format($processingFee, 2) }}</span><span class="per">per request</span></div>
                        @else
                            <div class="fee"><span class="amount" data-fee style="font-size:1.4rem">Set by KAMMA</span></div>
                        @endif
                        <p>Raised when KAMMA opens the Shadow File. Your request moves on once it is paid.</p>
                    </div>
                    <div class="card">
                        <span class="tile"><i data-lucide="landmark" class="icon" style="width:1.4rem;height:1.4rem" aria-hidden="true"></i></span>
                        <h3>Betterment</h3>
                        <ul class="feelist">
                            <li>Worked out on <b>the KSI's recommended area</b></li>
                            <li>Building with a BPA, or no building <b>Standard fee</b></li>
                            <li>Building without a BPA <b style="color:#e11d48">Standard × {{ $penalty }}</b></li>
                        </ul>
                        <p class="note"><i data-lucide="triangle-alert" class="icon" aria-hidden="true"></i> Built without a Building Plan Approval? The betterment fee is {{ $penalty }} times the standard fee.</p>
                    </div>
                    <div class="card">
                        <span class="tile"><i data-lucide="wallet" class="icon" style="width:1.4rem;height:1.4rem" aria-hidden="true"></i></span>
                        <h3>How to pay</h3>
                        <ul class="checklist">
                            <li><i data-lucide="check" class="icon" aria-hidden="true"></i> Pay the exact amount on your bill</li>
                            <li><i data-lucide="check" class="icon" aria-hidden="true"></i> Quote your bill number as the payment reference</li>
                            <li><i data-lucide="check" class="icon" aria-hidden="true"></i> Pay through an official channel: bank, POS, Interswitch or USSD</li>
                            <li><i data-lucide="check" class="icon" aria-hidden="true"></i> Keep the receipt; {{ $short }} confirms it</li>
                        </ul>
                        <p class="note"><i data-lucide="shield-alert" class="icon" aria-hidden="true"></i> {{ $short }} never asks for payment through private accounts or agents.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section class="block" id="faq">
            <div class="wrap">
                <div class="head">
                    <p class="eyebrow">Questions</p>
                    <h2>Frequently asked</h2>
                </div>
                <div class="faq">
                    @foreach ($faqs as [$q, $a])
                        <details @if ($loop->first) open @endif>
                            <summary>{{ $q }} <i data-lucide="chevron-down" class="icon chev" aria-hidden="true"></i></summary>
                            <p>{{ $a }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Contact --}}
        <section class="block alt" id="contact">
            <div class="wrap">
                <div class="head">
                    <p class="eyebrow">Contact</p>
                    <h2>Talk to {{ $short }}</h2>
                </div>
                <div class="grid grid-3">
                    @foreach ($contact as [$icon, $title, $text])
                        <div class="card">
                            <span class="tile"><i data-lucide="{{ $icon }}" class="icon" style="width:1.4rem;height:1.4rem" aria-hidden="true"></i></span>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Closing call --}}
        <section class="block">
            <div class="wrap" style="max-width:60rem">
                <div class="cta">
                    <img class="bg" src="{{ asset('images/landing/'.$photos[1]) }}" alt="" aria-hidden="true" loading="lazy">
                    <div>
                        <h2>Ready to apply?</h2>
                        <p>Create your account with your phone number, then make your request for a Planning Recommendation. Already have an account? Sign in to follow your file.</p>
                        <div class="ctas">
                            <a href="{{ route('portal.register') }}" class="btn btn-invert btn-lg"><i data-lucide="user-plus" class="icon" aria-hidden="true"></i> Create account</a>
                            <a href="{{ route('portal.login') }}" class="btn btn-ghost btn-lg"><i data-lucide="log-in" class="icon" aria-hidden="true"></i> Sign in</a>
                        </div>
                        @if ($demoEnabled)
                            <p style="font-size:.82rem;margin-top:1.25rem">Preview: try it with the demo applicant on the sign-in page. Nothing you do there is saved.</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="site">
        <div class="wrap">
            <div class="fgrid">
                <div>
                    <div class="brand" style="margin-bottom:1rem">
                        <img src="{{ asset('images/logo.png') }}" alt="" aria-hidden="true">
                        <span><strong style="color:#fff">{{ $agency }}</strong><small style="color:var(--on-deep-soft)">{{ $agencyName }}</small></span>
                    </div>
                    <p>The {{ $agency }} Applicant Portal is {{ $short }}'s official channel for Planning Recommendations inside the 10 km metropolitan buffer, part of the KLAES 2.0 Suite.</p>
                </div>
                <div>
                    <h4>Portal</h4>
                    <ul>
                        <li><a href="{{ route('portal.register') }}">Create account</a></li>
                        <li><a href="{{ route('portal.login') }}">Sign in</a></li>
                        <li><a href="#how-it-works">How it works</a></li>
                        <li><a href="#requirements">Requirements</a></li>
                        <li><a href="#fees">Fees</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Beware of fraud</h4>
                    <p>{{ $short }} never asks for payment through private accounts or agents. Every genuine update reaches you on this portal and by SMS from {{ $agency }}.</p>
                </div>
            </div>
            <div class="fbase">
                <span>&copy; {{ date('Y') }} {{ $agencyName }}. Part of the KLAES 2.0 Suite.</span>
                <img src="{{ asset('images/lade-logo.png') }}" alt="LAnd ADmin Enterprise System">
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/lucide@0.429.0/dist/umd/lucide.min.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) window.lucide.createIcons();

            // Theme: same saved choice as the app's theme toggle (localStorage 'theme').
            document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var dark = ! document.documentElement.classList.contains('dark');
                    document.documentElement.classList.toggle('dark', dark);
                    try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
                });
            });

            // Phone menu.
            var menuBtn = document.querySelector('[data-menu-toggle]');
            var menu = document.getElementById('mobile-nav');
            menuBtn.addEventListener('click', function () {
                var open = menu.classList.toggle('open');
                menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            menu.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { menu.classList.remove('open'); menuBtn.setAttribute('aria-expanded', 'false'); }); });

            // Hero photos: cross-fade every 6 s; the dots pick one.
            var slides = Array.from(document.querySelectorAll('#slides .slide'));
            var dots = Array.from(document.querySelectorAll('.dots button'));
            var current = 0;
            function show(i) {
                slides[current].classList.remove('active'); dots[current].classList.remove('active');
                current = i;
                slides[current].classList.add('active'); dots[current].classList.add('active');
            }
            dots.forEach(function (d, i) { d.addEventListener('click', function () { show(i); }); });
            if (slides.length > 1 && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                setInterval(function () { show((current + 1) % slides.length); }, 6000);
            }
        });
    </script>
</body>
</html>
