// Animated film-grain overlay for the hero backdrop.
// Vanilla port of ReactBits "Noise" (patternAlpha 15, refresh every 2 frames).
(function () {
    const canvas = document.getElementById('heroNoise');
    if (!canvas) return;

    const ctx = canvas.getContext('2d', { alpha: true });
    if (!ctx) return;

    const SIZE = 1024;
    const ALPHA = 15;   // 0–255 → ~6% specks
    const REFRESH = 2;  // redraw every N frames (~30fps at 60Hz)

    canvas.width = SIZE;
    canvas.height = SIZE;

    function drawGrain() {
        const imageData = ctx.createImageData(SIZE, SIZE);
        const data = imageData.data;
        for (let i = 0; i < data.length; i += 4) {
            const v = (Math.random() * 255) | 0;
            data[i] = v;
            data[i + 1] = v;
            data[i + 2] = v;
            data[i + 3] = ALPHA;
        }
        ctx.putImageData(imageData, 0, 0);
    }

    drawGrain();

    // Static single frame is enough when the visitor prefers reduced motion
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    let frame = 0;
    let rafId = null;

    function loop() {
        if (frame % REFRESH === 0) drawGrain();
        frame++;
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
