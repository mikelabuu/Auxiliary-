<div x-data="{ showModal: false, modalLog: null }">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold text-stone-800">Audit Logs Listing</h2>
        <div class="text-sm text-gray-500">Total: {{ $logs->total() }} logs</div>
    </div>

    {{-- Table Buttons (filters by target_type) --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @php
            $buttons = [
                'all' => 'All',
                'bookings' => 'Bookings',
                'discounts' => 'Discounts',
                'payments' => 'Payments',
                'users' => 'Users',
                'staff' => 'Staff',
                'rooms' => 'Rooms',
                'unsorted' => 'Unsorted',
            ];
        @endphp

        @foreach($buttons as $key => $label)
            <button type="button" wire:click="$set('table', '{{ $key === 'all' ? '' : $key }}')"
               class="px-3 py-1 rounded-md border text-sm font-semibold transition-all cursor-pointer {{ (($table === $key) || ($table === '' && $key === 'all')) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Filters row --}}
    <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2">
        <div>
            <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1">Search</label>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search description, staff, id, ip..."
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" />
        </div>

        <div>
            <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1">Role</label>
            <select wire:model.live="role" class="w-full border border-gray-300 rounded px-3 py-2 text-sm cursor-pointer focus:border-blue-500 outline-none">
                <option value="">All roles</option>
                @foreach($availableRoles as $r)
                    <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1">Sort by</label>
            <select wire:model.live="sort" class="w-full border border-gray-300 rounded px-3 py-2 text-sm cursor-pointer focus:border-blue-500 outline-none">
                <option value="latest">Latest</option>
                <option value="oldest">Oldest</option>
                <option value="role">By Role</option>
                <option value="target">By Table</option>
            </select>
        </div>

        <div>
            <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1">Date Range</label>
            <div class="flex gap-2">
                <input type="date" wire:model.live="from" class="border border-gray-300 rounded px-3 py-2 text-sm w-1/2 focus:border-blue-500 outline-none" />
                <input type="date" wire:model.live="to" class="border border-gray-300 rounded px-3 py-2 text-sm w-1/2 focus:border-blue-500 outline-none" />
            </div>
        </div>

        <div class="md:col-span-4 flex items-center gap-2 mt-2">
            @if($search || $table !== '' || $role !== '' || $action !== '' || $sort !== 'latest' || $from !== '' || $to !== '')
                <button type="button" wire:click="resetFilters" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded transition-all cursor-pointer">Reset Filters</button>
            @endif

            <div class="ml-auto flex items-center gap-2">
                <label class="text-sm text-gray-600">Per page</label>
                <select wire:model.live="perPage" class="border border-gray-300 rounded px-2 py-1 text-sm focus:border-blue-500 outline-none cursor-pointer">
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Logs table --}}
    <div class="bg-white shadow rounded overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-stone-500 uppercase tracking-wider text-[11px]">Timestamp</th>
                        <th class="px-4 py-3 text-left font-bold text-stone-500 uppercase tracking-wider text-[11px]">Staff</th>
                        <th class="px-4 py-3 text-left font-bold text-stone-500 uppercase tracking-wider text-[11px]">Role</th>
                        <th class="px-4 py-3 text-left font-bold text-stone-500 uppercase tracking-wider text-[11px]">Action</th>
                        <th class="px-4 py-3 text-left font-bold text-stone-500 uppercase tracking-wider text-[11px]">Target</th>
                        <th class="px-4 py-3 text-left font-bold text-stone-500 uppercase tracking-wider text-[11px]">Description</th>
                        <th class="px-4 py-3 text-left font-bold text-stone-500 uppercase tracking-wider text-[11px]">IP</th>
                        <th class="px-4 py-3 text-left font-bold text-stone-500 uppercase tracking-wider text-[11px]">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50/55 transition-colors">
                        <td class="px-4 py-2.5 text-stone-500 whitespace-nowrap">{{ $log->created_at->timezone('Asia/Manila')->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-2.5 font-semibold text-stone-700">{{ $log->staff?->name ?? 'System' }}</td>
                        <td class="px-4 py-2.5 text-stone-500">{{ $log->role ?? '-' }}</td>
                        <td class="px-4 py-2.5"><span class="font-bold text-blue-700">{{ $log->action }}</span></td>
                        <td class="px-4 py-2.5 text-stone-600 whitespace-nowrap">
                            @if($log->target_type)
                                {{ \Illuminate\Support\Str::afterLast($log->target_type, '\\') }}{{ $log->target_id ? " (#{$log->target_id})" : '' }}
                            @else
                                Unsorted
                            @endif
                        </td>
                        <td class="px-4 py-2.5 truncate max-w-xs text-stone-600" title="{{ $log->description }}">{{ $log->description }}</td>
                        <td class="px-4 py-2.5 text-stone-500 font-mono text-xs">{{ $log->ip_address }}</td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <button
                                type="button"
                                @click.prevent="
                                    fetch('{{ route('staff.audit.show', $log->id) }}')
                                      .then(r => r.json())
                                      .then(j => { modalLog = j.log; modalLog.old_values = j.old_values; modalLog.new_values = j.new_values; showModal = true; })
                                "
                                class="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs font-bold text-stone-700 cursor-pointer transition-colors">View</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-center text-stone-400" colspan="8">No logs found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 flex items-center justify-between border-t border-gray-200">
            <div class="text-sm text-gray-600">
                Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} logs
            </div>
            <div>
                {{ $logs->links() }}
            </div>
        </div>
    </div>

    {{-- Modal (Alpine.js) --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.away="showModal = false" class="bg-white w-full max-w-3xl rounded-xl shadow-lg p-6 overflow-auto max-h-[85vh] border border-stone-200">
            <div class="flex items-start justify-between mb-4 border-b border-stone-100 pb-3">
                <h3 class="text-lg font-bold text-stone-800">Audit Log Details</h3>
                <button type="button" @click="showModal = false" class="text-stone-400 hover:text-stone-600 text-lg cursor-pointer">✕</button>
            </div>

            <template x-if="modalLog">
                <div class="space-y-4 text-sm text-stone-700">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider font-bold">Action</span>
                            <span class="font-bold text-blue-700 text-base" x-text="modalLog.action"></span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider font-bold">Performed By</span>
                            <span x-text="modalLog.staff ? (modalLog.staff.name + ' (' + (modalLog.staff.role ?? '') + ')') : 'System'"></span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider font-bold">Target</span>
                            <span x-text="modalLog.target_type ? modalLog.target_type.split('\\\\').pop() + (modalLog.target_id ? ' (#' + modalLog.target_id + ')' : '') : 'Unsorted'"></span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider font-bold">IP Address</span>
                            <span class="font-mono text-xs" x-text="modalLog.ip_address"></span>
                        </div>
                    </div>

                    <div>
                        <span class="block text-[9px] text-stone-400 uppercase tracking-wider font-bold mb-1">Description</span>
                        <div class="bg-stone-50 border border-stone-100 rounded-xl p-3 text-stone-600 font-semibold" x-text="modalLog.description"></div>
                    </div>

                    <div>
                        <span class="block text-[9px] text-stone-400 uppercase tracking-wider font-bold mb-1">User Agent</span>
                        <div class="bg-stone-50 border border-stone-100 rounded-xl p-3 text-stone-500 font-mono text-xs break-words" x-text="modalLog.user_agent"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider font-bold mb-1">Old Values</span>
                            <pre class="bg-stone-900 text-stone-200 border border-stone-850 rounded-xl p-3 font-mono text-xs overflow-x-auto max-h-48" x-text="JSON.stringify(modalLog.old_values, null, 2)"></pre>
                        </div>

                        <div>
                            <span class="block text-[9px] text-stone-400 uppercase tracking-wider font-bold mb-1">New Values</span>
                            <pre class="bg-stone-900 text-stone-200 border border-stone-850 rounded-xl p-3 font-mono text-xs overflow-x-auto max-h-48" x-text="JSON.stringify(modalLog.new_values, null, 2)"></pre>
                        </div>
                    </div>

                    <div class="border-t border-stone-100 pt-3 flex justify-between items-center text-xs text-stone-400">
                        <div>
                            <span>Timestamp:</span>
                            <span x-text="modalLog.created_at"></span>
                        </div>
                        <button type="button" @click="showModal = false" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 font-bold rounded-xl transition-all cursor-pointer">Close</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
