<div class="mt-10 space-y-6" x-data="{ showDeleteModal: false }">
    <div class="divider"></div>

    <div>
        <h3 class="text-lg font-semibold text-base-content">{{ __('Delete account') }}</h3>
        <p class="text-sm text-base-content/60 mt-1">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <button
        type="button"
        class="btn btn-error btn-outline"
        @click="showDeleteModal = true"
    >
        {{ __('Delete account') }}
    </button>

    {{-- Delete Confirmation Modal --}}
    <dialog
        id="confirm-user-deletion"
        class="modal"
        x-bind:open="showDeleteModal"
        x-effect="showDeleteModal ? $el.showModal() : $el.close()"
        @close="showDeleteModal = false"
    >
        <div class="modal-box max-w-md">
            <form wire:submit="deleteUser" class="space-y-6">
                <div>
                    <h3 class="text-lg font-bold">{{ __('Are you sure you want to delete your account?') }}</h3>
                    <p class="text-sm text-base-content/60 mt-2">
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                    </p>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text">{{ __('Password') }}</span>
                    </label>
                    <input
                        type="password"
                        wire:model="password"
                        class="input input-bordered @error('password') input-error @enderror"
                        placeholder="{{ __('Enter your password') }}"
                    />
                    @error('password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="modal-action">
                    <button
                        type="button"
                        class="btn"
                        @click="showDeleteModal = false; $el.closest('dialog').close()"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-error">
                        {{ __('Delete account') }}
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click="showDeleteModal = false">close</button>
        </form>
    </dialog>
</div>
