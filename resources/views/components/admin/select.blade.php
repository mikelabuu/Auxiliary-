@props([
    'label' => null,
    'name',
    'selected' => null
])

<div class="mb-4">
    @if($label)
        <label for="{{ $name }}" class="block text-xs font-bold text-sage-secondary tracking-wider uppercase mb-1.5">{{ $label }}</label>
    @endif
    
    <select 
        name="{{ $name }}" 
        id="{{ $name }}" 
        {{ $attributes->merge([
            'class' => 'w-full px-4 py-2.5 rounded-md border border-sage-secondary/20 bg-white text-sage-primary text-sm transition-all focus:border-sage-tertiary focus:ring-1 focus:ring-sage-tertiary outline-none cursor-pointer ' . 
            ($errors->has($name) ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : '')
        ]) }}
    >
        {{ $slot }}
    </select>
    
    @error($name)
        <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
    @enderror
</div>
