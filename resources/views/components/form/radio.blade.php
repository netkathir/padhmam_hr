@props(['name', 'label' => null, 'options' => [], 'value' => null])

<div class="mb-3">
    @if($label)
        <div class="form-label">{{ $label }}</div>
    @endif
    <div class="d-flex flex-wrap gap-3">
        @foreach($options as $optionValue => $optionLabel)
            <div class="form-check">
                <input class="form-check-input" type="radio" name="{{ $name }}" id="{{ $name }}_{{ $loop->index }}" value="{{ $optionValue }}" @checked((string) old($name, $value) === (string) $optionValue) {{ $attributes }}>
                <label class="form-check-label" for="{{ $name }}_{{ $loop->index }}">{{ $optionLabel }}</label>
            </div>
        @endforeach
    </div>
    @error($name)
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>
