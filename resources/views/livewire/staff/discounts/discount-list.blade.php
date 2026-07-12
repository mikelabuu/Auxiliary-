<div wire:poll.60s>
    @php
        $statusMeta = [
            'pending'  => ['badge' => 'bg-palay-100 text-palay-800 border-palay-200', 'dot' => 'bg-palay-500'],
            'approved' => ['badge' => 'bg-clsu-50 text-clsu-700 border-clsu-200',     'dot' => 'bg-clsu-500'],
            'rejected' => ['badge' => 'bg-ember-50 text-ember-700 border-ember-200',  'dot' => 'bg-ember-500'],
        ];
        $tabs = [
            ''         => 'All requests',
            'pending'  => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
    @endphp

    <x-admin.ui.section-card icon="tag" title="Verification Queue" :subtitle="$discounts->total() . ' request' . ($discounts->total() === 1 ? '' : 's') . ' · refreshes automatically'" :delay="40">

        <!-- Status pills + sort -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div class="flex flex-wrap gap-2">
                @foreach ($tabs as $key => $label)
                    <button type="button" wire:click="$set('status', '{{ $key }}')"
                            class="flex items-center gap-1.5 text-sm font-medium px-4 py-2.5 rounded-xl border transition-colors cursor-pointer {{ $status === $key ? 'bg-clsu-600 border-clsu-700 text-white shadow-card' : 'border-stone-200 bg-white text-stone-600 hover:bg-stone-50' }}">
                        {{ $label }}
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none {{ $status === $key ? 'bg-white/15 text-white' : 'bg-stone-100 text-stone-500' }}">{{ $counts[$key] ?? 0 }}</span>
                    </button>
                @endforeach
            </div>

            <select wire:model.live="sort" class="w-full sm:w-48 px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-700 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 cursor-pointer transition-colors">
                <option value="">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="checkin">Soonest check-in</option>
            </select>
        </div>

        @if($discounts->isEmpty())
            <x-admin.ui.empty-state icon="tag" title="No discount requests in this view." />
        @else
            <div class="-mx-6 border-t border-stone-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-stone-50/70 border-b border-stone-100">
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Request</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Guest</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Booking</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Check-in</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">IDs</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Submitted</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Status</th>
                            <th class="text-right font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($discounts as $discount)
                            @php $meta = $statusMeta[$discount->status] ?? ['badge' => 'bg-stone-100 text-stone-600 border-stone-200', 'dot' => 'bg-stone-400']; @endphp
                            <tr class="border-b border-stone-100 hover:bg-clsu-50/40 transition-colors">
                                <td class="px-6 py-3 text-stone-700 font-data tabnum">#{{ $discount->id }}</td>
                                <td class="px-6 py-3 text-stone-800 font-medium">{{ $discount->booking->guest_name }}</td>
                                <td class="px-6 py-3 text-stone-700 font-data tabnum">#{{ $discount->booking->id }}</td>
                                <td class="px-6 py-3 text-stone-700 font-data tabnum whitespace-nowrap">{{ \Carbon\Carbon::parse($discount->booking->check_in)->format('M d, Y') }}</td>
                                <td class="px-6 py-3 text-stone-600 font-data tabnum">{{ $discount->files->count() }}</td>
                                <td class="px-6 py-3 text-stone-500 font-data tabnum whitespace-nowrap">{{ $discount->created_at->format('M d, Y · h:i A') }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $meta['badge'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                                        {{ ucfirst($discount->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('staff.discounts.show', $discount->id) }}"
                                       class="review-discount inline-flex items-center gap-1.5 rounded-xl border border-clsu-200 bg-white px-3.5 py-2 text-xs font-semibold text-clsu-700 transition-colors hover:bg-clsu-50 hover:border-clsu-300 !no-underline"
                                       data-discount-id="{{ $discount->id }}">
                                        <x-admin.ui.icon name="eye" class="w-3.5 h-3.5" />
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex flex-col items-center gap-2">
                {{ $discounts->links('vendor.pagination.simple-tailwind') }}
                <p class="text-xs text-stone-400">Showing {{ $discounts->firstItem() }}–{{ $discounts->lastItem() }} of {{ $discounts->total() }} requests</p>
            </div>
        @endif
    </x-admin.ui.section-card>
</div>
