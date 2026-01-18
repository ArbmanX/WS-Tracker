<x-layout.auth title="Log In">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-base-content">{{ __('Log in to your account') }}</h2>
        <p class="text-base-content/60 mt-2">{{ __('Enter your email and password below to log in') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Email address') }}</span>
            </label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="input input-bordered @error('email') input-error @enderror"
                placeholder="email@example.com"
                required
                autofocus
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
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="label-text-alt link link-primary" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </label>
            <input
                type="password"
                name="password"
                class="input input-bordered @error('password') input-error @enderror"
                placeholder="{{ __('Password') }}"
                required
                autocomplete="current-password"
            />
            @error('password')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="form-control">
            <label class="label cursor-pointer justify-start gap-3">
                <input type="checkbox" name="remember" class="checkbox checkbox-primary checkbox-sm" {{ old('remember') ? 'checked' : '' }} />
                <span class="label-text">{{ __('Remember me') }}</span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary w-full" data-test="login-button">
            {{ __('Log in') }}
        </button>
    </form>
</x-layout.auth>
