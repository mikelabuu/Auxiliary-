---
name: verify
description: Build, launch, and visually drive this Laravel + Vite booking site to verify changes at the browser surface.
---

# Verify — Farmers Hostel (Laravel + Vite + Blade)

## Build & launch

```powershell
npm run build                     # Vite assets (resources/css, resources/js)
php artisan view:clear; php artisan view:cache   # REQUIRED after Blade edits — views are cached
php artisan serve --port=8123     # dev server (matches .claude/launch.json); run in background
```

App URL: http://127.0.0.1:8123/ (landing), /checkout, /login. Staff portal: /staff/login.

## Drive the browser

No Playwright browsers are installed, but **system Edge works via
playwright-core with `channel: 'msedge'`**:

```js
const { chromium } = require('playwright-core'); // npm i playwright-core in scratchpad
const browser = await chromium.launch({ channel: 'msedge', headless: true });
```

Screenshot at multiple timestamps for animation work; check
`sessionStorage`, class state on `<html>`, and console/page errors.

## Auth for guest flows (checkout etc.)

Seeded guest: `user@example.com` / `password` — but the DB may not be
seeded. Create + verify via tinker (quote the whole `--execute` in
single quotes; PowerShell eats `$vars` in double quotes):

```powershell
php artisan tinker --execute='App\Models\User::firstOrCreate([\"email\" => \"user@example.com\"], [\"username\" => \"testuser\", \"phone\" => \"09123456789\", \"password\" => Illuminate\Support\Facades\Hash::make(\"password\"), \"email_verified_at\" => now()]);'
```

`email_verified_at` is NOT mass-assignable — `forceFill` it or login
redirects to /email/verify. Login form submit button: `#loginForm form button`
(no `type="submit"` attribute).

## Gotchas

- Blade edits do nothing until `view:clear` + `view:cache` (views are cached in this repo).
- The landing intro splash plays **once per tab session** (`sessionStorage.fhIntroSeen`); use a fresh browser context to replay it, or clear sessionStorage.
- `background-clip: text` breaks in Chromium when descendants are compositor-animated — animate the element itself (transform/mask), never per-char child spans under a clipped gradient.
- Public pages pull fonts/libs from CDNs; offline runs will look unstyled.
