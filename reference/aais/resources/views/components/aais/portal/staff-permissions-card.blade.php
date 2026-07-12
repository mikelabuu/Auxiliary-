@props([
    'staffPerms' => [],
    'title' => 'Staff Permissions',
])

<div class="card card-body">
    <p class="section-label portal-perm-title">{{ $title }}</p>

    @foreach ($staffPerms as $sp)
        <x-aais.ui.permission-item :icon="$sp['icon']" :text="$sp['text']" :ok="$sp['ok']" />
    @endforeach
</div>
