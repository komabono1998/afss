<?php
/**
 * Live online lessons. A teacher schedules a session in the admin; visitors join with one click.
 * The meeting itself happens on a video-call service (Jitsi Meet by default, or any Google Meet / Zoom / Teams link a teacher pastes).
 */
const LIVE_JOIN_OPENS_MIN = 10;   // joining opens this many minutes before the start time

function live_subjects(): array
{
    return ['Mathematics', 'Physics', 'Chemistry', 'Biology', 'English', 'Literature', 'History', 'Geography', 'Economics', 'Entrepreneurship', 'Commerce', 'ICT', 'Agriculture', 'Christian Religious Education', 'General Paper', 'Languages', 'Revision / Exam preparation', 'Career guidance', 'Other'];
}
function live_levels(): array { return ['O Level', 'A Level', 'O & A Level', 'Staff / teachers']; }

/** Unix start and end of a session (school time zone). */
function live_times(array $s): array
{
    $start = strtotime(($s['session_date'] ?: date('Y-m-d')) . ' ' . (preg_match('/^\d{1,2}:\d{2}$/', (string) $s['start_time']) ? $s['start_time'] : '09:00') . ':00');
    $end = $start + max(15, (int) ($s['duration_min'] ?: 60)) * 60;
    return [$start, $end];
}
/** 'live' (open to join), 'soon', 'upcoming' or 'ended'. */
function live_state(array $s, ?int $now = null): string
{
    $now = $now ?? time();
    [$start, $end] = live_times($s);
    if ($now > $end) return 'ended';
    if ($now >= $start - LIVE_JOIN_OPENS_MIN * 60) return $now >= $start ? 'live' : 'soon';
    return 'upcoming';
}
function live_new_room(string $title): string
{
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');
    return 'AFSS-' . substr($slug, 0, 24) . '-' . bin2hex(random_bytes(4));
}
/** The link people use to join: the pasted meeting link, or an automatic Jitsi Meet room. */
function live_join_url(array $s): string
{
    $u = trim((string) ($s['join_url'] ?? ''));
    if (preg_match('#^https://#i', $u)) return $u;
    return 'https://meet.jit.si/' . rawurlencode((string) $s['room']);
}
/** A pasted meeting link must be a plain https address. */
function live_clean_url(string $u): string
{
    $u = trim($u);
    return (preg_match('#^https://[^\s<>"\']+$#i', $u) && filter_var($u, FILTER_VALIDATE_URL)) ? $u : '';
}
function live_state_label(string $st): string { return ['live' => 'Live now', 'soon' => 'Starting soon', 'upcoming' => 'Upcoming', 'ended' => 'Ended'][$st] ?? $st; }
