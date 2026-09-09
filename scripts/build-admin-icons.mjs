/**
 * Generate the admin's inline Hugeicons Stroke Rounded SVGs.
 * Public booking icons keep their existing Font Awesome registry.
 * Edit MAP and run npm run icons:admin. No icon JavaScript is sent to browsers.
 * Source: @hugeicons/core-free-icons (MIT); notice in licenses/hugeicons.txt.
 */
import { readFile, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const pack = join(root, 'node_modules/@hugeicons/core-free-icons');
// Intent => [official export, optional legacy Font Awesome alias].
const MAP = {
    'dashboard': ['DashboardSquare02Icon', 'gauge-high'],
    'plus': ['Add01Icon'],
    'minus': ['Remove01Icon'],
    'printer': ['PrinterIcon', 'print'],
    'zap': ['FlashIcon', 'bolt'],
    'x': ['Cancel01Icon', 'xmark'],
    'chevron-left': ['ArrowLeft01Icon'],
    'chevron-right': ['ArrowRight01Icon'],
    'chevron-down': ['ArrowDown01Icon'],
    'chevron-up': ['ArrowUp01Icon'],
    'arrow-right': ['ArrowRight02Icon'],
    'arrow-left': ['ArrowLeft02Icon'],
    'arrow-up-right': ['LinkSquare02Icon', 'arrow-up-right-from-square'],
    'kebab': ['MoreVerticalIcon', 'ellipsis-vertical'],
    'ellipsis': ['MoreHorizontalIcon'],
    'search': ['Search01Icon', 'magnifying-glass'],
    'menu': ['Menu01Icon', 'bars'],
    'grid': ['GridViewIcon', 'table-cells-large'],
    'layers': ['Layers01Icon', 'layer-group'],
    'filter': ['FilterHorizontalIcon'],
    'download': ['Download04Icon'],
    'upload': ['Upload04Icon'],
    'refresh': ['RefreshIcon', 'arrows-rotate'],
    'expand': ['Maximize01Icon'],
    'external': ['LinkSquare02Icon', 'arrow-up-right-from-square'],
    'copy': ['Copy01Icon'],
    'sliders': ['Settings04Icon'],
    'check': ['Tick02Icon'],
    'check-circle': ['CheckmarkCircle02Icon', 'circle-check'],
    'x-circle': ['CancelCircleIcon', 'circle-xmark'],
    'info': ['InformationCircleIcon', 'circle-info'],
    'alert': ['Alert02Icon', 'triangle-exclamation'],
    'alert-circle': ['AlertCircleIcon', 'circle-exclamation'],
    'clock': ['Clock01Icon'],
    'history': ['TransactionHistoryIcon', 'clock-rotate-left'],
    'trend-up': ['TradeUpIcon', 'arrow-trend-up'],
    'trend-down': ['TradeDownIcon', 'arrow-trend-down'],
    'wrench': ['Wrench01Icon'],
    'droplet': ['DropletIcon'],
    'broom': ['CleanIcon'],
    'sparkle': ['SparklesIcon', 'wand-magic-sparkles'],
    'star': ['StarIcon'],
    'fire': ['FireIcon'],
    'bed': ['BedDoubleIcon'],
    'clipboard': ['Task01Icon', 'clipboard-list'],
    'clipboard-check': ['TaskDone01Icon'],
    'users': ['UserMultipleIcon'],
    'user': ['UserIcon'],
    'user-check': ['UserCheck01Icon'],
    'user-plus': ['UserAdd01Icon'],
    'id-card': ['IdentityCardIcon'],
    'briefcase': ['Briefcase01Icon'],
    'phone': ['Call02Icon'],
    'mail': ['Mail01Icon', 'envelope'],
    'maximize': ['Maximize01Icon', 'expand'],
    'receipt': ['Invoice01Icon'],
    'credit-card': ['CreditCardIcon'],
    'peso': ['PhilippinePesoIcon', 'peso-sign'],
    'wallet': ['Wallet01Icon'],
    'invoice': ['Invoice03Icon', 'file-invoice-dollar'],
    'calendar': ['Calendar03Icon', 'calendar-days'],
    'calendar-plus': ['CalendarAdd01Icon'],
    'calendar-check': ['CalendarCheckIn01Icon'],
    'log-in': ['Login02Icon', 'right-to-bracket'],
    'log-out': ['Logout02Icon', 'right-from-bracket'],
    'arrival': ['Login02Icon', 'right-to-bracket'],
    'departure': ['Logout02Icon', 'right-from-bracket'],
    'door': ['Door01Icon', 'door-open'],
    'key': ['Key01Icon'],
    'block': ['UnavailableIcon', 'ban'],
    'shield': ['Shield01Icon', 'shield-halved'],
    'chart-bar': ['ChartHistogramIcon', 'chart-column'],
    'chart-line': ['ChartLineData02Icon'],
    'chart-pie': ['PieChart01Icon'],
    'tag': ['SaleTag01Icon'],
    'percent': ['PercentIcon'],
    'map-pin': ['Location01Icon', 'location-dot'],
    'building': ['Building03Icon'],
    'edit': ['Edit02Icon', 'pen-to-square'],
    'eye': ['ViewIcon'],
    'eye-off': ['ViewOffIcon', 'eye-slash'],
    'trash': ['Delete02Icon', 'trash-can'],
    'note': ['Note01Icon', 'file-lines'],
    'file': ['File01Icon'],
    'settings': ['Settings01Icon', 'gear'],
    'bell': ['Notification03Icon'],
    'lock': ['SquareLock01Icon'],
    'unlock': ['SquareUnlock01Icon', 'lock-open'],
    'list': ['ListViewIcon', 'list-ul'],
    'list-check': ['TaskDaily01Icon'],
    'table': ['Table01Icon', 'table-list'],
    'hourglass': ['HourglassIcon'],
    'hourglass-half': ['HourglassIcon'],
    'id-badge': ['IdCardLanyardIcon'],
    'file-upload': ['FileUploadIcon', 'file-arrow-up'],
    'bank': ['BankIcon', 'building-columns'],
    'lightbulb': ['BulbIcon'],
    'utensils': ['Restaurant01Icon'],
    'user-tie': ['UserAccountIcon'],
    'user-gear': ['UserSettings01Icon'],
    'spinner': ['Loading03Icon'],
    'phone-volume': ['CallRinging02Icon'],
    'send': ['SentIcon', 'paper-plane'],
    'money': ['Money01Icon', 'money-bill-wave'],
    'save': ['FloppyDiskIcon', 'floppy-disk'],
    'house': ['Home01Icon'],
    'book': ['Book01Icon'],
    'mobile-screen-button': ['SmartPhone01Icon'],
    'sidebar-left': ['SidebarLeft01Icon'],
    'exchange': ['Exchange01Icon'],
};

const tags = new Set(['path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon']);
const allowed = new Set(['d', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'width', 'height', 'points', 'transform', 'fill', 'fill-rule', 'clip-rule', 'opacity', 'stroke', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity', 'fill-opacity']);
const escapeXml = value => String(value).replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
const phpString = value => "'" + value.replaceAll('\\', '\\\\').replaceAll("'", "\\'") + "'";
function markup(shapes) {
    if (!Array.isArray(shapes) || !shapes.length) throw new Error('Empty icon');
    return shapes.map(([tag, attributes]) => {
        if (!tags.has(tag)) throw new Error('Unsupported SVG tag: ' + tag);
        const attrs = Object.entries(attributes).flatMap(([key, value]) => {
            // Weight and rounded ends inherit the shared component's 1.75px stroke.
            if (['key', 'strokeWidth', 'strokeLinecap', 'strokeLinejoin'].includes(key)) return [];
            const name = key.replace(/[A-Z]/g, match => '-' + match.toLowerCase());
            if (!allowed.has(name)) throw new Error('Unsupported SVG attribute: ' + name);
            if (/url\s*\(/i.test(String(value))) throw new Error('External SVG reference');
            return [name + '="' + escapeXml(value) + '"'];
        });
        return '<' + tag + ' ' + attrs.join(' ') + '/>';
    }).join('');
}

const icons = [];
const aliases = [];
const claimed = new Set(Object.keys(MAP));
for (const [name, [exportName, alias]] of Object.entries(MAP)) {
    const { default: shapes } = await import(pathToFileURL(join(pack, 'dist/esm', exportName + '.js')));
    icons.push('        ' + phpString(name) + ' => ' + phpString(markup(shapes)) + ', // ' + exportName);
    if (alias && !claimed.has(alias)) {
        claimed.add(alias);
        aliases.push('        ' + phpString(alias) + ' => ' + phpString(name) + ',');
    }
}
const { version } = JSON.parse(await readFile(join(pack, 'package.json'), 'utf8'));
const php = `<?php

namespace App\\Support;

/**
 * GENERATED by scripts/build-admin-icons.mjs from Hugeicons Stroke Rounded ${version} (MIT).
 * See licenses/hugeicons.txt. Change the generator MAP, then npm run icons:admin.
 * Only the admin icon component uses this registry; public glyphs are independent.
 */
final class AdminStrokeIcons
{
    private const ICONS = [
${icons.join('\n')}
    ];

    private const ALIASES = [
${aliases.join('\n')}
    ];

    public const FALLBACK = 'grid';

    /** Trusted SVG markup generated from the pinned package, never user input. */
    public static function get(string $name): string
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

const output = join(root, 'app/Support/AdminStrokeIcons.php');
if (process.argv.includes('--check')) {
    if (await readFile(output, 'utf8') !== php) throw new Error('AdminStrokeIcons.php is stale; run npm run icons:admin');
} else {
    await writeFile(output, php, 'utf8');
}
console.log('AdminStrokeIcons: ' + icons.length + ' Hugeicons, ' + aliases.length + ' legacy aliases; version ' + version);
