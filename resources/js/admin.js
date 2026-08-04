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
