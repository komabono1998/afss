<?php
require_once __DIR__ . '/_layout.php';
admin_head('Dashboard');
$cards = [
    ['Admission applications', "SELECT COUNT(*) FROM admissions_apps", 'inbox.php?t=admissions', "SELECT COUNT(*) FROM admissions_apps WHERE status='new'"],
    ['Job applications', "SELECT COUNT(*) FROM job_apps", 'inbox.php?t=job_apps', "SELECT COUNT(*) FROM job_apps WHERE status='new'"],
    ['Messages', "SELECT COUNT(*) FROM messages", 'inbox.php?t=messages', "SELECT COUNT(*) FROM messages WHERE status='new'"],
    ['Subscribers', "SELECT COUNT(*) FROM subscribers WHERE active=1", 'newsletter.php', null],
    ['Published news', "SELECT COUNT(*) FROM news WHERE status='published'", 'crud.php?e=news', null],
    ['Upcoming events', "SELECT COUNT(*) FROM events WHERE date(COALESCE(end_date,start_date)) >= date('now')", 'crud.php?e=events', null],
    ['Open vacancies', "SELECT COUNT(*) FROM jobs WHERE status='open'", 'crud.php?e=jobs', null],
];
?>
<div class="stats">
<?php foreach ($cards as [$l, $sql, $href, $newSql]): ?>
  <a class="stat" href="<?= e($href) ?>"><b><?= (int) val($sql) ?></b><span><?= e($l) ?><?php if ($newSql && ($n = (int) val($newSql))): ?> · <strong style="color:#c0392b"><?= $n ?> new</strong><?php endif; ?></span></a>
<?php endforeach; ?>
</div>
<div class="cols">
  <div class="card"><h2>Recent admission applications</h2>
    <table><?php foreach (rows('SELECT id,ref,first_name,last_name,entry_class,status,created_at FROM admissions_apps ORDER BY id DESC LIMIT 6') as $a): ?>
      <tr><td><a href="inbox.php?t=admissions&id=<?= $a['id'] ?>"><?= e($a['first_name'] . ' ' . $a['last_name']) ?></a><br><small class="muted"><?= e($a['ref']) ?></small></td><td><?= e($a['entry_class']) ?></td><td><span class="badge <?= e($a['status']) ?>"><?= e($a['status']) ?></span></td></tr>
    <?php endforeach; ?></table>
    <?php if (!val('SELECT COUNT(*) FROM admissions_apps')): ?><p class="muted">No applications yet.</p><?php endif; ?></div>
  <div class="card"><h2>Recent messages</h2>
    <table><?php foreach (rows('SELECT id,name,subject,status,created_at FROM messages ORDER BY id DESC LIMIT 6') as $m): ?>
      <tr><td><a href="inbox.php?t=messages&id=<?= $m['id'] ?>"><?= e($m['name']) ?></a><br><small class="muted"><?= e($m['subject'] ?: '(no subject)') ?></small></td><td><span class="badge <?= e($m['status']) ?>"><?= e($m['status']) ?></span></td></tr>
    <?php endforeach; ?></table>
    <?php if (!val('SELECT COUNT(*) FROM messages')): ?><p class="muted">No messages yet.</p><?php endif; ?></div>
</div>
<div class="card"><h2>Quick actions</h2><div class="actions">
  <a class="btn" href="crud.php?e=news&a=new">+ Post news</a><a class="btn" href="crud.php?e=events&a=new">+ Add event</a>
  <a class="btn" href="crud.php?e=jobs&a=new">+ Post vacancy</a><a class="btn" href="newsletter.php?tab=compose">✉ Send newsletter</a>
  <a class="btn ghost" href="settings.php">⚙ Site settings</a></div></div>
<?php admin_foot(); ?>
