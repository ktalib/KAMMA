{{-- System Admin > Bulk SMS Management, copied from KLAES sms-management (one page per sender ID: the messages this sender
     sends, each with its on/off switch and wording, plus a test send). This app sends under its own sender ID. Only the
     sign-in code is live (LoginOtpService); the other messages are listed for the backend phase and post to the preview stub. --}}
<x-app-layout title="Bulk SMS Management">
    @php
        $sender = config('services.bulk_sms_ng.sender');
        $ready = filled(config('services.bulk_sms_ng.email')) && filled(config('services.bulk_sms_ng.password'));
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $messages = config('app.name') === 'KAMMA'
            ? [
                ['Staff sign-in code', 'Your :app sign-in code is 123456. It expires in 10 minutes. Do not share it.', true],
                ['Shadow file commissioned', 'Your file :file has been received by KLAES-METRO for planning recommendation.', false],
                ['Bill generated', 'A bill of N:amount has been raised on file :file. Pay at any designated bank.', false],
                ['Site visit scheduled', 'A KLAES-METRO officer will visit plot :plot on :date.', false],
                ['File returned to the Ministry', 'File :file has been returned to the Ministry with the planning recommendation.', false],
            ]
            : [
                ['Staff sign-in code', 'Your :app sign-in code is 123456. It expires in 10 minutes. Do not share it.', true],
                ['BPA application received', 'Your building plan application :app_no has been received. Bill: N:amount.', false],
                ['BP file commissioned', 'Payment confirmed. Your BP file number is :bp_no.', false],
                ['Site inspection scheduled', 'A KLAES-CONTROL officer will inspect plot :plot on :date.', false],
                ['Plan approved', 'The building plan on :bp_no is ready for collection.', false],
            ];
    @endphp

    <div class="space-y-6" x-data="{ test: false }">
        <x-ui.page-head icon="message-square-text" title="Bulk SMS Management" :subtitle="'Messages sent under the '.($sender ?: 'unset').' sender ID'" tone="slate" section="System Admin">
            <button type="button" @click.stop="test = true" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"><i data-lucide="send" class="h-4 w-4"></i> Test send</button>
        </x-ui.page-head>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.stat title="Sender ID" :value="$sender ?: 'Not set'" icon="badge-check" tone="indigo" />
            <x-ui.stat title="Gateway" :value="$ready ? 'Configured' : 'Not configured'" hint="Bulk-SMS.ng (BULK_SMS_NG_* in .env)" icon="radio-tower" :tone="$ready ? 'emerald' : 'rose'" />
            <x-ui.stat title="Applicant numbers" :value="number_format($applicants)" hint="Distinct phones on file" icon="smartphone" tone="amber" />
        </div>

        <x-ui.panel title="Messages" :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr><th class="px-4 py-3">S/N</th><th class="px-4 py-3">Message</th><th class="px-4 py-3">Wording</th><th class="px-4 py-3">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($messages as $i => [$name, $wording, $live])
                            <tr>
                                <td class="px-4 py-3 text-slate-500">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200">{{ $name }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ str_replace(':app', config('app.name') === 'KAMMA' ? 'KLAES-METRO' : 'KLAES-CONTROL', $wording) }}</td>
                                <td class="px-4 py-3">
                                    @if ($live)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">On</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">Off (backend phase)</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.panel>

        <div x-show="test" x-cloak class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-10" @keydown.escape.window="test = false">
            <form method="POST" action="{{ route('ui.preview', 'sms-test') }}" @click.outside="test = false" class="w-full max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                @csrf
                <div class="flex items-start justify-between"><h2 class="text-lg font-bold text-slate-900 dark:text-white">Test send</h2><button type="button" @click="test = false" class="text-xl leading-none text-slate-400" aria-label="Close">&times;</button></div>
                <x-ui.preview-note what="A test send" />
                <div><label for="sms-phone" class="{{ $label }}">Phone number <span class="text-rose-500">*</span></label><input id="sms-phone" name="phone" required inputmode="tel" placeholder="080..." class="{{ $field }}"></div>
                <div><label for="sms-text" class="{{ $label }}">Message <span class="text-rose-500">*</span></label><textarea id="sms-text" name="message" rows="3" required maxlength="320" class="{{ $field }}"></textarea></div>
                <div class="flex justify-end gap-3"><button type="button" @click="test = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-700 dark:text-slate-300">Cancel</button><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Send</button></div>
            </form>
        </div>
    </div>
</x-app-layout>
