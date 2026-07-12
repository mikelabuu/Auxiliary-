@props([
    'instructions' => [],
    'title' => 'How to Use This Portal',
])

<div class="card card-body">
    <div class="portal-guide-head">
        <svg class="portal-guide-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        <span class="section-title">{{ $title }}</span>
    </div>

    @foreach ($instructions as $ins)
        <x-aais.ui.instruction-item
            :number="$ins['number']"
            :title="$ins['title']"
            :description="$ins['description']"
        />
    @endforeach
</div>
