<x-layout.app-shell
    title="Shell Test"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Shell Test'],
    ]"
>
    {{-- Test Content --}}
    <div class="space-y-6">
        {{-- Header Card --}}
        <div class="card bg-base-200 shadow-lg">
            <div class="card-body">
                <h2 class="card-title text-2xl">
                    <x-ui.icon name="check-circle" size="lg" class="text-success" />
                    App Shell Layout Test
                </h2>
                <p class="text-base-content/70">
                    This page tests the new DaisyUI-based app shell layout with responsive sidebar,
                    theme system, and Alpine.js state management.
                </p>
            </div>
        </div>

        {{-- Theme Test --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h3 class="card-title">Theme System</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Current Theme Display --}}
                    <div class="p-4 rounded-lg bg-base-200">
                        <p class="text-sm text-base-content/70 mb-2">Current Theme</p>
                        <p class="text-lg font-semibold" x-text="$store.theme.currentName">Loading...</p>
                        <p class="text-sm text-base-content/60" x-text="'Effective: ' + $store.theme.effective"></p>
                    </div>
                    {{-- Dark Mode Status --}}
                    <div class="p-4 rounded-lg bg-base-200">
                        <p class="text-sm text-base-content/70 mb-2">Color Scheme</p>
                        <div class="flex items-center gap-2">
                            <span
                                class="badge"
                                :class="$store.theme.isDark ? 'badge-neutral' : 'badge-warning'"
                                x-text="$store.theme.isDark ? 'Dark' : 'Light'"
                            ></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar State Test --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h3 class="card-title">Sidebar State</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Breakpoint</div>
                        <div class="stat-value text-lg" x-text="$store.sidebar.breakpoint">-</div>
                    </div>
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Collapsed</div>
                        <div class="stat-value text-lg" x-text="$store.sidebar.isCollapsed ? 'Yes' : 'No'">-</div>
                    </div>
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Open (Mobile)</div>
                        <div class="stat-value text-lg" x-text="$store.sidebar.isOpen ? 'Yes' : 'No'">-</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Color Palette Test --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h3 class="card-title">DaisyUI Color Palette</h3>
                <div class="flex flex-wrap gap-2">
                    <div class="badge badge-primary">Primary</div>
                    <div class="badge badge-secondary">Secondary</div>
                    <div class="badge badge-accent">Accent</div>
                    <div class="badge badge-neutral">Neutral</div>
                    <div class="badge badge-info">Info</div>
                    <div class="badge badge-success">Success</div>
                    <div class="badge badge-warning">Warning</div>
                    <div class="badge badge-error">Error</div>
                </div>

                <div class="divider"></div>

                <h4 class="font-medium mb-2">Base Colors</h4>
                <div class="flex gap-2">
                    <div class="w-16 h-16 rounded-lg bg-base-100 border border-base-300 flex items-center justify-center text-xs">100</div>
                    <div class="w-16 h-16 rounded-lg bg-base-200 flex items-center justify-center text-xs">200</div>
                    <div class="w-16 h-16 rounded-lg bg-base-300 flex items-center justify-center text-xs">300</div>
                    <div class="w-16 h-16 rounded-lg bg-base-content text-base-100 flex items-center justify-center text-xs">content</div>
                </div>
            </div>
        </div>

        {{-- Button Variants Test --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h3 class="card-title">Button Variants</h3>
                <div class="flex flex-wrap gap-2">
                    <button class="btn btn-primary">Primary</button>
                    <button class="btn btn-secondary">Secondary</button>
                    <button class="btn btn-accent">Accent</button>
                    <button class="btn btn-ghost">Ghost</button>
                    <button class="btn btn-outline">Outline</button>
                </div>

                <div class="divider"></div>

                <h4 class="font-medium mb-2">Status Buttons</h4>
                <div class="flex flex-wrap gap-2">
                    <button class="btn btn-info">Info</button>
                    <button class="btn btn-success">Success</button>
                    <button class="btn btn-warning">Warning</button>
                    <button class="btn btn-error">Error</button>
                </div>
            </div>
        </div>

        {{-- Form Elements Test --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h3 class="card-title">Form Elements</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Text Input</span>
                        </label>
                        <input type="text" placeholder="Type here..." class="input input-bordered" />
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Select</span>
                        </label>
                        <select class="select select-bordered">
                            <option disabled selected>Pick one</option>
                            <option>Option 1</option>
                            <option>Option 2</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 mt-4">
                    <label class="label cursor-pointer gap-2">
                        <input type="checkbox" checked class="checkbox checkbox-primary" />
                        <span class="label-text">Checkbox</span>
                    </label>
                    <label class="label cursor-pointer gap-2">
                        <input type="checkbox" class="toggle toggle-primary" checked />
                        <span class="label-text">Toggle</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</x-layout.app-shell>
