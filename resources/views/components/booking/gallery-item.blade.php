@props([
    'image',
    'thumb' => null,
    'alt' => 'Farmers Hostel Gallery',
])

<a href="{{ asset($image) }}"
   data-lightbox="hotel-gallery"
   {{ $attributes->merge(['class' => 'group block overflow-hidden rounded-2xl border border-slate-100 shadow-sm relative break-inside-avoid bg-white']) }}
>
    <img src="{{ asset($thumb ?? $image) }}" alt="{{ $alt }}" class="w-full object-cover transform group-hover:scale-105 transition-transform duration-700" loading="lazy">
    <div class="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition-colors duration-300"></div>
    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
        <span class="material-icons text-white text-3xl drop-shadow-lg">zoom_in</span>
    </div>
</a>
