<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$ME = require_admin();

function admin_nav(): array
{
    return [
        'Overview' => [['index.php', '📊 Dashboard'], ['population.php', '👥 School Population']],
        'Content' => [
            ['crud.php?e=slides', '🖼 Hero Slides'], ['crud.php?e=pages', '📄 Pages'], ['crud.php?e=news', '📰 News & Stories'], ['issues.php', '🗞 Monthly Newsletter'],
            ['crud.php?e=events', '📅 Events'], ['albums.php', '📷 Photo Albums'], ['crud.php?e=achievers', '🏆 Achievers Corner'], ['crud.php?e=faqs', '❓ FAQ & Help Centre'], ['crud.php?e=elib', '📚 e-Library'], ['anthem_page.php', '🎵 School Anthem'], ['live_sessions.php', '🎥 Live Lessons'], ['crud.php?e=team', '🏛 Staff & Management'],
        ],
        'Giving' => [['crud.php?e=donation_methods', '💳 Donation Methods'], ['crud.php?e=donation_causes', '💚 Giving Causes'], ['inbox.php?t=donor_pledges', '🤝 Donor Enquiries'], ['donate_page.php', '📝 Donate Page Text']],
        'Student Welfare' => [['crud.php?e=clubs', '🎭 Clubs & Societies'], ['crud.php?e=welfare_services', '🩺 Health & Counselling Services'], ['help_requests.php', '💬 Help & Counselling Requests'], ['welfare_page.php', '📝 Welfare Page Text']],
        'Students' => [['students.php', '🎓 Student Database'], ['classes.php', '🏫 Classes & Streams'], ['student_api.php', '🔌 Student Data API']],
        'Recruitment' => [['crud.php?e=jobs', '💼 Job Vacancies'], ['inbox.php?t=job_apps', '📨 Job Applications'], ['careers_page.php', '📝 Careers Page Text']],
        'Admissions' => [['inbox.php?t=admissions', '🎓 Applications'], ['crud.php?e=scholarships', '🏅 Scholarships & Bursaries'], ['inbox.php?t=scholarship_apps', '📝 Scholarship Applications']],
        'Alumni' => [['crud.php?e=alumni_events', '🎉 Alumni Events'], ['alumni_db.php', '🎓 Alumni Database'], ['alumni_page.php', '📝 Alumni Page Text']],
        'Communication' => [['inbox.php?t=messages', '✉ Messages'], ['whistle.php', '🕊 Whistle-blower Reports'], ['newsletter.php', '📬 Subscribers & Email']],
        'System' => [['settings.php', '⚙ Site Settings'], ['users.php', '👤 Admin Users'], ['staff_requests.php', '🙋 Staff Requests'], ['account.php', '🔑 My Account']],
    ];
}
/** Which permission module a sidebar/dashboard link belongs to (null = open to every signed-in user). */
function nav_module(string $href): ?string
{
    if (preg_match('/^crud\.php\?e=(\w+)/', $href, $m)) return $m[1];
    if (preg_match('/^inbox\.php\?t=(\w+)/', $href, $m)) return $m[1];
    return ['help_requests.php' => 'help_requests', 'careers_page.php' => 'careers_page', 'live_sessions.php' => 'live_sessions', 'anthem_page.php' => 'anthem', 'staff_requests.php' => 'users', 'welfare_page.php' => 'welfare_page', 'donate_page.php' => 'donate_page', 'alumni_db.php' => 'alumni_members', 'students.php' => 'students', 'classes.php' => 'classes', 'student_api.php' => 'students_api', 'whistle.php' => 'whistleblower', 'alumni_page.php' => 'alumni_page', 'population.php' => 'population', 'issues.php' => 'issues', 'albums.php' => 'albums', 'newsletter.php' => 'newsletter', 'settings.php' => 'settings', 'users.php' => 'users'][$href] ?? null;
}
function badge_counts(): array
{
    return [
        'inbox.php?t=admissions' => (int) val("SELECT COUNT(*) FROM admissions_apps WHERE status='new'"),
        'inbox.php?t=job_apps' => (int) val("SELECT COUNT(*) FROM job_apps WHERE status='new'"),
        'inbox.php?t=messages' => (int) val("SELECT COUNT(*) FROM messages WHERE status='new'"),
        'inbox.php?t=donor_pledges' => (int) val("SELECT COUNT(*) FROM donor_pledges WHERE status='new'"),
        'help_requests.php' => (function () { $sc = scope_for('help_requests'); [$sql, $p] = scope_sql($sc); return (int) val("SELECT COUNT(*) FROM help_requests WHERE status='new'" . $sql, $p); })(),
        'staff_requests.php' => (int) val("SELECT COUNT(*) FROM staff_requests WHERE status='new'"),
        'whistle.php' => (int) val("SELECT COUNT(*) FROM whistle_reports WHERE status='new'"),
        'alumni_db.php' => (int) val("SELECT COUNT(*) FROM alumni_members WHERE status='new'"),
        'inbox.php?t=scholarship_apps' => (int) val("SELECT COUNT(*) FROM scholarship_apps WHERE status='new'"),
    ];
}
function admin_head(string $title): void
{
    global $ME;
    $badges = badge_counts();
    $cur = basename($_SERVER['SCRIPT_NAME']) . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '');
    ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · Admin · <?= e(setting('short_name', 'AFSS')) ?></title>
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
<link rel="icon" href="<?= e(media(setting('logo') ?: 'assets/img/logo.webp')) ?>">
<style>:root{--primary:<?= e(setting('primary_color', '#0b4a24')) ?>;--accent:<?= e(setting('accent_color', '#f2b705')) ?>;--secondary:<?= e(setting('secondary_color', '#d7141a')) ?>}</style>
</head><body>
<aside class="side" id="side">
  <a class="logo" href="index.php"><img src="<?= e(media(setting('logo') ?: 'assets/img/logo.webp')) ?>" alt=""><span><b><?= e(setting('short_name', 'AFSS')) ?></b> Admin<small>School portal</small></span></a>
  <?php foreach (admin_nav() as $group => $links):
      $links = array_filter($links, fn($l) => !nav_module($l[0]) || can(nav_module($l[0])));
      if (!$links) continue; ?>
    <div class="grp"><?= e($group) ?></div>
    <?php foreach ($links as [$href, $label]): $n = $badges[$href] ?? 0;
        if ($ME['role'] === 'alumni_coordinator') $label = ['issues.php' => '🗞 Alumni Newsletter', 'crud.php?e=team' => '🏛 Alumni Leadership'][$href] ?? $label; ?>
      <a href="<?= e($href) ?>" class="<?= $cur === $href || (str_contains($href, '?') === false && basename($href) === basename($_SERVER['SCRIPT_NAME']) && !str_contains($cur, '?')) ? 'on' : '' ?>"><?= $label ?><?php if ($n): ?><span class="pill"><?= $n ?></span><?php endif; ?></a>
    <?php endforeach; ?>
  <?php endforeach; ?>
</aside>
<div class="main">
  <div class="top">
    <button class="burger" onclick="document.getElementById('side').classList.toggle('open')" aria-label="Menu">☰</button>
    <h1><?= e($title) ?></h1>
    <span class="grow"></span>
    <?php if (can('settings')): $off = maintenance_on(); ?>
    <form class="live-switch" method="post" action="maintenance.php" onsubmit="return confirm('<?= $off ? 'Bring the website back online for everyone?' : 'Take the website offline? Visitors will see a \'back soon\' page until you switch it back on.' ?>')">
      <?= csrf_field() ?><input type="hidden" name="action" value="<?= $off ? 'on' : 'off' ?>">
      <button type="submit" class="ls <?= $off ? 'is-off' : 'is-on' ?>" title="<?= $off ? 'Website is offline. Click to go live' : 'Website is live. Click to take it offline for maintenance' ?>"><i></i><?= $off ? 'Offline · Go live' : 'Live · Take offline' ?></button>
    </form>
    <?php endif; ?>
    <a class="btn ghost" href="<?= url() ?>" target="_blank">View site ↗</a>
    <span class="who"><?= e($ME['name']) ?> · <?= e(role_label($ME['role'])) ?></span>
    <a class="btn" href="logout.php">Log out</a>
  </div>
  <?php if (maintenance_on()): ?><div class="maint-banner">🚧 <b>Website is OFFLINE.</b> Visitors see the maintenance page. <?php if (can('settings')): ?><a href="index.php#site-status">Manage</a><?php endif; ?></div><?php endif; ?>
  <div class="body">
  <?php foreach ((flash() ?? []) as [$t, $m]): ?><div class="alert alert-<?= e($t) ?>"><?= e($m) ?></div><?php endforeach;
}
function admin_foot(): void
{
    ?></div></div>
<script src="<?= asset('assets/js/admin.js') ?>"></script>
</body></html><?php
}
