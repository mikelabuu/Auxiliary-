@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => ''
])

<div class="mb-4">
    @if($label)
        <label for="{{ $name }}" class="block text-xs font-bold text-slate-700 tracking-wider uppercase mb-1.5">{{ $label }}</label>
    @endif
    
    <input 
        type="{{ $type }}" 
        name="{{ $name }}" 
        id="{{ $name }}" 
        value="{{ old($name, $value) }}"
        {{ $attributes->merge([
            'class' => 'w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-800 text-sm transition-all focus:bg-white focus:border-brand focus:ring-1 focus:ring-brand outline-none ' . 
            ($errors->has($name) ? 'border-red-400 focus:border-red-500 focus:ring-red-500 bg-red-50/10' : '')
        ]) }}
    >
    
    @error($name)
        <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>
    @enderror
</div>
