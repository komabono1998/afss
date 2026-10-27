<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = $pageTitle ?? '';
$pageDesc = $pageDesc ?? setting('intro');
$fullTitle = ($pageTitle ? $pageTitle . ' | ' : '') . setting('school_name');

function menu_pages(string $group): array
{
    return rows("SELECT slug,title FROM pages WHERE menu_group = ? AND active = 1 ORDER BY sort, title", [$group]);
}
function nav_item(string $label, string $href, array $children = []): string
{
    $cur = basename($_SERVER['SCRIPT_NAME']);
    $active = ($href !== '#' && basename(parse_url($href, PHP_URL_PATH)) === $cur && $cur !== 'page.php') ? ' active' : '';
    if (!$children) return '<li><a class="' . trim($active) . '" href="' . e($href) . '">' . e($label) . '</a></li>';
    $out = '<li class="has-sub"><a href="' . e($href) . '" class="' . trim($active) . '">' . e($label) . ' <span class="caret">▾</span></a><ul class="sub">';
    foreach ($children as [$l, $h]) $out .= '<li><a href="' . e($h) . '">' . e($l) . '</a></li>';
    return $out . '</ul></li>';
}
function group_children(string $group, array $extra = [], bool $extraFirst = false): array
{
    $c = [];
    $own = ['clubs' => 'clubs.php', 'health-services' => 'health.php', 'counselling' => 'counselling.php'];   // dedicated, admin-managed pages
    foreach (menu_pages($group) as $p) $c[] = [$p['title'], url($own[$p['slug']] ?? ('page.php?slug=' . $p['slug']))];
    return $extraFirst ? array_merge($extra, $c) : array_merge($c, $extra);
}
$logo = setting('logo') ?: 'assets/img/logo.webp';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e(excerpt($pageDesc, 160)) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(setting('school_name')) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta property="og:title" content="<?= e($fullTitle) ?>">
<meta property="og:description" content="<?= e(excerpt($pageDesc, 160)) ?>">
<meta property="og:image" content="<?= e(media(!empty($pageImage) ? $pageImage : (setting('logo') ?: 'assets/img/logo.webp'))) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
<link rel="icon" href="<?= e(media($logo)) ?>">
<meta name="theme-color" content="<?= e(setting('primary_color', '#0b4a24')) ?>">
<style>:root{--primary:<?= e(setting('primary_color', '#0b4a24')) ?>;--accent:<?= e(setting('accent_color', '#f2b705')) ?>;--secondary:<?= e(setting('secondary_color', '#d7141a')) ?>}</style>
</head>
<body<?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? ' class="home"' : '' ?>>
<a class="skip-link" href="#main">Skip to main content</a>
<?php if (maintenance_on() && admin_user()): ?><div class="maint-bar">🚧 Maintenance mode is ON. Visitors see a "back soon" page; you can see the site because you are signed in. <a href="<?= url('admin/index.php') ?>">Open dashboard to go live</a></div><?php endif; ?>
<?php $showClock = setting('show_clock') !== '0'; if (setting('announcement') || $showClock): ?>
<div class="topbar">
  <div class="container topbar-in">
    <span class="announce"><?php if (setting('announcement')): ?>📣 <?= e(setting('announcement')) ?><?php endif; ?></span>
    <?php if ($showClock): ?>
    <span class="clock" id="live-clock" data-tz="<?= e(date_default_timezone_get()) ?>" role="timer" aria-label="Current date and time">
      <i class="clock-dot" aria-hidden="true"></i>
      <span class="clock-date"><?= e(date('l, j F Y')) ?></span>
      <span class="clock-time"><b class="t h"><?= e(date('h')) ?></b><b class="sep">:</b><b class="t m"><?= e(date('i')) ?></b><b class="sep">:</b><b class="t s"><?= e(date('s')) ?></b><small class="ampm"><?= e(date('A')) ?></small></span>
      <small class="clock-tz">EAT</small>
    </span>
    <?php endif; ?>
    <span class="topbar-contact">
      <?php if (setting('phone')): ?><a href="tel:<?= e(preg_replace('/\s+/', '', setting('phone'))) ?>">☎ <?= e(setting('phone')) ?></a><?php endif; ?>
      <?php if (setting('email')): ?><a href="mailto:<?= e(setting('email')) ?>">✉ <?= e(setting('email')) ?></a><?php endif; ?>
    </span>
  </div>
</div>
<?php endif; ?>
<header class="site-header" id="top">
  <div class="container header-in">
    <a class="brand" href="<?= url() ?>" aria-label="<?= e(setting('school_name')) ?> — home" title="<?= e(setting('school_name')) ?>">
      <img src="<?= e(media($logo)) ?>" alt="<?= e(setting('school_name')) ?> logo">
      <?php if (false): ?><span class="crest" aria-hidden="true"><svg viewBox="0 0 40 46" width="38" height="44"><path d="M20 2 4 8v14c0 11 7 19 16 22 9-3 16-11 16-22V8z" fill="var(--accent)"/><path d="M20 10v24M12 18h16" stroke="var(--primary)" stroke-width="3" stroke-linecap="round"/></svg></span><?php endif; ?>
    </a>
    <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false"><span></span><span></span><span></span></button>
    <nav class="main-nav" aria-label="Main">
      <ul>
        <?= nav_item('Home', url()) ?>
        <?= nav_item('About', url('page.php?slug=about-us'), group_children('about', [['Our Staff', url('staff.php')], ['Careers — Work with Us', url('careers.php')]])) ?>
        <?= nav_item('Management', url('governance.php'), [['Governance Structure', url('governance.php')], ['Board of Governors', url('management.php?g=board')], ['School Management Team', url('management.php?g=team')], ['PTA Committee', url('management.php?g=pta')]]) ?>
        <?= nav_item('Academics', url('page.php?slug=academics'), group_children('academics')) ?>
        <?= nav_item('Admissions', url('admissions.php'), group_children('admissions', [['Apply Online', url('apply.php')], ['Scholarships & Bursaries', url('scholarships.php')]], true)) ?>
        <?= nav_item('Student Life', url('clubs.php'), group_children('students', [['Alumni (AFSSOSA)', url('alumni.php')], ['Photo Gallery', url('gallery.php')]])) ?>
        <?= nav_item('News', url('news.php'), [['News & Stories', url('news.php')], ['Monthly Newsletter', url('newsletters.php')]]) ?>
        <?= nav_item('Events', url('events.php')) ?>
        <?= nav_item('Contact', url('contact.php'), [['Contact Us', url('contact.php')], ['Report a Concern (confidential)', url('whistleblower.php')]]) ?>
      </ul>
      <?php if (setting('donate_button') !== '0'): ?><a class="btn btn-gold nav-donate" href="<?= url('donate.php') ?>">♥ Donate</a><?php endif; ?>
      <a class="btn btn-accent nav-cta" href="<?= url('apply.php') ?>">Apply Now</a>
    </nav>
  </div>
</header>
<main id="main">
<?php foreach ((flash() ?? []) as [$t, $m]): ?><div class="container"><div class="alert alert-<?= e($t) ?>"><?= e($m) ?></div></div><?php endforeach; ?>
