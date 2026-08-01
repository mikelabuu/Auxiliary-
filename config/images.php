<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Formats <x-img> is allowed to serve
    |---------------------------------------------------------------------------
    |
    | Order matters: the first entry the browser supports wins, and the
    | original-format derivative (jpg/png) is always the final fallback.
    |
    | scripts/build-images.mjs generates every format regardless, so changing
    | this is a page reload, not a rebuild.
    |
    | AVIF is the smallest on the wire but the most expensive to decode, and
    | unlike JPEG/WebP it rarely gets a hardware decode path. If first paint
    | feels sluggish on a particular machine while repeat views are fine,
    | decode is the thing to suspect - set IMAGE_FORMATS=webp in .env and
    | compare. WebP is roughly 1.7x AVIF's bytes here but still 10-20x smaller
    | than the original art, so the fallback costs little.
    |
    */

    'formats' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('IMAGE_FORMATS', 'avif,webp'))
    ))),

];
