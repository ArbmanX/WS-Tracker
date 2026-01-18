<x-layout.auth title="Forgot Password">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-base-content">{{ __('Forgot password') }}</h2>
        <p class="text-base-content/60 mt-2">{{ __('Enter your email to receive a password reset link') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Email Address') }}</span>
            </label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="input input-bordered @error('email') input-error @enderror"
                placeholder="email@example.com"
                required
                autofocus
            />
            @error('email')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full" data-test="email-password-reset-link-button">
            {{ __('Email password reset link') }}
        </button>
    </form>

    <div class="text-center mt-6">
        <span class="text-base-content/50 text-sm">{{ __('Or, return to') }}</span>
        <a href="{{ route('login') }}" class="link link-primary text-sm" wire:navigate>{{ __('log in') }}</a>
    </div>
</x-layout.auth>
