<?php
require_once __DIR__ . '/includes/bootstrap.php';

$all = rows("SELECT * FROM live_sessions WHERE active = 1 AND session_date >= date('now','-7 days') AND session_date <= date('now','+60 days') ORDER BY session_date, start_time");
$groups = ['live' => [], 'soon' => [], 'upcoming' => [], 'ended' => []];
foreach ($all as $s) $groups[live_state($s)][] = $s;
$groups['ended'] = array_reverse($groups['ended']);
$nowList = array_merge($groups['live'], $groups['soon']);

function live_card(array $s): string
{
    $st = live_state($s); [$t0, $t1] = live_times($s);
    $open = in_array($st, ['live', 'soon'], true);
    $join = $open
        ? '<a class="btn btn-accent" href="' . e(live_join_url($s)) . '" target="_blank" rel="noopener noreferrer">' . ($st === 'live' ? 'Join the lesson' : 'Join (opens now)') . '</a>'
        : ($st === 'upcoming' ? '<span class="lv-wait">Joining opens ' . LIVE_JOIN_OPENS_MIN . ' minutes before the start.</span>' : '<span class="lv-wait">This lesson has ended.</span>');
    return '<article class="lv-card lv-' . e($st) . ' reveal"><div class="lv-top"><span class="lv-state">' . e(live_state_label($st)) . '</span><span class="tag">' . e($s['level']) . '</span></div>'
        . '<h3>' . e($s['title']) . '</h3><div class="lv-meta"><span>📘 ' . e($s['subject']) . '</span><span>👩‍🏫 ' . e($s['host_name']) . '</span></div>'
        . '<div class="lv-when"><b>' . e(date('l, j F Y', $t0)) . '</b><span>' . e(date('g:i A', $t0)) . ' – ' . e(date('g:i A', $t1)) . ' (East Africa time)</span></div>'
        . ($s['description'] ? '<p>' . nl2br(e($s['description'])) . '</p>' : '')
        . (($st === 'upcoming' || $st === 'soon') ? countdown_mini(['countdown' => 1, 'start_date' => $s['session_date'], 'start_time' => $s['start_time'], 'end_date' => $s['session_date']]) : '')
        . '<div class="lv-join">' . $join . '</div></article>';
}

$pageTitle = 'Live Online Lessons';
$pageDesc = 'Join live online lessons led by Archbishop Flynn Secondary School teachers: revision, new topics and exam preparation for O and A level.';
$bannerTitle = 'Live Online Lessons';
$bannerSub = 'Join a lesson led by AFSS teachers, from wherever you are.';
$crumbs = [['e-Library', url('elibrary.php')]];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container">
  <?php if ($nowList): ?>
  <div class="section-head"><span class="eyebrow">Happening now</span><h2>Join a lesson</h2></div>
  <div class="lv-grid"><?php foreach ($nowList as $s) echo live_card($s); ?></div>
  <?php endif; ?>

  <div class="section-head" style="margin-top:2.5rem"><span class="eyebrow">Coming up</span><h2>Upcoming lessons</h2></div>
  <?php if ($groups['upcoming']): ?><div class="lv-grid"><?php foreach ($groups['upcoming'] as $s) echo live_card($s); ?></div>
  <?php else: ?><div class="help-none"><h3>No lessons scheduled right now</h3><p>New lessons are added by our teachers. Check back soon, or explore the <a href="<?= url('elibrary.php') ?>">e-Library</a> in the meantime.</p></div><?php endif; ?>

  <?php if ($groups['ended']): ?>
  <div class="section-head" style="margin-top:2.5rem"><span class="eyebrow">Earlier this week</span><h2>Recent lessons</h2></div>
  <div class="lv-grid lv-past"><?php foreach (array_slice($groups['ended'], 0, 6) as $s) echo live_card($s); ?></div>
  <?php endif; ?>
</div></section>

<section class="section alt"><div class="container narrow">
  <div class="section-head"><span class="eyebrow">How it works</span><h2>Joining a live lesson</h2></div>
  <ol class="lv-steps">
    <li><b>Pick a lesson</b> that matches your level and subject.</li>
    <li><b>Come back at the start time.</b> The Join button opens <?= LIVE_JOIN_OPENS_MIN ?> minutes before the lesson.</li>
    <li><b>Click Join.</b> The lesson opens in a new tab on a video-call website. You do not need an account for most lessons. Allow your microphone and camera only if your teacher asks.</li>
    <li><b>Use your real name</b>, keep your microphone muted unless asked, and use the chat to ask questions.</li>
  </ol>
  <div class="notice"><strong>Be safe and respectful:</strong> never share personal details in the chat, treat others kindly, and leave if anyone makes you uncomfortable. Teachers lead each lesson and can remove anyone who disrupts it. Lessons are not recorded unless the teacher says so first.</div>
  <p class="meta">Poor internet? Join with your camera off, or use your phone's data bundle for audio only.</p>
</div></section>

<section class="cta-band"><div class="container">
  <h2>Are you an AFSS teacher?</h2>
  <p>Staff can schedule and host live lessons for learners everywhere.</p>
  <a class="btn btn-accent" href="<?= url('admin/request.php') ?>">Request a staff account</a>
  <a class="btn btn-outline" href="<?= url('admin/login.php') ?>">Staff sign-in</a>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
