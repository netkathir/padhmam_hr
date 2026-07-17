@props(['label'])

@php
    $map = [
        'Active' => ['bg-success', 'bi-check-circle'],
        'Current' => ['bg-success', 'bi-check-circle'],
        'Expiring Soon' => ['bg-warning text-dark', 'bi-exclamation-triangle'],
        'Expired' => ['bg-danger', 'bi-x-circle'],
        'Upcoming' => ['bg-info text-dark', 'bi-clock-history'],
        'Inactive' => ['bg-secondary', 'bi-slash-circle'],
        'Not Set' => ['bg-light text-dark border', 'bi-dash-circle'],
        'Draft' => ['bg-light text-dark border', 'bi-pencil-square'],
    ];
    [$class, $icon] = $map[$label] ?? ['bg-secondary', 'bi-question-circle'];
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$class]) }}>
    <i class="bi {{ $icon }} me-1"></i>{{ $label }}
</span>
