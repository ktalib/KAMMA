<x-guest-layout>
    <div class="space-y-6">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 2 of 2</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">Enter your sign-in code</h1>
            </div>
        </div>

        <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            {{ $channel === 'sms' ? 'We texted' : 'We emailed' }} a {{ $codeLength }}-digit code to <span class="font-semibold">{{ $where }}</span>.
        </p>

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('login.otp.verify') }}" id="otp-form">
            @csrf
            <input type="hidden" name="code" id="otp-code">
            <div id="otp-boxes" class="grid grid-cols-6 gap-2">
                @for ($i = 0; $i < $codeLength; $i++)
                    <input
                        id="otp-{{ $i }}"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                        class="otp-digit h-12 rounded-lg border-slate-300 text-center text-xl font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        aria-label="Digit {{ $i + 1 }}"
                        required
                    >
                @endfor
            </div>
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Code expires in {{ $ttlMinutes }} minutes.</p>
            <x-primary-button id="otp-submit" class="mt-5 w-full justify-center py-3">Verify and sign in</x-primary-button>
        </form>

        <div class="space-y-3 border-t border-slate-100 pt-5 text-sm dark:border-slate-800">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form method="POST" action="{{ route('login.otp.resend') }}">
                    @csrf
                    <input type="hidden" name="channel" value="{{ $channel }}">
                    <button type="submit" id="otp-resend" data-wait="{{ $cooldown }}" class="font-semibold text-indigo-600 hover:text-indigo-700 disabled:cursor-not-allowed disabled:text-slate-400 dark:text-indigo-400">Resend code</button>
                </form>
                @if ($other)
                    <form method="POST" action="{{ route('login.otp.resend') }}">
                        @csrf
                        <input type="hidden" name="channel" value="{{ $other }}">
                        <button type="submit" class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">{{ $other === 'email' ? 'Email it to me instead' : 'Text it to me instead' }}</button>
                    </form>
                @endif
            </div>
            @if ($other)
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $other === 'email' ? 'Email' : 'Text' }} goes to {{ $otherWhere }}.</p>
            @endif
            <form method="POST" action="{{ route('login.otp.cancel') }}">
                @csrf
                <button type="submit" class="font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400">Cancel and return to sign in</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const boxes = Array.from(document.querySelectorAll('.otp-digit'));
            const code = document.getElementById('otp-code');
            const form = document.getElementById('otp-form');
            const button = document.getElementById('otp-submit');
            let sent = false;
            const submit = () => {
                code.value = boxes.map((box) => box.value).join('');
                // Every box filled (typed, pasted or SMS autofill): sign in straight away, no click on the button
                // (user 2026-11-25). Once only, so a fast extra key can't post the form twice.
                if (!sent && code.value.length === boxes.length) {
                    sent = true;
                    button.disabled = true;
                    button.textContent = 'Signing in…';
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                }
            };
            // Spread digits over the boxes from `start` (typing, SMS autofill or a pasted code) and focus the next empty one.
            // No maxlength on the boxes: it made a filled box ignore a corrected digit and cut an autofilled code to its
            // first digit, which is how many phone keyboards deliver ordinary typing too.
            const fill = (start, text) => {
                const digits = String(text).replace(/[^0-9]/g, '').split('');
                if (!digits.length) {
                    boxes[start].value = '';
                    submit();
                    return;
                }
                if (digits.length >= boxes.length) start = 0; // a whole code always starts at the first box
                digits.slice(0, boxes.length - start).forEach((digit, i) => { boxes[start + i].value = digit; });
                submit();
                const next = boxes.find((box, i) => i >= start && !box.value) ?? boxes[Math.min(start + digits.length, boxes.length - 1)];
                next.focus();
                next.select();
            };
            boxes.forEach((box, index) => {
                // Selecting on focus means the next key replaces the digit already there.
                box.addEventListener('focus', () => box.select());
                box.addEventListener('input', () => {
                    const value = box.value.replace(/[^0-9]/g, '');
                    // One new key in a box that held a digit: keep the key just typed.
                    fill(index, value.length === 2 && box.selectionStart === 1 ? value[0] : value.length === 2 ? value[1] : value);
                });
                box.addEventListener('keydown', (event) => {
                    if (event.key === 'Backspace' && !box.value && boxes[index - 1]) {
                        event.preventDefault();
                        boxes[index - 1].value = '';
                        boxes[index - 1].focus();
                        submit();
                    } else if (event.key === 'ArrowLeft' && boxes[index - 1]) {
                        event.preventDefault();
                        boxes[index - 1].focus();
                    } else if (event.key === 'ArrowRight' && boxes[index + 1]) {
                        event.preventDefault();
                        boxes[index + 1].focus();
                    }
                });
                box.addEventListener('paste', (event) => {
                    event.preventDefault();
                    fill(index, event.clipboardData.getData('text') || '');
                });
            });
            form.addEventListener('submit', () => {
                code.value = boxes.map((box) => box.value).join('');
                sent = true;
            });

            // Resend countdown: the button opens when the cooldown for this route has run out.
            const resend = document.getElementById('otp-resend');
            let wait = Number(resend?.dataset.wait || 0);
            const tick = () => {
                if (!resend) return;
                resend.disabled = wait > 0;
                resend.textContent = wait > 0 ? `Resend code (${wait}s)` : 'Resend code';
                if (wait-- > 0) setTimeout(tick, 1000);
            };
            tick();
        });
    </script>
</x-guest-layout>
