<x-guest-layout>
    <div class="space-y-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 2 of 2</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">Enter your sign-in code</h1>
        </div>

        <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            @if ($channel === 'email' && $maskedEmail)
                We emailed a {{ $codeLength }}-digit code to <span class="font-semibold">{{ $maskedEmail }}</span>.
            @elseif ($maskedPhone)
                We texted a {{ $codeLength }}-digit code to <span class="font-semibold">{{ $maskedPhone }}</span>.
            @else
                Enter the {{ $codeLength }}-digit code sent to your account.
            @endif
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
                        autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                        maxlength="1"
                        class="otp-digit h-12 rounded-lg border-slate-300 text-center text-xl font-semibold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        aria-label="Digit {{ $i + 1 }}"
                        required
                    >
                @endfor
            </div>
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Code expires in {{ $ttlMinutes }} minutes.</p>
            <x-primary-button id="otp-submit" class="mt-5 w-full justify-center py-3">Verify and sign in</x-primary-button>
        </form>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5 text-sm dark:border-slate-800">
            <form method="POST" action="{{ route('login.otp.resend') }}">
                @csrf
                <input type="hidden" name="channel" value="{{ $channel }}">
                <button type="submit" class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Resend code</button>
            </form>
            <form method="POST" action="{{ route('login.otp.cancel') }}">
                @csrf
                <button type="submit" class="font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400">Cancel and return</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const boxes = Array.from(document.querySelectorAll('.otp-digit'));
            const code = document.getElementById('otp-code');
            const form = document.getElementById('otp-form');
            const submit = () => { code.value = boxes.map((box) => box.value).join(''); };
            boxes.forEach((box, index) => {
                box.addEventListener('input', () => {
                    box.value = box.value.replace(/[^0-9]/g, '').slice(-1);
                    submit();
                    if (box.value && boxes[index + 1]) boxes[index + 1].focus();
                });
                box.addEventListener('keydown', (event) => {
                    if (event.key === 'Backspace' && !box.value && boxes[index - 1]) boxes[index - 1].focus();
                });
                box.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const digits = (event.clipboardData.getData('text') || '').replace(/[^0-9]/g, '').slice(0, boxes.length);
                    digits.split('').forEach((digit, digitIndex) => { boxes[digitIndex].value = digit; });
                    submit();
                    boxes[Math.min(digits.length, boxes.length) - 1]?.focus();
                });
            });
            form.addEventListener('submit', submit);
        });
    </script>
</x-guest-layout>
