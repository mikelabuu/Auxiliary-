@extends('layouts.admin')
@section('title', 'Admin - User Hub')
@section('page-title', 'User Hub')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
/* SweetAlert2 override for this page */
.swal2-container {
    position: fixed !important;
    top: 0;
    left: 0;
    height: 100vh !important;
    width: 100vw !important;
    z-index: 999999 !important; /* ensure it's above everything */
}

.swal2-popup {
    max-width: 450px !important;
}
</style>
@php
    $statusTabs = [
        'all'       => ['label' => 'All users',  'count' => $stats['total']],
        'active'    => ['label' => 'Active',     'count' => $stats['active']],
        'suspended' => ['label' => 'Suspended',  'count' => $stats['suspended']],
    ];
@endphp

<div class="space-y-6 max-w-[1680px] mx-auto">

    <x-admin.ui.page-header subtitle="Every guest account on the platform — activity, verification, and standing.">
        User Hub
    </x-admin.ui.page-header>

    <!-- Account stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <x-admin.ui.stat-card icon="users" badge="ALL TIME" label="Registered Users" :delay="40" dark>
            {{ number_format($stats['total']) }}
            <x-slot:footnote><p class="text-xs text-clsu-300">{{ $stats['new_this_month'] }} joined this month</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="check-circle" badge="IN GOOD STANDING" label="Active Accounts" :delay="80">
            {{ number_format($stats['active']) }}
            <x-slot:footnote><p class="text-xs text-faint">Able to book and log in</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="check" color="palay" badge="EMAIL" label="Verified Emails" :delay="120">
            {{ number_format($stats['verified']) }}
            <x-slot:footnote><p class="text-xs text-faint">{{ $stats['total'] - $stats['verified'] }} still unverified</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="block" color="ember" badge="RESTRICTED" label="Suspended" :delay="160">
            {{ number_format($stats['suspended']) }}
            <x-slot:footnote><p class="text-xs text-faint">Blocked from booking</p></x-slot:footnote>
        </x-admin.ui.stat-card>
    </div>

    <x-admin.ui.section-card icon="users" title="User Directory" :subtitle="$users->total() . ' record' . ($users->total() === 1 ? '' : 's') . ($search ? ' matching “' . $search . '”' : '')" :delay="200">
        {{-- The exports sit on the card holding the data they export, the same
             way the booking log carries its own. In the page header they read
             as belonging to the screen, and the three of them at full size were
             the loudest thing on it.

             data-no-loader: these serve a spreadsheet download and leave the
             page where it is, so the navigation curtain
             (partials/page-loader) must not be raised for them. --}}
        <x-slot:actions>
            <span class="section-label hidden sm:inline">Export</span>
            <x-admin.ui.button variant="secondary" size="sm" data-no-loader :href="route('reports.users.all')">
                <x-admin.ui.icon name="download" class="w-3.5 h-3.5" />
                All
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" size="sm" data-no-loader :href="route('reports.users.active')">
                <x-admin.ui.icon name="download" class="w-3.5 h-3.5" />
                Active
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" size="sm" data-no-loader :href="route('reports.users.suspended')">
                <x-admin.ui.icon name="download" class="w-3.5 h-3.5" />
                Suspended
            </x-admin.ui.button>
        </x-slot:actions>

        {{-- Status filters --}}
        <div class="filter-row mb-4">
            <span class="filter-row-label">Standing</span>
            @foreach ($statusTabs as $key => $meta)
                <a href="{{ route('staff.userrecords.index', array_filter(['status' => $key, 'search' => $search, 'sort' => $sort])) }}"
                   @class(['filter-tab', 'selected' => $status === $key])>
                    {{ $meta['label'] }}
                    <span class="ft-count">{{ $meta['count'] }}</span>
                </a>
            @endforeach
        </div>

        {{-- Search + sort --}}
        <form method="GET" class="filter-toolbar">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="filter-search">
                <x-admin.ui.icon name="search" class="w-4 h-4" stroke-width="2" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Username, email, or phone…" aria-label="Search users">
            </div>
            <select name="sort" class="filter-select" aria-label="Sort order">
                <option value="latest" @selected($sort === 'latest')>Newest first</option>
                <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
                <option value="stays" @selected($sort === 'stays')>Most stays</option>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
            <div class="filter-toolbar-spacer"></div>
            <x-admin.ui.density-switch />
            @if($search || $status !== 'all' || $sort !== 'latest')
                <a href="{{ route('staff.userrecords.index') }}" class="filter-clear">
                    <x-admin.ui.icon name="x" class="w-3 h-3" stroke-width="2.5" /> Clear
                </a>
            @endif
        </form>

        @if($users->isEmpty())
            <x-admin.ui.empty-state icon="users" title="No users match this view." />
        @else
            {{-- table-fold is the query container for the folded layout (see
                 20-table-fold.css). It wraps .scroll-x rather than sitting on
                 it: container-type applies containment, which would disturb
                 the sticky Actions column living inside the scroll region. --}}
            <div class="table-fold">
            <div class="scroll-x -mx-6 -mb-6 border-t border-stone-100">
                <table class="data-table data-table-records">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th class="col-fold">Phone</th>
                            <th class="col-fold">Email Status</th>
                            <th class="text-right">Stays</th>
                            <th>Standing</th>
                            <th class="col-fold">Last Login</th>
                            <th>Joined</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <div class="cell-name">
                                        <x-admin.ui.avatar :state="$user->is_suspended ? 'suspended' : null" />
                                        <div class="cell-name-text">
                                            <p class="cell-name-primary truncate">{{ $user->username }}</p>
                                            <p class="cell-name-secondary truncate">{{ $user->email }}</p>
                                            {{-- The folded-away columns reappear here, so narrowing
                                                 the table hides no information. --}}
                                            <span class="cell-name-secondary fold-show">{{ $user->phone ?? 'No phone' }}</span>
                                            @if($user->email_verified_at)
                                                <span class="status status-success fold-show">Verified</span>
                                            @else
                                                <span class="status status-neutral fold-show">Unverified</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="font-data tabnum whitespace-nowrap text-muted col-fold">{{ $user->phone ?? '—' }}</td>
                                <td class="col-fold">
                                    @if($user->email_verified_at)
                                        <span class="status status-success">Verified</span>
                                    @else
                                        <span class="status status-neutral">Unverified</span>
                                    @endif
                                </td>
                                <td class="text-right font-data tabnum font-semibold">{{ $user->completed_bookings_count }}</td>
                                <td>
                                    @if($user->is_suspended)
                                        <span class="status status-cancelled">Suspended</span>
                                    @else
                                        <span class="status status-active">Active</span>
                                    @endif
                                </td>
                                <td class="font-data tabnum text-xs whitespace-nowrap text-faint col-fold">
                                    {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->timezone(config('hostel.timezone'))->format('M d, Y · h:i A') : '—' }}
                                </td>
                                <td class="font-data tabnum text-xs whitespace-nowrap text-faint">
                                    {{ $user->created_at->timezone(config('hostel.timezone'))->format('M d, Y') }}
                                    {{-- Last login travels with the date it belongs beside. Kept
                                         terse (no year, no "last") because this cell is
                                         whitespace-nowrap: every extra word here widens the
                                         column and pushes it back under the pinned Actions. --}}
                                    <span class="fold-show fold-sub">
                                        {{ $user->last_login_at ? 'Seen ' . \Carbon\Carbon::parse($user->last_login_at)->timezone(config('hostel.timezone'))->format('M d') : 'Never seen' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="table-actions justify-end">
                                        <button class="view-user-btn btn btn-ghost btn-sm btn-icon cursor-pointer"
                                                data-user-id="{{ $user->id }}"
                                                title="View details" aria-label="View details">
                                            <x-admin.ui.icon name="eye" class="w-4 h-4" />
                                        </button>
                                        @if(!$user->is_suspended)
                                            <button class="password-verify-btn btn btn-danger btn-sm cursor-pointer"
                                                    data-action="suspend"
                                                    data-user-id="{{ $user->id }}">
                                                Suspend
                                            </button>
                                        @else
                                            <button class="password-verify-btn btn btn-outline btn-sm cursor-pointer"
                                                    data-action="unsuspend"
                                                    data-user-id="{{ $user->id }}">
                                                Unsuspend
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>

            <div class="mt-6">
                {{ $users->links('vendor.pagination.admin', ['mode' => 'links']) }}
            </div>
        @endif
    </x-admin.ui.section-card>

    {{-- User detail modal (populated via AJAX) --}}
    <x-admin.ui.modal id="userDetailModal" icon="user" title="User Details" max-width="xl" scroll-body>
        <div id="udLoading" class="p-10 text-center text-sm text-faint">Loading user details…</div>

        <div id="udBody" class="hidden">
            <div class="p-6 space-y-5">
                {{-- Identity row --}}
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div id="udAvatar" class="w-12 h-12 rounded-full shrink-0 flex items-center justify-center text-base font-bold bg-clsu-100 text-clsu-700"></div>
                        <div class="min-w-0">
                            <p id="udName" class="font-bold text-stone-900 truncate"></p>
                            <p id="udEmail" class="text-xs text-faint truncate"></p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-1.5">
                        <span id="udStanding" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-2xs font-bold border"></span>
                        <span id="udVerifiedPill" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-2xs font-bold border"></span>
                    </div>
                </div>

                {{-- Booking stats --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-3 text-center">
                        <p id="udStatTotal" class="text-lg font-bold font-data tabnum text-stone-900"></p>
                        <p class="text-2xs font-bold uppercase tracking-widest text-faint mt-0.5">Bookings</p>
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-3 text-center">
                        <p id="udStatCompleted" class="text-lg font-bold font-data tabnum text-clsu-700"></p>
                        <p class="text-2xs font-bold uppercase tracking-widest text-faint mt-0.5">Stays</p>
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-3 text-center">
                        <p id="udStatCancelled" class="text-lg font-bold font-data tabnum text-ember-700"></p>
                        <p class="text-2xs font-bold uppercase tracking-widest text-faint mt-0.5">Cancelled</p>
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-3 text-center">
                        <p id="udStatSpend" class="text-lg font-bold font-data tabnum text-stone-900"></p>
                        <p class="text-2xs font-bold uppercase tracking-widest text-faint mt-0.5">Spent</p>
                    </div>
                </div>

                {{-- Profile facts --}}
                <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <p class="text-2xs font-bold uppercase tracking-widest text-faint">Phone</p>
                        <p id="udPhone" class="text-stone-700 font-data tabnum mt-0.5"></p>
                    </div>
                    <div>
                        <p class="text-2xs font-bold uppercase tracking-widest text-faint">Joined</p>
                        <p id="udJoined" class="text-stone-700 font-data tabnum mt-0.5"></p>
                    </div>
                    <div>
                        <p class="text-2xs font-bold uppercase tracking-widest text-faint">Last Login</p>
                        <p id="udLastLogin" class="text-stone-700 font-data tabnum mt-0.5"></p>
                    </div>
                    <div>
                        <p class="text-2xs font-bold uppercase tracking-widest text-faint">Last Cancellation</p>
                        <p id="udLastCancelled" class="text-stone-700 font-data tabnum mt-0.5"></p>
                    </div>
                </div>

                {{-- Recent bookings --}}
                <div>
                    <p class="text-2xs font-bold uppercase tracking-widest text-faint mb-2">Recent Bookings</p>
                    <div id="udBookings" class="divide-y divide-stone-100 rounded-xl border border-stone-200 overflow-hidden"></div>
                    <p id="udNoBookings" class="hidden text-sm text-faint text-center py-4">No bookings yet.</p>
                </div>
            </div>

            {{-- Footer actions --}}
            <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-stone-100 bg-stone-50/50 px-6 py-4">
                <button type="button" id="udVerifyBtn" class="hidden text-xs font-semibold text-palay-800 border border-palay-200 bg-palay-50 rounded-lg px-3 py-2 hover:bg-palay-100 transition-colors cursor-pointer">
                    Mark Email Verified
                </button>
                <button type="button" id="udSuspendBtn" class="password-verify-btn text-xs font-semibold border bg-white rounded-lg px-3 py-2 transition-colors cursor-pointer"></button>
                <button type="button" data-modal-close="userDetailModal" class="text-xs font-semibold text-stone-600 border border-stone-200 bg-white rounded-lg px-3 py-2 hover:bg-stone-50 transition-colors cursor-pointer">Close</button>
            </div>
        </div>
    </x-admin.ui.modal>
</div>
@endsection

{{-- Behaviour: resources/js/pages/admin-user-records.js (bundled via admin.js) --}}
@push('scripts')
<script type="application/json" id="admin-user-records-data">@json([
    'base' => url('staff/user-records'),
])</script>
@endpush
