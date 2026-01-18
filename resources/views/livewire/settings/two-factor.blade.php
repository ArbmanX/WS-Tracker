<div class="w-full" wire:cloak>
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Two-Factor Authentication')"
        :subheading="__('Manage your two-factor authentication settings')"
    >
        <div class="space-y-6">
            @if ($twoFactorEnabled)
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="badge badge-success gap-2">
                            <x-heroicon-o-check-circle class="size-4" />
                            {{ __('Enabled') }}
                        </span>
                    </div>

                    <p class="text-sm text-base-content/70">
                        {{ __('With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                    </p>

                    <livewire:settings.two-factor.recovery-codes :$requiresConfirmation />

                    <button
                        type="button"
                        wire:click="disable"
                        class="btn btn-error btn-outline"
                    >
                        <x-heroicon-o-shield-exclamation class="size-5" />
                        {{ __('Disable 2FA') }}
                    </button>
                </div>
            @else
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="badge badge-error gap-2">
                            <x-heroicon-o-x-circle class="size-4" />
                            {{ __('Disabled') }}
                        </span>
                    </div>

                    <p class="text-sm text-base-content/60">
                        {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                    </p>

                    <button
                        type="button"
                        wire:click="enable"
                        class="btn btn-primary"
                    >
                        <x-heroicon-o-shield-check class="size-5" />
                        {{ __('Enable 2FA') }}
                    </button>
                </div>
            @endif
        </div>
    </x-settings.layout>

    {{-- Two-Factor Setup Modal --}}
    <dialog
        id="two-factor-setup-modal"
        class="modal"
        x-data="{ open: $wire.entangle('showModal') }"
        x-bind:open="open"
        x-effect="open ? $el.showModal() : $el.close()"
        @close="$wire.closeModal()"
    >
        <div class="modal-box max-w-md">
            <div class="space-y-6">
                {{-- Header with QR Icon --}}
                <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-primary/20 rounded-full flex items-center justify-center">
                        <x-heroicon-o-qr-code class="size-8 text-primary" />
                    </div>

                    <div class="text-center">
                        <h3 class="text-lg font-bold">{{ $this->modalConfig['title'] }}</h3>
                        <p class="text-sm text-base-content/60 mt-1">{{ $this->modalConfig['description'] }}</p>
                    </div>
                </div>

                @if ($showVerificationStep)
                    <div class="space-y-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">{{ __('Authentication Code') }}</span>
                            </label>
                            <input
                                type="text"
                                wire:model="code"
                                class="input input-bordered text-center tracking-[0.5em] text-lg font-mono @error('code') input-error @enderror"
                                placeholder="000000"
                                maxlength="6"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                autofocus
                            />
                            @error('code')
                                <label class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>

                        <div class="flex gap-3">
                            <button type="button" wire:click="resetVerification" class="btn btn-ghost flex-1">
                                {{ __('Back') }}
                            </button>
                            <button
                                type="button"
                                wire:click="confirmTwoFactor"
                                class="btn btn-primary flex-1"
                                x-bind:disabled="$wire.code.length < 6"
                            >
                                {{ __('Confirm') }}
                            </button>
                        </div>
                    </div>
                @else
                    @error('setupData')
                        <div role="alert" class="alert alert-error">
                            <x-heroicon-o-x-circle class="size-5" />
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    {{-- QR Code --}}
                    <div class="flex justify-center">
                        <div class="p-4 bg-white rounded-lg">
                            @empty($qrCodeSvg)
                                <div class="w-48 h-48 flex items-center justify-center animate-pulse bg-base-200 rounded">
                                    <span class="loading loading-spinner loading-lg"></span>
                                </div>
                            @else
                                <div class="w-48 h-48">
                                    {!! $qrCodeSvg !!}
                                </div>
                            @endempty
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn btn-primary w-full"
                        wire:click="showVerificationIfNecessary"
                        @disabled($errors->has('setupData'))
                    >
                        {{ $this->modalConfig['buttonText'] }}
                    </button>

                    {{-- Manual Setup Key --}}
                    <div class="space-y-3">
                        <div class="divider text-xs text-base-content/50">{{ __('or, enter the code manually') }}</div>

                        <div
                            class="join w-full"
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
                            @empty($manualSetupKey)
                                <div class="join-item flex-1 input input-bordered flex items-center justify-center">
                                    <span class="loading loading-spinner loading-sm"></span>
                                </div>
                            @else
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $manualSetupKey }}"
                                    class="join-item input input-bordered flex-1 font-mono text-sm"
                                />
                                <button
                                    type="button"
                                    @click="copy()"
                                    class="btn join-item"
                                >
                                    <template x-if="!copied">
                                        <x-heroicon-o-document-duplicate class="size-5" />
                                    </template>
                                    <template x-if="copied">
                                        <x-heroicon-o-check class="size-5 text-success" />
                                    </template>
                                </button>
                            @endempty
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click="$wire.closeModal()">close</button>
        </form>
    </dialog>
</div>
