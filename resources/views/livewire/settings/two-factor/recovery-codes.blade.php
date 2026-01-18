<div
    class="card bg-base-200 shadow-sm"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div class="card-body space-y-4">
        {{-- Header --}}
        <div class="flex items-center gap-2">
            <x-heroicon-o-lock-closed class="size-5 text-base-content/70" />
            <h3 class="card-title text-base">{{ __('2FA Recovery Codes') }}</h3>
        </div>
        <p class="text-sm text-base-content/60">
            {{ __('Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.') }}
        </p>

        {{-- Action Buttons --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <button
                x-show="!showRecoveryCodes"
                type="button"
                class="btn btn-primary btn-sm"
                @click="showRecoveryCodes = true;"
                aria-expanded="false"
                aria-controls="recovery-codes-section"
            >
                <x-heroicon-o-eye class="size-4" />
                {{ __('View Recovery Codes') }}
            </button>

            <button
                x-show="showRecoveryCodes"
                type="button"
                class="btn btn-primary btn-sm"
                @click="showRecoveryCodes = false"
                aria-expanded="true"
                aria-controls="recovery-codes-section"
            >
                <x-heroicon-o-eye-slash class="size-4" />
                {{ __('Hide Recovery Codes') }}
            </button>

            @if (filled($recoveryCodes))
                <button
                    x-show="showRecoveryCodes"
                    type="button"
                    class="btn btn-ghost btn-sm"
                    wire:click="regenerateRecoveryCodes"
                >
                    <x-heroicon-o-arrow-path class="size-4" />
                    {{ __('Regenerate Codes') }}
                </button>
            @endif
        </div>

        {{-- Recovery Codes Display --}}
        <div
            x-show="showRecoveryCodes"
            x-transition
            id="recovery-codes-section"
            class="relative overflow-hidden"
            x-bind:aria-hidden="!showRecoveryCodes"
        >
            <div class="space-y-3">
                @error('recoveryCodes')
                    <div role="alert" class="alert alert-error">
                        <x-heroicon-o-x-circle class="size-5" />
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                @if (filled($recoveryCodes))
                    <div
                        class="grid gap-1 p-4 font-mono text-sm rounded-lg bg-base-300"
                        role="list"
                        aria-label="{{ __('Recovery codes') }}"
                    >
                        @foreach($recoveryCodes as $code)
                            <div
                                role="listitem"
                                class="select-text"
                                wire:loading.class="opacity-50 animate-pulse"
                            >
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-base-content/50">
                        {{ __('Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate Codes above.') }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
