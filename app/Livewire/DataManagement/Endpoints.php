<?php

namespace App\Livewire\DataManagement;

use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layout.app-shell', ['title' => 'API Endpoints'])]
class Endpoints extends Component
{
    public ?string $lastHealthCheck = null;

    public ?bool $isHealthy = null;

    public ?string $healthCheckError = null;

    public bool $isChecking = false;

    /**
     * Get the configured endpoints.
     *
     * @return array<int, array{name: string, url: string, protocol: string, view_guid: string|null}>
     */
    public function getEndpointsProperty(): array
    {
        $baseUrl = config('workstudio.base_url');
        $views = config('workstudio.views', []);

        return [
            [
                'name' => 'Vegetation Assessments',
                'url' => $baseUrl.'GETVIEWDATA',
                'protocol' => 'GETVIEWDATA',
                'view_guid' => $views['vegetation_assessments'] ?? null,
            ],
            [
                'name' => 'Work Jobs',
                'url' => $baseUrl.'GETVIEWDATA',
                'protocol' => 'GETVIEWDATA',
                'view_guid' => $views['work_jobs'] ?? null,
            ],
            [
                'name' => 'Planned Units',
                'url' => $baseUrl.'GETVIEWDATA',
                'protocol' => 'GETVIEWDATA',
                'view_guid' => $views['planned_units'] ?? null,
            ],
        ];
    }

    /**
     * Get the connection settings.
     *
     * @return array<string, mixed>
     */
    public function getSettingsProperty(): array
    {
        return [
            'base_url' => config('workstudio.base_url'),
            'timeout' => config('workstudio.timeout'),
            'connect_timeout' => config('workstudio.connect_timeout'),
            'max_retries' => config('workstudio.max_retries'),
            'rate_limit_delay' => config('workstudio.sync.rate_limit_delay'),
            'calls_before_delay' => config('workstudio.sync.calls_before_delay'),
        ];
    }

    /**
     * Test connection to the API.
     */
    public function testConnection(): void
    {
        $this->isChecking = true;
        $this->healthCheckError = null;

        try {
            $baseUrl = config('workstudio.base_url');
            $timeout = config('workstudio.connect_timeout', 10);

            // Try a simple HEAD request to check connectivity
            $response = Http::timeout($timeout)
                ->connectTimeout($timeout)
                ->get(rtrim($baseUrl, '/').'/GETECHO');

            if ($response->successful() || $response->status() < 500) {
                $this->isHealthy = true;
                $this->dispatch('notify', message: 'API endpoint is reachable.', type: 'success');
            } else {
                $this->isHealthy = false;
                $this->healthCheckError = 'HTTP '.$response->status().': Server returned an error.';
                $this->dispatch('notify', message: 'API endpoint returned an error.', type: 'error');
            }
        } catch (\Exception $e) {
            $this->isHealthy = false;
            $this->healthCheckError = $e->getMessage();
            $this->dispatch('notify', message: 'Connection failed: '.$e->getMessage(), type: 'error');
        }

        $this->lastHealthCheck = now()->format('M d, Y g:i:s A');
        $this->isChecking = false;
    }

    public function render()
    {
        return view('livewire.data-management.endpoints');
    }
}
