<div class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form wire:submit="updatePassword" class="space-y-4">
            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ __('Current password') }}</span>
                </label>
                <input
                    type="password"
                    wire:model="current_password"
                    class="input input-bordered @error('current_password') input-error @enderror"
                    required
                    autocomplete="current-password"
                />
                @error('current_password')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ __('New password') }}</span>
                </label>
                <input
                    type="password"
                    wire:model="password"
                    class="input input-bordered @error('password') input-error @enderror"
                    required
                    autocomplete="new-password"
                />
                @error('password')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ __('Confirm password') }}</span>
                </label>
                <input
                    type="password"
                    wire:model="password_confirmation"
                    class="input input-bordered"
                    required
                    autocomplete="new-password"
                />
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit" class="btn btn-primary">
                    {{ __('Save') }}
                </button>

                <x-action-message class="text-success" on="password-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</div>
