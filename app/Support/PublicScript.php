<?php

namespace App\Support;

/**
 * Resolves a script in public/js/ to its minified sibling when one exists.
 *
 * The files in public/js/ are hand-written classic scripts that sit outside the
 * Vite graph, so nothing was minifying them — Lighthouse measured 20 KiB of
 * comments and whitespace shipping on every page load. `npm run js:minify`
 * (scripts/minify-public-js.mjs) now emits a *.min.js next to each one, and
 * this is the single place that decides which of the two a view links to.
 *
 * A missing *.min.js is deliberately not an error: it falls back to the source
 * file, which keeps `npm run dev` working against readable, debuggable scripts
 * and means a deploy that skipped the build step degrades to "unminified"
 * rather than to a 404.
 *
 * The cache-buster is taken from whichever file is actually served, so editing
 * a source during development busts the cache even before it is re-minified.
 */
class PublicScript
{
    /** Resolved paths for this process; the filesystem cannot change mid-request. */
    private static array $resolved = [];

    /**
     * Versioned URL for a public/js script.
     *
     *   <script src="{{ \App\Support\PublicScript::url('js/home.js') }}" defer></script>
     *
     * @param  string  $path  Public-relative path, e.g. `js/home.js`.
     */
    public static function url(string $path): string
    {
        $path = ltrim($path, '/');

        return self::$resolved[$path] ??= self::resolve($path);
    }

    private static function resolve(string $path): string
    {
        $minified = preg_replace('/\.js$/', '.min.js', $path);

        // Only prefer the minified copy when it is at least as new as its
        // source. A stale *.min.js — source edited, builder not re-run — would
        // otherwise be served silently, which is the one failure mode of this
        // whole arrangement that is genuinely hard to notice.
        $served = $path;

        if ($minified !== null && $minified !== $path && is_file(public_path($minified))) {
            $sourceTime = @filemtime(public_path($path)) ?: 0;

            if (@filemtime(public_path($minified)) >= $sourceTime) {
                $served = $minified;
            }
        }

        $version = @filemtime(public_path($served)) ?: 0;

        return asset($served).'?v='.$version;
    }
}
