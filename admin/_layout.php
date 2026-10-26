<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$ME = require_admin();

function admin_nav(): array
{
    return [
        'Overview' => [['index.php', '📊 Dashboard']],
        'Content' => [
            ['crud.php?e=slides', '🖼 Hero Slides'], ['crud.php?e=pages', '📄 Pages'], ['crud.php?e=news', '📰 News & Stories'], ['issues.php', '🗞 Monthly Newsletter'],
            ['crud.php?e=events', '📅 Events'], ['albums.php', '📷 Photo Albums'], ['crud.php?e=team', '🏛 Management Profiles'],
        ],
        'Recruitment' => [['crud.php?e=jobs', '💼 Job Vacancies'], ['inbox.php?t=job_apps', '📨 Job Applications']],
        'Admissions' => [['inbox.php?t=admissions', '🎓 Applications'], ['crud.php?e=scholarships', '🏅 Scholarships & Bursaries'], ['inbox.php?t=scholarship_apps', '📝 Scholarship Applications']],
        'Communication' => [['inbox.php?t=messages', '✉ Messages'], ['issues.php', '🗞 Monthly Newsletter'], ['newsletter.php', '📬 Subscribers & Email']],
        'System' => [['settings.php', '⚙ Site Settings'], ['users.php', '👤 Admin Users'], ['account.php', '🔑 My Account']],
    ];
}
/** Which permission module a sidebar/dashboard link belongs to (null = open to every signed-in user). */
function nav_module(string $href): ?string
{
    if (preg_match('/^crud\.php\?e=(\w+)/', $href, $m)) return $m[1];
    if (preg_match('/^inbox\.php\?t=(\w+)/', $href, $m)) return $m[1];
    return ['issues.php' => 'issues', 'albums.php' => 'albums', 'newsletter.php' => 'newsletter', 'settings.php' => 'settings', 'users.php' => 'users'][$href] ?? null;
}
function badge_counts(): array
{
    return [
        'inbox.php?t=admissions' => (int) val("SELECT COUNT(*) FROM admissions_apps WHERE status='new'"),
        'inbox.php?t=job_apps' => (int) val("SELECT COUNT(*) FROM job_apps WHERE status='new'"),
        'inbox.php?t=messages' => (int) val("SELECT COUNT(*) FROM messages WHERE status='new'"),
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
<style>:root{--primary:<?= e(setting('primary_color', '#0b2a4a')) ?>;--accent:<?= e(setting('accent_color', '#c9962b')) ?>}</style>
</head><body>
<aside class="side" id="side">
  <div class="logo"><b><?= e(setting('short_name', 'AFSS')) ?></b> Admin</div>
  <?php foreach (admin_nav() as $group => $links):
      $links = array_filter($links, fn($l) => !nav_module($l[0]) || can(nav_module($l[0])));
      if (!$links) continue; ?>
    <div class="grp"><?= e($group) ?></div>
    <?php foreach ($links as [$href, $label]): $n = $badges[$href] ?? 0; ?>
      <a href="<?= e($href) ?>" class="<?= $cur === $href || (str_contains($href, '?') === false && basename($href) === basename($_SERVER['SCRIPT_NAME']) && !str_contains($cur, '?')) ? 'on' : '' ?>"><?= $label ?><?php if ($n): ?><span class="pill"><?= $n ?></span><?php endif; ?></a>
    <?php endforeach; ?>
  <?php endforeach; ?>
</aside>
<div class="main">
  <div class="top">
    <button class="burger" onclick="document.getElementById('side').classList.toggle('open')" aria-label="Menu">☰</button>
    <h1><?= e($title) ?></h1>
    <span class="grow"></span>
    <a class="btn ghost" href="<?= url() ?>" target="_blank">View site ↗</a>
    <span class="who"><?= e($ME['name']) ?> · <?= e(role_label($ME['role'])) ?></span>
    <a class="btn" href="logout.php">Log out</a>
  </div>
  <div class="body">
  <?php foreach ((flash() ?? []) as [$t, $m]): ?><div class="alert alert-<?= e($t) ?>"><?= e($m) ?></div><?php endforeach;
}
function admin_foot(): void
{
    ?></div></div>
<script src="<?= asset('assets/js/admin.js') ?>"></script>
</body></html><?php
}
