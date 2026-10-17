<?php

/*
 * Generates the PNG app icons in public/icons from the logo
 * (raspberry square with a peach lens ring). Needs the GD extension.
 *
 *   php deploy/make-app-icons.php
 */

const RASPBERRY = [0xAC, 0x3B, 0x61];
const PEACH = [0xED, 0xC7, 0xB7];
const SUPERSAMPLE = 4;

$target = dirname(__DIR__).'/public/app-icons';
if (! is_dir($target)) {
    mkdir($target, 0755, true);
}

// [file, size, ring diameter and stroke relative to the icon size]
// Maskable icons are cropped to a circle by Android: keep the ring inside the inner 80 %.
$icons = [
    ['icon-192.png', 192, 0.50, 0.105],
    ['icon-512.png', 512, 0.50, 0.105],
    ['icon-maskable-512.png', 512, 0.40, 0.085],
    ['apple-touch-icon.png', 180, 0.50, 0.105],
];

foreach ($icons as [$file, $size, $diameter, $stroke]) {
    // Draw large and scale down for smooth (anti-aliased) edges.
    $big = $size * SUPERSAMPLE;
    $canvas = imagecreatetruecolor($big, $big);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, ...RASPBERRY));

    $center = intdiv($big, 2);
    $outer = (int) round($big * $diameter);
    $inner = (int) round($outer - 2 * $big * $stroke);
    imagefilledellipse($canvas, $center, $center, $outer, $outer, imagecolorallocate($canvas, ...PEACH));
    imagefilledellipse($canvas, $center, $center, $inner, $inner, imagecolorallocate($canvas, ...RASPBERRY));

    $icon = imagecreatetruecolor($size, $size);
    imagecopyresampled($icon, $canvas, 0, 0, 0, 0, $size, $size, $big, $big);
    imagepng($icon, "{$target}/{$file}", 9);

    echo "{$file} ({$size}×{$size})".PHP_EOL;
}
