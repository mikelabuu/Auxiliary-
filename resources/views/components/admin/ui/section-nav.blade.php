@props(['items' => []])

{{--
    Sticky in-page jump navigation (AAIS filter tabs). Highlights the section
    currently in view via IntersectionObserver.

    Each item: ['id' => 'all-bookings', 'label' => 'All Bookings', 'icon' => 'clipboard']
    The `id` must match an element id on the page; give those elements a
    scroll-margin (e.g. class="scroll-mt-32") so they clear the sticky bar.
--}}

<nav data-section-nav class="section-nav-bar animate-in">
    @foreach($items as $item)
        <a href="#{{ $item['id'] }}" data-nav-target="{{ $item['id'] }}" class="filter-tab !no-underline">
            @isset($item['icon'])
                <x-admin.ui.icon :name="$item['icon']" class="w-3.5 h-3.5 shrink-0" stroke-width="2" />
            @endisset
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-section-nav]').forEach(function (nav) {
        const links = Array.from(nav.querySelectorAll('[data-nav-target]'));
        const sections = links
            .map(l => document.getElementById(l.dataset.navTarget))
            .filter(Boolean);
        if (!sections.length) return;

        function setActive(id) {
            links.forEach(l => l.classList.toggle('selected', l.dataset.navTarget === id));
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
