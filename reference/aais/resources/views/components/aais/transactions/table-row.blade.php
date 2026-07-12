@props([
    'doc',
    'idx',
])

@php
    $nameParts = preg_split('/\s+/', trim((string) ($doc['name'] ?? 'Client')));
    $firstInitial = strtoupper(substr($nameParts[0] ?? 'C', 0, 1));
    $secondInitial = strtoupper(substr($nameParts[1] ?? '', 0, 1));
    $clientInitials = $firstInitial . $secondInitial;
@endphp

<tr x-show="isVisible({{ $idx }})" x-transition.opacity.duration.250ms :class="'transactions-row transactions-row-' + docs[{{ $idx }}].status">
    <td>
        <div class="transactions-ref-cell">
            <button type="button" class="transactions-ref-link" @click="viewDoc({{ $idx }})" title="Open transaction details">
                <span class="ref-code">{{ $doc['ref'] }}</span>
            </button>
            <button
                type="button"
                class="transactions-copy-ref"
                @click="copyRef(docs[{{ $idx }}].ref)"
                :aria-label="'Copy reference code ' + docs[{{ $idx }}].ref"
            >
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                <span x-text="copiedRef === docs[{{ $idx }}].ref ? 'Copied' : 'Copy'"></span>
            </button>
            <span class="transactions-ref-meta">Entry {{ str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT) }}</span>
        </div>
    </td>
    <td>
        <div class="transactions-client-cell">
            <span class="transactions-client-avatar">{{ $clientInitials }}</span>
            <span class="transactions-client-name">{{ $doc['name'] }}</span>
        </div>
    </td>
    <td><span class="transactions-doc-type-badge">{{ $doc['type'] }}</span></td>
    <td><span class="chip chip-muted">{{ $doc['office'] }}</span></td>
    <td class="text-muted">{{ $doc['staff'] }}</td>
    <td class="text-muted transactions-date">{{ $doc['received'] }}</td>
    <td>
        <span :title="statusTooltip(docs[{{ $idx }}])">
            <x-aais.ui.status-badge-dynamic
                class-expr="'status-' + docs[{{ $idx }}].status"
                text-expr="labels[docs[{{ $idx }}].status]"
            />
        </span>
    </td>
    <td>
        <div class="transactions-actions">
            <button class="btn btn-outline btn-sm transactions-action-btn" title="View Details" @click="viewDoc({{ $idx }})">
                View
            </button>
            <button class="btn btn-outline btn-sm transactions-action-btn" title="Update Status" @click="editDoc({{ $idx }})">
                Edit
            </button>
        </div>
    </td>
</tr>
