@props(['status' => 'inactive'])

@php
    $classes = $status === 'active' ? 'bg-success' : 'bg-secondary';
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$classes]) }}>
    {{ ucfirst($status) }}
</span>
