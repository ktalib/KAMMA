{{-- Sign-in / register frame, copied from the KLAES LAAS portal sign-in: the form on the left, the assurance panel on the
     right (large screens). Expects $heading, $intro and a "form" section. --}}
<div class="grid min-h-screen lg:grid-cols-[1fr_1.05fr]">
    <main class="flex flex-col justify-center px-4 py-10 sm:px-8 lg:px-14">
        <div class="mx-auto w-full max-w-md">
            <a href="{{ route('portal.home') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white" data-back-home>
                <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i> Back to home
            </a>
            <div class="mb-8 flex items-center justify-between gap-3">
                <a href="{{ route('portal.home') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="KAMMA KLAES-METRO logo" class="h-14 w-14 object-contain">
                    <span>
                        <span class="block text-base font-extrabold tracking-wide text-slate-900 dark:text-white">KLAES-METRO</span>
                        <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400">Applicant Portal</span>
                    </span>
                </a>
                <x-theme-toggle />
            </div>

            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $heading }}</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $intro }}</p>

            @if (session('status'))
                <div role="status" class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="mt-6 space-y-1 rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-500/30 dark:bg-rose-500/10">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm font-medium text-rose-700 dark:text-rose-300">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @yield('form')

            <p class="mt-10 border-t border-slate-200 pt-6 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                Kano State Metropolitan Area Management Agency &middot; Part of the KLAES 2.0 Suite
            </p>
        </div>
    </main>

    <aside class="relative hidden overflow-hidden bg-emerald-950 lg:flex lg:flex-col lg:justify-center dark:bg-slate-900">
        <div class="pointer-events-none absolute -left-32 top-1/3 h-[460px] w-[460px] rounded-full bg-emerald-500/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative px-14 py-16">
            <p class="mb-5 inline-flex items-center gap-2.5 rounded-full border border-white/20 bg-white/5 py-2 pl-2 pr-4">
                <img src="{{ asset('images/logo.png') }}" alt="" aria-hidden="true" class="h-6 w-6 object-contain">
                <span class="text-[11px] font-bold uppercase tracking-widest text-emerald-200">Official KAMMA Portal</span>
            </p>
            <h2 class="max-w-md text-4xl font-black leading-tight text-white">Your Planning Recommendation, from request to the Ministry.</h2>
            <ul class="mt-10 space-y-6">
                @foreach ([
                    ['file-up', 'Apply from your phone', 'Pick your land file from KLAES records, choose Sub-Division, Merger, Extension, Change of Purpose or a land application, and upload your documents.'],
                    ['route', 'Follow every desk', 'See your Shadow File move from the processing fee to the site visit, the zonal KSI, the Planning Recommendation and the MD KAMMA.'],
                    ['landmark', 'Bills in one place', 'Pay the processing fee and the betterment bill. A building without a Building Plan Approval makes betterment four times the standard fee.'],
                    ['triangle-alert', 'Beware of fraud', 'KAMMA never asks for payment through private accounts or agents.'],
                ] as [$icon, $title, $desc])
                    <li class="flex gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10">
                            <i data-lucide="{{ $icon }}" class="h-5 w-5 text-emerald-200" aria-hidden="true"></i>
                        </span>
                        <div>
                            <p class="font-bold text-white">{{ $title }}</p>
                            <p class="mt-1 max-w-sm text-sm leading-6 text-emerald-200">{{ $desc }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="mt-14 border-t border-white/15 pt-7 text-xs font-semibold text-emerald-200">
                Kano State Metropolitan Area Management Agency<br>KLAES-METRO &middot; Part of the KLAES 2.0 Suite
            </p>
        </div>
    </aside>
</div>
