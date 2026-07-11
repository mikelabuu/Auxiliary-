@props([
    'label' => null,
    'name',
    'selected' => null
])

<div class="form-group mb-4">
    @if($label)
        <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $attributes->merge([
            'class' => 'form-select' . ($errors->has($name) ? ' form-input-invalid' : '')
        ]) }}
    >
        {{ $slot }}
    </select>

    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
