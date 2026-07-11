// Animated film-grain overlay for the hero backdrop.
// Vanilla port of ReactBits "Noise" (patternAlpha 15), optimized: instead of
// regenerating a 1024×1024 random buffer every other frame (~30 full-buffer
// CPU passes/sec, indefinitely), we lazily pre-render a small pool of grain
// frames — one per tick, so there's no startup hitch — and then only cycle
// through them with drawImage. After the pool fills (~half a second) the
// per-frame cost drops to a single composited blit.
(function () {
    const canvas = document.getElementById('heroNoise');
    if (!canvas) return;

    const ctx = canvas.getContext('2d', { alpha: true });
    if (!ctx) return;

    const SIZE = 1024;
    const ALPHA = 15;   // 0–255 → ~6% specks
    const REFRESH = 3;  // swap grain every N display frames (~20fps at 60Hz — filmic)
    const POOL = 6;     // distinct grain frames to cycle through

    canvas.width = SIZE;
    canvas.height = SIZE;
    // 'copy' replaces the whole surface on drawImage — no clearRect needed
    ctx.globalCompositeOperation = 'copy';

    const frames = [];

    function makeGrainFrame() {
        const off = document.createElement('canvas');
        off.width = SIZE;
        off.height = SIZE;
        const octx = off.getContext('2d');
        const imageData = octx.createImageData(SIZE, SIZE);
        const data = imageData.data;
        for (let i = 0; i < data.length; i += 4) {
            const v = (Math.random() * 255) | 0;
            data[i] = v;
            data[i + 1] = v;
            data[i + 2] = v;
            data[i + 3] = ALPHA;
        }
        octx.putImageData(imageData, 0, 0);
        return off;
    }

    // First frame immediately so the hero never renders grainless
    frames.push(makeGrainFrame());
    ctx.drawImage(frames[0], 0, 0);

    // Static single frame is enough when the visitor prefers reduced motion
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    let tick = 0;
    let cursor = 0;
    let rafId = null;

    function loop() {
        if (tick % REFRESH === 0) {
            // Grow the pool lazily (one buffer per swap), then just cycle it
            if (frames.length < POOL) frames.push(makeGrainFrame());
            cursor = (cursor + 1) % frames.length;
            ctx.drawImage(frames[cursor], 0, 0);
        }
        tick++;
        rafId = requestAnimationFrame(loop);
    }

    // Animate only while the hero is actually on screen
    const observer = new IntersectionObserver(function (entries) {
        if (entries[0].isIntersecting) {
            if (rafId === null) rafId = requestAnimationFrame(loop);
        } else if (rafId !== null) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
    });
    observer.observe(canvas);
})();
