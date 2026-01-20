@extends('layouts.admin') {{-- Replace with your staff layout --}}
@section('title', 'Admin - Audit Logs')
@section('page-title', 'Audit Logs')

@section('content')
<div class="container mx-auto p-4" x-data="{ showModal: false, modalLog: null }">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">Audit Logs</h1>
        <div class="text-sm text-gray-500">Total: {{ $logs->total() }} logs</div>
    </div>
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
            <a href="{{ route('staff.audit.index', array_merge(request()->except('page'), ['table' => $key === 'all' ? null : $key])) }}"
               class="px-3 py-1 rounded-md border {{ (request('table') === $key || (empty(request('table')) && $key === 'all')) ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Filters row --}}
    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2">
        <div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description, staff, id, ip..."
                class="w-full border rounded px-3 py-2" />
        </div>

        <div>
            <select name="role" class="w-full border rounded px-3 py-2">
                <option value="">All roles</option>
                @foreach($availableRoles as $r)
                    <option value="{{ $r }}" {{ request('role') == $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <select name="sort" class="w-full border rounded px-3 py-2">
                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest</option>
                <option value="role" {{ request('sort') == 'role' ? 'selected' : '' }}>By Role</option>
                <option value="target" {{ request('sort') == 'target' ? 'selected' : '' }}>By Table</option>
            </select>
        </div>

        <div class="flex gap-2">
            <input type="date" name="from" value="{{ request('from') }}" class="border rounded px-3 py-2 w-1/2" />
            <input type="date" name="to" value="{{ request('to') }}" class="border rounded px-3 py-2 w-1/2" />
        </div>

        <div class="md:col-span-4 flex gap-2 mt-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Apply</button>
            <a href="{{ route('staff.audit.index') }}" class="px-4 py-2 bg-gray-100 rounded">Reset</a>

            <div class="ml-auto">
                <label class="text-sm text-gray-600 mr-2">Per page</label>
                <select name="per_page" onchange="this.form.submit()" class="border rounded px-2 py-1">
                    <option value="15" {{ request('per_page')==15 ? 'selected':'' }}>15</option>
                    <option value="30" {{ request('per_page')==30 ? 'selected':'' }}>30</option>
                    <option value="50" {{ request('per_page')==50 ? 'selected':'' }}>50</option>
                </select>
            </div>
        </div>
    </form>

    {{-- Logs table --}}
    <div class="bg-white shadow rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left">Timestamp</th>
                    <th class="px-4 py-2 text-left">Staff</th>
                    <th class="px-4 py-2 text-left">Role</th>
                    <th class="px-4 py-2 text-left">Action</th>
                    <th class="px-4 py-2 text-left">Target</th>
                    <th class="px-4 py-2 text-left">Description</th>
                    <th class="px-4 py-2 text-left">IP</th>
                    <th class="px-4 py-2 text-left">Details</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $log->created_at->timezone('Asia/Manila')->format('Y-m-d H:i:s') }}</td>
                    <td class="px-4 py-2">{{ $log->staff?->name ?? 'System' }}</td>
                    <td class="px-4 py-2">{{ $log->role ?? '-' }}</td>
                    <td class="px-4 py-2"><span class="font-medium">{{ $log->action }}</span></td>
                    <td class="px-4 py-2">
                        @if($log->target_type)
                            {{-- Show short target type --}}
                            {{ \Illuminate\Support\Str::afterLast($log->target_type, '\\') }}{{ $log->target_id ? " (#{$log->target_id})" : '' }}
                        @else
                            Unsorted
                        @endif
                    </td>
                    <td class="px-4 py-2 truncate max-w-md" title="{{ $log->description }}">{{ $log->description }}</td>
                    <td class="px-4 py-2">{{ $log->ip_address }}</td>
                    <td class="px-4 py-2">
                        <button
                            @click.prevent="
                                fetch('{{ route('staff.audit.show', $log->id) }}')
                                  .then(r => r.json())
                                  .then(j => { modalLog = j.log; modalLog.old_values = j.old_values; modalLog.new_values = j.new_values; showModal = true; })
                            "
                            class="px-2 py-1 bg-gray-100 rounded text-xs">View</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-6 text-center" colspan="8">No logs found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="p-4 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} logs
            </div>
            <div>
                {{-- Render only pagination links (no extra text) --}}
                {{ $logs->links('vendor.pagination.simple-tailwind') }}
            </div>
        </div>
    </div>

    {{-- Modal (Alpine.js) --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.away="showModal = false" class="bg-white w-full max-w-3xl rounded shadow p-4 overflow-auto max-h-[80vh]">
            <div class="flex items-start justify-between mb-4">
                <h2 class="text-lg font-semibold">Audit Log Details</h2>
                <button @click="showModal = false" class="text-gray-500 hover:text-gray-700">Close</button>
            </div>

            <template x-if="modalLog">
                <div class="space-y-3 text-sm">
                    <div><strong>Action:</strong> <span x-text="modalLog.action"></span></div>
                    <div><strong>Performed by:</strong> <span x-text="modalLog.staff ? (modalLog.staff.name + ' (' + (modalLog.staff.role ?? '') + ')') : 'System'"></span></div>
                    <div><strong>Target:</strong>
                        <span x-text="modalLog.target_type ? modalLog.target_type.split('\\\\').pop() + (modalLog.target_id ? ' (#' + modalLog.target_id + ')' : '') : 'Unsorted'"></span>
                    </div>
                    <div><strong>Description:</strong> <span x-text="modalLog.description"></span></div>
                    <div><strong>IP:</strong> <span x-text="modalLog.ip_address"></span></div>
                    <div><strong>User agent:</strong> <div x-text="modalLog.user_agent" class="break-words"></div></div>

                    <div>
                        <strong>Old values:</strong>
                        <pre x-text="JSON.stringify(modalLog.old_values, null, 2)"></pre>
                    </div>

                    <div>
                        <strong>New values:</strong>
                        <pre x-text="JSON.stringify(modalLog.new_values, null, 2)"></pre>
                    </div>

                    <div><strong>Timestamp:</strong> <span x-text="modalLog.created_at"></span></div>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- Alpine.js (if not present in layout) --}}
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection
