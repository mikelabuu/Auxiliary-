/**
 * Admin dashboard (staff/dashboard/index) — the live Room Status Map.
 *
 * Was ~145 lines inline in the Blade view, plus a stray $.ajaxSetup block and a
 * duplicate <meta name="csrf-token"> in the content section (the layout already
 * emits one in <head>).
 *
 * Runs immediately rather than on jQuery ready: admin.js is a deferred module,
 * so the DOM is fully parsed by the time this executes, and $.ajaxSetup then
 * lands before any ready callback registered by an inline body script.
 *
 * Depends on jQuery, and optionally window.Echo / window.Livewire.
 * No-ops off this page.
 */

function initAdminDashboard() {
    const dataEl = document.getElementById('admin-dashboard-data');
    if (!dataEl) return;

    const ROUTES = JSON.parse(dataEl.textContent);

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // ── Real-time Room Status Map ───────────────────────────────────────────
    // Targets .room-map-btn (original flat buttons).
    // Also keeps proportional legend bars (data-map-fill) in sync.
    if (!document.querySelector('.room-map-btn')) return;

    // Must stay in step with the same maps in components/admin/rooms/map-tile.
    const CLASSES = {
        available:   'bg-clsu-50 text-clsu-800 border-clsu-200 hover:bg-clsu-100 hover:border-clsu-300 border-solid',
        occupied:    'bg-clsu-700 text-white border-clsu-700 hover:bg-clsu-800 border-solid',
        reserved:    'bg-palay-100 text-palay-800 border-palay-300 hover:bg-palay-200 border-dashed',
        pending:     'bg-palay-50 text-palay-700 border-palay-400 hover:bg-palay-100 border-dashed',
        cleaning:    'bg-sky-50 text-sky-800 border-sky-300 hover:bg-sky-100 hover:border-sky-400 border-dotted',
        maintenance: 'bg-ember-50 text-ember-800 border-ember-300 hover:bg-ember-100 border-double border-[3px]',
    };
    const STATUS_LABELS = {
        available: 'Available', occupied: 'Occupied', reserved: 'Reserved',
        pending: 'Reserved · awaiting payment',
        cleaning: 'Cleaning', maintenance: 'Maintenance',
    };
    const cap = s => s ? s.charAt(0).toUpperCase() + s.slice(1) : s;

    function patchButton(btn, status, occupant, updatedAt) {
        const prev = btn.dataset.displayStatus;
        if (prev === status && (btn.dataset.occupant || '') === (occupant || '')) return;

        // Swap Tailwind classes
        if (prev && CLASSES[prev]) {
            CLASSES[prev].split(/\s+/).forEach(c => btn.classList.remove(c));
        }
        if (CLASSES[status]) {
            CLASSES[status].split(/\s+/).forEach(c => btn.classList.add(c));
        }
        btn.dataset.displayStatus = status;

        // Occupant data attr
        if (occupant) { btn.dataset.occupant = occupant; } else { delete btn.dataset.occupant; }

        // Update status dot/shape class for accessibility
        const dot = btn.querySelector('[data-status-dot]');
        if (dot) {
            dot.className = 'absolute top-1.5 right-1.5';
            if (status === 'available') {
                dot.className += ' w-1.5 h-1.5 rounded-full border border-clsu-400';
            } else if (status === 'occupied') {
                dot.className += ' w-1.5 h-1.5 rounded-full bg-white';
            } else if (status === 'reserved') {
                dot.className += ' w-1.5 h-1.5 rounded-full border border-dashed border-palay-500';
            } else if (status === 'pending') {
                dot.className += ' w-1.5 h-1.5 rounded-full border border-dashed border-palay-600';
            } else if (status === 'cleaning') {
                dot.className += ' w-1.5 h-1.5 rounded-full border border-dotted border-sky-500';
            } else if (status === 'maintenance') {
                dot.className += ' w-1.5 h-1.5 bg-ember-500 rotate-45';
            }
        }

        // Tooltip: number · type (from existing title if present) · status · occupant [· Updated last-updated]
        const existing = btn.title || '';
        const typeMatch = existing.match(/·\s*([^·]+?)\s*·/);
        // The data attribute first: the tile renders two lines now, so
        // textContent is "102Deluxe" rather than a bare room number.
        const num  = btn.dataset.roomNumber || (existing.match(/^([^\s·]+)/) || [])[1] || '';
        const type = typeMatch ? typeMatch[1].trim() : '';
        const label = STATUS_LABELS[status] || cap(status);
        let newTitle = num + (type ? ' · ' + type : '') + ' · ' + label;
        if (occupant) {
            newTitle += ' · ' + occupant;
        }
        if (updatedAt) {
            newTitle += ' · Updated ' + updatedAt;
        }
        btn.title = newTitle;

        // The tile is a role=button announced by its label; leaving that at
        // the status the page loaded with told a screen reader the opposite
        // of what the colour said.
        btn.setAttribute('aria-label', 'Room ' + num + ' — ' + label + '. Show occupancy.');
    }

    /**
     * Per-wing "N available" figure and bar, recomputed from the tiles after a
     * feed lands. Same numbers the server renders, so a check-in made anywhere
     * moves the wing header without a reload — otherwise the headline said one
     * thing and the tiles under it another.
     */
    function updateWingBars() {
        document.querySelectorAll('[data-map-wing]').forEach(group => {
            const tiles = group.querySelectorAll('.room-map-btn');
            const open = group.querySelectorAll('.room-map-btn[data-display-status="available"]').length;
            const openEl = group.querySelector('[data-wing-open]');
            const barEl = group.querySelector('[data-wing-bar]');

            if (openEl) openEl.textContent = open;
            if (barEl) barEl.style.width = (tiles.length ? Math.round((open / tiles.length) * 100) : 0) + '%';
        });
    }

    function updateLegendBars(counts) {
        if (!counts) return;
        const total = Object.values(counts).reduce((a, b) => a + b, 0);
        if (!total) return;
        Object.keys(counts).forEach(k => {
            const fill = document.querySelector('[data-map-fill="' + k + '"]');
            if (fill) fill.style.width = Math.round((counts[k] / total) * 100) + '%';
        });
    }

    function applyFeed(data) {
        if (!data || !data.success) return;
        (data.rooms || []).forEach(r => {
            // querySelectorAll, not querySelector: a room drawn more than once
            // must not have one copy left on a stale colour while the other
            // updates. The map no longer duplicates tiles (it groups by wing
            // now, from Room::groupByWing), but a patcher that silently only
            // half-applies is how that stayed invisible for so long.
            document.querySelectorAll('.room-map-btn[data-room-btn="' + r.id + '"]')
                .forEach(btn => patchButton(btn, r.display_status, r.occupant, r.updated_at));
        });
        updateWingBars();
        if (data.counts) {
            Object.keys(data.counts).forEach(k => {
                document.querySelectorAll('[data-map-count="' + k + '"]').forEach(el => {
                    el.textContent = data.counts[k];
                });
            });
            updateLegendBars(data.counts);
        }
    }

    let inFlight = false;
    function fetchMap() {
        if (inFlight || document.hidden) return;
        inFlight = true;
        fetch(ROUTES.roomMapFeed, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(applyFeed)
            .catch(() => {})
            .finally(() => { inFlight = false; });
    }

    let mapTimer = null;
    function scheduleFetch() {
        clearTimeout(mapTimer);
        mapTimer = setTimeout(function () {
            fetchMap();
            if (window.Livewire) {
                window.Livewire.dispatch('refreshOccupancy');
                window.Livewire.dispatch('refreshDashboardStats');
                window.Livewire.dispatch('refreshRecentActivity');
            }
        }, 400);
    }

    if (window.Echo) {
        window.Echo.channel('rooms').listen('.RoomStatusChanged', scheduleFetch);
        window.Echo.channel('bookings').listen('.BookingChanged', scheduleFetch);
    }

    setInterval(fetchMap, 20000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) fetchMap(); });
    window.addEventListener('focus', fetchMap);

    // Clickable tiles → expandable bento (resources/js/expandable-bento.js): each
    // room morphs into an occupancy detail card, fetched from
    // staff/rooms/{id}/occupancy and built by the roomOccupancy renderer that the
    // map-tile component registers. The real-time feed above keeps tiles patched.
}

initAdminDashboard();
