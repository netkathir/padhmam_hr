@props(['href'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-outline-secondary']) }}>
    {{ $slot->isEmpty() ? 'Cancel' : $slot }}
</a>
