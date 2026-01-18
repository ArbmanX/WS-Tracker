@props([
    'status',
])

@if ($status)
    <div role="alert" class="alert alert-success">
        <x-heroicon-o-check-circle class="size-5" />
        <span>{{ $status }}</span>
    </div>
@endif
