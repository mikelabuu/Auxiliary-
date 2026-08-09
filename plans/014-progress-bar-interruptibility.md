# 014 — Fix the progress bar's untracked settle timer

- **Status**: TODO
- **Commit**: `770cf6a`
- **Severity**: HIGH
- **Category**: Interruptibility
- **Estimated scope**: 1 file, ~6 lines

## Problem

`resources/views/partials/page-progress.blade.php` shows a top progress bar for
Livewire round-trips (pagination, filter pills, sorting, search). When a request
completes, it schedules a two-stage teardown: an outer timer that enforces the
minimum visible duration, then an inner timer 220ms later that removes the
classes.

**The inner timer is never stored, so it can never be cancelled.**

```js
/* resources/views/partials/page-progress.blade.php:166-169 — current */
        settleTimer = setTimeout(function () {
            el.classList.add('is-done');
            setTimeout(function () { el.classList.remove('is-on', 'is-done'); }, 220);
        }, remaining);
```

`lwStart()` only clears the outer one:

```js
/* resources/views/partials/page-progress.blade.php:143-154 — current */
    function lwStart() {
        if (performance.now() - lastInteraction > 1500) return false;

        pending++;
        if (pending > 1) return true;

        clearTimeout(settleTimer);
        startedAt = performance.now();
        el.classList.remove('is-done');
        el.classList.add('is-on');
        return true;
    }
```

Failure sequence:

1. Click "next page". Bar shows. Request finishes. The outer timer fires after
   `MIN_VISIBLE`, adds `is-done`, and schedules the inner timer T for +220ms.
2. Within those 220ms the user clicks "next page" again. `lwStart()` runs,
   `clearTimeout(settleTimer)` is a no-op (it already fired), the classes are
   reset and the bar starts.
3. **Timer T fires and strips `is-on` and `is-done`.** The second bar vanishes
   almost immediately.

Rapid pagination — clicking through pages in sequence — is exactly this pattern,
and it is the interaction the bar was added for. This is the interruptibility
rule: state that can be re-triggered mid-teardown must retarget, not be
cancelled by a stale timer.

## Target

Track the inner timer alongside the outer one and clear both on every start.

```js
/* target — replaces lines 130-132 */
    var MIN_VISIBLE = 420;
    var pending = 0;
    var startedAt = 0;
    var settleTimer = null;
    var clearTimer = null;
```

```js
/* target — replaces lines 149 (inside lwStart) */
        clearTimeout(settleTimer);
        clearTimeout(clearTimer);
```

```js
/* target — replaces lines 166-169 (inside lwStop) */
        settleTimer = setTimeout(function () {
            el.classList.add('is-done');
            clearTimer = setTimeout(function () {
                el.classList.remove('is-on', 'is-done');
            }, 220);
        }, remaining);
```

## Repo conventions to follow

- This file is a self-contained Blade partial holding its own inline `<style>`
  and `<script>`. It deliberately uses no build-time tooling and no `var(--…)`
  CSS tokens, because it must run before `admin.css` has downloaded. Keep that
  property: plain ES5-style `var`, no imports, no optional chaining.
- Timer handles in this file are module-scoped `var`s declared together near the
  top of the IIFE (lines 129-132). Add the new one to that same group.
- The existing comment style explains *why*, not *what*. Match it.

## Steps

1. In `resources/views/partials/page-progress.blade.php`, at the variable group
   that currently ends with `var settleTimer = null;` (line 132), add a sibling
   declaration `var clearTimer = null;`.
2. In `lwStart()`, immediately after the existing `clearTimeout(settleTimer);`
   (line 149), add `clearTimeout(clearTimer);`.
3. In `lwStop()`, assign the inner `setTimeout` to `clearTimer` instead of
   leaving it anonymous, as shown in the Target section.
4. Add a short comment above the inner timer noting that it is tracked so a
   re-trigger inside the 220ms fade cannot tear down the new run.

## Boundaries

- Do NOT touch `resources/views/partials/page-loader.blade.php`. Its single
  teardown timer removes the node from the DOM once per document load and cannot
  be re-triggered, so it has no equivalent bug.
- Do NOT change `MIN_VISIBLE`, the 1500ms interaction gate, the `wire:poll`
  exclusion, or any CSS in this file.
- Do NOT convert the file to modern syntax or move the script into
  `resources/js/`. Its placement and plainness are load-bearing.
- Do NOT add dependencies.
- If the code at these lines does not match the excerpts above, STOP and report.

## Verification

- **Mechanical**: `php artisan view:clear && php artisan view:cache` — expect
  `INFO  Blade templates cached successfully.` and no errors. No asset rebuild is
  needed; this partial is not part of the Vite bundle.
- **Feel check**: log into the admin console and open a paginated table
  (`/staff/audit-logs` has the most pages).
  - Click through pages 2 → 3 → 4 as fast as you can. The bar must appear for
    **every** click. Before this fix, a click landing inside the 220ms fade
    produced no bar at all.
  - Click one page, wait for the bar to fully disappear, click again — the bar
    still appears normally (no regression on the slow path).
  - Sit on `/staff/dashboard` for 60 seconds without touching anything. The bar
    must never appear on its own — the 15s `wire:poll` must stay silent.
- **Done when**: rapid successive pagination clicks each produce a visible bar,
  and idle polling produces none.
