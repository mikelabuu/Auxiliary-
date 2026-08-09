<?php

namespace App\Support;

/**
 * The verse shown on the page-loading curtain (partials/page-loader.blade.php).
 *
 * Reads the payload committed by `php artisan verses:sync`; nothing here touches
 * the network, because the loader exists to cover a wait and cannot start by
 * waiting on a request of its own.
 *
 * The pick is random per page load on purpose. Staff cross the console many
 * times a shift, and re-reading one fixed line every time is the opposite of a
 * reason to put text on a loading screen.
 */
class DailyVerse
{
    /**
     * @return array{reference:string, text:string, label:string}|null
     *         Null when the payload is missing or empty — callers render
     *         nothing rather than a placeholder.
     */
    public static function random(): ?array
    {
        $payload = self::payload();
        $verses = $payload['verses'] ?? [];

        if ($verses === []) {
            return null;
        }

        $chosen = $verses[array_rand($verses)];

        return [
            'reference' => (string) ($chosen['reference'] ?? ''),
            'text'      => (string) ($chosen['text'] ?? ''),
            'label'     => (string) ($payload['label'] ?? ''),
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
