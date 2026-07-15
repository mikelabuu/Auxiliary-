<x-admin.ui.section-card icon="check-circle" title="Completed Bookings" :subtitle="$bookings->total() . ' total'" :delay="40">
    {{-- Search + Sort toolbar --}}
    <div class="filter-toolbar">
        <div class="filter-search">
            <x-admin.ui.icon name="search" class="w-4 h-4" stroke-width="2" />
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search booking ID or guest…" aria-label="Search completed bookings">
        </div>
        <select wire:model.live="sort" class="filter-select" aria-label="Sort order">
            <option value="latest">Newest first</option>
            <option value="oldest">Oldest first</option>
        </select>
        <div class="filter-toolbar-spacer"></div>
        @if($search)
            <button type="button" wire:click="resetFilters" class="filter-clear">
                <x-admin.ui.icon name="x" class="w-3 h-3" stroke-width="2.5" /> Clear
            </button>
        @endif
    </div>

    @if($bookings->isEmpty())
        <x-admin.ui.empty-state icon="check-circle" title="No completed bookings found." />
    @else
        <div class="scroll-x -mx-6 -mb-6 border-t border-stone-100">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ref code</th>
                        <th>Guest</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $booking)
                        @php
                            $initials = collect(explode(' ', trim($booking->guest_name)))
                                ->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
                            $initials = strtoupper($initials ?: 'G');
                        @endphp
                        <tr>
                            <td>
                                <div class="ref-cell">
                                    <div class="ref-cell-top">
                                        <span class="ref-code">BK-{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</span>
                                        <button type="button" class="copy-ref" data-copy="BK-{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}" title="Copy reference">
                                            <x-admin.ui.icon name="clipboard" class="w-3 h-3" /> Copy
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-name">
                                    <span class="avatar-initials">{{ $initials }}</span>
                                    <div class="cell-name-text">
                                        <p class="cell-name-primary" title="{{ $booking->guest_name }}">{{ $booking->guest_name }}</p>
                                        <p class="cell-name-secondary">#{{ $booking->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="font-data tabnum">{{ $booking->check_in->format('M d, Y') }}</td>
                            <td class="font-data tabnum">{{ $booking->check_out->format('M d, Y') }}</td>
                            <td><span class="status status-completed">Completed</span></td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="password-verify btn btn-outline btn-sm cursor-pointer" data-id="{{ $booking->id }}">
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

        <div class="mt-6">
            {{ $bookings->links() }}
        </div>
    @endif
</x-admin.ui.section-card>
