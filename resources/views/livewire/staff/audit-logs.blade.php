<div x-data="{ showModal: false, modalLog: null }">
    @php
        $tableTabs = [
            'all'       => 'All',
            'bookings'  => 'Bookings',
            'discounts' => 'Discounts',
            'payments'  => 'Payments',
            'users'     => 'Users',
            'staff'     => 'Staff',
            'rooms'     => 'Rooms',
            'unsorted'  => 'Unsorted',
        ];
        $actionTone = function ($action) {
            $a = strtolower((string) $action);
            return match (true) {
                str_contains($a, 'delete') || str_contains($a, 'suspend') || str_contains($a, 'reject') || str_contains($a, 'cancel') => 'status-danger',
                str_contains($a, 'create') || str_contains($a, 'approve') || str_contains($a, 'verify') || str_contains($a, 'unsuspend') => 'status-success',
                str_contains($a, 'update') || str_contains($a, 'edit') => 'status-approved',
                default => 'status-neutral',
            };
        };
    @endphp

    <x-admin.ui.section-card icon="shield" title="Audit Trail" :subtitle="$logs->total() . ' record' . ($logs->total() === 1 ? '' : 's') . ($search ? ' matching “' . $search . '”' : '')" :delay="40">

        {{-- Target-type tabs --}}
        <div class="filter-row mb-4">
            <span class="filter-row-label">Records</span>
            @foreach($tableTabs as $key => $label)
                <button type="button" wire:click="$set('table', '{{ $key === 'all' ? '' : $key }}')"
                        @class(['filter-tab', 'selected' => ($table === $key) || ($table === '' && $key === 'all')])>
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Search + filters --}}
        <div class="filter-toolbar">
            <div class="filter-search">
                <x-admin.ui.icon name="search" class="w-4 h-4" stroke-width="2" />
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Description, staff, id, or IP…" aria-label="Search audit logs">
            </div>
            <select wire:model.live="role" class="filter-select" aria-label="Filter by role">
                <option value="">All roles</option>
                @foreach($availableRoles as $r)
                    <option value="{{ $r }}">{{ ucwords(str_replace('_', ' ', $r)) }}</option>
                @endforeach
            </select>
            <select wire:model.live="action" class="filter-select" aria-label="Filter by action">
                <option value="">All actions</option>
                @foreach($availableActions as $a)
                    <option value="{{ $a }}">{{ ucwords(str_replace('_', ' ', $a)) }}</option>
                @endforeach
            </select>
            <select wire:model.live="sort" class="filter-select" aria-label="Sort order">
                <option value="latest">Latest first</option>
                <option value="oldest">Oldest first</option>
                <option value="role">By role</option>
                <option value="target">By record</option>
            </select>
            <input type="date" wire:model.live="from" class="filter-select" aria-label="From date">
            <input type="date" wire:model.live="to" class="filter-select" aria-label="To date">
            <div class="filter-toolbar-spacer"></div>
            <span class="refresh-chip" wire:loading.delay.flex wire:target="search, table, role, action, sort, from, to, perPage, resetFilters, gotoPage, previousPage, nextPage">
                <svg class="spinner-inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9" class="opacity-20"/><path d="M21 12a9 9 0 0 0-9-9"/></svg>
                Updating
            </span>
            @if($search || $table !== '' || $role !== '' || $action !== '' || $sort !== 'latest' || $from !== '' || $to !== '')
                <button type="button" wire:click="resetFilters" class="filter-clear">
                    <x-admin.ui.icon name="x" class="w-3 h-3" stroke-width="2.5" /> Clear filters
                </button>
            @endif
        </div>

        @if($logs->isEmpty())
            <x-admin.ui.empty-state icon="shield" title="No log entries match this view." />
        @else
            <div class="wire-panel" wire:loading.delay.class="is-refreshing" wire:target="search, table, role, action, sort, from, to, perPage, resetFilters, gotoPage, previousPage, nextPage">
                <div class="scroll-x -mx-6 border-t border-stone-100">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Staff</th>
                                <th>Action</th>
                                <th>Record</th>
                                <th>Description</th>
                                <th>IP</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                @php
                                    $staffName = $log->staff?->name ?? 'System';
                                    $initials = strtoupper(mb_substr($staffName, 0, 1));
                                    $target = $log->target_type
                                        ? \Illuminate\Support\Str::afterLast($log->target_type, '\\') . ($log->target_id ? " #{$log->target_id}" : '')
                                        : 'Unsorted';
                                @endphp
                                <tr>
                                    <td class="font-data tabnum text-muted whitespace-nowrap">{{ $log->created_at->timezone('Asia/Manila')->format('M d, Y · H:i:s') }}</td>
                                    <td>
                                        <div class="cell-name">
                                            <span class="avatar-initials">{{ $initials }}</span>
                                            <div class="cell-name-text">
                                                <p class="cell-name-primary truncate">{{ $staffName }}</p>
                                                <p class="cell-name-secondary truncate">{{ $log->role ? ucwords(str_replace('_', ' ', $log->role)) : '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="status {{ $actionTone($log->action) }}">{{ str_replace('_', ' ', $log->action) }}</span></td>
                                    <td><span class="cell-tag">{{ $target }}</span></td>
                                    <td class="text-muted max-w-xs truncate" title="{{ $log->description }}">{{ $log->description }}</td>
                                    <td class="font-data tabnum text-xs text-faint">{{ $log->ip_address }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="btn btn-outline btn-sm cursor-pointer"
                                                @click.prevent="
                                                    fetch('{{ route('staff.audit.show', $log->id) }}')
                                                      .then(r => r.json())
                                                      .then(j => { modalLog = j.log; modalLog.old_values = j.old_values; modalLog.new_values = j.new_values; showModal = true; })
                                                ">
                                                <x-admin.ui.icon name="eye" class="w-3.5 h-3.5" />
                                                View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[280px]">
                        {{ $logs->links('vendor.pagination.admin') }}
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-muted">
                        Per page
                        <select wire:model.live="perPage" class="filter-select !h-9" aria-label="Records per page">
                            <option value="15">15</option>
                            <option value="30">30</option>
                            <option value="50">50</option>
                        </select>
                    </label>
                </div>
            </div>
        @endif
    </x-admin.ui.section-card>

    {{-- Details modal (entry animated by @starting-style on .modal) --}}
    <div x-show="showModal" x-transition:leave.opacity.duration.140ms x-cloak
         class="fixed inset-0 z-[300] flex items-center justify-center p-5"
         @keydown.escape.window="showModal = false">
        <div class="modal-backdrop-tint" @click="showModal = false"></div>
        <div class="modal modal-lg relative w-full max-h-[88vh] overflow-y-auto custom-scrollbar" role="dialog" aria-modal="true" aria-labelledby="auditLogModalTitle">
            <div class="modal-header">
                <h3 class="modal-title" id="auditLogModalTitle">
                    <x-admin.ui.icon name="shield" class="w-[18px] h-[18px] shrink-0" stroke-width="2" style="color:var(--color-g-600);" />
                    Audit Log Details
                </h3>
                <button type="button" class="btn btn-ghost btn-sm btn-icon" @click="showModal = false" aria-label="Close">
                    <x-admin.ui.icon name="x" class="w-4 h-4" stroke-width="2" />
                </button>
            </div>

            <template x-if="modalLog">
                <div class="modal-body">
                    <div class="record-detail-panel">
                        <div class="record-detail-row">
                            <span class="record-detail-label">Action</span>
                            <span class="record-detail-value" x-text="(modalLog.action || '').replace(/_/g, ' ')"></span>
                        </div>
                        <div class="record-detail-row">
                            <span class="record-detail-label">Performed by</span>
                            <span class="record-detail-value" x-text="modalLog.staff ? (modalLog.staff.name + ' (' + (modalLog.staff.role ?? '') + ')') : 'System'"></span>
                        </div>
                        <div class="record-detail-row">
                            <span class="record-detail-label">Record</span>
                            <span class="record-detail-value" x-text="modalLog.target_type ? modalLog.target_type.split('\\\\').pop() + (modalLog.target_id ? ' #' + modalLog.target_id : '') : 'Unsorted'"></span>
                        </div>
                        <div class="record-detail-row">
                            <span class="record-detail-label">IP address</span>
                            <span class="record-detail-value font-data" x-text="modalLog.ip_address"></span>
                        </div>
                        <div class="record-detail-row">
                            <span class="record-detail-label">Timestamp</span>
                            <span class="record-detail-value font-data tabnum" x-text="modalLog.created_at"></span>
                        </div>
                        <div class="record-detail-row">
                            <span class="record-detail-label">Description</span>
                            <span class="record-detail-value" x-text="modalLog.description"></span>
                        </div>
                        <div class="record-detail-row">
                            <span class="record-detail-label">User agent</span>
                            <span class="record-detail-value !font-normal text-[12px] text-muted font-data break-words" x-text="modalLog.user_agent"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                        <div>
                            <p class="kv-label">Old values</p>
                            <pre class="bg-stone-900 text-stone-200 rounded-xl p-3.5 font-mono text-xs leading-relaxed overflow-x-auto max-h-52 custom-scrollbar" x-text="JSON.stringify(modalLog.old_values, null, 2)"></pre>
                        </div>
                        <div>
                            <p class="kv-label">New values</p>
                            <pre class="bg-stone-900 text-stone-200 rounded-xl p-3.5 font-mono text-xs leading-relaxed overflow-x-auto max-h-52 custom-scrollbar" x-text="JSON.stringify(modalLog.new_values, null, 2)"></pre>
                        </div>
                    </div>
                </div>
            </template>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" @click="showModal = false">Close</button>
            </div>
        </div>
    </div>
</div>
