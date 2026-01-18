@props([
    'activities' => collect(),
    'maxHeight' => '500px',
])

<div {{ $attributes->merge(['class' => 'overflow-auto']) }} style="max-height: {{ $maxHeight }}">
    @if($activities->isEmpty())
        <div class="flex flex-col items-center justify-center py-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-base-content/20 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-base-content/50 text-sm">No activity recorded</p>
        </div>
    @else
        <ul class="timeline timeline-vertical timeline-compact">
            @foreach($activities as $activity)
                <li wire:key="activity-{{ $activity->id }}">
                    @if(!$loop->first)
                        <hr class="bg-base-300" />
                    @endif
                    <div class="timeline-start text-xs text-base-content/60 whitespace-nowrap">
                        <div class="tooltip" data-tip="{{ $activity->created_at->format('M d, Y g:i A') }}">
                            {{ $activity->created_at->diffForHumans() }}
                        </div>
                    </div>
                    <div class="timeline-middle">
                        @php
                            $eventColors = [
                                'created' => 'text-success',
                                'updated' => 'text-info',
                                'deleted' => 'text-error',
                            ];
                            $eventColor = $eventColors[$activity->event] ?? 'text-base-content';
                        @endphp
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $eventColor }}" fill="currentColor" viewBox="0 0 20 20">
                            <circle cx="10" cy="10" r="5" />
                        </svg>
                    </div>
                    <div class="timeline-end timeline-box bg-base-100 border-base-200">
                        {{-- Causer --}}
                        <div class="flex items-center gap-2 mb-2">
                            @if($activity->causer)
                                <div class="avatar placeholder">
                                    <div class="bg-primary text-primary-content w-6 rounded-full">
                                        <span class="text-xs">{{ substr($activity->causer->name, 0, 2) }}</span>
                                    </div>
                                </div>
                                <span class="font-medium text-sm">{{ $activity->causer->name }}</span>
                            @else
                                <div class="avatar placeholder">
                                    <div class="bg-neutral text-neutral-content w-6 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <span class="font-medium text-sm text-base-content/60">System</span>
                            @endif
                            <span class="badge badge-{{ $activity->event === 'created' ? 'success' : ($activity->event === 'deleted' ? 'error' : 'info') }} badge-xs">
                                {{ ucfirst($activity->event) }}
                            </span>
                        </div>

                        {{-- Description --}}
                        <p class="text-sm text-base-content/80 mb-2">{{ $activity->description }}</p>

                        {{-- Changed Fields --}}
                        @if($activity->event === 'updated' && !empty($activity->properties['old']) && !empty($activity->properties['attributes']))
                            <div class="space-y-1 text-xs">
                                @foreach($activity->properties['attributes'] as $field => $newValue)
                                    @php
                                        $oldValue = $activity->properties['old'][$field] ?? null;
                                    @endphp
                                    @if($oldValue !== $newValue)
                                        <div class="flex flex-wrap items-center gap-1 py-1 border-t border-base-200 first:border-0">
                                            <span class="font-mono font-medium text-base-content">{{ Str::title(str_replace('_', ' ', $field)) }}:</span>
                                            <span class="text-error line-through">{{ is_array($oldValue) ? json_encode($oldValue) : ($oldValue ?? 'null') }}</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                            </svg>
                                            <span class="text-success">{{ is_array($newValue) ? json_encode($newValue) : ($newValue ?? 'null') }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @elseif($activity->event === 'created' && !empty($activity->properties['attributes']))
                            <div class="text-xs text-base-content/60">
                                Initial values set for {{ count($activity->properties['attributes']) }} fields
                            </div>
                        @endif
                    </div>
                    @if(!$loop->last)
                        <hr class="bg-base-300" />
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
