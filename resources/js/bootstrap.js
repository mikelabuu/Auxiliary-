import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ── Laravel Echo + Reverb (real-time WebSocket push) ─────────────────────────
// Connects to the Reverb server so pages can react instantly to broadcast
// events (room status changes, booking updates) instead of only polling.
// The connection itself is built in ./echo — keep it there and only there, or
// the second `new Echo()` silently opens a duplicate socket and orphans the first.
import './echo';
