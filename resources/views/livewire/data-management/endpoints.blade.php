<div class="container mx-auto p-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm breadcrumbs">
            <ul>
                <li><a href="{{ route('admin.data') }}">Data Management</a></li>
                <li>API Endpoints</li>
            </ul>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-base-content">API Endpoints</h1>
                <p class="text-base-content/60">Configured WorkStudio DDOProtocol endpoints</p>
            </div>
            <button
                wire:click="testConnection"
                wire:loading.attr="disabled"
                class="btn btn-primary btn-sm"
            >
                <span wire:loading.remove wire:target="testConnection">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <span wire:loading wire:target="testConnection" class="loading loading-spinner loading-xs"></span>
                Test Connection
            </button>
        </div>
    </div>

    {{-- Health Check Result --}}
    @if($lastHealthCheck)
        <div class="mb-6">
            @if($isHealthy)
                <div role="alert" class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="font-bold">API Endpoint Reachable</h3>
                        <div class="text-xs">Last checked: {{ $lastHealthCheck }}</div>
                    </div>
                </div>
            @else
                <div role="alert" class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="font-bold">Connection Failed</h3>
                        <div class="text-xs">{{ $healthCheckError }}</div>
                        <div class="text-xs opacity-70">Last checked: {{ $lastHealthCheck }}</div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Connection Settings --}}
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body">
            <h2 class="card-title text-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Connection Settings
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                <div>
                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Base URL</span>
                    <p class="font-mono text-sm break-all">{{ $this->settings['base_url'] }}</p>
                </div>
                <div>
                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Timeout</span>
                    <p class="font-mono">{{ $this->settings['timeout'] }}s</p>
                </div>
                <div>
                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Connect Timeout</span>
                    <p class="font-mono">{{ $this->settings['connect_timeout'] }}s</p>
                </div>
                <div>
                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Max Retries</span>
                    <p class="font-mono">{{ $this->settings['max_retries'] }}</p>
                </div>
                <div>
                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Rate Limit Delay</span>
                    <p class="font-mono">{{ number_format($this->settings['rate_limit_delay'] / 1000) }}ms</p>
                </div>
                <div>
                    <span class="text-xs text-base-content/60 uppercase tracking-wide">Calls Before Delay</span>
                    <p class="font-mono">{{ $this->settings['calls_before_delay'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Endpoints List --}}
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            <h2 class="card-title text-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                </svg>
                Configured Endpoints
            </h2>

            <div class="overflow-x-auto mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Protocol</th>
                            <th>URL</th>
                            <th>View GUID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->endpoints as $endpoint)
                            <tr class="hover" wire:key="endpoint-{{ $loop->index }}">
                                <td class="font-medium">{{ $endpoint['name'] }}</td>
                                <td>
                                    <span class="badge badge-ghost badge-sm font-mono">{{ $endpoint['protocol'] }}</span>
                                </td>
                                <td class="font-mono text-sm max-w-xs truncate" title="{{ $endpoint['url'] }}">
                                    {{ $endpoint['url'] }}
                                </td>
                                <td>
                                    @if($endpoint['view_guid'])
                                        <span class="font-mono text-xs text-base-content/60">{{ $endpoint['view_guid'] }}</span>
                                    @else
                                        <span class="text-base-content/40 text-sm">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 p-3 bg-base-200 rounded-lg">
                <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-info shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-base-content/70">
                        <p class="font-medium text-base-content">Configuration is read-only</p>
                        <p class="mt-1">Endpoint configuration is managed via environment variables and the <code class="bg-base-300 px-1 py-0.5 rounded text-xs">config/workstudio.php</code> file.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
