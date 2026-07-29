# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

**Primary for the sign-in surface: staff and administrators.** Front desk, housekeeping,
admins and master admins sign in at the start of a shift, on desktop, as a routine daily
act. `Staff::ROLES` is `master_admin`, `admin`, `frontdesk`, `housekeeping`; only the
first three can reach a dashboard (`housekeeping` currently has no landing route and is
rejected after login with "no valid role assigned").

**Secondary: guests booking a room.** Public visitors, frequently first-time and often on
a phone, arriving mid-booking. They pass through the same door and land on `/checkout`.

Registration is guest-only in practice — staff accounts are provisioned by admins through
the staff-management UI, never through the public form.

## Product Purpose

The Farmers Hostel booking and operations system for the CLSU Auxiliary Services Program.
Guests search availability, reserve rooms and pay; staff run the front desk, room board,
housekeeping and records behind the same data. Success for the sign-in surface is narrow
and unglamorous: the right person reaches the right dashboard quickly, and the wrong
person learns nothing from trying.

## Positioning

**One door, three keys.** A single login form serves guests, front desk and admins.
`LoginController` resolves the identity first, then hands off to the correct guard — guest
to `web`, staff to `staff` plus an emailed six-digit code. The system previously shipped
two forms against two guards (`/login` and `/staff/login`), which meant two rate limiters,
two error vocabularies and two places for an auth bug to hide; `/staff/login` is now a
redirect to `/login`. The unification is a real, deliberate engineering property of this
product, not a marketing claim.

## Operating Context

Farmers Hostel sits inside Central Luzon State University, Science City of Muñoz, Nueva
Ecija. The front desk is a physical counter with a shift-based staff rotation. Staff sign
in at shift start; guests sign in when they are already committed to booking and want to
finish. After a correct staff password the flow does not end — it continues to an OTP
screen, so this surface is step one of two for its primary user.

## Capabilities and Constraints

- `GET /login` renders the single sign-in surface; `POST /login` (`login.attempt`) authenticates.
- `POST /signup` registers a guest. The registration form currently shares the login page as a second panel, and stays there (confirmed).
- Throttle: 5 attempts per `email|IP`, 15-minute decay, checked **before** any account lookup.
- Every failure returns one identical message, "Invalid email or password." A miss still costs one bcrypt comparison so timing reveals nothing. Neither property may be weakened by a design change.
- Suspension is reported only after the password is proven.
- Staff success → `staff.otp.form` (six-digit emailed code) → `/staff/dashboard` (admin, master_admin) or `/front-desk/dashboard` (frontdesk). Guest success → `/checkout`.
- Password reset exists at `password.request`; email verification at `verify-email`.
- Server-rendered Blade + Tailwind v4, Vite, Livewire. The public CSS bundle is `resources/css/app.css` importing strictly ordered numbered partials from `resources/css/public/`; **the import order is load-bearing and must not be reordered.**
- Tailwind scans compiled Blade, so CSS must be built via `npm run build` (which runs `view:cache` first), not `vite build`.

## Brand Commitments

- **Farmers Hostel**, under the **Auxiliary Services Program** of **Central Luzon State University**. **Est. 1998.** All confirmed real.
- Assets: `public/image/clsu.logo.png` (CLSU seal), `public/image/fh-mark.png`, `FHLogo.png`, plus real hostel photography (`hostel1.jpg`, `hostel-front.png`, `deluxe.jpg`, `dormitory1.jpg`, `roomtypes/`, `gallery/`).
- **Binding (confirmed):** the sign-in surface belongs to the same visual world as the rest
  of the public site — the "Boutique Farmstead" system already shipped in
  `resources/css/public/01-tokens.css` and `03-theme-boutique.css`. Continuity across the
  whole journey is the intent. The outgoing dark "tech console" auth skin was a divergence
  and is being retired, not preserved.

## Evidence on Hand

Real: the institutional identity above, the room *types* (executive / deluxe / dormitory /
double / triple / quadruple), and the hostel photography in `public/image/`.

**Explicitly absent — must never be fabricated (confirmed by the user):**

- No uptime, availability or service-level figure exists. The outgoing page's "98.4% Campus Service Uptime" was invented.
- No security certification, audit or badge exists. "SSL 256", "ENCRYPTED GATEWAY" and the holographic passcard serial "CARD 1998-AUX" were invented.
- **No confirmed room rates.** The ₱1,800 / ₱1,200 / ₱350 figures shown on the outgoing login page were invented and must not appear.
- No testimonials, guest counts, awards or press.

**Never describe the auth mechanism on an unauthenticated surface (confirmed).** An earlier
sign-in screen listed that staff receive an emailed six-digit code and that five failed
attempts lock an address for fifteen minutes. Both are accurate, and both are useful only
to someone attacking the form: the threshold says how far to push before backing off, and
the code line confirms which accounts carry a second factor. Accuracy is not the test here
— *"the wrong person learns nothing from trying"* is. Attempt limits, lockout windows,
resend caps, the existence of the staff second factor, and which guard an address belongs
to stay out of the UI. The server's error messages already say what a legitimate user
needs at the moment they need it.

## Product Principles

1. **Tell the truth or say nothing.** This product has a real institution behind it and no
   metrics; invented proof is worse than blank space.
2. **The door is not the destination.** Sign-in is a threshold, traversed daily by people
   who are not here to admire it. Cost of use beats depth of impression.
3. **One journey, one house.** Guest and staff, marketing and console, are the same
   property; a surface that looks like a different product is a defect.
4. **Reveal nothing on failure.** Enumeration resistance, uniform errors and constant-time
   misses are product requirements, and they constrain copy and UI states, not just PHP.

## Accessibility & Inclusion

No product-specific standard has been formally set, but the codebase establishes a working
floor that must be preserved: skip links, visible focus rings, `prefers-reduced-motion`
handling throughout, labelled controls, and `aria-*` state on tabs, toggles and alerts.
