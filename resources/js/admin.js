/**
 * Staff console bundle — admin + front desk only.
 *
 * Kept separate from app.js because the public site loads that one: these
 * modules depend on jQuery and on window.openModal, neither of which exists
 * on a guest page, so bundling them together would both bloat the landing
 * page and throw on load.
 *
 * Every page module below guards on its own root element and no-ops elsewhere,
 * so this single bundle is safe on every staff screen.
 */

// axios + Laravel Echo/Reverb. These used to ride in app.js, but the public
// site loads that bundle and never makes an axios call or joins a channel —
// worse, with VITE_REVERB_HOST unset the guest pages spent their whole life
// retrying `wss://localhost:8080`. Staff screens are the only real consumers,
// so the socket is opened here. Must stay first: live-refresh and
// admin-notifications below both read window.Echo at import time.
import './bootstrap';

// Echo-driven console updates — inert without the import above.
import './live-refresh';
import './admin-notifications';

// Staff-only DOM behaviours (moved out of app.js for the same reason).
import './sortable-tables';
import './animated-content';
import './expandable-bento';
import './staff-tables';

// Shell behaviours shared by layouts/admin and layouts/frontdesk (entrance
// cleanup, card spotlight, KPI count-up, live clock, back-to-top, copy-ref).
// Both layouts used to carry their own inline copy of these.
import './staff-console';
// Confirm-then-submit for irreversible actions, with the double-submit guard.
import './staff-actions';

import './pages/admin-dashboard';
import './pages/admin-reports';
import './pages/admin-rooms';
import './pages/admin-user-records';
import './pages/staff-booking-form';
