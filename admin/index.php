<?php
require_once __DIR__ . '/_layout.php';
$p = population();
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$hasPop = $p['students'] || $p['staff'];
admin_head('Dashboard');

$logo = setting('logo') ?: 'assets/img/logo.webp';
$pct = fn($a, $b) => $b > 0 ? round($a / $b * 100) : 0;
?>
<section class="hello">
  <img src="<?= e(media($logo)) ?>" alt="">
  <div><h2><?= e($greet) ?>, <?= e(explode(' ', trim($ME['name']))[0]) ?></h2>
    <p><?= e(role_label($ME['role'])) ?> · <?= e(date('l, j F Y')) ?></p></div>
  <span class="grow"></span>
  <a class="btn ghost light" href="<?= url() ?>" target="_blank">View website ↗</a>
</section>

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
if ($attn): ?>
<h3 class="sec">Needs your attention</h3>
<div class="kpis">
  <?php foreach ($attn as [$ic, $l, $tbl, $href, $mod]): [$scSql, $scPar] = $tbl === 'help_requests' ? scope_sql(scope_for('help_requests')) : ['', []];
      $new = (int) val("SELECT COUNT(*) FROM $tbl WHERE status='new'" . $scSql, $scPar); $all = (int) val("SELECT COUNT(*) FROM $tbl WHERE 1=1" . $scSql, $scPar); ?>
  <a class="kpi link <?= $new ? 'hot' : '' ?>" href="<?= e($href) ?>"><span class="kpi-ico"><?= $ic ?></span><b><?= $new ?></b><span>New <?= e(strtolower($l)) ?></span><small><?= $all ?> in total</small></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$tiles = [
    ['📰', 'Post news', 'crud.php?e=news&a=new', 'news'], ['📅', 'Add event', 'crud.php?e=events&a=new', 'events'],
    ['📷', 'New photo album', 'albums.php?a=new', 'albums'], ['🏛', 'Alumni leadership', 'crud.php?e=team', 'team_alumni'], ['🎉', 'Add alumni event', 'crud.php?e=alumni_events&a=new', 'alumni_events'], ['🗞', 'New newsletter issue', 'issues.php?a=new', 'issues'],
    ['💼', 'Post vacancy', 'crud.php?e=jobs&a=new', 'jobs'], ['🏅', 'Add scholarship', 'crud.php?e=scholarships&a=new', 'scholarships'],
    ['🎓', 'Review admissions', 'inbox.php?t=admissions', 'admissions'], ['📄', 'Edit pages', 'crud.php?e=pages', 'pages'],
    ['🖼', 'Hero slides', 'crud.php?e=slides', 'slides'], ['🏛', 'Management profiles', 'crud.php?e=team', 'team'],
    ['🎓', 'Student database', 'students.php', 'students'], ['🎭', 'Clubs & societies', 'crud.php?e=clubs', 'clubs'], ['🩺', 'Health & counselling', 'crud.php?e=welfare_services', 'welfare_services'], ['💬', 'Help requests', 'help_requests.php', 'help_requests'], ['📝', 'Welfare page text', 'welfare_page.php', 'welfare_page'], ['👩‍🏫', 'Staff profiles', 'crud.php?e=team&a=new', 'team'], ['💳', 'Donation methods', 'crud.php?e=donation_methods', 'donation_methods'], ['⬆', 'Upload class list', 'students.php?a=upload', 'students_manage'], ['🏫', 'Classes & streams', 'classes.php', 'classes'], ['🕊', 'Whistle-blower reports', 'whistle.php', 'whistleblower'], ['🔌', 'Student data API', 'student_api.php', 'students_api'], ['👥', 'School population', 'population.php', 'population'], ['📝', 'Alumni page text', 'alumni_page.php', 'alumni_page'], ['📬', 'Subscribers & email', 'newsletter.php', 'newsletter'],
    ['⚙', 'Site settings', 'settings.php', 'settings'], ['👤', 'Admin users', 'users.php', 'users'],
];
$tiles = array_filter($tiles, fn($t) => $t[3] === 'team_alumni' ? ($ME['role'] === 'alumni_coordinator') : can($t[3]));
if ($ME['role'] === 'alumni_coordinator') $tiles = array_map(fn($t) => $t[2] === 'issues.php?a=new' ? ['🗞', 'New alumni newsletter', $t[2], $t[3]] : ($t[2] === 'crud.php?e=team' ? ['🏛', 'Alumni leadership', $t[2], $t[3]] : $t), $tiles);
?>
<h3 class="sec">Quick links</h3>
<div class="tiles">
  <?php foreach ($tiles as [$ic, $l, $href]): ?><a class="tile" href="<?= e($href) ?>"><span><?= $ic ?></span><?= e($l) ?></a><?php endforeach; ?>
  <a class="tile" href="account.php"><span>🔑</span>My account</a>
</div>

<div class="cols" style="margin-top:1.4rem">
  <?php if (can('admissions')): ?><div class="card"><h2>Recent admission applications</h2>
    <table><?php foreach (rows('SELECT id,ref,first_name,last_name,entry_class,admission_type,status FROM admissions_apps ORDER BY id DESC LIMIT 5') as $a): ?>
      <tr><td><a href="inbox.php?t=admissions&id=<?= $a['id'] ?>"><strong><?= e($a['first_name'] . ' ' . $a['last_name']) ?></strong></a><br><small class="muted"><?= e($a['ref']) ?> · <?= e($a['admission_type'] ?: 'New admission') ?></small></td><td><?= e($a['entry_class']) ?></td><td><span class="badge <?= e($a['status']) ?>"><?= e($a['status']) ?></span></td></tr>
    <?php endforeach; ?></table>
    <?php if (!val('SELECT COUNT(*) FROM admissions_apps')): ?><p class="muted">No applications yet.</p><?php endif; ?></div><?php endif; ?>
  <?php if (can('messages')): ?><div class="card"><h2>Recent messages</h2>
    <table><?php foreach (rows('SELECT id,name,subject,status FROM messages ORDER BY id DESC LIMIT 5') as $m): ?>
      <tr><td><a href="inbox.php?t=messages&id=<?= $m['id'] ?>"><strong><?= e($m['name']) ?></strong></a><br><small class="muted"><?= e($m['subject'] ?: '(no subject)') ?></small></td><td><span class="badge <?= e($m['status']) ?>"><?= e($m['status']) ?></span></td></tr>
    <?php endforeach; ?></table>
    <?php if (!val('SELECT COUNT(*) FROM messages')): ?><p class="muted">No messages yet.</p><?php endif; ?></div><?php endif; ?>
  <?php if (can('events')): ?><div class="card"><h2>Upcoming events</h2>
    <table><?php foreach (rows("SELECT id,title,start_date,category FROM events WHERE date(COALESCE(end_date,start_date)) >= date('now') ORDER BY start_date LIMIT 5") as $ev): ?>
      <tr><td><a href="crud.php?e=events&a=edit&id=<?= $ev['id'] ?>"><strong><?= e($ev['title']) ?></strong></a><br><small class="muted"><?= e($ev['category']) ?></small></td><td><?= e(fmt_date($ev['start_date'])) ?></td></tr>
    <?php endforeach; ?></table>
    <?php if (!val("SELECT COUNT(*) FROM events WHERE date(COALESCE(end_date,start_date)) >= date('now')")): ?><p class="muted">Nothing scheduled.</p><?php endif; ?></div><?php endif; ?>
  <?php if (can('news') || can('issues') || can('jobs')): ?><div class="card"><h2>Website at a glance</h2>
    <div class="chips">
      <?php foreach ([['Published news', "SELECT COUNT(*) FROM news WHERE status='published'", 'news'], ['Newsletter issues', "SELECT COUNT(*) FROM newsletter_issues WHERE status='published'", 'issues'],
          ['Photo albums', 'SELECT COUNT(*) FROM albums', 'albums'], ['Open vacancies', "SELECT COUNT(*) FROM jobs WHERE status='open'", 'jobs'],
          ['Subscribers', 'SELECT COUNT(*) FROM subscribers WHERE active=1', 'newsletter'], ['Scholarships', 'SELECT COUNT(*) FROM scholarships WHERE active=1', 'scholarships']] as [$l, $sql, $mod]):
          if (!can($mod)) continue; ?><div class="chip"><b><?= (int) val($sql) ?></b><span><?= e($l) ?></span></div><?php endforeach; ?>
    </div></div><?php endif; ?>
</div>
<?php admin_foot(); ?>
