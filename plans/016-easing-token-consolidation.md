# 016 — Consolidate hand-typed easing curves onto tokens

- **Status**: TODO
- **Commit**: `770cf6a`
- **Severity**: MEDIUM
- **Category**: Cohesion & tokens
- **Estimated scope**: ~20 files, ~77 substitutions + 3 token declarations

## Problem

Measured at commit `770cf6a`:

- **109** raw `cubic-bezier(…)` literals across `resources/css/` and
  `resources/views/`, versus **67** uses of a `var(--ease-*)` token.
- The primary ease-out curve is hand-typed **57 times across 19 files**, in three
  different spellings that are all the identical curve:
  - `cubic-bezier(0.22, 1, 0.36, 1)`
  - `cubic-bezier(.22, 1, .36, 1)`
  - `cubic-bezier(0.22,1,0.36,1)`
- …while **two tokens already equal exactly that curve**:
  - `resources/css/admin/01-tokens.css:272` — `--ease-out: cubic-bezier(.22, 1, .36, 1);`
  - `resources/css/public/03-theme-boutique.css:20` — `--ease-boutique: cubic-bezier(0.22, 1, 0.36, 1);`
- Two further curves are each duplicated across spellings:
  - `cubic-bezier(0.2, 0.7, 0.2, 1)` / `cubic-bezier(.2,.7,.2,1)` — **12 uses**
  - `cubic-bezier(.16, 1, .3, 1)` / `cubic-bezier(0.16, 1, 0.3, 1)` — **8 uses**

This was recorded as an unpromoted item at the end of batch 1 and has grown since.
The cost is that no curve can be retuned in one place, and near-identical values
drift apart silently.

## Target

Three tokens carry the repeating curves; every exact-duplicate literal becomes a
`var()`. **No curve value changes — this is a pure text-to-token substitution
with zero visual difference.**

Admin bundle, `resources/css/admin/01-tokens.css`, alongside the existing tokens
at lines 272-274:

```css
/* target — add after line 273 */
    --ease-soft: cubic-bezier(0.2, 0.7, 0.2, 1);
    --ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
```

Public bundle, `resources/css/public/01-tokens.css`, in the same `:root`/`@theme`
block the other public tokens live in:

```css
/* target — the public bundle has no --ease-out today; add all three */
    --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
    --ease-soft: cubic-bezier(0.2, 0.7, 0.2, 1);
    --ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
```

Then every literal listed below becomes the matching token:

| Literal (all spellings) | Becomes |
| --- | --- |
| `cubic-bezier(0.22, 1, 0.36, 1)`, `cubic-bezier(.22, 1, .36, 1)`, `cubic-bezier(0.22,1,0.36,1)` | `var(--ease-out)` |
| `cubic-bezier(0.2, 0.7, 0.2, 1)`, `cubic-bezier(.2,.7,.2,1)` | `var(--ease-soft)` |
| `cubic-bezier(.16, 1, .3, 1)`, `cubic-bezier(0.16, 1, 0.3, 1)` | `var(--ease-expo)` |

## Curves that must NOT be touched

These look similar and are **different curves**. Substituting them would change
how the app moves, which is out of scope for a consolidation:

- `cubic-bezier(0.2, 0.75, 0.2, 1)` — 6 uses. Differs from `--ease-soft` in the
  second control point. Leave every one as-is.
- `cubic-bezier(.34, 1.4, .5, 1)` — 2 uses. Near-miss against
  `--ease-spring: cubic-bezier(.34, 1.3, .5, 1)` (`01-tokens.css:273`). Leave as-is.
- `cubic-bezier(0.32, 0.72, 0, 1)`, `cubic-bezier(0.4, 0, 0.6, 1)`,
  `cubic-bezier(0.2, 0.65, 0.3, 0.9)`, `cubic-bezier(0.19, 1, 0.22, 1)`,
  `cubic-bezier(.65, 0, .45, 1)`, `cubic-bezier(0.76, 0, 0.24, 1)` — one-offs and
  small-count distinct curves. Leave as-is.

Do **not** retune `--ease-out` to any other value. A different strong ease-out
exists in the wider literature, but this repo's curve is already established
across 57 sites and changing it is a visual decision, not a cleanup.

## Files that must NOT be touched

- `resources/views/partials/page-loader.blade.php`
- `resources/views/partials/page-progress.blade.php`

Both are inline `<style>` blocks that render **before `admin.css` has
downloaded** — that is the entire reason they exist. A `var(--ease-out)` there
resolves to nothing and silently falls back to the initial value. Plan 018
handles their curves separately by inlining literals.

## Repo conventions to follow

- Admin tokens live in `resources/css/admin/01-tokens.css`; the easing group is
  lines 272-274 (`--ease-out`, `--ease-spring`, `--transition`). Add new easings
  immediately after `--ease-spring`, matching the existing one-per-line format.
- **Exemplar** — `resources/css/public/14-auth.css:42` already does the right
  thing: `--fha-ease: var(--ease-boutique, cubic-bezier(0.22, 1, 0.36, 1));`.
  It references a token with the literal as a fallback. Imitate the reference,
  not the fallback.
- Keep `--ease-boutique` declared at `03-theme-boutique.css:20`. It is referenced
  by `14-auth.css:42`; deleting it breaks that. Leave the declaration in place
  and simply stop adding new uses of the literal.

## Steps

1. Add `--ease-soft` and `--ease-expo` to `resources/css/admin/01-tokens.css`
   directly after line 273, using the values in the Target section.
2. Add `--ease-out`, `--ease-soft` and `--ease-expo` to the public token block in
   `resources/css/public/01-tokens.css`, using the values in the Target section.
   (The public bundle currently has no `--ease-out` at all.)
3. Find every occurrence of the three ease-out spellings, excluding the two
   forbidden partials:

   ```bash
   grep -rnE 'cubic-bezier\(0?\.22, ?1, ?0?\.36, ?1\)' resources/css/ resources/views/
   ```

   Replace each with `var(--ease-out)`. Expect 57 across 19 files.
4. Repeat for the soft curve, replacing with `var(--ease-soft)`:

   ```bash
   grep -rnE 'cubic-bezier\(0?\.2, ?0?\.7, ?0?\.2, ?1\)' resources/css/ resources/views/
   ```

   Expect 12. **Be careful not to match `0.2, 0.75, 0.2, 1`** — the regex above
   already excludes it, so do not loosen it.
5. Repeat for the expo curve, replacing with `var(--ease-expo)`:

   ```bash
   grep -rnE 'cubic-bezier\(0?\.16, ?1, ?0?\.3, ?1\)' resources/css/ resources/views/
   ```

   Expect 8.
6. Re-run all three greps. Each must return only the two forbidden partials, or
   nothing at all.

## Boundaries

- Do NOT change any curve's numeric value anywhere.
- Do NOT touch the two inline partials named above.
- Do NOT touch the "must NOT be touched" curves listed above.
- Do NOT delete `--ease-boutique` or rewrite `14-auth.css:42`.
- Do NOT reformat, reorder or otherwise tidy the files you edit — change only the
  easing literals.
- Do NOT add dependencies.
- If a grep returns a materially different count than stated (drift since
  `770cf6a`), STOP and report the actual counts instead of improvising.

## Verification

- **Mechanical**:
  - `npm run build:only` — expect a clean build, new `admin-<hash>.css` and
    `app-<hash>.css`.
  - `php artisan view:clear && php artisan view:cache` — expect
    `Blade templates cached successfully.`
  - `grep -rc 'cubic-bezier' resources/css/ | sort` — the total literal count
    should fall from 109 to about 32.
- **Feel check**: a correct execution changes **nothing** visually. That is the
  test.
  - Admin: open a modal (Rooms → Add Room), open the topbar command palette
    (`Ctrl+K`), hover a card, trigger a toast. All must move exactly as before.
  - Public: load `/`, watch the hero rise and scroll to the gallery; open
    `/checkout` and open a date picker.
  - In DevTools, inspect any element that previously used a literal and confirm
    the computed `transition-timing-function` still reads
    `cubic-bezier(0.22, 1, 0.36, 1)` — resolved through the token, not broken to
    `ease`. **A token that fails to resolve silently degrades to `ease`, so a
    computed value of `ease` anywhere is the failure signal for this plan.**
- **Done when**: the literal count is down, the computed timing functions are
  unchanged, and nothing in either bundle animates with plain `ease` that did not
  before.
