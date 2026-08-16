/**
 * Generates app/Support/AdminIcons.php from the Font Awesome Free SVGs in
 * node_modules.
 *
 * Why generate instead of hand-writing paths: the admin console addresses icons
 * by intent ("arrival", "trend-up"), not by vendor name, and every call site
 * passes Tailwind box classes (`class="w-4 h-4"`). Inlining the real SVG keeps
 * those sizes working, keeps `currentColor` inheritance, and costs no webfont
 * request — while the MAP below stays the one place a glyph choice is made.
 *
 * The public site now reads the same registry through
 * components/booking/ui/icon-solid.blade.php, and it is no longer a
 * convenience there but the only way to draw a Font Awesome glyph: the public
 * layout stopped loading the webfont entirely (see layouts/public/base). Every
 * glyph a guest-facing view needs must therefore resolve here — a name that
 * does not is a blank box, where it used to silently fall back to `<i>`.
 *
 * The staff consoles still load the webfont, so bare `<i class="fa-…">` keeps
 * working in admin and front-desk views.
 *
 * ALIASES: each icon is registered twice, under its intent name and under its
 * Font Awesome name. Views that pass a vendor name straight through — the
 * account nav's `$item['icon']`, `<x-booking.ui.card icon="user-gear">` — then
 * resolve without every call site having to be renamed to an intent.
 *
 * Re-run after changing MAP or bumping the package:
 *
 *   npm run icons:build
 */
import { readFile, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const fa = join(root, 'node_modules', '@fortawesome', 'fontawesome-free');

/**
 * registry name => [font-awesome name, style]
 * Style is `solid` unless an outline reads better at small sizes.
 */
const MAP = {
    // ── navigation / chrome ──────────────────────────────────────────
    'dashboard': ['gauge-high', 'solid'],
    'plus': ['plus', 'solid'],
    'minus': ['minus', 'solid'],
    'printer': ['print', 'solid'],
    'zap': ['bolt', 'solid'],
    'x': ['xmark', 'solid'],
    'chevron-left': ['chevron-left', 'solid'],
    'chevron-right': ['chevron-right', 'solid'],
    'chevron-down': ['chevron-down', 'solid'],
    'chevron-up': ['chevron-up', 'solid'],
    'arrow-right': ['arrow-right', 'solid'],
    'arrow-left': ['arrow-left', 'solid'],
    'arrow-up-right': ['arrow-up-right-from-square', 'solid'],
    'kebab': ['ellipsis-vertical', 'solid'],
    'ellipsis': ['ellipsis', 'solid'],
    'search': ['magnifying-glass', 'solid'],
    'menu': ['bars', 'solid'],
    'grid': ['table-cells-large', 'solid'],
    'layers': ['layer-group', 'solid'],
    'filter': ['filter', 'solid'],
    'download': ['download', 'solid'],
    'upload': ['upload', 'solid'],
    'refresh': ['arrows-rotate', 'solid'],
    'expand': ['expand', 'solid'],
    'external': ['arrow-up-right-from-square', 'solid'],
    'copy': ['copy', 'regular'],
    'sliders': ['sliders', 'solid'],

    // ── status / feedback ────────────────────────────────────────────
    'check': ['check', 'solid'],
    'check-circle': ['circle-check', 'solid'],
    'x-circle': ['circle-xmark', 'solid'],
    'info': ['circle-info', 'solid'],
    'alert': ['triangle-exclamation', 'solid'],
    'alert-circle': ['circle-exclamation', 'solid'],
    'clock': ['clock', 'solid'],
    'history': ['clock-rotate-left', 'solid'],
    'trend-up': ['arrow-trend-up', 'solid'],
    'trend-down': ['arrow-trend-down', 'solid'],
    // Plain wrench, not screwdriver-wrench: this one renders at 12px in stat
    // footnotes, where the two-tool glyph collapses into a smudge.
    'wrench': ['wrench', 'solid'],
    'droplet': ['droplet', 'solid'],
    'broom': ['broom', 'solid'],
    'sparkle': ['wand-magic-sparkles', 'solid'],
    'star': ['star', 'solid'],
    'fire': ['fire', 'solid'],

    // ── hostel domain ────────────────────────────────────────────────
    'bed': ['bed', 'solid'],
    'clipboard': ['clipboard-list', 'solid'],
    'clipboard-check': ['clipboard-check', 'solid'],
    'users': ['users', 'solid'],
    'user': ['user', 'solid'],
    'user-check': ['user-check', 'solid'],
    'user-plus': ['user-plus', 'solid'],
    'id-card': ['id-card', 'solid'],
    'briefcase': ['briefcase', 'solid'],
    'phone': ['phone', 'solid'],
    'mail': ['envelope', 'solid'],
    'maximize': ['expand', 'solid'],
    'receipt': ['receipt', 'solid'],
    'credit-card': ['credit-card', 'solid'],
    'peso': ['peso-sign', 'solid'],
    'wallet': ['wallet', 'solid'],
    'invoice': ['file-invoice-dollar', 'solid'],
    'calendar': ['calendar-days', 'solid'],
    'calendar-plus': ['calendar-plus', 'solid'],
    'calendar-check': ['calendar-check', 'solid'],
    'log-in': ['right-to-bracket', 'solid'],
    'log-out': ['right-from-bracket', 'solid'],
    'arrival': ['right-to-bracket', 'solid'],
    'departure': ['right-from-bracket', 'solid'],
    'door': ['door-open', 'solid'],
    'key': ['key', 'solid'],
    'block': ['ban', 'solid'],
    'shield': ['shield-halved', 'solid'],
    'chart-bar': ['chart-column', 'solid'],
    'chart-line': ['chart-line', 'solid'],
    'chart-pie': ['chart-pie', 'solid'],
    'tag': ['tag', 'solid'],
    'percent': ['percent', 'solid'],
    'map-pin': ['location-dot', 'solid'],
    'building': ['building', 'solid'],

    // ── CRUD affordances ─────────────────────────────────────────────
    'edit': ['pen-to-square', 'solid'],
    'eye': ['eye', 'solid'],
    'eye-off': ['eye-slash', 'solid'],
    'trash': ['trash-can', 'solid'],
    'note': ['file-lines', 'solid'],
    'file': ['file', 'solid'],
    'settings': ['gear', 'solid'],
    'bell': ['bell', 'solid'],
    'lock': ['lock', 'solid'],
    'unlock': ['lock-open', 'solid'],
    'list': ['list-ul', 'solid'],
    'list-check': ['list-check', 'solid'],
    'table': ['table-list', 'solid'],

    // ── guest-facing booking journey ─────────────────────────────────
    // Added when the public site dropped the Font Awesome webfont; these are
    // the glyphs checkout, booking detail, payment proof, discount,
    // reschedule and the account pages were drawing with `<i class="fa-…">`.
    'hourglass': ['hourglass', 'solid'],
    'hourglass-half': ['hourglass-half', 'solid'],
    'id-badge': ['id-badge', 'solid'],
    'file-upload': ['file-arrow-up', 'solid'],
    'bank': ['building-columns', 'solid'],
    'lightbulb': ['lightbulb', 'solid'],
    'utensils': ['utensils', 'solid'],
    'user-tie': ['user-tie', 'solid'],
    'user-gear': ['user-gear', 'solid'],
    'spinner': ['spinner', 'solid'],
    'phone-volume': ['phone-volume', 'solid'],
    'send': ['paper-plane', 'solid'],
    'money': ['money-bill-wave', 'solid'],
    'save': ['floppy-disk', 'solid'],
    'house': ['house', 'solid'],
    'book': ['book', 'solid'],
    'mobile-screen-button': ['mobile-screen-button', 'solid'],
};

const entries = [];
const missing = [];

for (const [key, [faName, style]] of Object.entries(MAP)) {
    const file = join(fa, 'svgs', style, `${faName}.svg`);
    let raw;

    try {
        raw = await readFile(file, 'utf8');
    } catch {
        missing.push(`${key} -> ${style}/${faName}`);
        continue;
    }

    const viewBox = raw.match(/viewBox="([^"]+)"/)?.[1];
    const d = raw.match(/\sd="([^"]+)"/)?.[1];

    if (!viewBox || !d) {
        missing.push(`${key} -> ${style}/${faName} (unparseable)`);
        continue;
    }

    entries.push({ key, faName, style, viewBox, d });
}

if (missing.length) {
    console.error('Unresolved icons:\n  ' + missing.join('\n  '));
    process.exitCode = 1;
}

const { version } = JSON.parse(await readFile(join(fa, 'package.json'), 'utf8'));

const body = entries
    .map(
        (e) =>
            `        '${e.key}' => ['${e.viewBox}', '${e.d}'], // fa-${e.style === 'solid' ? 'solid' : e.style} fa-${e.faName}`
    )
    .join('\n');

/*
 * Vendor-name aliases. An intent key always wins — `search` stays the
 * magnifying glass even though `magnifying-glass` also resolves — and the
 * first intent to claim a glyph owns its alias, so the several intents that
 * share one glyph (arrival/log-in, maximize/expand) collapse harmlessly onto
 * the same path data.
 */
const claimed = new Set(entries.map((e) => e.key));
const aliases = [];

for (const e of entries) {
    if (claimed.has(e.faName)) continue;
    claimed.add(e.faName);
    aliases.push(`        '${e.faName}' => '${e.key}',`);
}

const php = `<?php

namespace App\\Support;

/**
 * GENERATED FILE — do not edit by hand.
 *
 * Written by scripts/build-icon-registry.mjs from Font Awesome Free ${version}
 * (icons CC BY 4.0). Change the MAP in that script and re-run:
 *
 *     npm run icons:build
 *
 * Consumed by resources/views/components/admin/ui/icon.blade.php and
 * resources/views/components/booking/ui/icon-solid.blade.php, which are the
 * only things in the app that should call these methods.
 */
final class AdminIcons
{
    /** name => [viewBox, path data] */
    private const ICONS = [
${body}
    ];

    /** Font Awesome name => intent name in ICONS above. */
    private const ALIASES = [
${aliases.join('\n')}
    ];

    /** Fallback keeps a typo rendering *something* rather than a blank box. */
    public const FALLBACK = 'grid';

    /**
     * @return array{0: string, 1: string} [viewBox, path data]
     */
    public static function get(string $name): array
    {
        return self::ICONS[$name]
            ?? self::ICONS[self::ALIASES[$name] ?? '']
            ?? self::ICONS[self::FALLBACK];
    }

    public static function has(string $name): bool
    {
        return isset(self::ICONS[$name]) || isset(self::ALIASES[$name]);
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::ICONS);
    }
}
`;

await writeFile(join(root, 'app', 'Support', 'AdminIcons.php'), php, 'utf8');
console.log(`AdminIcons.php — ${entries.length} icons from Font Awesome Free ${version}`);
