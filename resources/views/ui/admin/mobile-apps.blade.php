{{-- System Admin > KLAES Mobile Apps, copied from KLAES system-admin/mobile-apps/show (app name, version, minimum Android
     version, short description, about, icon, APK file, share link). The field app (one app, two stages, a WebView copy of
     the KLAES Field Observations app) is not built yet, so the details post to the preview stub. --}}
<x-app-layout title="KLAES Mobile Apps">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $brand = config('app.name') === 'KAMMA' ? 'KLAES-METRO' : 'KLAES-CONTROL';
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="smartphone" title="KLAES Mobile Apps" :subtitle="$brand.' Field App · details'" tone="slate" section="System Admin" />
        <x-ui.preview-note what="Saving the app details and uploading an APK" />

        <form method="POST" action="{{ route('ui.preview', 'mobile-app') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
            @csrf
            <x-ui.panel title="Details" class="lg:col-span-2">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="ma-name" class="{{ $label }}">App name <span class="text-rose-500">*</span></label><input id="ma-name" name="name" required value="{{ $brand }} Field App" class="{{ $field }}"></div>
                    <div><label for="ma-version" class="{{ $label }}">Version</label><input id="ma-version" name="version" placeholder="1.0.0" class="{{ $field }}"></div>
                    <div><label for="ma-android" class="{{ $label }}">Minimum Android version</label><input id="ma-android" name="min_android" placeholder="8.0" class="{{ $field }}"></div>
                    <div><label for="ma-short" class="{{ $label }}">Short description</label><input id="ma-short" name="short" maxlength="120" value="Site visits with geocoded photos" class="{{ $field }}"></div>
                    <div class="sm:col-span-2"><label for="ma-about" class="{{ $label }}">About the app</label><textarea id="ma-about" name="about" rows="4" class="{{ $field }}">Field officers record the site visit and inspection for a file: location, geocoded photos and observations, sent straight to the desk.</textarea></div>
                </div>
            </x-ui.panel>
            <div class="space-y-6">
                <x-ui.panel title="Files">
                    <div class="space-y-4">
                        <div><label for="ma-icon" class="{{ $label }}">App icon</label><input id="ma-icon" type="file" name="icon" accept="image/png" class="block w-full text-sm text-slate-600 dark:text-slate-300"></div>
                        <div><label for="ma-apk" class="{{ $label }}">APK file</label><input id="ma-apk" type="file" name="apk" accept=".apk" class="block w-full text-sm text-slate-600 dark:text-slate-300"></div>
                    </div>
                </x-ui.panel>
                <x-ui.panel title="Download & share">
                    <x-ui.empty icon="link" title="No APK uploaded yet">The share link (no login needed) appears once an APK is uploaded.</x-ui.empty>
                </x-ui.panel>
            </div>
            <div class="flex justify-end lg:col-span-3"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save details</button></div>
        </form>
    </div>
</x-app-layout>
