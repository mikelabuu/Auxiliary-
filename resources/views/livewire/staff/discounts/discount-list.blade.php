<div wire:poll.60s class="space-y-4">

    {{-- Filter & Sort --}}
    <form class="flex flex-wrap items-center gap-3 mb-4">
        <div class="flex flex-col">
            <label for="status" class="font-medium text-gray-700 mb-1">Status:</label>
            <select wire:model.live="status" id="status" class="border rounded-lg px-3 py-2">
                <option value="">All</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>

        <div class="flex flex-col">
            <label for="sort" class="font-medium text-gray-700 mb-1">Sort by:</label>
            <select wire:model.live="sort" id="sort" class="border rounded-lg px-3 py-2">
                <option value="">Latest</option>
                <option value="oldest">Oldest</option>
            </select>
        </div>
    </form>

    {{-- Table Container --}}
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">ID</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Guest</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Booking</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Submitted</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($discounts as $discount)
                    <tr>
                        <td class="px-4 py-2 font-medium">#{{ $discount->id }}</td>
                        <td class="px-4 py-2">{{ $discount->booking->guest_name }}</td>
                        <td class="px-4 py-2">Booking #{{ $discount->booking->id }}</td>
                        <td class="px-4 py-2">{{ $discount->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-2">
                            @php
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800'
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$discount->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($discount->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <a href="{{ route('staff.discounts.show', $discount->id) }}"
                                       class="btn btn-view review-discount"
                                       data-discount-id="{{ $discount->id }}">
                                        Review
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-4 text-center text-gray-500">No discount requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="pagination-wrapper mt-6 flex flex-col items-center space-y-2">
        <div>
            {{-- Render only pagination links (no extra text) --}}
            {{ $discounts->links('vendor.pagination.simple-tailwind') }}
        </div>

        <div class="text-gray-400 text-sm">
            Showing {{ $discounts->firstItem() }} to {{ $discounts->lastItem() }} of {{ $discounts->total() }} results
        </div>
    </div>

</div>
