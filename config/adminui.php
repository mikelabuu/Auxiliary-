<?php

// Single source of truth for the admin panel's icon-chip color tokens, shared
// by resources/views/components/admin/{mini-stat,modal,section-card,stat-card}.blade.php.
// Previously each component defined its own copy of this map and they had
// already drifted (mini-stat used bg-clsu-50/bg-ember-50 while modal and
// section-card used bg-clsu-100/bg-ember-100 for the same color name).
//
// NOTE for Tailwind: this file is registered via @source in resources/css/app.css
// so its literal class strings are still picked up by the build-time scanner.
return [
    'chip_colors' => [
        'clsu'  => 'bg-clsu-100 text-clsu-700',
        'palay' => 'bg-palay-100 text-palay-700',
        'ember' => 'bg-ember-100 text-ember-700',
    ],
];
