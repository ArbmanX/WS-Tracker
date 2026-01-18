<x-layout.auth title="Two-Factor Authentication">
    <div
        x-cloak
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            code: '',
            recovery_code: '',
            toggleInput() {
                this.showRecoveryInput = !this.showRecoveryInput;
                this.code = '';
                this.recovery_code = '';
                $nextTick(() => {
                    if (this.showRecoveryInput) {
                        this.$refs.recovery_code?.focus();
                    } else {
                        this.$refs.code?.focus();
                    }
                });
            },
        }"
    >
        {{-- Header for Code Input --}}
        <div x-show="!showRecoveryInput" class="text-center mb-6">
            <h2 class="text-2xl font-bold text-base-content">{{ __('Authentication Code') }}</h2>
            <p class="text-base-content/60 mt-2">{{ __('Enter the authentication code provided by your authenticator application.') }}</p>
        </div>

        {{-- Header for Recovery Input --}}
        <div x-show="showRecoveryInput" class="text-center mb-6">
            <h2 class="text-2xl font-bold text-base-content">{{ __('Recovery Code') }}</h2>
            <p class="text-base-content/60 mt-2">{{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}</p>
        </div>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-4">
            @csrf

            {{-- OTP Code Input --}}
            <div x-show="!showRecoveryInput" class="form-control">
                <label class="label">
                    <span class="label-text">{{ __('Authentication Code') }}</span>
                </label>
                <input
                    type="text"
                    name="code"
                    x-ref="code"
                    x-model="code"
                    class="input input-bordered text-center tracking-[0.5em] text-lg font-mono @error('code') input-error @enderror"
                    placeholder="000000"
                    maxlength="6"
                    autocomplete="one-time-code"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    x-bind:required="!showRecoveryInput"
                    autofocus
                />
                @error('code')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            {{-- Recovery Code Input --}}
            <div x-show="showRecoveryInput" class="form-control">
                <label class="label">
                    <span class="label-text">{{ __('Recovery Code') }}</span>
                </label>
                <input
                    type="text"
                    name="recovery_code"
                    x-ref="recovery_code"
                    x-model="recovery_code"
                    class="input input-bordered font-mono @error('recovery_code') input-error @enderror"
                    placeholder="XXXXX-XXXXX"
                    autocomplete="one-time-code"
                    x-bind:required="showRecoveryInput"
                />
                @error('recovery_code')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full">
                {{ __('Continue') }}
            </button>
        </form>

        <div class="text-center mt-6">
            <span class="text-base-content/50 text-sm">{{ __('or you can') }}</span>
            <button
                type="button"
                class="link link-primary text-sm"
                @click="toggleInput()"
            >
                <span x-show="!showRecoveryInput">{{ __('login using a recovery code') }}</span>
                <span x-show="showRecoveryInput">{{ __('login using an authentication code') }}</span>
            </button>
        </div>
    </div>
</x-layout.auth>
