@props(['name', 'label', 'checked' => false])

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="{{ $name }}" id="{{ $name }}" value="1" @checked(old($name, $checked)) {{ $attributes }}>
    <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
    @error($name)
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>
