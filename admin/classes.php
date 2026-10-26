<?php
require_once __DIR__ . '/_layout.php';
require_perm('classes');

if (is_post()) {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'add_class') {
        $name = post('class_name');
        $level = in_array(post('level'), ['O Level', 'A Level'], true) ? post('level') : 'O Level';
        $streams = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) ($_POST['streams'] ?? ''))))));
        if ($name === '') flash('Enter a class name, e.g. Senior 1.', 'err');
        else {
            $sort = (int) val('SELECT COALESCE(MAX(sort),0) FROM class_streams') + 1;
            $made = 0;
            foreach ($streams ?: [''] as $s) {
                if (val('SELECT COUNT(*) FROM class_streams WHERE lower(class_name)=lower(?) AND lower(stream_name)=lower(?)', [$name, $s])) continue;
                q('INSERT INTO class_streams (class_name,stream_name,level,sort) VALUES (?,?,?,?)', [$name, $s, $level, $sort]);
                $made++;
            }
            flash($made ? "$made class/stream row(s) added to $name." : 'Those streams already exist.', $made ? 'ok' : 'err');
        }
    } elseif ($do === 'save') {
        $id = (int) $_POST['id'];
        $teacher = (int) ($_POST['teacher_id'] ?? 0) ?: null;
        if ($teacher && !val("SELECT COUNT(*) FROM users WHERE id = ? AND role = 'class_teacher' AND active = 1", [$teacher])) $teacher = null;
        q('UPDATE class_streams SET class_name=?, stream_name=?, level=?, teacher_id=?, sort=?, active=? WHERE id=?',
            [post('class_name') ?: 'Class', post('stream_name'), post('level') === 'A Level' ? 'A Level' : 'O Level', $teacher, (int) ($_POST['sort'] ?? 0), isset($_POST['active']) ? 1 : 0, $id]);
        flash('Saved.');
    } elseif ($do === 'delete') {
        $id = (int) $_POST['id'];
        if (val('SELECT COUNT(*) FROM students WHERE class_stream_id = ?', [$id])) flash('This class still has students. Move them to another class first.', 'err');
        else { q('DELETE FROM class_streams WHERE id = ?', [$id]); flash('Class/stream deleted.'); }
    } elseif ($do === 'prefix') {
        $p = preg_replace('/[^A-Za-z0-9]/', '', post('reg_prefix'));
        if ($p === '') flash('Enter a registration prefix, e.g. AFSS.', 'err'); else { save_setting('reg_prefix', strtoupper($p)); flash('Registration number prefix saved.'); }
    }
    redirect('admin/classes.php');
}
admin_head('Classes & Streams');
$teachers = rows("SELECT id,name,email FROM users WHERE role = 'class_teacher' AND active = 1 ORDER BY name");
$list = rows('SELECT c.*, (SELECT COUNT(*) FROM students s WHERE s.class_stream_id = c.id) AS n, (SELECT COUNT(*) FROM students s WHERE s.class_stream_id = c.id AND s.status = \'present\') AS present_n FROM class_streams c ORDER BY sort, class_name, stream_name');
?>
<div class="cols">
<form class="card" method="post"><h2>Add a class</h2><?= csrf_field() ?><input type="hidden" name="do" value="add_class">
  <div class="f"><label>Class name</label><input type="text" name="class_name" placeholder="e.g. Senior 1" required></div>
  <div class="f"><label>Level</label><select name="level"><option>O Level</option><option>A Level</option></select></div>
  <div class="f"><label>Streams (comma-separated)</label><input type="text" name="streams" placeholder="e.g. A, B, C — or leave empty for a single stream"><small>Each stream becomes its own row below, so it can have its own class teacher.</small></div>
  <button class="btn accent">Add</button>
</form>
<form class="card" method="post"><h2>Student registration numbers</h2><?= csrf_field() ?><input type="hidden" name="do" value="prefix">
  <p class="muted">Every student gets an automatic number like <strong><?= e(next_reg_no()[0]) ?></strong> (prefix / year / running number). The number never changes and is used to identify the student.</p>
  <div class="f"><label>Prefix</label><input type="text" name="reg_prefix" value="<?= e(setting('reg_prefix', 'AFSS')) ?>" maxlength="10"><small>Applies to new registrations only; existing numbers are kept.</small></div>
  <button class="btn">Save prefix</button>
</form>
</div>

<div class="card"><h2>Classes, streams and class teachers</h2>
<?php if (!$teachers): ?><p class="notice-card" style="margin-bottom:1rem">No class teachers yet. <a class="btn sm accent" href="users.php">Add a user with the “Class Teacher” role</a>, then assign them to a stream here.</p><?php endif; ?>
<div class="tablewrap"><table>
  <thead><tr><th>Class</th><th>Stream</th><th>Level</th><th>Class teacher</th><th>Students</th><th>Order</th><th>Active</th><th></th></tr></thead><tbody>
  <?php foreach ($list as $c): ?>
  <form method="post" id="f<?= $c['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"></form>
  <tr>
    <td><input form="f<?= $c['id'] ?>" type="text" name="class_name" value="<?= e($c['class_name']) ?>" style="width:120px;padding:.35rem;border:1px solid var(--line);border-radius:6px"></td>
    <td><input form="f<?= $c['id'] ?>" type="text" name="stream_name" value="<?= e($c['stream_name']) ?>" style="width:80px;padding:.35rem;border:1px solid var(--line);border-radius:6px"></td>
    <td><select form="f<?= $c['id'] ?>" name="level" style="padding:.35rem;border-radius:6px;border:1px solid var(--line)"><option <?= $c['level'] === 'O Level' ? 'selected' : '' ?>>O Level</option><option <?= $c['level'] === 'A Level' ? 'selected' : '' ?>>A Level</option></select></td>
    <td><select form="f<?= $c['id'] ?>" name="teacher_id" style="padding:.35rem;border-radius:6px;border:1px solid var(--line);max-width:190px"><option value="">— none —</option><?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>" <?= (int) $c['teacher_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></td>
    <td><a href="students.php?cs=<?= $c['id'] ?>&status=all"><?= (int) $c['present_n'] ?> present</a><?= $c['n'] > $c['present_n'] ? '<br><small class="muted">' . ($c['n'] - $c['present_n']) . ' other</small>' : '' ?></td>
    <td><input form="f<?= $c['id'] ?>" type="number" name="sort" value="<?= (int) $c['sort'] ?>" style="width:60px;padding:.35rem;border:1px solid var(--line);border-radius:6px"></td>
    <td><input form="f<?= $c['id'] ?>" type="checkbox" name="active" value="1" <?= $c['active'] ? 'checked' : '' ?>></td>
    <td class="actions" style="justify-content:flex-end"><button form="f<?= $c['id'] ?>" name="do" value="save" class="btn sm">Save</button>
      <button form="f<?= $c['id'] ?>" name="do" value="delete" class="btn sm danger" formnovalidate onclick="return confirm('Delete this class/stream?')">Delete</button></td>
  </tr>
  <?php endforeach; if (!$list): ?><tr><td colspan="8" class="muted">No classes yet. Add Senior 1 to Senior 6 above, with their streams.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<?php admin_foot(); ?>
