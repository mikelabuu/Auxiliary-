/**
 * Minifies the hand-written scripts in public/js/ into sibling *.min.js files.
 *
 * Why these are not simply moved into the Vite graph: they are classic scripts,
 * not modules. They talk to each other through `window` (home.js calls into the
 * hooks booking.js and availability-search.js install), several are loaded by
 * exactly one Blade view, and `defer` ordering in the document is load-bearing.
 * Bundling them would mean untangling all of that for no delivery benefit —
 * they are already cached hard and already deferred. What they were NOT was
 * minified: Lighthouse measured 20 KiB of comments and whitespace shipping to
 * every visitor, because nothing in the toolchain ever touched them.
 *
 * `transform`, not `build`, on purpose. These files declare globals at the top
 * level, and esbuild's transform pass leaves top-level identifiers alone (they
 * are genuinely global, so it cannot safely rename them) while still shortening
 * everything inside the IIFEs. `build` would want an entry graph and would wrap
 * each file, which is exactly the semantics change we do not want here.
 *
 * Output is committed, like public/build — the production host cannot run npm
 * (see the deploy notes), so a missing *.min.js there would silently fall the
 * site back to the unminified source rather than 404. That fallback lives in
 * App\Support\PublicScript.
 *
 *   npm run js:minify
 */
import { readdir, readFile, stat, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { transform } from 'esbuild';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const jsDir = join(root, 'public', 'js');
const force = process.argv.includes('--force');

// es2019 keeps optional chaining and nullish coalescing compiled away for the
// odd older browser while leaving everything these files actually use intact.
// Nothing here is transpiled *up*, so this cannot reintroduce the "legacy
// JavaScript" polyfill weight Lighthouse flags on vendor bundles.
const TARGET = 'es2019';

async function newer(target, sourceMtime) {
    try {
        return (await stat(target)).mtimeMs >= sourceMtime;
    } catch {
        return false;
    }
}

async function run() {
    const names = (await readdir(jsDir))
        .filter((n) => n.endsWith('.js') && !n.endsWith('.min.js'))
        .sort();

    let written = 0;
    let skipped = 0;
    let srcTotal = 0;
    let outTotal = 0;

    for (const name of names) {
        const src = join(jsDir, name);
        const target = join(jsDir, name.replace(/\.js$/, '.min.js'));
        const srcStat = await stat(src);

        const code = await readFile(src, 'utf8');
        srcTotal += Buffer.byteLength(code);

        if (!force && (await newer(target, srcStat.mtimeMs))) {
            outTotal += (await stat(target)).size;
            skipped++;
            continue;
        }

        const result = await transform(code, {
            minify: true,
            target: TARGET,
            // These are <script> tags, not modules — `format: undefined` keeps
            // esbuild from adding any module scaffolding.
            loader: 'js',
            legalComments: 'none',
        });

        await writeFile(target, result.code, 'utf8');
        outTotal += Buffer.byteLength(result.code);
        written++;

        const before = (Buffer.byteLength(code) / 1024).toFixed(1);
        const after = (Buffer.byteLength(result.code) / 1024).toFixed(1);
        console.log(`  ${name.padEnd(26)} ${before.padStart(7)} KB -> ${after.padStart(7)} KB`);
    }

    const saved = ((srcTotal - outTotal) / 1024).toFixed(1);
    console.log(`\n  ${written} written, ${skipped} up to date. Saved ${saved} KB uncompressed.`);
}

run().catch((err) => {
    console.error(err);
    process.exitCode = 1;
});
