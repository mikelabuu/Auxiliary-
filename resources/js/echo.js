import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Connection details come from the server: partials/reverb sets
// window.__REVERB__ from config, synchronously, ahead of this deferred bundle.
//
// Deliberately NOT import.meta.env.VITE_REVERB_*. Vite freezes those at build
// time and this project commits public/build (the host cannot run npm), so
// reading them here — even only as a fallback — bakes whoever built the bundle
// into production. That is how every staff browser ended up dialling
// ws://localhost: the committed bundle carried a developer's .env.
const config = window.__REVERB__;

// Unconfigured means no Reverb: leave window.Echo undefined rather than open a
// socket that can never connect and then retries for the life of the page.
// Every consumer already guards on `if (!window.Echo)` and falls back to
// polling, so this is the documented degraded mode, not a new failure path.
if (config && config.key && config.host) {
    const port = Number(config.port) || (config.scheme === 'https' ? 443 : 80);

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: config.key,
        wsHost: config.host,
        wsPort: port,
        wssPort: port,
        forceTLS: config.scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
