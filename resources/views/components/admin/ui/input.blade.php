@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => ''
])

<div class="form-group mb-4">
    @if($label)
        <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        {{ $attributes->merge([
            'class' => 'form-input' . ($errors->has($name) ? ' form-input-invalid' : '')
        ]) }}
    >

    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
