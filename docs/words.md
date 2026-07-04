# Room Management Revamp — Project Documentation

This document records everything done across this project's work session: the
admin **Room Management** page redesign, the new **Room Types** backend
feature, the **reusable admin component library**, and the follow-up code
review and bug fixes. It's meant to be the one place a future developer (or
future you) can read to understand what changed, why, and how the pieces fit
together.

---

## Table of Contents

1. [Overview](#1-overview)
2. [Part 1 — Visual Revamp](#2-part-1--visual-revamp)
3. [Part 2 — Room Types Backend Feature](#3-part-2--room-types-backend-feature)
4. [Part 3 — Reusable Component Library](#4-part-3--reusable-component-library)
5. [Part 4 — Code Review & Fixes](#5-part-4--code-review--fixes)
6. [Database Reference](#6-database-reference)
7. [Route Reference](#7-route-reference)
8. [File Manifest](#8-file-manifest)
9. [Setup Notes](#9-setup-notes)
10. [Known Follow-ups](#10-known-follow-ups)

---

## 1. Overview

The work happened in four phases, in this order:

| Phase | What | Where documented |
|---|---|---|
| 1 | Redesigned the admin Rooms page to match the (already-good) Dashboard's visual language | [§2](#2-part-1--visual-revamp) |
| 2 | Added a real **Room Types** feature (DB table, CRUD, capacity, pricing) plus per-room notes, quick status changes, and delete | [§3](#3-part-2--room-types-backend-feature) |
| 3 | Extracted the repeated markup from both pages into a proper Blade component library | [§4](#4-part-3--reusable-component-library) |
| 4 | Ran a multi-angle code review (`/code-review xhigh`) against the whole diff, found 15 real issues, fixed all of them | [§5](#5-part-4--code-review--fixes) |

The app is a Laravel 11 hostel-booking admin panel ("Farmers Hostel"). Admin
pages live under `resources/views/staff/`, share `layouts/admin.blade.php`,
and use Tailwind CSS v4 (CSS-based `@theme`, no `tailwind.config.js`) with a
custom brand palette: **clsu** (green), **palay** (gold), **ember** (red/alert),
plus Tailwind's default **stone** (neutral).

---

## 2. Part 1 — Visual Revamp

### Starting point

The Rooms page (`resources/views/staff/rooms/index.blade.php`) predated the
Dashboard's redesign. It used:
- Bootstrap modals (`class="modal fade"`, `bootstrap.Modal(...)`)
- Material Icons
- An old `emerald`/`blue`/`gray` Tailwind palette instead of the app's actual
  `clsu`/`palay`/`ember`/`stone` theme
- A legacy hand-rolled stylesheet, `public/css/roomManagement.css`

### Bug found: the old modals never actually worked

`layouts/admin.blade.php` does not load Bootstrap's JS anywhere — only
jQuery, Chart.js, and SweetAlert2. That means every `new bootstrap.Modal(...)`
call on the old Rooms page threw a `ReferenceError` at runtime. **The Edit
Room and Occupancy modals had never been openable in production.** This was
discovered, not assumed, and confirmed by inspecting the layout's loaded
scripts before touching anything.

### What changed

- Rebuilt the whole page using the Dashboard's design language: `clsu`/`palay`/`ember`/`stone`
  tokens, hand-drawn feather-style inline SVG icons (not Material Icons),
  `rounded-2xl` / `shadow-card` / `shadow-card-lg` card shells, and the
  `.animate-in` staggered entrance animation already defined in
  `resources/css/app.css`.
- Replaced the broken Bootstrap modals with a plain show/hide implementation
  (`hidden`/`flex` class toggling driven by jQuery, `[data-modal-close]`
  attribute convention) — this both fixed the bug above and removed the
  Bootstrap dependency for this page.
- Added stat cards (Total Rooms, Available, Occupied, Maintenance) matching
  the Dashboard's KPI card look, including one "hero" dark-gradient card per
  row for visual parity.
- Added a secondary metrics strip (Cleaning count, Wings in use, Room types
  offered).
- Rooms are grouped by **wing** with per-wing headers and counts, instead of
  one flat grid.
- Added a search box (room number / type / wing) and a status filter,
  combined via client-side JS filtering.
- Dropped the legacy `roomManagement.css` `<link>` from this page (the file
  itself was left in place since other, not-yet-revamped pages still use it).

---

## 3. Part 2 — Room Types Backend Feature

The user asked to go further than styling: Room Types needed to become a real,
admin-manageable entity (name, base price, capacity), and rooms needed quick
status changes, deletion, and notes — all with working backend support, not
just UI.

### Database changes

**`database/migrations/2026_07_04_000001_create_room_types_table.php`**
Creates the `room_types` table:

| Column | Type | Notes |
|---|---|---|
| `slug` | `string(100)`, unique | matches the existing `rooms.room_type` string values (e.g. `deluxe`, `dormitory1`) |
| `name` | `string(100)` | display label, editable |
| `base_price` | `decimal(10,2)` | pre-fills new rooms' price; editing it does **not** retroactively change existing rooms |
| `capacity` | `unsigned tinyint` | max guests for that room type |

The migration also **backfills** the table from whatever distinct
`room_type` values already exist in `rooms` at migration time, using
`MAX(price)` per type as the initial `base_price` and a hardcoded capacity
default map (`deluxe=2, double=2, triple=3, quadruple=4, dormitory1=5,
dormitory2=6`) matching the values already hardcoded elsewhere in the
booking flow (see [§5](#5-part-4--code-review--fixes) for why that mattered).

**`database/migrations/2026_07_04_000002_add_notes_to_rooms_table.php`**
Adds a nullable `notes` text column to `rooms`.

**`database/migrations/2026_07_04_000003_add_unique_index_to_room_types_name.php`**
(Added during the code-review fix pass.) Adds a DB-level unique constraint on
`room_types.name` — see [§5](#5-part-4--code-review--fixes).

### Models

- **`app/Models/RoomType.php`** (new) — `slug`, `name`, `base_price`,
  `capacity` fillable; `hasMany(Room::class, 'room_type', 'slug')`.
- **`app/Models/Room.php`** — added `notes` to `$fillable`, added
  `roomType(): belongsTo(RoomType::class, 'room_type', 'slug')`.

### Controllers & routes

**`app/Http/Controllers/Staff/RoomController.php`**
- `index()` — now also loads `RoomType::withCount('rooms')` for the Room
  Types & Pricing section and the Add/Edit Room type dropdowns.
- `store()` / `update()` — gained `status` and `notes` fields (both
  `nullable`, so old callers without them still work).
- `updateStatus(Room $room)` — **new**. `PATCH` endpoint for the room card's
  kebab-menu quick status change. Validates against the same 4-value enum,
  audit-logs the change.
- `destroy(Room $room)` — **new**. `DELETE` endpoint with a booking-history
  guard: refuses to delete (422) if `$room->reservations()->exists() ||
  $room->bookings()->exists()`. Audit-logs before deleting.

**`app/Http/Controllers/Staff/RoomTypeController.php`** (new)
- `store()` — validates `name` (unique), `base_price`, `capacity`; generates
  a URL-safe `slug` from the name with a collision-avoidance suffix loop.
- `update()` — same validation, slug is immutable once created (it's the FK
  key into `rooms.room_type`, so changing it would orphan existing rooms).

**Routes** (`routes/web.php`, inside the existing `auth:staff` +
`staff.role:admin,master_admin` group — see [§7](#7-route-reference) for the
full table).

### Frontend features added to the Rooms page

- **Room Types & Pricing** tiles are now interactive: click one to filter the
  room grid by that type; each tile has an edit button; a dashed "+ Add Room
  Type" tile opens a create modal. Tiles are rendered client-side from a
  JSON array (`roomTypes`) bootstrapped from the controller, so add/edit
  updates the UI instantly with no page reload.
- **Wing filter** dropdown, alongside the existing status filter and search.
- **Notes** field on Add/Edit Room (optional textarea), shown as a small
  annotation with a note icon on the room card if present.
- **Kebab menu** per room card: quick status change (PATCH, no reload,
  updates the card's color bar/badge/dot in place) and **delete** (two-click
  arm/confirm pattern, DELETE, blocked server-side if the room has booking
  history).
- Feedback switched to **SweetAlert2 toasts** (bottom-right), matching a
  library already loaded app-wide.

### Bug found: Blade's `@json` directive mangles multi-line expressions

While wiring the room-types JSON into the page's `<script>` block, using
`@json(collect($statusMeta)->map(fn ($m) => [...]))` **silently truncated**
the output mid-array. Root cause: Laravel's `@json` directive implementation
does a naive `explode(',', ...)` on its raw argument text to separate an
optional second `$options` parameter — it has no awareness of nested
brackets/parens, so any comma inside a multi-line array literal gets treated
as an argument separator. The fix: compute the JSON in the `@php` block with
plain `json_encode(...)` and echo it via `{!! $variable !!}`, bypassing the
directive entirely. (`resources/views/staff/rooms/index.blade.php`, top
`@php` block: `$statusMetaJson`, `$roomTypesJson`.)

---

## 4. Part 3 — Reusable Component Library

After the feature work, the request was to turn the admin pages into
reusable, prop-driven components — "easy to navigate and edit, page by page,
cleaner" — using the Dashboard/Rooms pages (the two finished pages) to prove
the pattern out, **without** touching any of the other, not-yet-revamped
admin pages (which still depend on the older `x-admin.card` / `button` /
`input` / `select` components — those were left alone on purpose).

### Convention established

- **Generic, page-agnostic pieces** live directly under
  `resources/views/components/admin/` → `<x-admin.icon>`, `<x-admin.stat-card>`, etc.
- **Page-specific composites** live under a subfolder named after the page →
  `resources/views/components/admin/rooms/room-card.blade.php` →
  `<x-admin.rooms.room-card>`. Future pages (bookings, payments, ...) should
  follow the same `components/admin/{page}/*` pattern when their turn comes.

### Component reference

#### `<x-admin.icon>`
Central SVG icon registry — one file, ~30 named icons, so no page hand-copies
raw `<svg>` markup anymore.

```blade
<x-admin.icon name="plus" class="w-4 h-4" stroke-width="2" />
```
Props: `name` (required), `strokeWidth` (default `1.75`).
Registered names: `plus, x, chevron-left, chevron-right, chevron-down, kebab,
search, menu, grid, check, check-circle, clock, trend-up, trend-down,
wrench, droplet, bed, clipboard, users, user, receipt, credit-card,
calendar, calendar-plus, log-in, log-out, arrival, departure, block,
chart-bar, tag, map-pin, edit, eye, trash, note, settings, bell`.
An unrecognized name silently falls back to `grid` — always verify a new
icon name against this list before using it.

#### `<x-admin.page-header>`
The title/subtitle/actions row at the top of every page.
Props: `subtitle`. Slots: default (title HTML — put the italic accent span
directly in it), `actions`.

```blade
<x-admin.page-header subtitle="Manage availability, wings, and pricing across all rooms.">
    Room <span class="font-display italic font-medium text-clsu-800">Management</span>
    <x-slot:actions>
        <button ...>Add Room</button>
    </x-slot:actions>
</x-admin.page-header>
```

#### `<x-admin.stat-card>`
Big KPI card (light or dark "hero" variant).
Props: `icon` (default `grid`), `color` (`clsu`/`palay`/`ember`/`sky`),
`badge`, `label`, `dark` (bool), `delay` (ms), `valueId`, `footnoteId`
(for JS hooks that need to update the number in place later).
Default slot = the value. `footnote` slot = the small line underneath — it's
a **bare slot, not a styled prop**, specifically so a metric that needs a
colored trend arrow (see the dashboard's Bookings/Revenue cards) can bring
its own markup.

```blade
<x-admin.stat-card icon="bed" badge="ALL WINGS" label="Total Rooms" :delay="40" value-id="statTotalNum">
    {{ $totalRooms }}
    <x-slot:footnote><p class="text-xs text-stone-400">Across {{ $wings }} wings</p></x-slot:footnote>
</x-admin.stat-card>
```

#### `<x-admin.mini-stat>`
Small inline icon+number card for "secondary metrics" strips.
Props: `icon`, `color`, `label`, `valueId`. Default slot = value.

#### `<x-admin.section-card>`
The white rounded panel shell used below the stat row (Room Types & Pricing,
All Rooms, Bookings Insights, Room Status Map, ...).
Props: `icon`, `color`, `title`, `subtitle`, `subtitleId`, `delay`. Omit
`title` for a bare panel with no header row. `actions` slot = header-right
content (legend, buttons).

#### `<x-admin.quick-action>`
The icon+label link tile used in the Dashboard's Quick Actions row.
Props: `icon`, `title`, `subtitle`, `href`.

#### `<x-admin.modal>`
Backdrop + centered panel + optional icon/title/close header. Works with the
existing jQuery `openModal('id')` / `closeModal('id')` / `[data-modal-close]`
convention already used across the admin panel — no JS changes needed to
adopt it on a new page.
Props: `id` (required), `icon`, `color`, `title`, `titleId` (pass this when
JS needs to swap the header text, e.g. "Add" ↔ "Edit"), `maxWidth`
(`sm`/`md`/`lg`/`xl`), `scrollBody` (adds `max-h-[90vh] overflow-y-auto`).
Includes `role="dialog"`, `aria-modal`, and `aria-labelledby` (auto-derived
from `id` if `titleId` isn't given), plus focus-on-open / restore-on-close
via the page's `openModal()`/`closeModal()` helpers.

```blade
<x-admin.modal id="addRoomModal" icon="plus" title="Add New Room" scroll-body>
    <form ...>...fields + footer buttons...</form>
</x-admin.modal>
```

#### `<x-admin.modal-footer>`
The standard Cancel + primary-submit button pair, so it isn't re-typed at
every modal's footer.
Props: `closeTarget` (required), `submitLabel` (default `Save`).

```blade
<div class="flex gap-2.5 justify-end pt-2">
    <x-admin.modal-footer close-target="addRoomModal" submit-label="Add Room" />
</div>
```

#### `<x-admin.empty-state>`
Small centered "nothing here" placeholder.
Props: `icon` (default `search`), `title` (or use the default slot).

#### `<x-admin.rooms.room-card>`
Page-specific composite: a single room tile (status bar, edit button, kebab
menu, status badge, notes, occupancy-view footer). Pulled out of
`rooms/index.blade.php` purely for readability — every class/data-attribute
the page's `<script>` block depends on is preserved exactly, so extracting it
required no JS changes.
Props: `room` (a `Room` model instance), `statusMeta` (the status→style map).

### Shared color tokens

`config/adminui.php` (added during the code-review fix pass) is the single
source of truth for the `clsu`/`palay`/`ember` icon-chip colors shared by
`mini-stat`, `modal`, and `section-card` — see
[§5](#5-part-4--code-review--fixes) for why this exists.

### What was intentionally *not* touched

The pre-existing `x-admin.card` / `button` / `input` / `select` components
(using an old, partly-broken `sage-*` color palette) were left exactly as-is.
They're still used by `resources/views/staff/staffrecords/index.blade.php`,
which hasn't been revamped yet. When that page's turn comes, it should adopt
the new component library the same way Dashboard and Rooms did.

---

## 5. Part 4 — Code Review & Fixes

A `/code-review xhigh` pass was run against the full diff (everything in
§2–§4, committed as `5d6a1a6 Updates the Room page`). The process: 9 parallel
finder "angles" (line-by-line scan, removed-behavior audit, cross-file
tracing, language-pitfall scan, wrapper/proxy correctness, plus reuse /
simplification / efficiency / altitude cleanup angles), each looking for
different classes of defect, followed by verification and a gap sweep. Three
finder agents hit an API session-limit mid-run and were covered manually via
direct file inspection instead of being retried.

**15 findings, all fixed.** Grouped by severity:

### Real bugs

| # | Problem | Fix |
|---|---|---|
| 1 | **Price auto-clobber.** The Add/Edit Room price field's `readonly` lock was dropped in the rewrite, but the type-change handler still unconditionally overwrote the price field — silently reverting a manually-typed custom price whenever the Room Type dropdown fired a `change` event. | The handler now only auto-fills when the field is empty or still equals some known type's base price (i.e. hasn't been customized away from an auto-filled value). |
| 2 | **Fresh-install breakage.** `DatabaseSeeder` created `Room` rows but never `RoomType` rows, and the migration's backfill only pulls from rooms that exist *at migration time* — empty on a truly fresh DB. Result: the Add Room modal's required Room Type dropdown had zero options. | Seeder now `firstOrCreate`s the 6 `RoomType` rows before seeding rooms. |
| 3 | **Stale capacity source of truth.** The new admin-editable `RoomType.capacity` was never read by the actual guest-count validation in `ManualBookingController`/`WalkInBookingController`, which kept their own independent hardcoded `$roomCapacityMap`. | Both controllers now look up `RoomType::pluck('capacity', 'slug')` first, falling back to the legacy hardcoded map only for a slug with no matching row. |
| 4 | **Stale room-type tile counts.** Deleting a room updated the aggregate stat counters but never re-rendered the "Room Types & Pricing" tiles, so a type's room count stayed wrong until an unrelated edit or a reload. | Delete handler now decrements the in-memory count for that type and calls `renderTypeTiles()`. |
| 5 | **RoomTypeController race condition.** Name uniqueness was checked via a plain `SELECT` (no DB constraint), and the slug-collision loop had no locking — two concurrent identical submissions could throw an unhandled 500. | Added a DB-level unique index on `room_types.name` (new migration) and wrapped the create/update in try/catch, turning a collision into a clean `409` response instead of a raw crash. |
| 6 | **Room rename breaks occupancy history.** `Room::reservations()` matches by the mutable `room_number` string. Renaming a room via the redesigned Edit modal (freely editable, no cascade) orphaned any reservation created under the old number from the occupancy lookup — a currently-checked-in guest could disappear from "View Occupancy". | `RoomController::update()` now cascades a `room_number` change to matching `Reservation` rows, inside a DB transaction with the room update. |

### Consistency / quality issues

| # | Problem | Fix |
|---|---|---|
| 7 | Room status labels hardcoded in 4 places (`$statusMeta`, the status filter, and both Add/Edit status selects), already drifted ("Under Maintenance" vs "Maintenance"). | All 4 now render from the single `$statusMeta` array via `@foreach`. |
| 8 | Wings hardcoded in 3 places (Add/Edit selects duplicating `$wingOrder`), the exact pattern the PR had just eliminated for room types. | Both selects now render from `$wingOrder` via `@foreach`. |
| 9 | Add Room fields lost the red-border validation error state (only the text message remained) when the old `x-admin.input`/`select` components were replaced by hand-rolled markup. | Restored conditional `border-ember-300` styling driven by `$errors->has(...)`. |
| 10 | Four new components each defined their own `clsu`/`palay`/`ember` icon-chip color map; two had already drifted (`bg-clsu-50` vs `bg-clsu-100` for the same name). | Consolidated into `config/adminui.php`, consumed by `mini-stat`, `modal`, and `section-card` (registered with Tailwind's `@source` scanner so the build still picks up the literal class strings). |
| 11 | New modals are plain toggled `<div>`s with no `role="dialog"`, `aria-modal`, or focus management (the Bootstrap modals they replaced never actually worked either — see §2 — so this isn't a regression from working behavior, but it was a real gap in the first *functional* version). | Added `role="dialog"`, `aria-modal="true"`, `aria-labelledby`, and focus-on-open / restore-on-close in `openModal()`/`closeModal()`. |

### Cleanup

| # | Problem | Fix |
|---|---|---|
| 12 | `RoomController::index()` computed `$prices` and passed it to the view, but nothing in the refactored view reads it anymore. | Removed. |
| 13 | `$statusMetaJson` was built via a `->map()` closure that copied the exact same 4 keys `$statusMeta` already had — equivalent to `json_encode($statusMeta)`. | Simplified. |
| 14 | The Cancel + primary-submit button markup was copy-pasted verbatim across 3 modal footers. | Extracted into `<x-admin.modal-footer>`. |
| 15 | `rooms/room-card.blade.php` was the only component in the library that didn't merge `$attributes` onto its root element, breaking the library's own convention (dormant — no call site was affected yet). | Fixed to match every sibling component. |

### Deliberately not changed

Two lower-confidence, more architectural findings from the review were
**not** acted on, to avoid scope creep into pages/components used elsewhere:
- The new inline form-field styling in the Rooms modals duplicates the
  pattern `x-admin.input`/`select` already solve (with a different, older
  color palette) — those components are still load-bearing for
  `staffrecords/index.blade.php` and weren't touched.
- `section-card.blade.php` and the pre-existing `card.blade.php` solve a
  similar "card with header" shape with two different styling systems — left
  as-is pending that page's own eventual revamp.

---

## 6. Database Reference

### `room_types`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint, PK | |
| `slug` | string(100), **unique** | FK-like key into `rooms.room_type` (a plain string column, not a real foreign key) |
| `name` | string(100), **unique** (added in fix #5) | |
| `base_price` | decimal(10,2) | default for *new* rooms of this type only |
| `capacity` | unsigned tinyint | consumed by `ManualBookingController`/`WalkInBookingController` guest-count validation |
| `created_at` / `updated_at` | timestamps | |

### `rooms` (additions)

| Column | Type | Notes |
|---|---|---|
| `notes` | text, nullable | added by migration `2026_07_04_000002` |

### Model relationships added

```
Room::roomType()      → belongsTo(RoomType::class, 'room_type', 'slug')
RoomType::rooms()     → hasMany(Room::class, 'room_type', 'slug')
```

(Existing `Room::reservations()` — `hasMany(Reservation::class, 'room_number', 'room_number')`
— and `Room::bookings()` — `belongsToMany(Booking::class, 'booking_room')` —
were not changed, but are relevant context for fix #6 above.)

---

## 7. Route Reference

All under the `auth:staff` + `staff.role:admin,master_admin` middleware group.

| Method | URI | Name | Controller@method | Added/changed |
|---|---|---|---|---|
| GET | `/staff/rooms` | `staff.rooms` | `RoomController@index` | changed (now loads `RoomType`s too) |
| POST | `/staff/rooms/store` | `staff.rooms.store` | `RoomController@store` | changed (+status, +notes) |
| GET | `/staff/rooms/{room}/edit` | `staff.rooms.edit` | `RoomController@edit` | unchanged |
| PUT | `/staff/rooms/{room}` | `staff.rooms.update` | `RoomController@update` | changed (+status, +notes, +rename cascade) |
| PATCH | `/staff/rooms/{room}/status` | `staff.rooms.updateStatus` | `RoomController@updateStatus` | **new** |
| DELETE | `/staff/rooms/{room}` | `staff.rooms.destroy` | `RoomController@destroy` | **new** |
| GET | `/staff/rooms/{room}/occupancy` | `staff.rooms.occupancy` | `RoomController@occupancyForRoom` | unchanged |
| POST | `/staff/room-types` | `staff.roomtypes.store` | `RoomTypeController@store` | **new** |
| PUT | `/staff/room-types/{roomType}` | `staff.roomtypes.update` | `RoomTypeController@update` | **new** |

---

## 8. File Manifest

### New files

```
app/Http/Controllers/Staff/RoomTypeController.php
app/Models/RoomType.php
config/adminui.php
database/migrations/2026_07_04_000001_create_room_types_table.php
database/migrations/2026_07_04_000002_add_notes_to_rooms_table.php
database/migrations/2026_07_04_000003_add_unique_index_to_room_types_name.php
resources/views/components/admin/icon.blade.php
resources/views/components/admin/page-header.blade.php
resources/views/components/admin/stat-card.blade.php
resources/views/components/admin/section-card.blade.php
resources/views/components/admin/mini-stat.blade.php
resources/views/components/admin/quick-action.blade.php
resources/views/components/admin/modal.blade.php
resources/views/components/admin/modal-footer.blade.php
resources/views/components/admin/empty-state.blade.php
resources/views/components/admin/rooms/room-card.blade.php
docs/words.md   (this file)
```

### Changed files

```
app/Models/Room.php
app/Http/Controllers/Staff/RoomController.php
app/Http/Controllers/Staff/ManualBookingController.php
app/Http/Controllers/Staff/frontdesk/WalkInBookingController.php
database/seeders/DatabaseSeeder.php
resources/css/app.css                              (added @source line for config/adminui.php)
resources/views/staff/dashboard/index.blade.php     (refactored to use the component library)
resources/views/staff/rooms/index.blade.php         (full revamp + backend features + component refactor)
routes/web.php
```

### Deliberately untouched (still on the old design system)

```
resources/views/components/admin/card.blade.php
resources/views/components/admin/button.blade.php
resources/views/components/admin/input.blade.php
resources/views/components/admin/select.blade.php
resources/views/staff/staffrecords/index.blade.php
public/css/roomManagement.css   (dead weight for the Rooms page specifically, but still linked by other unrevamped pages)
```

---

## 9. Setup Notes

To bring a checkout up to date:

```bash
php artisan migrate        # creates room_types, adds rooms.notes, adds room_types.name unique index
php artisan db:seed        # if seeding fresh — now also creates the 6 RoomType rows
php artisan config:clear   # if config/adminui.php was added after config was cached
```

No `npm run build` is strictly required for the color-map consolidation
(the same literal Tailwind class strings — `bg-clsu-100`, `bg-ember-100`,
etc. — already exist elsewhere in already-scanned `.blade.php` files), but
running the asset build after pulling these changes is good practice as usual.

---

## 10. Known Follow-ups

Not bugs, just things worth knowing about if you're picking this up next:

- **Wing taxonomy is still hardcoded** (`rooster`/`tumana`/`chev_re`/`torii`
  as a `$wingOrder` PHP array in `rooms/index.blade.php`), unlike Room Types
  which now have a real table. If wings need to become admin-manageable too,
  follow the same pattern used for Room Types (a `wings` table + a small
  controller).
- **`staffrecords/index.blade.php`** (and any other not-yet-revamped admin
  page) still uses the older `x-admin.card`/`button`/`input`/`select`
  components with a partially-broken `sage-*` color palette. When that page
  gets its turn, migrate it to the new component library from §4.
- **`Reservation.room_id`** exists as a column but is never populated by
  `ManualBookingController`/`WalkInBookingController` — only `room_number`
  is set. The room-rename cascade fix (§5, #6) works around this by updating
  `room_number` directly rather than switching the relation to `room_id`,
  since populating `room_id` retroactively was out of scope for this pass.
