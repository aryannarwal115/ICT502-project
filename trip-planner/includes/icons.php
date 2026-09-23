<?php
/**
 * Inline SVG icon set (stroke-based, currentColor) — replaces emoji throughout the UI.
 * Usage: <?= icon('map') ?>  or  <?= icon('wallet', 'icon-lg') ?>
 */
function icon(string $name, string $class = ''): string
{
    $cls = trim('icon ' . $class);
    $paths = [
        'map' => '<path d="M9 4.5 3 6.75v13.5l6-2.25 6 2.25 6-2.25V4.5l-6 2.25L9 4.5Z"/><path d="M9 4.5v14.25M15 6.75V21"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M16 3v4M8 3v4M3.5 10h17"/>',
        'wallet' => '<path d="M3.5 7.5A2.5 2.5 0 0 1 6 5h11a2.5 2.5 0 0 1 2.5 2.5v9A2.5 2.5 0 0 1 17 19H6a2.5 2.5 0 0 1-2.5-2.5v-9Z"/><path d="M15.5 12.75h3v2.5h-3a1.25 1.25 0 0 1 0-2.5Z"/><path d="M3.5 8.5h17"/>',
        'link' => '<path d="M9.5 14.5 14.5 9.5"/><path d="M11 6.5 12.5 5a3.5 3.5 0 1 1 5 5L16 11.5"/><path d="M13 17.5 11.5 19a3.5 3.5 0 1 1-5-5L8 12.5"/>',
        'globe' => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5a13 13 0 0 1 0 17M12 3.5a13 13 0 0 0 0 17"/>',
        'suitcase' => '<rect x="3.5" y="7.5" width="17" height="12" rx="2"/><path d="M8.5 7.5V6a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v1.5M3.5 13h17"/>',
        'pin' => '<path d="M12 21s7-6.6 7-11.5A7 7 0 0 0 5 9.5C5 14.4 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.25"/>',
        'check' => '<path d="M5 12.5 9.5 17 19 7"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="m14.8 9.2-1.6 4.4-4.4 1.6 1.6-4.4 4.4-1.6Z"/>',
        'plane' => '<path d="M10.5 13.5 3 11l1.2-1.5 5 1L14.8 4.5a1.4 1.4 0 0 1 2.5 1.2l-2.2 6.9 1 5-1.7 1-2-4.3-4 3.4.3 2.2-1.3.8-1.4-3.1-3.1-1.4.8-1.3 2.2.3 3.4-4Z"/>',
        'users' => '<circle cx="9" cy="8.5" r="3"/><path d="M3.5 19c.7-3 3-4.5 5.5-4.5s4.8 1.5 5.5 4.5"/><circle cx="17" cy="9" r="2.3"/><path d="M15.8 14.7c1.9.4 3.3 1.7 3.7 4"/>',
        'shield' => '<path d="M12 3.5 19 6v6c0 4.5-3 7.5-7 8.5-4-1-7-4-7-8.5V6l7-2.5Z"/><path d="m9 12 2 2 4-4.2"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'moon' => '<path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/>',
        'sun' => '<circle cx="12" cy="12" r="4.2"/><path d="M12 3v2.2M12 18.8V21M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M3 12h2.2M18.8 12H21M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/>',
    ];
    $body = $paths[$name] ?? $paths['pin'];
    return '<svg class="' . htmlspecialchars($cls) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}
