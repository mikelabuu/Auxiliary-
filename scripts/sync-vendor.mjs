/**
 * Copies third-party browser libraries out of node_modules and into
 * public/vendor/ so the app can serve them itself.
 *
 * Why these aren't just bundled by Vite: six admin views call `$(function(){…})`
 * at parse time, and @vite() emits type="module" scripts, which are deferred and
 * therefore run *after* those inline blocks. Bundling jQuery would break them.
 * Serving the same files locally from a plain synchronous <script src> keeps the
 * original load order and globals exactly as they were.
 *
 * Why not a CDN: the versions were unpinned (jsDelivr resolves `npm/chart.js` to
 * whatever is newest), nothing carried an integrity hash, and a blocked CDN took
 * the whole staff console's date pickers, dialogs, and charts down with it.
 *
 * The npm versions are the source of truth — package-lock.json pins them and npm
 * verifies their hashes on install. Re-run after changing any of those versions:
 *
 *   npm run vendor:sync
 */
import { cp, mkdir, rm, stat } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const from = (p) => join(root, 'node_modules', p);
const to = (p) => join(root, 'public', 'vendor', p);

/** [source in node_modules, destination under public/vendor] */
const ASSETS = [
    ['jquery/dist/jquery.min.js', 'jquery/jquery.min.js'],
    ['sweetalert2/dist/sweetalert2.all.min.js', 'sweetalert2/sweetalert2.all.min.js'],
    ['chart.js/dist/chart.umd.min.js', 'chart.js/chart.umd.min.js'],
    ['sweetalert/dist/sweetalert.min.js', 'sweetalert/sweetalert.min.js'],
    ['flatpickr/dist/flatpickr.min.js', 'flatpickr/flatpickr.min.js'],
    ['flatpickr/dist/flatpickr.min.css', 'flatpickr/flatpickr.min.css'],
    ['swiper/swiper-bundle.min.js', 'swiper/swiper-bundle.min.js'],
    ['swiper/swiper-bundle.min.css', 'swiper/swiper-bundle.min.css'],
    // Lenis was dropped: it drove scrolling from the main thread, which is why
    // desktop scrolled worse than mobile. See layouts/public/base.blade.php.

    // Alpine, standalone. Livewire bundles its own copy, so this is loaded
    // only on public pages that use Alpine directives WITHOUT having a
    // Livewire component on them - the landing page's room modal and the
    // rooms/show tabs. Those pages used to pull Livewire's whole 340 KB
    // runtime just to get Alpine; this is ~45 KB. Two Alpines on one page
    // throw, so layouts/public/base picks exactly one (see the @stack there).
    ['alpinejs/dist/cdn.min.js', 'alpine/alpine.min.js'],

    // lightbox.min.css resolves its controls as url(../images/…), so the
    // css/ and images/ folders have to stay siblings.
    ['lightbox2/dist/js/lightbox-plus-jquery.min.js', 'lightbox2/js/lightbox-plus-jquery.min.js'],
    ['lightbox2/dist/css/lightbox.min.css', 'lightbox2/css/lightbox.min.css'],
    ['lightbox2/dist/images', 'lightbox2/images'],

    // Font Awesome Free — the icon system for both consoles. all.min.css
    // resolves its faces as url(../webfonts/…), so css/ and webfonts/ have to
    // stay siblings, same as lightbox above. Blade components go through
    // app/Support/AdminIcons (inlined SVG, no network); this stylesheet is what
    // makes a bare `<i class="fa-solid fa-bed">` work in any view.
    ['@fortawesome/fontawesome-free/css/all.min.css', 'fontawesome/css/all.min.css'],
    ['@fortawesome/fontawesome-free/webfonts', 'fontawesome/webfonts'],

    // ── Public type system: Playfair Display, Oswald, Manrope ────────
    //
    // These used to come from fonts.googleapis.com. Two extra origins on the
    // critical path is cheap when the tester sits next to the server and
    // expensive when it does not: this app is served from Kuala Lumpur with no
    // CDN, and a distant client pays DNS + TCP + TLS to googleapis *and* to
    // gstatic before the first glyph is even requested. Measured with the
    // Lighthouse desktop preset against the live site, the performance score
    // tracks round-trip time almost exactly — 98 at 40ms RTT, 58 at 250ms —
    // while total blocking time stays pinned at 0ms. The score is bound by
    // connection setup, so removing whole origins from the path is the lever.
    //
    // Variable fonts, so one file per family covers every weight the three
    // public layouts ask for (Playfair 400-700 + italic, Oswald 300-500,
    // Manrope 400-700) instead of a file per weight.
    //
    // Only latin and latin-ext are synced. public/fonts.css keeps Google's own
    // `unicode-range` values on each face, so latin-ext is fetched lazily — a
    // page with no accented characters never downloads it. The other four
    // subsets Fontsource ships (cyrillic, greek, vietnamese) are not copied:
    // nothing in this app renders them.
    ['@fontsource-variable/playfair-display/files/playfair-display-latin-wght-normal.woff2', 'fonts/playfair-display-latin-wght-normal.woff2'],
    ['@fontsource-variable/playfair-display/files/playfair-display-latin-wght-italic.woff2', 'fonts/playfair-display-latin-wght-italic.woff2'],
    ['@fontsource-variable/playfair-display/files/playfair-display-latin-ext-wght-normal.woff2', 'fonts/playfair-display-latin-ext-wght-normal.woff2'],
    ['@fontsource-variable/playfair-display/files/playfair-display-latin-ext-wght-italic.woff2', 'fonts/playfair-display-latin-ext-wght-italic.woff2'],
    ['@fontsource-variable/oswald/files/oswald-latin-wght-normal.woff2', 'fonts/oswald-latin-wght-normal.woff2'],
    ['@fontsource-variable/oswald/files/oswald-latin-ext-wght-normal.woff2', 'fonts/oswald-latin-ext-wght-normal.woff2'],
    ['@fontsource-variable/manrope/files/manrope-latin-wght-normal.woff2', 'fonts/manrope-latin-wght-normal.woff2'],
    ['@fontsource-variable/manrope/files/manrope-latin-ext-wght-normal.woff2', 'fonts/manrope-latin-ext-wght-normal.woff2'],
];

// Only ever clear the folders this script owns. public/vendor/ is shared —
// `php artisan livewire:publish --assets` puts livewire/ in here too, and
// wiping the whole directory silently deletes it.
const OWNED = [...new Set(ASSETS.map(([, dest]) => dest.split('/')[0]))];
for (const dir of OWNED) {
    await rm(to(dir), { recursive: true, force: true });
}

let bytes = 0;
for (const [src, dest] of ASSETS) {
    const source = from(src);

    try {
        await stat(source);
    } catch {
        console.error(`  missing: node_modules/${src} — run npm install first`);
        process.exitCode = 1;
        continue;
    }

    await mkdir(dirname(to(dest)), { recursive: true });
    await cp(source, to(dest), { recursive: true });

    const { size, isDirectory } = await stat(source).then((s) => ({
        size: s.size,
        isDirectory: s.isDirectory(),
    }));
    if (!isDirectory) bytes += size;

    console.log(`  ${dest}`);
}

console.log(`\nvendor assets synced (${(bytes / 1024).toFixed(0)} KB of js/css)`);
