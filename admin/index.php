<?php
require_once __DIR__ . '/_layout.php';
$p = population();
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$hasPop = $p['students'] || $p['staff'];
admin_head('Dashboard');

$logo = setting('logo') ?: 'assets/img/logo.webp';
$pct = fn($a, $b) => $b > 0 ? round($a / $b * 100) : 0;
$count = fn(string $sql, array $par = []) => (int) val($sql, $par);
?>
<section class="hello">
  <img src="<?= e(media($logo)) ?>" alt="">
  <div><h2><?= e($greet) ?>, <?= e(explode(' ', trim($ME['name']))[0]) ?></h2>
    <p><?= e(role_label($ME['role'])) ?> · <?= e(date('l, j F Y')) ?></p>
    <div class="hello-pills">
      <span class="pill-s <?= setting('admissions_open') === '1' ? 'ok' : 'off' ?>">Online applications: <?= setting('admissions_open') === '1' ? 'open' : 'closed' ?></span>
      <span class="pill-s <?= setting('show_clock') !== '0' ? 'ok' : 'off' ?>">Live clock: <?= setting('show_clock') !== '0' ? 'on' : 'off' ?></span>
      <?php if (can('donation_methods')): $gm = $count('SELECT COUNT(*) FROM donation_methods WHERE active = 1'); ?><span class="pill-s <?= $gm ? 'ok' : 'off' ?>">Giving options: <?= $gm ?></span><?php endif; ?>
    </div></div>
  <span class="grow"></span>
  <a class="btn ghost light" href="<?= url() ?>" target="_blank">View website ↗</a>
</section>

<?php
$attn = [
    ['📝', 'Admission applications', 'admissions_apps', 'inbox.php?t=admissions', 'admissions'],
    ['💼', 'Job applications', 'job_apps', 'inbox.php?t=job_apps', 'job_apps'],
    ['🏅', 'Scholarship applications', 'scholarship_apps', 'inbox.php?t=scholarship_apps', 'scholarship_apps'],
    ['🎓', 'Alumni awaiting approval', 'alumni_members', 'alumni_db.php?status=new', 'alumni_members'],
    ['🕊', 'Whistle-blower reports', 'whistle_reports', 'whistle.php', 'whistleblower'],
    ['💬', 'Help & counselling requests', 'help_requests', 'help_requests.php', 'help_requests'],
    ['🤝', 'Donor & partner enquiries', 'donor_pledges', 'inbox.php?t=donor_pledges', 'donor_pledges'],
    ['✉', 'Messages', 'messages', 'inbox.php?t=messages', 'messages'],
];
$attn = array_filter($attn, fn($a) => can($a[4]));
if ($attn): $totalNew = 0; ob_start(); ?>
<div class="kpis">
  <?php foreach ($attn as [$ic, $l, $tbl, $href, $mod]): [$scSql, $scPar] = $tbl === 'help_requests' ? scope_sql(scope_for('help_requests')) : ['', []];
      $new = $count("SELECT COUNT(*) FROM $tbl WHERE status='new'" . $scSql, $scPar); $all = $count("SELECT COUNT(*) FROM $tbl WHERE 1=1" . $scSql, $scPar); $totalNew += $new; ?>
  <a class="kpi link <?= $new ? 'hot' : '' ?>" href="<?= e($href) ?>"><span class="kpi-ico"><?= $ic ?></span><b><?= $new ?></b><span>New <?= e(strtolower($l)) ?></span><small><?= $all ?> in total</small></a>
  <?php endforeach; ?>
</div>
<?php $attnHtml = ob_get_clean(); ?>
<h3 class="sec">Needs your attention <?= $totalNew ? '<span class="sec-count">' . $totalNew . ' new</span>' : '<span class="sec-count ok">all caught up ✓</span>' ?></h3>
<?= $attnHtml ?>
<?php endif; ?>

<h3 class="sec">School at a glance</h3>
<div class="kpis">
  <div class="kpi"><span class="kpi-ico">🎓</span><b><?= number_format($p['students']) ?></b><span>Total students</span>
    <?php if ($p['boys'] || $p['girls']): ?><small><?= number_format($p['boys']) ?> boys · <?= number_format($p['girls']) ?> girls</small><?php endif; ?></div>
  <div class="kpi"><span class="kpi-ico">👩‍🏫</span><b><?= number_format($p['teaching']) ?></b><span>Teaching staff</span></div>
  <div class="kpi"><span class="kpi-ico">🧰</span><b><?= number_format($p['support']) ?></b><span>Other staff</span><small>Non-teaching</small></div>
  <div class="kpi"><span class="kpi-ico">👥</span><b><?= number_format($p['staff']) ?></b><span>Total staff</span>
    <?php if ($p['teaching'] && $p['students']): ?><small>1 teacher : <?= round($p['students'] / $p['teaching']) ?> students</small><?php endif; ?></div>
</div>
<?php if ($hasPop && ($p['boys'] + $p['girls'] > 0 || $p['o_level'] + $p['a_level'] > 0 || $p['boarding'] + $p['day'] > 0)): ?>
<div class="card splits">
  <?php foreach ([['Gender', 'Boys', $p['boys'], 'Girls', $p['girls']], ['Level', 'O Level', $p['o_level'], 'A Level', $p['a_level']], ['Residence', 'Boarding', $p['boarding'], 'Day', $p['day']]] as [$t, $la, $a, $lb, $b]):
      if ($a + $b <= 0) continue; ?>
    <div><div class="split-h"><strong><?= e($t) ?></strong><span class="muted"><?= e($la) ?> <?= number_format($a) ?> · <?= e($lb) ?> <?= number_format($b) ?></span></div>
      <div class="split-bar"><i style="width:<?= $pct($a, $a + $b) ?>%"></i></div></div>
  <?php endforeach; ?>
</div>
<?php elseif (!$hasPop && can('population')): ?>
<div class="notice-card">Add your student and staff numbers to see them here. <a class="btn sm accent" href="population.php">Update school population</a></div>
<?php endif; ?>
<?php if ($hasPop && can('population') && $p['updated']): ?><p class="muted" style="margin:.3rem 0 1rem">Figures last updated <?= e(fmt_date($p['updated'], 'j F Y')) ?>. <a href="population.php">Update</a></p><?php endif; ?>

<?php
/* ---------- 14-day activity chart (only the sources this person may see) ---------- */
$sources = [
    ['Admissions', 'admissions_apps', 'admissions', ''], ['Messages', 'messages', 'messages', ''], ['Job applications', 'job_apps', 'job_apps', ''],
    ['Scholarships', 'scholarship_apps', 'scholarship_apps', ''], ['Alumni', 'alumni_members', 'alumni_members', ''], ['Donor enquiries', 'donor_pledges', 'donor_pledges', ''],
    ['Whistle-blower', 'whistle_reports', 'whistleblower', ''], ['Help requests', 'help_requests', 'help_requests', 'scope'],
];
$days = [];
for ($i = 13; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i days"))] = ['n' => 0, 'by' => []];
$legend = [];
foreach ($sources as [$label, $tbl, $mod, $sc]) {
    if (!can($mod)) continue;
    [$scSql, $scPar] = $sc ? scope_sql(scope_for('help_requests')) : ['', []];
    $tot = 0;
    foreach (rows("SELECT date(created_at, '+3 hours') d, COUNT(*) c FROM $tbl WHERE created_at >= datetime('now', '-15 days')" . $scSql . ' GROUP BY d', $scPar) as $r) {
        if (!isset($days[$r['d']])) continue;
        $days[$r['d']]['n'] += (int) $r['c']; $days[$r['d']]['by'][$label] = (int) $r['c']; $tot += (int) $r['c'];
    }
    $legend[$label] = $tot;
}
$max = max(1, ...array_column($days, 'n'));
$chartTotal = array_sum(array_column($days, 'n'));

/* ---------- site readiness: what still needs real content ---------- */
$sample = $count("SELECT COUNT(*) FROM scholarships WHERE eligibility LIKE '%Sample wording%'") + $count("SELECT COUNT(*) FROM clubs WHERE description LIKE '%Sample description%'") + $count("SELECT COUNT(*) FROM alumni_events WHERE description LIKE '%Sample description%'");
$placeholders = $count("SELECT COUNT(*) FROM team WHERE name = 'Name to be updated'");
$feesBody = (string) val("SELECT body FROM pages WHERE slug = 'tuition-and-fees'");
$checks = [
    ['School contact details (phone, email, address)', setting('phone') && setting('email') && setting('address'), 'settings', 'settings.php', ''],
    ['Social media links', setting('facebook') || setting('twitter') || setting('instagram') || setting('youtube') || setting('whatsapp'), 'settings', 'settings.php', 'Add at least one link'],
    ['Hero slides with photos', $count("SELECT COUNT(*) FROM slides WHERE active = 1 AND COALESCE(image,'') != ''") > 0, 'slides', 'crud.php?e=slides', 'Upload a photo for a slide'],
    ['Leadership and staff names filled in', $placeholders === 0, 'team', 'crud.php?e=team', $placeholders . ' still say “Name to be updated”'],
    ['Student and staff numbers', $p['students'] > 0 && $p['staff'] > 0, 'population', 'population.php', 'Enter the figures'],
    ['Fees amounts on the fees page', $feesBody !== '' && stripos($feesBody, 'Ask the Bursar') === false, 'pages', 'crud.php?e=pages', 'Replace “Ask the Bursar” with the real fees'],
    ['Bank or mobile-money giving details', $count('SELECT COUNT(*) FROM donation_methods WHERE active = 1') > 0, 'donation_methods', 'crud.php?e=donation_methods', 'Fill in and switch on a method'],
    ['News posted in the last 30 days', $count("SELECT COUNT(*) FROM news WHERE status = 'published' AND published_at >= date('now','-30 days')") > 0, 'news', 'crud.php?e=news&a=new', 'Post a story'],
    ['Upcoming events scheduled', $count("SELECT COUNT(*) FROM events WHERE date(COALESCE(end_date, start_date)) >= date('now')") > 0, 'events', 'crud.php?e=events&a=new', 'Add an event'],
    ['Photo gallery has pictures', $count('SELECT COUNT(*) FROM gallery') > 0, 'albums', 'albums.php?a=new', 'Create an album'],
    ['Alumni constitution uploaded', setting('alumni_constitution') !== '', 'alumni_page', 'alumni_page.php', 'Upload the PDF'],
    ['Sample wording replaced (scholarships, clubs, alumni)', $sample === 0, $sample ? 'scholarships' : 'clubs', 'crud.php?e=scholarships', $sample . ' item(s) still use sample text'],
];
$checks = array_values(array_filter($checks, fn($c) => can($c[2])));
$done = count(array_filter($checks, fn($c) => $c[1]));
if ($legend || $checks): ?>
<div class="cols dash-two">
  <?php if ($legend): ?>
  <div class="card"><h2>Activity — last 14 days <span class="muted" style="font-weight:400;font-size:.85rem">· <?= $chartTotal ?> new item<?= $chartTotal === 1 ? '' : 's' ?></span></h2>
    <div class="chart" role="img" aria-label="Bar chart of new submissions per day over the last 14 days">
      <?php foreach ($days as $d => $v): $h = $v['n'] ? max(6, round($v['n'] / $max * 100)) : 0; $tip = date('D j M', strtotime($d)) . ': ' . $v['n'] . ' new' . ($v['by'] ? ' (' . implode(', ', array_map(fn($k, $n) => "$k $n", array_keys($v['by']), $v['by'])) . ')' : ''); ?>
        <div class="col <?= $d === date('Y-m-d') ? 'today' : '' ?>" title="<?= e($tip) ?>"><span class="n"><?= $v['n'] ?: '' ?></span><i style="height:<?= $h ?>%"></i><small><?= e(date('j', strtotime($d))) ?></small></div>
      <?php endforeach; ?>
    </div>
    <?php if ($chartTotal === 0): ?><p class="muted" style="text-align:center;margin:-5.2rem 0 4rem;position:relative">No new submissions in the last 14 days.</p><?php endif; ?>
    <div class="legend"><?php foreach ($legend as $l => $n): ?><span class="lg-i"><b><?= $n ?></b> <?= e($l) ?></span><?php endforeach; ?></div>
  </div>
  <?php endif; ?>
  <?php if ($checks): $rp = $pct($done, count($checks)); ?>
  <div class="card"><h2>Site readiness <span class="muted" style="font-weight:400;font-size:.85rem">· <?= $done ?> of <?= count($checks) ?> done</span></h2>
    <div class="progress"><i style="width:<?= $rp ?>%"></i></div>
    <ul class="checklist">
      <?php foreach ($checks as [$label, $ok, $mod, $href, $hint]): ?>
        <li class="<?= $ok ? 'done' : '' ?>"><span class="tick"><?= $ok ? '✓' : '' ?></span><span class="cl-t"><?= $ok ? e($label) : '<a href="' . e($href) . '">' . e($label) . '</a>' ?><?= !$ok && $hint ? '<small>' . e($hint) . '</small>' : '' ?></span></li>
      <?php endforeach; ?>
    </ul></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php
/* ---------- quick links, grouped ---------- */
$groups = [
    'Create' => [
        ['📰', 'Post news', 'crud.php?e=news&a=new', 'news'], ['📅', 'Add event', 'crud.php?e=events&a=new', 'events'], ['📷', 'New photo album', 'albums.php?a=new', 'albums'],
        ['🗞', 'New newsletter issue', 'issues.php?a=new', 'issues'], ['💼', 'Post vacancy', 'crud.php?e=jobs&a=new', 'jobs'], ['🏅', 'Add scholarship', 'crud.php?e=scholarships&a=new', 'scholarships'],
        ['🎉', 'Add alumni event', 'crud.php?e=alumni_events&a=new', 'alumni_events'], ['👩‍🏫', 'Add staff profile', 'crud.php?e=team&a=new', 'team'],
    ],
    'Review' => [
        ['🎓', 'Admissions', 'inbox.php?t=admissions', 'admissions'], ['🎓', 'Alumni database', 'alumni_db.php', 'alumni_members'], ['💬', 'Help requests', 'help_requests.php', 'help_requests'],
        ['🕊', 'Whistle-blower reports', 'whistle.php', 'whistleblower'], ['🤝', 'Donor enquiries', 'inbox.php?t=donor_pledges', 'donor_pledges'], ['✉', 'Messages', 'inbox.php?t=messages', 'messages'],
    ],
    'Website content' => [
        ['📄', 'Pages', 'crud.php?e=pages', 'pages'], ['🖼', 'Hero slides', 'crud.php?e=slides', 'slides'], ['🏛', 'Staff & management', 'crud.php?e=team', 'team'],
        ['🎭', 'Clubs & societies', 'crud.php?e=clubs', 'clubs'], ['🩺', 'Health & counselling', 'crud.php?e=welfare_services', 'welfare_services'], ['📝', 'Welfare page text', 'welfare_page.php', 'welfare_page'],
        ['💳', 'Donation methods', 'crud.php?e=donation_methods', 'donation_methods'], ['📝', 'Alumni page text', 'alumni_page.php', 'alumni_page'], ['👥', 'School population', 'population.php', 'population'],
    ],
    'Students' => [
        ['🎓', 'Student database', 'students.php', 'students'], ['⬆', 'Upload class list', 'students.php?a=upload', 'students_manage'], ['🏫', 'Classes & streams', 'classes.php', 'classes'], ['🔌', 'Student data API', 'student_api.php', 'students_api'],
    ],
    'System' => [
        ['⚙', 'Site settings', 'settings.php', 'settings'], ['📬', 'Subscribers & email', 'newsletter.php', 'newsletter'], ['👤', 'Admin users', 'users.php', 'users'], ['🔑', 'My account', 'account.php', ''],
    ],
];
$alum = $ME['role'] === 'alumni_coordinator';
foreach ($groups as $g => $list) {
    $groups[$g] = array_values(array_filter($list, fn($t) => $t[3] === '' || can($t[3])));
    if ($alum) $groups[$g] = array_map(fn($t) => $t[2] === 'issues.php?a=new' ? ['🗞', 'New alumni newsletter', $t[2], $t[3]] : ($t[2] === 'crud.php?e=team' ? ['🏛', 'Alumni leadership', $t[2], $t[3]] : $t), $groups[$g]);
}
?>
<h3 class="sec">Quick links</h3>
<div class="tilegroups">
  <?php foreach ($groups as $g => $list): if (!$list) continue; ?>
  <div class="tg"><div class="tg-h"><?= e($g) ?></div><div class="tiles"><?php foreach ($list as [$ic, $l, $href]): ?><a class="tile" href="<?= e($href) ?>"><span><?= $ic ?></span><?= e($l) ?></a><?php endforeach; ?></div></div>
  <?php endforeach; ?>
</div>

<div class="cols" style="margin-top:1.4rem">
  <?php if (can('admissions')): ?><div class="card"><h2>Recent admission applications</h2>
    <table><?php foreach (rows('SELECT id,ref,first_name,last_name,entry_class,admission_type,status FROM admissions_apps ORDER BY id DESC LIMIT 5') as $a): ?>
      <tr><td><a href="inbox.php?t=admissions&id=<?= $a['id'] ?>"><strong><?= e($a['first_name'] . ' ' . $a['last_name']) ?></strong></a><br><small class="muted"><?= e($a['ref']) ?> · <?= e($a['admission_type'] ?: 'New admission') ?></small></td><td><?= e($a['entry_class']) ?></td><td><span class="badge <?= e($a['status']) ?>"><?= e($a['status']) ?></span></td></tr>
    <?php endforeach; ?></table>
    <?php if (!$count('SELECT COUNT(*) FROM admissions_apps')): ?><p class="muted">No applications yet.</p><?php endif; ?></div><?php endif; ?>
  <?php if (can('messages')): ?><div class="card"><h2>Recent messages</h2>
    <table><?php foreach (rows('SELECT id,name,subject,status FROM messages ORDER BY id DESC LIMIT 5') as $m): ?>
      <tr><td><a href="inbox.php?t=messages&id=<?= $m['id'] ?>"><strong><?= e($m['name']) ?></strong></a><br><small class="muted"><?= e($m['subject'] ?: '(no subject)') ?></small></td><td><span class="badge <?= e($m['status']) ?>"><?= e($m['status']) ?></span></td></tr>
    <?php endforeach; ?></table>
    <?php if (!$count('SELECT COUNT(*) FROM messages')): ?><p class="muted">No messages yet.</p><?php endif; ?></div><?php endif; ?>
  <?php if (can('events')): ?><div class="card"><h2>Upcoming events</h2>
    <table><?php foreach (rows("SELECT id,title,start_date,category FROM events WHERE date(COALESCE(end_date,start_date)) >= date('now') ORDER BY start_date LIMIT 5") as $ev): ?>
      <tr><td><a href="crud.php?e=events&a=edit&id=<?= $ev['id'] ?>"><strong><?= e($ev['title']) ?></strong></a><br><small class="muted"><?= e($ev['category']) ?></small></td><td><?= e(fmt_date($ev['start_date'])) ?></td></tr>
    <?php endforeach; ?></table>
    <?php if (!$count("SELECT COUNT(*) FROM events WHERE date(COALESCE(end_date,start_date)) >= date('now')")): ?><p class="muted">Nothing scheduled.</p><?php endif; ?></div><?php endif; ?>
  <?php if (can('news')): ?><div class="card"><h2>Latest news</h2>
    <table><?php foreach (rows('SELECT id,title,status,published_at FROM news ORDER BY published_at DESC, id DESC LIMIT 5') as $n): ?>
      <tr><td><a href="crud.php?e=news&a=edit&id=<?= $n['id'] ?>"><strong><?= e($n['title']) ?></strong></a><br><small class="muted"><?= e(fmt_date($n['published_at'])) ?></small></td><td><span class="badge <?= e($n['status']) ?>"><?= e($n['status']) ?></span></td></tr>
    <?php endforeach; ?></table>
    <?php if (!$count('SELECT COUNT(*) FROM news')): ?><p class="muted">No stories yet.</p><?php endif; ?></div><?php endif; ?>
  <?php if (can('news') || can('issues') || can('jobs')): ?><div class="card"><h2>Website at a glance</h2>
    <div class="chips">
      <?php foreach ([['Published news', "SELECT COUNT(*) FROM news WHERE status='published'", 'news'], ['Newsletter issues', "SELECT COUNT(*) FROM newsletter_issues WHERE status='published'", 'issues'],
          ['Photo albums', 'SELECT COUNT(*) FROM albums', 'albums'], ['Open vacancies', "SELECT COUNT(*) FROM jobs WHERE status='open'", 'jobs'],
          ['Subscribers', 'SELECT COUNT(*) FROM subscribers WHERE active=1', 'newsletter'], ['Scholarships', 'SELECT COUNT(*) FROM scholarships WHERE active=1', 'scholarships']] as [$l, $sql, $mod]):
          if (!can($mod)) continue; ?><div class="chip"><b><?= $count($sql) ?></b><span><?= e($l) ?></span></div><?php endforeach; ?>
    </div></div><?php endif; ?>
</div>
<?php admin_foot(); ?>
