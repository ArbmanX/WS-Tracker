<x-layout.auth title="Verify Email">
    <div class="text-center mb-6">
        <div class="mb-4">
            <div class="w-16 h-16 mx-auto bg-info/20 rounded-full flex items-center justify-center">
                <x-heroicon-o-envelope class="size-8 text-info" />
            </div>
        </div>
        <h2 class="text-2xl font-bold text-base-content">{{ __('Verify Your Email') }}</h2>
        <p class="text-base-content/60 mt-2">
            {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div role="alert" class="alert alert-success mb-4">
            <x-heroicon-o-check-circle class="size-5" />
            <span>{{ __('A new verification link has been sent to the email address you provided during registration.') }}</span>
        </div>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary w-full">
                {{ __('Resend verification email') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-ghost w-full" data-test="logout-button">
                {{ __('Log out') }}
            </button>
        </form>
    </div>
</x-layout.auth>
