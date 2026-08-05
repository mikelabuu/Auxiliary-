<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response hardening headers.
 *
 * The app previously sent none of these. The one that mattered most is
 * X-Frame-Options: without it any site can load the staff console in an
 * invisible iframe over its own page and collect the clicks of a signed-in
 * admin — approve a discount, verify a payment, suspend a user — with the
 * admin seeing only whatever the attacker chose to display. The session is
 * valid, the request is genuine, and nothing in the logs looks unusual.
 *
 * DENY rather than SAMEORIGIN because nothing in this app frames anything: a
 * search for <iframe across every Blade view returns nothing, so the stricter
 * value costs us no functionality.
 *
 * WHY THERE IS NO Content-Security-Policy HERE
 * --------------------------------------------
 * CSP is the biggest win left, and it cannot be switched on yet. The app has
 * inline <script> blocks in roughly 29 Blade files, inline event handlers, and
 * loads fonts and libraries from CDNs. Any policy strict enough to be worth
 * having would break all of it, and one loose enough not to ('unsafe-inline')
 * would buy nothing while looking like protection — which is worse than
 * absence, because it stops anyone asking.
 *
 * The inline-script extraction is already on the cleanup list. CSP belongs
 * immediately after it, not before.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            // No page here is ever framed; block it outright.
            'X-Frame-Options' => 'DENY',

            // Stop the browser second-guessing Content-Type. Without it, an
            // uploaded payment proof served as one type can be sniffed into
            // another and executed.
            'X-Content-Type-Options' => 'nosniff',

            // Send the full URL only to ourselves. Staff URLs carry booking
            // ids and search terms; those should not ride along to a CDN or an
            // external link's access log.
            'Referrer-Policy' => 'strict-origin-when-cross-origin',

            // None of these are used. Denying them means a compromised script
            // cannot quietly turn any of them on.
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        ];

        // HSTS only over a secure connection. Sent over plain HTTP it is
        // ignored by browsers, and setting it from a local dev box that is
        // later reached over http would pin a host to HTTPS it cannot serve.
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            // Do not clobber a header a specific response set deliberately.
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
