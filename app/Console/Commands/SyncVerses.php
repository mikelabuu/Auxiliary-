<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Pull the loading-screen verse texts into the repo.
 *
 * Same shape as psgc:sync, and for the same reason: the console must not make
 * a third-party request to render a page. wldeh/bible-api is static JSON on
 * jsDelivr, which is fast and free, but a CDN that is slow or blocked would
 * otherwise be able to hold up a staff dashboard for a decorative line of
 * text. Fetch once, commit the result, render from disk.
 *
 * TRANSLATION: King James Version, which is public domain worldwide. That is a
 * deliberate constraint rather than a preference — the upstream repo carries
 * 200+ versions and many modern ones (NIV, ESV, NLT) are under copyright that
 * does not permit redistributing their text inside an application. If you want
 * a different translation, check its licence before changing VERSION; ASV
 * (en-asv) and the World English Bible (en-web) are the other public-domain
 * options already present upstream.
 */
class SyncVerses extends Command
{
    // Not --version: Artisan reserves that one globally.
    protected $signature = 'verses:sync {--translation= : Override the translation id (must be public domain)}';

    protected $description = 'Refresh the bundled loading-screen verses from wldeh/bible-api.';

    private const CDN = 'https://cdn.jsdelivr.net/gh/wldeh/bible-api/bibles';

    private const VERSION = 'en-kjv';

    private const LABEL = 'King James Version';

    /**
     * Chosen for a hostel front desk: welcome, service, steady work and rest.
     * Book slugs are lowercase with no separators — "1peter", "psalms".
     *
     * @var array<int, array{0:string, 1:string, 2:int, 3:int}>  slug, display name, chapter, verse
     */
    private const REFERENCES = [
        ['psalms', 'Psalm', 121, 8],
        ['hebrews', 'Hebrews', 13, 2],
        ['1peter', '1 Peter', 4, 9],
        ['romans', 'Romans', 12, 13],
        ['matthew', 'Matthew', 25, 35],
        ['romans', 'Romans', 15, 7],
        ['colossians', 'Colossians', 3, 23],
        ['proverbs', 'Proverbs', 16, 3],
        ['psalms', 'Psalm', 127, 1],
        ['galatians', 'Galatians', 6, 9],
        ['proverbs', 'Proverbs', 3, 5],
        ['joshua', 'Joshua', 1, 9],
        ['isaiah', 'Isaiah', 40, 31],
        ['ecclesiastes', 'Ecclesiastes', 9, 10],
        ['1corinthians', '1 Corinthians', 10, 31],
        ['proverbs', 'Proverbs', 22, 29],
        ['luke', 'Luke', 6, 31],
        ['philippians', 'Philippians', 2, 4],
        ['psalms', 'Psalm', 90, 17],
        ['colossians', 'Colossians', 3, 17],
        ['proverbs', 'Proverbs', 15, 1],
        ['james', 'James', 1, 19],
        ['matthew', 'Matthew', 5, 16],
        ['psalms', 'Psalm', 118, 24],
        ['proverbs', 'Proverbs', 11, 25],
        ['1thessalonians', '1 Thessalonians', 5, 18],
        ['nehemiah', 'Nehemiah', 8, 10],
        ['micah', 'Micah', 6, 8],
        ['philippians', 'Philippians', 4, 13],
        ['psalms', 'Psalm', 23, 1],
    ];

    public function handle(): int
    {
        $version = $this->option('translation') ?: self::VERSION;
        $path = resource_path('data/verses.json');

        $this->info("Fetching " . count(self::REFERENCES) . " verses ({$version}).");

        $verses = [];
        $failed = 0;

        foreach (self::REFERENCES as [$slug, $name, $chapter, $verse]) {
            $url = self::CDN . "/{$version}/books/{$slug}/chapters/{$chapter}/verses/{$verse}.json";

            try {
                $response = Http::timeout(30)->get($url);

                if (! $response->successful()) {
                    throw new \RuntimeException("HTTP {$response->status()}");
                }

                $text = trim((string) ($response->json()['text'] ?? ''));

                if ($text === '') {
                    throw new \RuntimeException('empty text');
                }

                $verses[] = [
                    'reference' => "{$name} {$chapter}:{$verse}",
                    'text'      => $text,
                ];
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("  skipped {$name} {$chapter}:{$verse} — {$e->getMessage()}");
            }
        }

        if ($verses === []) {
            $this->error('Nothing fetched. The committed copy is untouched.');

            return self::FAILURE;
        }

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'version'      => $version,
            'label'        => $version === self::VERSION ? self::LABEL : $version,
            'source'       => 'https://github.com/wldeh/bible-api',
            'verses'       => $verses,
        ];

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->info('Wrote ' . count($verses) . ' verses to ' . $path);

        if ($failed > 0) {
            $this->warn("{$failed} reference(s) could not be fetched and are absent from the file.");
        }

        return self::SUCCESS;
    }
}
