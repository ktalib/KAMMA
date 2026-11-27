@extends('portal.layouts.portal')

@section('title', 'Construction progress')

@section('content')
@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900';
    $field = 'block w-full rounded-lg border-slate-300 py-2.5 text-base shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500';
    $label = 'mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300';
    $h2 = 'text-sm font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400';
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Construction progress</h1>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Photograph the work at each stage. Every photo is stamped with your phone's location and the time, so take them on the site.</p>
</div>

@if ($applications->isEmpty())
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
        <i data-lucide="hard-hat" class="mx-auto mb-4 h-10 w-10 text-slate-300 dark:text-slate-600" aria-hidden="true"></i>
        <p class="text-base font-semibold text-slate-900 dark:text-white">Nothing to report yet</p>
        <p class="mx-auto mt-2 max-w-md text-sm text-slate-600 dark:text-slate-400">Construction progress opens once your building plan is approved. Building before approval is charged a penalty.</p>
        <a href="{{ route('portal.bpa.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Back to my applications</a>
    </div>
@else
<script>
    // Geocoded construction photos: the browser takes the position (GPS) and the time when the photos are added,
    // as the zonal officers' site photos are stamped. The photos go up together in one hidden file input.
    window.progressUpload = function (cfg) {
        return {
            photos: [],           // { file, url, time }
            gps: null,            // { lat, lng, accuracy, at }
            locating: false,
            gpsError: '',
            max: cfg.max,
            maxBytes: cfg.maxKb * 1024,
            sizeError: '',

            init() { this.locate(); },

            locate() {
                if (! navigator.geolocation) { this.gpsError = 'This browser cannot give a location. Use a phone with location on.'; return; }
                this.locating = true;
                this.gpsError = '';
                navigator.geolocation.getCurrentPosition((p) => {
                    this.gps = { lat: p.coords.latitude, lng: p.coords.longitude, accuracy: Math.round(p.coords.accuracy), at: new Date() };
                    this.locating = false;
                }, (e) => {
                    this.locating = false;
                    this.gpsError = e.code === 1
                        ? 'Location is blocked. Allow location for this site in your phone settings, then tap "Get my location".'
                        : 'Your location could not be found. Move to an open spot and tap "Get my location".';
                }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
            },

            add(event) {
                this.sizeError = '';
                for (const file of event.target.files) {
                    if (this.photos.length >= this.max) { this.sizeError = 'Up to ' + this.max + ' photos at a time.'; break; }
                    if (! file.type.startsWith('image/')) continue;
                    if (file.size > this.maxBytes) { this.sizeError = file.name + ' is larger than ' + Math.round(cfg.maxKb / 1024) + ' MB.'; continue; }
                    this.photos.push({ file, url: URL.createObjectURL(file), time: new Date() });
                }
                event.target.value = '';
                if (! this.gps && ! this.locating) this.locate();
                this.sync();
            },

            remove(i) {
                URL.revokeObjectURL(this.photos[i].url);
                this.photos.splice(i, 1);
                this.sync();
            },

            // Put the chosen photos into the real file input that is submitted.
            sync() {
                const dt = new DataTransfer();
                this.photos.forEach((p) => dt.items.add(p.file));
                this.$refs.upload.files = dt.files;
            },

            stamp(p) {
                const where = this.gps ? this.gps.lat.toFixed(6) + ', ' + this.gps.lng.toFixed(6) : 'no location yet';
                return where + ' · ' + p.time.toLocaleString('en-NG', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
            },

            get ready() { return this.photos.length > 0 && this.gps !== null; },
        };
    };
</script>

<div class="grid gap-6 lg:grid-cols-3">
    <form method="POST" action="{{ route('portal.bpa.progress.store') }}" enctype="multipart/form-data"
          x-data="progressUpload(@js(['max' => $maxPhotos, 'maxKb' => $maxKb]))" @submit="if (! ready) $event.preventDefault()"
          class="min-w-0 space-y-6 lg:col-span-2">
        @csrf

        <div class="{{ $card }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="application" class="{{ $label }}">Application <span class="text-rose-500">*</span></label>
                    <select id="application" name="application" required class="{{ $field }}"
                            onchange="window.location = '{{ route('portal.bpa.progress.index') }}?application=' + encodeURIComponent(this.value)">
                        @foreach ($applications as $a)
                            <option value="{{ $a['ref'] }}" @selected($selected && $selected['ref'] === $a['ref'])>{{ $a['ref'] }}{{ $a['bp_file_no'] ? ' · '.$a['bp_file_no'] : '' }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('application')" class="mt-2" />
                </div>
                <div>
                    <label for="construction_stage" class="{{ $label }}">Stage of the work <span class="text-rose-500">*</span></label>
                    <select id="construction_stage" name="construction_stage" required class="{{ $field }}">
                        <option value="">Select…</option>
                        @foreach ($stages as $stage)
                            <option value="{{ $stage }}" @selected(old('construction_stage') === $stage)>{{ $stage }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('construction_stage')" class="mt-2" />
                </div>
            </div>
            @if ($selected)
                <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Plot {{ $selected['plot_no'] ?: '—' }}, {{ $selected['district'] }}, {{ $selected['lga'] }} &middot; land file {{ $selected['file_number'] }}</p>
            @endif
        </div>

        {{-- Location --}}
        <div class="{{ $card }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="{{ $h2 }}">Your location</h2>
                <button type="button" @click="locate()" :disabled="locating" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    <i data-lucide="locate-fixed" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    <span x-text="locating ? 'Finding you…' : (gps ? 'Refresh location' : 'Get my location')"></span>
                </button>
            </div>
            <template x-if="gps">
                <div class="mt-3 flex items-start gap-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">
                    <i data-lucide="map-pin" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i>
                    <div>
                        <p class="font-mono font-semibold" x-text="gps.lat.toFixed(6) + ', ' + gps.lng.toFixed(6)"></p>
                        <p class="text-xs" x-text="'Accurate to about ' + gps.accuracy + ' m · taken ' + gps.at.toLocaleTimeString('en-NG')"></p>
                    </div>
                </div>
            </template>
            <p x-show="gpsError" x-cloak x-text="gpsError" class="mt-3 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-300"></p>
            <p x-show="! gps && ! gpsError" class="mt-3 text-sm text-slate-500 dark:text-slate-400">Allow location when your phone asks. Photos cannot be sent without it.</p>
            <input type="hidden" name="latitude" :value="gps ? gps.lat : ''">
            <input type="hidden" name="longitude" :value="gps ? gps.lng : ''">
            <input type="hidden" name="accuracy" :value="gps ? gps.accuracy : ''">
            <input type="hidden" name="captured_at" :value="gps ? gps.at.toISOString() : ''">
            <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
        </div>

        {{-- Photos --}}
        <div class="{{ $card }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="{{ $h2 }}">Photos</h2>
                <span class="text-xs text-slate-500 dark:text-slate-400" x-text="photos.length + ' of ' + max"></span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-emerald-300 bg-emerald-50 px-3 py-6 text-center text-sm font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20">
                    <i data-lucide="camera" class="h-6 w-6" aria-hidden="true"></i> Take a photo
                    <input type="file" accept="image/*" capture="environment" class="sr-only" @change="add($event)">
                </label>
                <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-3 py-6 text-center text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-800">
                    <i data-lucide="images" class="h-6 w-6" aria-hidden="true"></i> Choose photos
                    <input type="file" accept="image/*" multiple class="sr-only" @change="add($event)">
                </label>
            </div>
            <input type="file" name="photos[]" multiple x-ref="upload" class="hidden" aria-hidden="true" tabindex="-1">
            <template x-for="p in photos" :key="'t' + p.url">
                <input type="hidden" name="photo_times[]" :value="p.time.toISOString()">
            </template>
            <p x-show="sizeError" x-cloak x-text="sizeError" class="mt-3 text-sm text-rose-600 dark:text-rose-400"></p>
            <x-input-error :messages="$errors->get('photos')" class="mt-2" />
            <x-input-error :messages="$errors->get('photos.*')" class="mt-2" />

            <ul x-show="photos.length" x-cloak class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <template x-for="(p, i) in photos" :key="p.url">
                    <li class="relative overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                        <img :src="p.url" alt="" class="aspect-square w-full object-cover">
                        <p class="absolute inset-x-0 bottom-0 bg-black/60 px-2 py-1 font-mono text-[10px] leading-tight text-white" x-text="stamp(p)"></p>
                        <button type="button" @click="remove(i)" class="absolute right-1.5 top-1.5 rounded-full bg-black/60 p-1.5 text-white hover:bg-black/80" aria-label="Remove photo">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                        </button>
                    </li>
                </template>
            </ul>
        </div>

        <div class="{{ $card }}">
            <label for="notes" class="{{ $label }}">Note for KAMMA <span class="font-normal text-slate-400">(optional)</span></label>
            <textarea id="notes" name="notes" rows="3" maxlength="1000" class="{{ $field }}" placeholder="e.g. Foundation complete; blockwork starts next week.">{{ old('notes') }}</textarea>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <p x-show="! ready" class="text-xs text-slate-500 dark:text-slate-400">Add at least one photo and allow your location to send.</p>
            <button type="submit" :disabled="! ready" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50">
                <i data-lucide="upload" class="h-4 w-4" aria-hidden="true"></i> Send progress photos
            </button>
        </div>
    </form>

    <div class="min-w-0 space-y-6">
        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">Sent so far</h2>
            @if ($selected && ! empty($selected['progress']))
                <ol class="mt-3 space-y-3">
                    @foreach (array_reverse($selected['progress']) as $p)
                        <li class="rounded-lg border border-slate-200 px-4 py-3 dark:border-slate-800">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $p['stage'] }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $p['photos'] }} photos &middot; {{ $p['at']->format('j M Y, g:i a') }}</p>
                            <p class="mt-0.5 font-mono text-[11px] text-slate-400 dark:text-slate-500">{{ number_format($p['lat'], 5) }}, {{ number_format($p['lng'], 5) }}</p>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">No progress photos sent for this application yet.</p>
            @endif
        </div>
        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">Good photos</h2>
            <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm text-slate-600 dark:text-slate-400">
                <li>Stand on the plot when you take them, so the location is right.</li>
                <li>Show the whole building, then close-ups of the work for this stage.</li>
                <li>Take them in daylight.</li>
            </ul>
        </div>
    </div>
</div>
@endif
@endsection
