@props([
    'id',
    'title' => '',
    'size' => 'md'
])

@php
    $sizes = [
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        '2xl' => 'max-w-6xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<div id="{{ $id }}" 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300 hidden"
     aria-modal="true" 
     role="dialog"
>
    <!-- Modal Container -->
    <div class="bg-white rounded-3xl shadow-2xl w-full {{ $sizeClass }} overflow-hidden flex flex-col border border-slate-100 max-h-[90vh] animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header -->
        <div class="px-6 py-5 border-b border-slate-50 flex items-center justify-between">
            <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">{{ $title }}</h3>
            <button type="button" 
                    class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center transition-all cursor-pointer"
                    onclick="document.getElementById('{{ $id }}').classList.add('hidden')"
                    aria-label="Close"
            >
                <span class="material-icons text-[20px]">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-5 overflow-y-auto flex-1 text-slate-600 text-sm">
            {{ $slot }}
        </div>

        <!-- Footer -->
        @if(isset($footer))
            <div class="px-6 py-4 border-t border-slate-50 bg-slate-50/50 flex items-center justify-end gap-3">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
