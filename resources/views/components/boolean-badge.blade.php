@props(['value' => false, 'trueLabel' => 'Yes', 'falseLabel' => 'No'])

@php
    $classes = $value ? 'bg-success' : 'bg-secondary';
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$classes]) }}>
    {{ $value ? $trueLabel : $falseLabel }}
</span>
