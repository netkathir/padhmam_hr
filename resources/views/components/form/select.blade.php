@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'required' => false,
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
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $attributes->merge(['class' => 'form-select '.($errors->has($name) ? 'is-invalid' : '')]) }}
        @if($required) required @endif
    >
        {{ $slot }}
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $fieldValue === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
