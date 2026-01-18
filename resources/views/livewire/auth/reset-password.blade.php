<x-layout.auth title="Reset Password">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-base-content">{{ __('Reset password') }}</h2>
        <p class="text-base-content/60 mt-2">{{ __('Please enter your new password below') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf

        <!-- Token -->
        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <!-- Email Address -->
        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Email') }}</span>
            </label>
            <input
                type="email"
                name="email"
                value="{{ request('email') }}"
                class="input input-bordered @error('email') input-error @enderror"
                required
                autocomplete="email"
            />
            @error('email')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <!-- Password -->
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
                autocomplete="new-password"
            />
            @error('password')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Confirm password') }}</span>
            </label>
            <input
                type="password"
                name="password_confirmation"
                class="input input-bordered"
                placeholder="{{ __('Confirm password') }}"
                required
                autocomplete="new-password"
            />
        </div>

        <button type="submit" class="btn btn-primary w-full" data-test="reset-password-button">
            {{ __('Reset password') }}
        </button>
    </form>
</x-layout.auth>
