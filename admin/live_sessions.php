<?php
require_once __DIR__ . '/_layout.php';
require_perm('live_sessions');

$all = can('live_all');               // administrators and academic heads see every teacher's lessons
$uid = (int) $ME['id'];
$self = 'live_sessions.php';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$rec = $id ? row('SELECT * FROM live_sessions WHERE id = ?', [$id]) : null;
if ($rec && !$all && (int) $rec['host_id'] !== $uid) deny();

if (is_post()) {
    csrf_check();
    $do = post('do');
    if ($do === 'delete' && $rec) { q('DELETE FROM live_sessions WHERE id = ?', [$id]); flash('Lesson deleted.'); redirect("admin/$self"); }
    if ($do === 'toggle' && $rec) { q('UPDATE live_sessions SET active = 1 - active WHERE id = ?', [$id]); flash($rec['active'] ? 'Lesson cancelled and hidden from the website.' : 'Lesson is visible again.'); redirect("admin/$self"); }
    if ($do === 'save') {
        $title = post('title'); $date = post('session_date'); $time = post('start_time'); $dur = max(15, min(480, (int) post('duration_min') ?: 60));
        $err = null;
        if ($title === '') $err = 'Please give the lesson a title.';
        elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $err = 'Please choose the date.';
        elseif (!preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $time)) $err = 'Please enter the start time like 14:30.';
        $link = trim(post('join_url')) !== '' ? live_clean_url(post('join_url')) : '';
        if (!$err && trim(post('join_url')) !== '' && $link === '') $err = 'The meeting link must start with https:// and be a single web address.';
        if ($err) { flash($err, 'err'); $_SESSION['old'] = $_POST; redirect("admin/$self?a=" . ($rec ? "edit&id=$id" : 'new')); }
        $d = [$title, post('subject') ?: 'Other', post('level') ?: 'O & A Level', $date, $time, $dur, trim((string) ($_POST['description'] ?? '')), $link];
        if ($rec) {
            q('UPDATE live_sessions SET title=?,subject=?,level=?,session_date=?,start_time=?,duration_min=?,description=?,join_url=? WHERE id=?', array_merge($d, [$id]));
            flash('Lesson updated.');
        } else {
            q('INSERT INTO live_sessions (title,subject,level,session_date,start_time,duration_min,description,join_url,host_id,host_name,room) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                array_merge($d, [$uid, $ME['name'], live_new_room($title)]));
            flash('Lesson scheduled. It now shows on the website under Live Online Lessons.');
        }
        redirect("admin/$self");
    }
}

$a = $_GET['a'] ?? '';
admin_head('Live Online Lessons');
if ($a === 'new' || $a === 'edit'):
    $v = fn(string $k, string $d = '') => e((string) ($_SESSION['old'][$k] ?? $rec[$k] ?? $d));
    $old = $_SESSION['old'] ?? null; unset($_SESSION['old']);
?>
<form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $id ?>">
  <h2><?= $rec ? 'Edit lesson' : 'Schedule a live lesson' ?></h2>
  <div class="f"><label>Lesson title *</label><input type="text" name="title" value="<?= $v('title') ?>" required placeholder="e.g. S.4 Physics: Electricity revision"></div>
  <div class="cols">
    <div class="f"><label>Subject</label><select name="subject"><?php foreach (live_subjects() as $s): ?><option<?= $v('subject') === e($s) ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
    <div class="f"><label>Level</label><select name="level"><?php foreach (live_levels() as $l): ?><option<?= $v('level', 'O & A Level') === e($l) ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="f"><label>Date *</label><input type="date" name="session_date" value="<?= $v('session_date', date('Y-m-d')) ?>" required></div>
    <div class="f"><label>Start time * (24-hour, East Africa time)</label><input type="text" name="start_time" value="<?= $v('start_time') ?>" placeholder="e.g. 16:00" required></div>
    <div class="f"><label>Length (minutes)</label><input type="number" name="duration_min" value="<?= $v('duration_min', '60') ?>" min="15" max="480"></div>
  </div>
  <div class="f"><label>What will you cover?</label><textarea name="description" placeholder="Topics, what learners should bring, who the lesson is for"><?= $v('description') ?></textarea></div>
  <div class="f"><label>Your own meeting link (optional)</label><input type="text" name="join_url" value="<?= $v('join_url') ?>" placeholder="Leave empty to use a free Jitsi Meet room made for you">
    <small>Prefer Google Meet, Zoom or Microsoft Teams? Create the meeting there and paste its https:// link. If you leave this empty, the website creates a private Jitsi Meet room automatically. You do not need an account, and the first person in, you, hosts the lesson.</small></div>
  <button class="btn accent">Save lesson</button> <a class="btn ghost" href="<?= $self ?>">Cancel</a>
</form>
<?php else:
    $list = rows('SELECT * FROM live_sessions' . ($all ? '' : ' WHERE host_id = ?') . ' ORDER BY session_date DESC, start_time DESC LIMIT 200', $all ? [] : [$uid]);
?>
<div class="card">
  <h2>Live online lessons</h2>
  <p class="muted">Schedule a lesson and it appears on the public <a href="<?= url('live.php') ?>" target="_blank">Live Online Lessons</a> page. Learners and the public join with one click, from <?= LIVE_JOIN_OPENS_MIN ?> minutes before the start. <?= $all ? 'You can see every teacher\'s lessons.' : 'You see your own lessons.' ?></p>
  <p><a class="btn accent" href="<?= $self ?>?a=new">+ Schedule a lesson</a> <a class="btn ghost" href="<?= url('elibrary.php') ?>" target="_blank">View e-Library ↗</a></p>
</div>
<div class="tablewrap"><table>
  <thead><tr><th>Lesson</th><?= $all ? '<th>Teacher</th>' : '' ?><th>When</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($list as $s): $st = live_state($s); [$t0, $t1] = live_times($s); ?>
    <tr>
      <td><strong><?= e($s['title']) ?></strong><br><small class="muted"><?= e($s['subject']) ?> · <?= e($s['level']) ?></small></td>
      <?= $all ? '<td>' . e($s['host_name']) . '</td>' : '' ?>
      <td><?= e(date('D j M Y', $t0)) ?><br><small class="muted"><?= e(date('g:i A', $t0)) ?> – <?= e(date('g:i A', $t1)) ?></small></td>
      <td><?= $s['active'] ? '<span class="badge ' . ($st === 'live' ? 'new' : ($st === 'ended' ? 'closed' : 'open')) . '">' . e(live_state_label($st)) . '</span>' : '<span class="badge closed">Cancelled</span>' ?></td>
      <td class="actions">
        <?php if ($s['active'] && $st !== 'ended'): ?><a class="btn sm accent" href="<?= e(live_join_url($s)) ?>" target="_blank" rel="noopener noreferrer">▶ Start / join</a><?php endif; ?>
        <a class="btn sm ghost" href="<?= $self ?>?a=edit&id=<?= (int) $s['id'] ?>">Edit</a>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="do" value="toggle"><button class="btn sm ghost"><?= $s['active'] ? 'Cancel' : 'Restore' ?></button></form>
        <form method="post" onsubmit="return confirm('Delete this lesson?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="do" value="delete"><button class="btn sm danger">Delete</button></form>
      </td>
    </tr>
  <?php endforeach; if (!$list): ?><tr><td colspan="<?= $all ? 5 : 4 ?>" class="muted">No lessons yet. Click "Schedule a lesson" to create your first one.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php endif; admin_foot(); ?>
