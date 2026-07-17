@props([
    'name',
    'label' => null,
    'value' => null,
    'required' => false,
    'rows' => 4,
])

@php
    $fieldValue = old($name, $value);
@endphp

<div class="mb-3">
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->merge(['class' => 'form-control '.($errors->has($name) ? 'is-invalid' : '')]) }}
        @if($required) required @endif
    >{{ $fieldValue }}</textarea>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
