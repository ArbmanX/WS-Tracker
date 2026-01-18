<div class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="space-y-4">
            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ __('Name') }}</span>
                </label>
                <input
                    type="text"
                    wire:model="name"
                    class="input input-bordered @error('name') input-error @enderror"
                    required
                    autofocus
                    autocomplete="name"
                />
                @error('name')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ __('Email') }}</span>
                </label>
                <input
                    type="email"
                    wire:model="email"
                    class="input input-bordered @error('email') input-error @enderror"
                    required
                    autocomplete="email"
                />
                @error('email')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                    <div class="mt-2">
                        <p class="text-sm text-warning">
                            {{ __('Your email address is unverified.') }}
                            <button
                                type="button"
                                wire:click.prevent="resendVerificationNotification"
                                class="link link-primary"
                            >
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <div role="alert" class="alert alert-success mt-2">
                                <x-heroicon-o-check-circle class="size-5" />
                                <span>{{ __('A new verification link has been sent to your email address.') }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit" class="btn btn-primary">
                    {{ __('Save') }}
                </button>

                <x-action-message class="text-success" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</div>
