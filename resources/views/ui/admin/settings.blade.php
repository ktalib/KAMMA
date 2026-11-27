{{-- System Admin > System Settings, copied from KLAES settings/index (tabs from its partials: General, Company, Email (SMTP),
     Two-factor / security). Values shown are KLAES-METRO's current configuration; secrets are never shown. Saves post to
     the preview stub (settings live in .env / config until a settings table is approved). --}}
<x-app-layout title="System Settings">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $tabs = ['general' => 'General', 'company' => 'Company', 'email' => 'Email (SMTP)', 'security' => 'Security'];
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="settings" title="System Settings" subtitle="KLAES-METRO configuration" tone="slate" section="System Admin" />
        <x-ui.preview-note what="Changing a setting" />

        <div class="grid gap-6 lg:grid-cols-4">
            <nav class="space-y-1 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                @foreach ($tabs as $k => $l)
                    <a href="{{ route('admin.settings', ['tab' => $k]) }}" @class(['block rounded-lg px-3 py-2 text-sm font-medium', 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' => $tab === $k, 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' => $tab !== $k])>{{ $l }}</a>
                @endforeach
            </nav>

            <form method="POST" action="{{ route('ui.preview', 'settings-'.$tab) }}" class="lg:col-span-3">
                @csrf
                <x-ui.panel :title="$tabs[$tab]">
                    <div class="grid gap-4 sm:grid-cols-2">
                        @if ($tab === 'general')
                            <div><label for="st-name" class="{{ $label }}">Application name</label><input id="st-name" name="app_name" value="{{ config('app.name') }}" class="{{ $field }}"></div>
                            <div><label for="st-module" class="{{ $label }}">Module name</label><input id="st-module" name="module_name" value="KLAES-METRO" class="{{ $field }}"></div>
                            <div><label for="st-tz" class="{{ $label }}">Time zone</label><input id="st-tz" name="timezone" value="{{ config('app.timezone') }}" class="{{ $field }}"></div>
                            <div><label for="st-loc" class="{{ $label }}">Language</label><input id="st-loc" name="locale" value="{{ config('app.locale') }}" class="{{ $field }}"></div>
                            <div><label for="st-df" class="{{ $label }}">Date format</label><select id="st-df" name="date_format" class="{{ $field }}"><option>d M Y</option><option>d/m/Y</option><option>Y-m-d</option></select></div>
                            <div><label for="st-theme" class="{{ $label }}">Default theme</label><select id="st-theme" name="theme" class="{{ $field }}"><option>Device setting</option><option>Light</option><option>Dark</option></select></div>
                        @elseif ($tab === 'company')
                            <div class="sm:col-span-2"><label for="st-co" class="{{ $label }}">Agency name</label><input id="st-co" name="company_name" value="Kano State Development Control Agency (KAMMA)" class="{{ $field }}"></div>
                            <div class="sm:col-span-2"><label for="st-addr" class="{{ $label }}">Address</label><input id="st-addr" name="company_address" class="{{ $field }}"></div>
                            <div><label for="st-ph" class="{{ $label }}">Phone</label><input id="st-ph" name="company_phone" class="{{ $field }}"></div>
                            <div><label for="st-em" class="{{ $label }}">Email</label><input id="st-em" type="email" name="company_email" class="{{ $field }}"></div>
                            <div class="sm:col-span-2"><span class="{{ $label }}">Logo</span><div class="flex items-center gap-4"><img src="{{ asset('images/logo.png') }}" alt="" class="h-14 w-14 object-contain"><input type="file" name="logo" accept="image/png" class="text-sm text-slate-600 dark:text-slate-300"></div></div>
                        @elseif ($tab === 'email')
                            <div><label for="st-mh" class="{{ $label }}">Mail host</label><input id="st-mh" name="mail_host" value="{{ config('mail.mailers.smtp.host') }}" class="{{ $field }}"></div>
                            <div><label for="st-mp" class="{{ $label }}">Port</label><input id="st-mp" name="mail_port" value="{{ config('mail.mailers.smtp.port') }}" class="{{ $field }}"></div>
                            <div><label for="st-mu" class="{{ $label }}">Username</label><input id="st-mu" name="mail_username" class="{{ $field }}" placeholder="Not shown"></div>
                            <div><label for="st-mpw" class="{{ $label }}">Password</label><input id="st-mpw" type="password" name="mail_password" class="{{ $field }}" placeholder="Not shown" autocomplete="new-password"></div>
                            <div><label for="st-mfa" class="{{ $label }}">From address</label><input id="st-mfa" type="email" name="mail_from_address" value="{{ config('mail.from.address') }}" class="{{ $field }}"></div>
                            <div><label for="st-mfn" class="{{ $label }}">From name</label><input id="st-mfn" name="mail_from_name" value="{{ config('mail.from.name') }}" class="{{ $field }}"></div>
                        @else
                            <label class="flex items-start gap-2 text-sm sm:col-span-2"><input type="checkbox" name="otp" value="1" class="mt-0.5 rounded dark:border-slate-600 dark:bg-slate-800"> <span><span class="font-semibold">One-time code at sign-in</span><br><span class="text-xs text-slate-500 dark:text-slate-400">KLAES's SMS code is not ported to KLAES-METRO yet (decision pending); sign-in is password only.</span></span></label>
                            <label class="flex items-start gap-2 text-sm sm:col-span-2"><input type="checkbox" name="security_questions" value="1" class="mt-0.5 rounded dark:border-slate-600 dark:bg-slate-800"> <span><span class="font-semibold">Security questions</span><br><span class="text-xs text-slate-500 dark:text-slate-400">KLAES's fallback questions, also pending.</span></span></label>
                            <div><label for="st-idle" class="{{ $label }}">Sign out after idle (minutes)</label><input id="st-idle" type="number" min="5" max="480" name="idle_minutes" value="{{ config('session.lifetime') }}" class="{{ $field }}"></div>
                        @endif
                    </div>
                    <div class="mt-6 flex justify-end"><button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Save {{ $tabs[$tab] }}</button></div>
                </x-ui.panel>
            </form>
        </div>
    </div>
</x-app-layout>
