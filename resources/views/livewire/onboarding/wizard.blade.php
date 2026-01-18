<div>
    {{-- Step 1: Verify Email & Temporary Password --}}
    @if($step === 1)
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-base-content">Welcome to {{ config('app.name') }}</h2>
            <p class="text-base-content/60 mt-2">Please verify your credentials to continue</p>
        </div>

        <form wire:submit="verifyCredentials" class="space-y-4">
            <div class="form-control">
                <label class="label">
                    <span class="label-text">Email Address</span>
                </label>
                <input type="email" value="{{ $email }}" class="input input-bordered bg-base-200" disabled />
                <label class="label">
                    <span class="label-text-alt text-base-content/50">This was set by your administrator</span>
                </label>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Temporary Password</span>
                </label>
                <input
                    type="password"
                    wire:model="temporaryPassword"
                    class="input input-bordered @error('temporaryPassword') input-error @enderror"
                    placeholder="Enter your temporary password"
                    autofocus
                />
                @error('temporaryPassword')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full">
                Continue
                <x-heroicon-o-arrow-right class="size-5" />
            </button>
        </form>
    @endif

    {{-- Step 2: Set Password & Name --}}
    @if($step === 2)
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-base-content">Set Your Password</h2>
            <p class="text-base-content/60 mt-2">Create a secure password for your account</p>
        </div>

        <form wire:submit="setPassword" class="space-y-4">
            <div class="form-control">
                <label class="label">
                    <span class="label-text">Your Name</span>
                </label>
                <input
                    type="text"
                    wire:model="name"
                    class="input input-bordered @error('name') input-error @enderror"
                />
                @error('name')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">New Password</span>
                </label>
                <input
                    type="password"
                    wire:model="newPassword"
                    class="input input-bordered @error('newPassword') input-error @enderror"
                    placeholder="Minimum 8 characters"
                />
                @error('newPassword')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Confirm Password</span>
                </label>
                <input
                    type="password"
                    wire:model="newPassword_confirmation"
                    class="input input-bordered"
                    placeholder="Confirm your password"
                />
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" wire:click="previousStep" class="btn btn-ghost flex-1">
                    <x-heroicon-o-arrow-left class="size-5" />
                    Back
                </button>
                <button type="submit" class="btn btn-primary flex-1">
                    Continue
                    <x-heroicon-o-arrow-right class="size-5" />
                </button>
            </div>
        </form>
    @endif

    {{-- Step 3: Theme Selection --}}
    @if($step === 3)
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-base-content">Choose Your Theme</h2>
            <p class="text-base-content/60 mt-2">Select how you want the app to look</p>
        </div>

        <form wire:submit="saveTheme" class="space-y-4">
            <x-ui.theme-picker :selected="$selectedTheme" />

            <div class="flex gap-3 pt-4">
                <button type="button" wire:click="previousStep" class="btn btn-ghost flex-1">
                    <x-heroicon-o-arrow-left class="size-5" />
                    Back
                </button>
                <button type="submit" class="btn btn-primary flex-1">
                    Continue
                    <x-heroicon-o-arrow-right class="size-5" />
                </button>
            </div>
        </form>
    @endif

    {{-- Step 4: Dashboard Preferences --}}
    @if($step === 4)
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-base-content">Dashboard Preferences</h2>
            <p class="text-base-content/60 mt-2">Customize your default dashboard view</p>
        </div>

        <form wire:submit="savePreferences" class="space-y-6">
            {{-- Default View --}}
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-medium">Default View</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex flex-col items-center gap-2 p-4 rounded-lg border-2 cursor-pointer transition-all
                        {{ $defaultView === 'cards' ? 'border-primary bg-primary/10' : 'border-base-300 hover:border-base-content/20' }}">
                        <x-heroicon-o-squares-2x2 class="size-8" />
                        <input type="radio" wire:model.live="defaultView" value="cards" class="radio radio-primary" />
                        <span class="text-sm">Cards</span>
                    </label>
                    <label class="flex flex-col items-center gap-2 p-4 rounded-lg border-2 cursor-pointer transition-all
                        {{ $defaultView === 'table' ? 'border-primary bg-primary/10' : 'border-base-300 hover:border-base-content/20' }}">
                        <x-heroicon-o-table-cells class="size-8" />
                        <input type="radio" wire:model.live="defaultView" value="table" class="radio radio-primary" />
                        <span class="text-sm">Table</span>
                    </label>
                </div>
            </div>

            {{-- Region Preference --}}
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-medium">Region Display</span>
                </label>
                <label class="label cursor-pointer justify-start gap-3 bg-base-200 rounded-lg px-4">
                    <input type="checkbox" wire:model.live="showAllRegions" class="toggle toggle-primary" />
                    <span class="label-text">Show all regions</span>
                </label>
            </div>

            @if(!$showAllRegions)
                <div class="form-control" wire:transition>
                    <label class="label">
                        <span class="label-text">Default Region</span>
                    </label>
                    <select wire:model="selectedRegionId" class="select select-bordered @error('selectedRegionId') select-error @enderror">
                        <option value="">Select a region...</option>
                        @foreach($this->regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedRegionId')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>
            @endif

            <div class="flex gap-3 pt-2">
                <button type="button" wire:click="previousStep" class="btn btn-ghost flex-1">
                    <x-heroicon-o-arrow-left class="size-5" />
                    Back
                </button>
                <button type="submit" class="btn btn-primary flex-1">
                    Continue
                    <x-heroicon-o-arrow-right class="size-5" />
                </button>
            </div>
        </form>
    @endif

    {{-- Step 5: Complete --}}
    @if($step === 5)
        <div class="text-center py-4">
            <div class="mb-6">
                <div class="w-20 h-20 mx-auto bg-success/20 rounded-full flex items-center justify-center">
                    <x-heroicon-o-check class="size-10 text-success" />
                </div>
            </div>

            <h2 class="text-2xl font-bold text-base-content">You're All Set!</h2>
            <p class="text-base-content/60 mt-2 mb-8">
                Your account is ready. You can change these settings anytime from the Settings page.
            </p>

            <button wire:click="complete" class="btn btn-primary btn-lg w-full">
                Go to Dashboard
                <x-heroicon-o-arrow-right class="size-5" />
            </button>
        </div>
    @endif
</div>
