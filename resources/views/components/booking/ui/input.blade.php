@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => '',
])

<div class="mb-4">
    @if($label)
        <label for="{{ $name }}" class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">{{ $label }}</label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        {{ $attributes->merge([
            'class' => 'w-full px-4 py-2.5 rounded-xl border bg-stone-50/60 text-stone-800 text-sm transition-all focus:bg-white focus:ring-2 outline-none ' .
            ($errors->has($name)
                ? 'border-ember-300 focus:border-ember-400 focus:ring-ember-200 bg-ember-50/40'
                : 'border-stone-200 focus:border-clsu-400 focus:ring-clsu-200')
        ]) }}
    >

    @error($name)
        <p class="text-xs text-ember-600 mt-1.5 font-semibold">{{ $message }}</p>
    @enderror
</div>
