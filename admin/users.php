<?php
require_once __DIR__ . '/_layout.php';
require_perm('users');
$roles = roles();

function active_admins(int $excludeId = 0): int
{
    return (int) val("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1 AND id != ?", [$excludeId]);
}

if (is_post()) {
    csrf_check();
    $do = $_POST['do'] ?? '';
    $uid = (int) ($_POST['id'] ?? 0);
    $target = $uid ? row('SELECT * FROM users WHERE id = ?', [$uid]) : null;
    $isSelf = $target && (int) $target['id'] === (int) $ME['id'];

    if ($do === 'add') {
        $email = strtolower(post('email')); $pw = (string) ($_POST['password'] ?? ''); $role = post('role');
        if (post('name') === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) flash('Enter a name and valid email.', 'err');
        elseif (!isset($roles[$role])) flash('Choose a role for this user.', 'err');
        elseif (strlen($pw) < 8) flash('Password must be at least 8 characters.', 'err');
        elseif (val('SELECT COUNT(*) FROM users WHERE email = ?', [$email])) flash('That email already has an account.', 'err');
        else {
            q('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)', [post('name'), $email, password_hash($pw, PASSWORD_DEFAULT), $role]);
            flash(post('name') . ' was added as ' . $roles[$role]['label'] . '. Share the password with them securely and ask them to change it under My Account.');
        }
    } elseif ($target && $do === 'role') {
        $role = post('role');
        if (!isset($roles[$role])) flash('Unknown role.', 'err');
        elseif ($target['role'] === 'admin' && $role !== 'admin' && !active_admins($uid)) flash('At least one active Administrator must remain.', 'err');
        elseif ($isSelf && $role !== 'admin') flash('You cannot remove your own Administrator role.', 'err');
        else { q('UPDATE users SET role = ? WHERE id = ?', [$role, $uid]); flash('Role updated.'); }
    } elseif ($target && $do === 'password') {
        $pw = (string) ($_POST['password'] ?? '');
        if (strlen($pw) < 8) flash('Password must be at least 8 characters.', 'err');
        else { q('UPDATE users SET password = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $uid]); flash('Password updated.'); }
    } elseif ($target && $do === 'toggle') {
        if ($isSelf) flash('You cannot disable your own account.', 'err');
        elseif ($target['active'] && $target['role'] === 'admin' && !active_admins($uid)) flash('At least one active Administrator must remain.', 'err');
        else { q('UPDATE users SET active = ? WHERE id = ?', [$target['active'] ? 0 : 1, $uid]); flash($target['active'] ? 'Account disabled.' : 'Account enabled.'); }
    } elseif ($target && $do === 'delete') {
        if ($isSelf) flash('You cannot delete your own account.', 'err');
        elseif ($target['role'] === 'admin' && !active_admins($uid)) flash('At least one active Administrator must remain.', 'err');
        else { q('DELETE FROM users WHERE id = ?', [$uid]); flash('User deleted.'); }
    }
    redirect('admin/users.php');
}
admin_head('Admin Users');
$users = rows('SELECT id,name,email,role,active,created_at FROM users ORDER BY id');
?>
<form class="card" method="post"><h2>Add a user</h2><?= csrf_field() ?><input type="hidden" name="do" value="add">
  <div class="cols">
    <div class="f"><label>Full name</label><input type="text" name="name" required></div>
    <div class="f"><label>Email (used to sign in)</label><input type="email" name="email" required></div>
    <div class="f"><label>Role</label>
      <select name="role" required id="roleSel"><option value="">Choose a role…</option>
        <?php foreach ($roles as $k => $r): ?><option value="<?= e($k) ?>" data-desc="<?= e($r['desc']) ?>"><?= e($r['label']) ?></option><?php endforeach; ?></select>
      <small id="roleDesc">The role decides which parts of the admin panel this person can use.</small></div>
    <div class="f"><label>Temporary password</label><input type="password" name="password" minlength="8" required><small>At least 8 characters.</small></div>
  </div>
  <button class="btn accent">Create user</button>
</form>
<script>document.getElementById('roleSel').addEventListener('change',function(){var o=this.selectedOptions[0];document.getElementById('roleDesc').textContent=o&&o.dataset.desc?o.dataset.desc:'';});</script>

<div class="tablewrap"><table>
  <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Reset password</th><th></th></tr></thead><tbody>
  <?php foreach ($users as $u): $self = (int) $u['id'] === (int) $ME['id']; ?>
  <tr>
    <td><strong><?= e($u['name']) ?></strong><?= $self ? ' <span class="badge">you</span>' : '' ?><br><small class="muted"><?= e($u['email']) ?></small></td>
    <td><form method="post" style="display:flex;gap:.4rem"><?= csrf_field() ?><input type="hidden" name="do" value="role"><input type="hidden" name="id" value="<?= $u['id'] ?>">
      <select name="role" style="padding:.35rem;border-radius:8px;border:1px solid var(--line)"><?php foreach ($roles as $k => $r): ?><option value="<?= e($k) ?>" <?= $u['role'] === $k ? 'selected' : '' ?>><?= e($r['label']) ?></option><?php endforeach; ?></select>
      <button class="btn sm ghost">Save</button></form></td>
    <td><span class="badge <?= $u['active'] ? 'yes' : 'no' ?>"><?= $u['active'] ? 'Active' : 'Disabled' ?></span></td>
    <td><form method="post" style="display:flex;gap:.4rem"><?= csrf_field() ?><input type="hidden" name="do" value="password"><input type="hidden" name="id" value="<?= $u['id'] ?>">
      <input type="password" name="password" placeholder="new password" minlength="8" style="padding:.35rem .6rem;border:1px solid var(--line);border-radius:8px;width:150px"><button class="btn sm">Set</button></form></td>
    <td class="actions" style="justify-content:flex-end">
      <?php if (!$self): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn sm ghost"><?= $u['active'] ? 'Disable' : 'Enable' ?></button></form>
        <form method="post" data-confirm="Delete this user permanently?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn sm danger">Delete</button></form>
      <?php endif; ?></td>
  </tr>
  <?php endforeach; ?></tbody></table></div>

<div class="card" style="margin-top:1.2rem"><h2>What each role can do</h2>
  <div class="tablewrap"><table>
    <thead><tr><th>Role</th><th>Access</th><th>Limits</th></tr></thead><tbody>
    <?php foreach ($roles as $k => $r): $limits = [];
        foreach (($r['scope'] ?? []) as $mod => [$col, $vals]) $limits[] = ADMIN_MODULES[$mod] . ': ' . (is_array($vals) ? implode(', ', $vals) : 'categories containing “' . $vals . '”'); ?>
      <tr><td><strong><?= e($r['label']) ?></strong><br><small class="muted"><?= e($r['desc']) ?></small></td>
        <td><?= e(implode(', ', array_map(fn($m) => ADMIN_MODULES[$m], $r['modules']))) ?></td>
        <td><?= $limits ? e(implode(' · ', $limits)) : '<span class="muted">—</span>' ?></td></tr>
    <?php endforeach; ?></tbody></table></div></div>
<?php admin_foot(); ?>
