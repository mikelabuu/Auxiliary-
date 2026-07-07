@props(['items' => []])

{{--
    Sticky in-page jump navigation for long admin pages (Bookings Hub, Rooms).
    Renders a pill bar that scrolls to each section and highlights the section
    currently in view (scrollspy via IntersectionObserver).

    Each item: ['id' => 'all-bookings', 'label' => 'All Bookings', 'icon' => 'clipboard']
    The `id` must match an element id on the page. Give those elements a
    scroll-margin (e.g. class="scroll-mt-32") so they clear the sticky bar.
--}}

<nav data-section-nav
     class="animate-in sticky top-16 z-20 -mx-1 mb-2 rounded-2xl border border-stone-200/70 bg-stone-50/85 px-2 py-2 backdrop-blur-md shadow-subtle">
    <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar">
        @foreach($items as $item)
            <a href="#{{ $item['id'] }}" data-nav-target="{{ $item['id'] }}"
               class="section-nav-link flex items-center gap-1.5 shrink-0 text-xs font-semibold rounded-full border px-3.5 py-2 transition-colors whitespace-nowrap !no-underline bg-white text-stone-600 border-stone-200 hover:bg-stone-50">
                @isset($item['icon'])
                    <x-admin.icon :name="$item['icon']" class="w-3.5 h-3.5 shrink-0" stroke-width="2" />
                @endisset
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>

@once
@push('scripts')
<style>
    html { scroll-behavior: smooth; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ON  = ['bg-clsu-700', 'text-white', 'border-clsu-700', 'shadow-sm'];
    const OFF = ['bg-white', 'text-stone-600', 'border-stone-200', 'hover:bg-stone-50'];

    document.querySelectorAll('[data-section-nav]').forEach(function (nav) {
        const links = Array.from(nav.querySelectorAll('[data-nav-target]'));
        const sections = links
            .map(l => document.getElementById(l.dataset.navTarget))
            .filter(Boolean);
        if (!sections.length) return;

        function setActive(id) {
            links.forEach(function (l) {
                const on = l.dataset.navTarget === id;
                ON.forEach(c => l.classList.toggle(c, on));
                OFF.forEach(c => l.classList.toggle(c, !on));
            });
        }

        // Instant highlight on click so it feels responsive before the scroll settles.
        links.forEach(l => l.addEventListener('click', () => setActive(l.dataset.navTarget)));

        const observer = new IntersectionObserver(function (entries) {
            const visible = entries
                .filter(e => e.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
            if (visible.length) setActive(visible[0].target.id);
        }, { rootMargin: '-140px 0px -55% 0px', threshold: 0 });

        sections.forEach(s => observer.observe(s));
        setActive(sections[0].id);
    });
});
</script>
@endpush
@endonce
