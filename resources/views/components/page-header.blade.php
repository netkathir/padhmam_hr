@props(['title', 'subtitle' => null])

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="d-flex gap-2">
        {{ $actions ?? $slot }}
    </div>
</div>
