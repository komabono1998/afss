<?php
/**
 * Event countdowns. Staff switch a countdown on per event in the admin ("Show a countdown") and may add a start time.
 * The page prints a placeholder carrying the target moment (school time zone, Africa/Kampala); assets/js/main.js ticks it.
 */
function countdown_target(?string $date, ?string $time = null): ?string
{
    if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}/', $date)) return null;
    $t = preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', trim((string) $time), $mm) ? sprintf('%02d:%s', (int) $mm[1], $mm[2]) : '00:00';
    return substr($date, 0, 10) . 'T' . $t . ':00+03:00';
}

/** Is the countdown wanted and still relevant (event has not ended)? */
function countdown_active(array $ev, string $dateKey = 'start_date'): bool
{
    if (empty($ev['countdown'])) return false;
    $start = $ev[$dateKey] ?? null;
    if (!$start) return false;
    $end = ($ev['end_date'] ?? '') ?: $start;
    return $end >= date('Y-m-d');
}

/** Big boxed countdown for an event page. */
function countdown_block(array $ev, string $dateKey = 'start_date', string $label = 'Starts in'): string
{
    if (!countdown_active($ev, $dateKey)) return '';
    $to = countdown_target($ev[$dateKey], $ev['start_time'] ?? null);
    $end = ($ev['end_date'] ?? '') ?: $ev[$dateKey];
    $endTo = countdown_target($end, '23:59');
    return '<div class="cd" data-to="' . e($to) . '" data-end="' . e($endTo) . '" role="timer" aria-label="Countdown to ' . e($ev['title']) . '">'
        . '<div class="cd-label">' . e($label) . '</div><div class="cd-boxes">'
        . '<span><b data-u="d">--</b><small>Days</small></span><span><b data-u="h">--</b><small>Hours</small></span>'
        . '<span><b data-u="m">--</b><small>Minutes</small></span><span><b data-u="s">--</b><small>Seconds</small></span></div>'
        . '<div class="cd-done" hidden></div></div>';
}

/** One-line countdown for cards and lists, e.g. "12d 04h 10m". */
function countdown_mini(array $ev, string $dateKey = 'start_date'): string
{
    if (!countdown_active($ev, $dateKey)) return '';
    $to = countdown_target($ev[$dateKey], $ev['start_time'] ?? null);
    $end = ($ev['end_date'] ?? '') ?: $ev[$dateKey];
    return '<span class="cd-mini" data-to="' . e($to) . '" data-end="' . e(countdown_target($end, '23:59')) . '">⏳ <i>…</i></span>';
}
