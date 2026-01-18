<x-layout.auth title="Confirm Password">
    <div class="text-center mb-6">
        <div class="mb-4">
            <div class="w-16 h-16 mx-auto bg-warning/20 rounded-full flex items-center justify-center">
                <x-heroicon-o-shield-exclamation class="size-8 text-warning" />
            </div>
        </div>
        <h2 class="text-2xl font-bold text-base-content">{{ __('Confirm password') }}</h2>
        <p class="text-base-content/60 mt-2">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4">
        @csrf

        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Password') }}</span>
            </label>
            <input
                type="password"
                name="password"
                class="input input-bordered @error('password') input-error @enderror"
                placeholder="{{ __('Password') }}"
                required
                autocomplete="current-password"
                autofocus
            />
            @error('password')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full" data-test="confirm-password-button">
            {{ __('Confirm') }}
        </button>
    </form>
</x-layout.auth>
