@props(['label' => 'Save'])

<button type="submit" {{ $attributes->merge(['class' => 'btn btn-primary']) }}>
    <span class="spinner-border spinner-border-sm me-2 d-none" data-loading-spinner></span>
    {{ $label }}
</button>
