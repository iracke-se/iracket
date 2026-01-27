<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('auth.verify_email_title')"
        :description="__('auth.verify_email_description')"
    />

    <div class="text-center">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('auth.code_sent_to') }} <strong>{{ Auth::user()->email }}</strong>
        </p>
    </div>

    @if ($error)
        <div class="p-3 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400 rounded-lg text-center">
            {{ $error }}
        </div>
    @endif

    @if ($success)
        <div class="p-3 text-sm text-green-600 bg-green-50 dark:bg-green-900/20 dark:text-green-400 rounded-lg text-center"
             id="successMessage"
             x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 3000)">
            {{ $success }}
        </div>
    @endif

    <form wire:submit="verify" class="flex flex-col gap-6">
        <div class="flex flex-col items-center gap-2">
            <label for="code" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                {{ __('auth.verification_code') }}
            </label>
            <input
                type="text"
                id="code"
                wire:model="code"
                maxlength="6"
                placeholder="000000"
                class="w-48 text-center text-2xl font-mono tracking-[0.5em] px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-accent focus:border-accent outline-none"
                autofocus
                autocomplete="one-time-code"
                inputmode="numeric"
                pattern="[0-9]*"
            />
            <p class="text-xs text-zinc-500">{{ __('auth.code_expires') }}</p>
        </div>

        <flux:button type="submit" variant="primary" class="w-full">
            {{ __('auth.verify_email') }}
        </flux:button>
    </form>

    <div class="text-center">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('auth.didnt_receive_code') }}
            <button
                type="button"
                wire:click="resend"
                onclick="handleResendClick(event)"
                id="resendButton"
                class="text-accent hover:underline font-medium disabled:text-zinc-400 disabled:opacity-60 disabled:cursor-not-allowed disabled:no-underline dark:disabled:text-zinc-600"
            >
                <span id="resendText">{{ __('auth.resend_code') }}</span>
            </button>
        </p>
    </div>

    <script>
        (function() {
            // Prevent multiple initializations
            if (window.resendCodeInitialized) {
                return;
            }
            window.resendCodeInitialized = true;

            const COOLDOWN_SECONDS = 15;
            const STORAGE_KEY = 'verify_email_resend_cooldown';
            let countdownInterval = null;

        function handleResendClick(event) {
            const button = document.getElementById('resendButton');
            const textSpan = document.getElementById('resendText');

            // Prevent action if button is already disabled
            if (button.disabled) {
                if (event) event.preventDefault();
                return false;
            }

            // Set localStorage immediately when button is clicked
            localStorage.setItem(STORAGE_KEY, Date.now().toString());

            // Immediately disable button and show countdown
            const originalText = textSpan.getAttribute('data-original-text') || textSpan.textContent;
            if (!textSpan.getAttribute('data-original-text')) {
                textSpan.setAttribute('data-original-text', originalText);
            }
            disableButton(COOLDOWN_SECONDS);
        }

        function updateButtonState() {
            const button = document.getElementById('resendButton');
            const textSpan = document.getElementById('resendText');

            if (!button || !textSpan) return;

            const originalText = textSpan.getAttribute('data-original-text') || textSpan.textContent;
            if (!textSpan.getAttribute('data-original-text')) {
                textSpan.setAttribute('data-original-text', originalText);
            }

            const lastResend = localStorage.getItem(STORAGE_KEY);

            if (!lastResend) {
                enableButton();
                return;
            }

            const elapsed = Math.floor((Date.now() - parseInt(lastResend)) / 1000);
            const remaining = COOLDOWN_SECONDS - elapsed;

            if (remaining > 0) {
                disableButton(remaining);
            } else {
                enableButton();
                localStorage.removeItem(STORAGE_KEY);
            }
        }

        function disableButton(seconds) {
            const button = document.getElementById('resendButton');
            const textSpan = document.getElementById('resendText');
            const originalText = textSpan.getAttribute('data-original-text');

            button.disabled = true;
            textSpan.textContent = `${originalText} (${seconds}s)`;

            if (countdownInterval) {
                clearInterval(countdownInterval);
            }

            countdownInterval = setInterval(() => {
                const lastResend = localStorage.getItem(STORAGE_KEY);
                if (!lastResend) {
                    enableButton();
                    return;
                }

                const elapsed = Math.floor((Date.now() - parseInt(lastResend)) / 1000);
                const remaining = COOLDOWN_SECONDS - elapsed;

                if (remaining > 0) {
                    textSpan.textContent = `${originalText} (${remaining}s)`;
                } else {
                    enableButton();
                    localStorage.removeItem(STORAGE_KEY);
                }
            }, 1000);
        }

        function enableButton() {
            const button = document.getElementById('resendButton');
            const textSpan = document.getElementById('resendText');
            const originalText = textSpan.getAttribute('data-original-text');

            button.disabled = false;
            textSpan.textContent = originalText;
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }

            // Clear localStorage when countdown completes
            localStorage.removeItem(STORAGE_KEY);
        }

        // Clean up old localStorage entries on initialization
        function cleanupOldStorage() {
            const lastResend = localStorage.getItem(STORAGE_KEY);
            if (lastResend) {
                const elapsed = Math.floor((Date.now() - parseInt(lastResend)) / 1000);
                if (elapsed >= COOLDOWN_SECONDS) {
                    localStorage.removeItem(STORAGE_KEY);
                }
            }
        }

        // Check button state on page load
        document.addEventListener('DOMContentLoaded', () => {
            cleanupOldStorage();
            updateButtonState();
        });

        // Also check on Livewire load
        document.addEventListener('livewire:init', () => {
            cleanupOldStorage();
            updateButtonState();

            // Re-apply button state after Livewire updates
            Livewire.hook('morph.updated', () => {
                setTimeout(() => {
                    updateButtonState();
                }, 50);
            });

            // Re-apply after any Livewire request finishes
            Livewire.hook('request.finished', () => {
                setTimeout(() => {
                    updateButtonState();
                }, 50);
            });
        });

        // Clean up interval when page is unloaded
        window.addEventListener('beforeunload', () => {
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
        });

        // Make functions globally accessible
        window.handleResendClick = handleResendClick;
        window.updateButtonState = updateButtonState;
        })();
    </script>

    <div class="text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer">
                {{ __('auth.log_out') }}
            </flux:button>
        </form>
    </div>
</div>
