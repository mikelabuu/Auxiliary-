/**
 * Generates responsive image derivatives into public/image/derived/ plus the
 * manifest that <x-img> reads.
 *
 * Why this exists: the source art in public/image/ is print-sized. hostel1.jpg
 * was 8.8 MB at 2390px for a band that renders 915x333; farmers-hostel-wide.png
 * was 3.7 MB of PNG holding a photograph with a fully-opaque alpha channel;
 * clsu.logo.png was 1.26 MB at 2500px for a 40px seal that also served as the
 * favicon on every staff page. The auth page had already solved this by hand
 * (public/image/auth/seal-132.webp, 7 KB) — this generalises that fix so it
 * survives the next art drop instead of being re-done manually.
 *
 * Output is committed: derivatives are deterministic, and generating them at
 * request time would put sharp on the production path for no benefit.
 *
 * Re-run after changing SOURCES or replacing any source art:
 *
 *   npm run images:build          # only what is out of date
 *   npm run images:build -- --force
 */
import { mkdir, readdir, readFile, stat, writeFile } from 'node:fs/promises';
import { dirname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const imageDir = join(root, 'public', 'image');
const outDir = join(imageDir, 'derived');
const manifestPath = join(outDir, 'manifest.json');
const force = process.argv.includes('--force');

/**
 * source (relative to public/image) => widths to emit, or { widths, quality }.
 *
 * Widths are chosen from the real rendered size, doubled for retina, and stop
 * there — a derivative wider than the source is upscaling, and the builder
 * drops those automatically.
 *
 * **The smallest width is a sharpness decision, not just a weight one.**
 * Browsers pick the smallest candidate at or above the slot's device pixels, so
 * a ladder starting at exactly the CSS width hands back a 1:1 image. That is
 * measurably softer than what this site did before, where the browser
 * downscaled a 2048px source into a 400px card and supersampled it. Where the
 * photo *is* the product (room types, gallery), the ladder therefore starts
 * around 1.5x the largest slot so there is always something to downsample.
 */
const SOURCES = {
    // Front desk hero band (915x333 rendered, masked to the right half), the
    // auth split-panel, and the full-bleed CTA band on the landing page.
    'hostel1.jpg': [640, 1024, 1280, 1600, 2048],

    // Landing hero atmosphere: an out-of-focus wash plus a horizon band. Both
    // carry a heavy CSS blur, so detail here is invisible by construction and
    // this is the one source that genuinely wants a low quality setting.
    'farmers-hostel-wide.png': { widths: [480, 960, 1500], quality: 45 },

    // Landing hero cut-out building — the LCP element. Real transparency, so
    // the fallback stays PNG.
    'hostel-front.png': [900, 1350, 1800],

    // Institutional seal: 42px in the hero, ~40px in the admin sidebar, and
    // the favicon for both staff consoles.
    'clsu.logo.png': [48, 96, 144, 264],

    // Hostel lockup: 40px in the frontdesk hero, 80px on the auth card, and
    // the source for the cropped circular mark.
    'FHLogo2.png': [80, 160, 240],
    'FHLogo.png': [80, 160, 240],

    // Nav/footer mark, rendered at 30px.
    'fh-mark.png': [60, 120],

    // Room type photography — the paths config/room_types.php actually points
    // at. (public/image/roomtypes/ holds byte-identical copies that nothing
    // references; left alone here rather than silently blessed.)
    // The room cards cap at 400 CSS px (1280px container, 3 cols, 40px gaps),
    // so there is deliberately nothing between 200 and 600: a card asks for
    // 400 and lands on 600, i.e. 1.5x supersampling instead of a flat 1:1, and
    // a 2x display lands on 900. The 200 rung exists only for the genuinely
    // tiny slots (the 44px avatar in the admin guest-history table), which
    // would otherwise be handed a 600w file.
    'double.jpg': [200, 600, 900, 1400],
    'triple.jpg': [200, 600, 900, 1400],
    'quadruple.jpg': [200, 600, 900, 1400],
    'deluxe.jpg': [200, 600, 900, 1400],
    'dormitory1.jpg': [200, 600, 900, 1400],
    'dormitory2.jpg': [200, 600, 900, 1400],
};

// Bento gallery tiles + the per-room-type galleries, all drawn from the same
// numbered pool. Listed by loop so adding a photo means dropping in a file.
for (let i = 1; i <= 12; i++) SOURCES[`gallery/${i}.jpg`] = [200, 600, 900, 1400];

/**
 * Encoder settings per output format.
 *
 * Quality was originally set far too low (avif 52 / webp 78) and visibly
 * mushed the room photography. Measured against a clean downscale of the
 * source, avif 52 scored RMSE 3.87; 65 scores 3.01 for ~13 KB more on an 800px
 * tile. These numbers sit at the knee of that curve — past ~72 the bytes climb
 * fast for very little.
 */
const QUALITY = { avif: 65, webp: 84, jpg: 82 };

const ENCODERS = {
    avif: (p, q) => p.avif({ quality: q ?? QUALITY.avif, effort: 6 }),
    webp: (p, q) => p.webp({ quality: Math.min(100, (q ?? QUALITY.avif) + 19), effort: 5 }),
    jpg: (p, q) => p.jpeg({ quality: Math.min(100, (q ?? QUALITY.avif) + 17), mozjpeg: true, chromaSubsampling: '4:4:4' }),
    png: (p) => p.png({ compressionLevel: 9, palette: true }),
};

/**
 * A source whose alpha channel is entirely opaque is a photograph that was
 * saved as a PNG. Detecting that (rather than trusting the extension) is what
 * turns farmers-hostel-wide.png from 3.7 MB into a JPEG fallback.
 */
async function isOpaque(file) {
    const meta = await sharp(file).metadata();
    if (!meta.hasAlpha) return true;
    const alpha = (await sharp(file).stats()).channels[3];
    return !alpha || alpha.min === 255;
}

async function newerThanSource(target, sourceMtime) {
    try {
        return (await stat(target)).mtimeMs >= sourceMtime;
    } catch {
        return false;
    }
}

async function build() {
    await mkdir(outDir, { recursive: true });

    const manifest = {};
    let written = 0;
    let skipped = 0;
    let sourceBytes = 0;
    let bestBytes = 0;

    for (const [rel, spec] of Object.entries(SOURCES)) {
        const requested = Array.isArray(spec) ? spec : spec.widths;
        const quality = Array.isArray(spec) ? undefined : spec.quality;
        const src = join(imageDir, rel);

        let meta;
        let srcStat;
        try {
            srcStat = await stat(src);
            meta = await sharp(src).metadata();
        } catch {
            console.warn(`  skip  ${rel} (missing)`);
            continue;
        }

        const opaque = await isOpaque(src);
        const fallbackFormat = opaque ? 'jpg' : 'png';
        const formats = ['avif', 'webp', fallbackFormat];

        // Never upscale. If every requested width exceeds the source, keep the
        // source's own width so the entry still gets a compressed derivative.
        const widths = requested.filter((w) => w <= meta.width);
        if (widths.length === 0) widths.push(meta.width);

        const slug = rel.replace(/\.[^.]+$/, '').replace(/[\\/]/g, '-');
        const entry = { width: meta.width, height: meta.height, sources: {} };

        for (const format of formats) {
            const list = [];

            for (const width of widths) {
                const name = `${slug}-${width}.${format}`;
                const target = join(outDir, name);

                if (!force && (await newerThanSource(target, srcStat.mtimeMs))) {
                    skipped++;
                } else {
                    let pipe = sharp(src).resize({ width, withoutEnlargement: true });
                    // Flattening drops the dead alpha channel these sources
                    // carry; without it JPEG encoding fails outright.
                    if (opaque) pipe = pipe.flatten({ background: '#ffffff' });
                    await ENCODERS[format](pipe, quality).toFile(target);
                    written++;
                }

                list.push({ w: width, src: `image/derived/${name}` });
            }

            entry.sources[format] = list;
        }

        entry.fallback = fallbackFormat;
        manifest[`image/${rel.replace(/\\/g, '/')}`] = entry;

        // Report the widest AVIF against the source — the honest before/after.
        const widest = widths[widths.length - 1];
        const bestPath = join(outDir, `${slug}-${widest}.avif`);
        sourceBytes += srcStat.size;
        bestBytes += (await stat(bestPath)).size;

        const kb = (n) => `${(n / 1024).toFixed(0)} KB`;
        console.log(
            `  ${rel.padEnd(28)} ${kb(srcStat.size).padStart(9)} -> ` +
                `${kb((await stat(bestPath)).size).padStart(8)} avif @${widest}w`
        );
    }

    await writeFile(manifestPath, `${JSON.stringify(manifest, null, 2)}\n`, 'utf8');

    console.log(
        `\n  ${written} written, ${skipped} up to date. ` +
            `Widest derivative vs source: ${(sourceBytes / 1048576).toFixed(1)} MB -> ` +
            `${(bestBytes / 1048576).toFixed(2)} MB\n` +
            `  manifest: ${relative(root, manifestPath)}`
    );
}

build().catch((err) => {
    console.error(err);
    process.exit(1);
});
