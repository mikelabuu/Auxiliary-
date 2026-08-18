{{-- Reverb connection details, handed to the browser by the server.

     These used to reach the client only as VITE_REVERB_*. Vite freezes
     import.meta.env at build time, and this project builds locally and commits
     public/build (the host cannot run npm — see the deploy notes), so the
     committed bundle shipped whatever the developer's .env happened to say.
     Production was therefore telling every staff browser to open
     ws://localhost:80 — the visitor's own machine — which can never connect.

     Reading them from config here instead means one bundle works in every
     environment and production is configured by production's .env, with no
     rebuild needed to point at a different host.

     Only the public app key is exposed, which is what the client must present
     to open a socket. REVERB_APP_SECRET stays server-side and is never emitted.

     Must stay ahead of the vite tag: this is a synchronous script and the
     bundle is a deferred module, so window.__REVERB__ is set before it runs. --}}
@php
    $reverbConfig = config('broadcasting.default') === 'reverb'
        ? [
            'key'    => config('broadcasting.connections.reverb.key'),
            'host'   => config('broadcasting.connections.reverb.options.host'),
            'port'   => (int) config('broadcasting.connections.reverb.options.port'),
            'scheme' => config('broadcasting.connections.reverb.options.scheme'),
        ]
        : null;
@endphp
<script>window.__REVERB__ = @json($reverbConfig);</script>
