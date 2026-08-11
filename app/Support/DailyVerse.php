<?php

namespace App\Support;

/**
 * The verse shown on the page-loading curtain (partials/page-loader.blade.php).
 *
 * Reads the payload committed by `php artisan verses:sync`; nothing here touches
 * the network, because the loader exists to cover a wait and cannot start by
 * waiting on a request of its own.
 *
 * Two verses come back per page, not one. A navigation is covered by two
 * documents — the outgoing page raises the curtain on click, the incoming one
 * is already wearing it — and a verse picked independently at each end would
 * visibly change text halfway through a single wait. So every page also carries
 * the verse it will HAND FORWARD: the outgoing curtain switches to it the
 * moment it is raised (while still invisible), and the incoming page picks it
 * up from sessionStorage instead of showing its own. One trip, one verse.
 *
 * The pick stays random rather than fixed. Staff cross the console many times a
 * shift, and re-reading one line every time is the opposite of a reason to put
 * text on a loading screen.
 */
class DailyVerse
{
    /**
     * The verse this page shows, and the one it hands to the page it navigates
     * to next. Distinct whenever there is more than one to choose from, so two
     * screens in a row never repeat.
     *
     * Either may be null when the payload is missing or empty — callers render
     * nothing rather than a placeholder.
     *
     * @return array{current: ?array{reference:string, text:string, label:string},
     *               next: ?array{reference:string, text:string, label:string}}
     */
    public static function pair(): array
    {
        $payload = self::payload();
        $verses = array_values($payload['verses'] ?? []);

        if ($verses === []) {
            return ['current' => null, 'next' => null];
        }

        $label = (string) ($payload['label'] ?? '');
        $current = array_rand($verses);

        // One verse in the file is a degenerate but legal payload; hand the
        // same one forward rather than failing to produce a next at all.
        $next = count($verses) === 1
            ? $current
            : ($current + random_int(1, count($verses) - 1)) % count($verses);

        return [
            'current' => self::shape($verses[$current], $label),
            'next' => self::shape($verses[$next], $label),
        ];
    }

    /**
     * @return array{reference:string, text:string, label:string}
     */
    private static function shape(array $verse, string $label): array
    {
        return [
            'reference' => (string) ($verse['reference'] ?? ''),
            'text' => (string) ($verse['text'] ?? ''),
            'label' => $label,
        ];
    }

    private static function payload(): array
    {
        $path = resource_path('data/verses.json');

        if (! is_readable($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?: [];
    }
}
