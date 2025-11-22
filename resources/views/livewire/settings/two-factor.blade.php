<div class="max-w-2xl mx-auto">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Two Factor Authentication')"
        :subheading="__('Manage your two-factor authentication settings')"
    >
        <div class="space-y-4" wire:cloak>
            @if ($twoFactorEnabled)
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500/20 text-green-400">
                            {{ __('Enabled') }}
                        </span>
                    </div>

                    <p class="text-sm text-zinc-400">
                        {{ __('With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                    </p>

                    <livewire:settings.two-factor.recovery-codes :$requiresConfirmation/>

                    <div class="flex justify-start">
                        <button
                            wire:click="disable"
                            class="flex items-center gap-2 px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01"/>
                            </svg>
                            {{ __('Disable 2FA') }}
                        </button>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-500/20 text-red-400">
                            {{ __('Disabled') }}
                        </span>
                    </div>

                    <p class="text-sm text-zinc-400">
                        {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                    </p>

                    <button
                        wire:click="enable"
                        class="flex items-center gap-2 px-6 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        {{ __('Enable 2FA') }}
                    </button>
                </div>
            @endif
        </div>
    </x-settings.layout>

    <flux:modal
        name="two-factor-setup-modal"
        class="max-w-md md:min-w-md"
        @close="closeModal"
        wire:model="showModal"
    >
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="p-0.5 w-auto rounded-full border border-zinc-600 bg-zinc-800 shadow-sm">
                    <div class="p-2.5 rounded-full border border-zinc-600 overflow-hidden bg-zinc-200 relative">
                        <div class="flex items-stretch absolute inset-0 w-full h-full divide-x [&>div]:flex-1 divide-zinc-300 justify-around opacity-50">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <div class="flex flex-col items-stretch absolute w-full h-full divide-y [&>div]:flex-1 inset-0 divide-zinc-300 justify-around opacity-50">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <flux:icon.qr-code class="relative z-20 text-zinc-800"/>
                    </div>
                </div>

                <div class="space-y-2 text-center">
                    <h3 class="text-lg font-semibold text-white">{{ $this->modalConfig['title'] }}</h3>
                    <p class="text-sm text-zinc-400">{{ $this->modalConfig['description'] }}</p>
                </div>
            </div>

            @if ($showVerificationStep)
                <div class="space-y-6">
                    <div class="flex flex-col items-center space-y-3">
                        <x-input-otp
                            :digits="6"
                            name="code"
                            wire:model="code"
                            autocomplete="one-time-code"
                        />
                        @error('code')
                            <p class="text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            wire:click="resetVerification"
                            class="flex-1 px-4 py-3 bg-zinc-700 text-zinc-300 rounded-lg hover:bg-zinc-600 transition-colors"
                        >
                            {{ __('Back') }}
                        </button>

                        <button
                            wire:click="confirmTwoFactor"
                            x-bind:disabled="$wire.code.length < 6"
                            class="flex-1 px-4 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors disabled:opacity-50"
                        >
                            {{ __('Confirm') }}
                        </button>
                    </div>
                </div>
            @else
                @error('setupData')
                    <div class="p-4 bg-red-500/20 border border-red-500/30 rounded-lg text-red-400 text-sm">
                        {{ $message }}
                    </div>
                @enderror

                <div class="flex justify-center">
                    <div class="relative w-64 overflow-hidden border rounded-lg border-zinc-700 aspect-square">
                        @empty($qrCodeSvg)
                            <div class="absolute inset-0 flex items-center justify-center bg-zinc-700 animate-pulse">
                                <flux:icon.loading/>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-full p-4">
                                <div class="bg-white p-3 rounded">
                                    {!! $qrCodeSvg !!}
                                </div>
                            </div>
                        @endempty
                    </div>
                </div>

                <button
                    wire:click="showVerificationIfNecessary"
                    @if($errors->has('setupData')) disabled @endif
                    class="w-full px-4 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors disabled:opacity-50"
                >
                    {{ $this->modalConfig['buttonText'] }}
                </button>

                <div class="space-y-4">
                    <div class="relative flex items-center justify-center w-full">
                        <div class="absolute inset-0 w-full h-px top-1/2 bg-zinc-600"></div>
                        <span class="relative px-2 text-sm bg-zinc-800 text-zinc-400">
                            {{ __('or, enter the code manually') }}
                        </span>
                    </div>

                    <div
                        class="flex items-center"
                        x-data="{
                            copied: false,
                            async copy() {
                                try {
                                    await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                } catch (e) {
                                    console.warn('Could not copy to clipboard');
                                }
                            }
                        }"
                    >
                        <div class="flex items-stretch w-full border rounded-xl border-zinc-700">
                            @empty($manualSetupKey)
                                <div class="flex items-center justify-center w-full p-3 bg-zinc-700">
                                    <flux:icon.loading variant="mini"/>
                                </div>
                            @else
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $manualSetupKey }}"
                                    class="w-full p-3 bg-transparent outline-none text-white"
                                />

                                <button
                                    @click="copy()"
                                    class="px-3 transition-colors border-l cursor-pointer border-zinc-600 text-zinc-400 hover:text-white"
                                >
                                    <flux:icon.document-duplicate x-show="!copied" variant="outline"/>
                                    <flux:icon.check
                                        x-show="copied"
                                        variant="solid"
                                        class="text-green-500"
                                    />
                                </button>
                            @endempty
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
