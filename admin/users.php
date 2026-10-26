<?php
require_once __DIR__ . '/_layout.php';

if (is_post()) {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'add') {
        $email = strtolower(post('email')); $pw = (string) ($_POST['password'] ?? '');
        if (post('name') === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) flash('Enter a name and valid email.', 'err');
        elseif (strlen($pw) < 8) flash('Password must be at least 8 characters.', 'err');
        elseif (val('SELECT COUNT(*) FROM users WHERE email = ?', [$email])) flash('That email already has an account.', 'err');
        else { q('INSERT INTO users (name,email,password) VALUES (?,?,?)', [post('name'), $email, password_hash($pw, PASSWORD_DEFAULT)]); flash('Admin user added.'); }
    } elseif ($do === 'password') {
        $uid = (int) $_POST['id']; $pw = (string) ($_POST['password'] ?? '');
        if (strlen($pw) < 8) flash('Password must be at least 8 characters.', 'err');
        else { q('UPDATE users SET password = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $uid]); flash('Password updated.'); }
    } elseif ($do === 'delete') {
        $uid = (int) $_POST['id'];
        if ($uid === (int) $ME['id']) flash('You cannot delete your own account.', 'err');
        elseif ((int) val('SELECT COUNT(*) FROM users') <= 1) flash('At least one admin must remain.', 'err');
        else { q('DELETE FROM users WHERE id = ?', [$uid]); flash('User deleted.'); }
    }
    redirect('admin/users.php');
}
admin_head('Admin Users');
?>
<div class="tablewrap"><table><thead><tr><th>Name</th><th>Email</th><th>New password</th><th></th></tr></thead><tbody>
<?php foreach (rows('SELECT id,name,email FROM users ORDER BY id') as $u): ?>
  <tr><td><?= e($u['name']) ?><?= $u['id'] == $ME['id'] ? ' <span class="badge">you</span>' : '' ?></td><td><?= e($u['email']) ?></td>
    <td><form method="post" style="display:flex;gap:.4rem"><?= csrf_field() ?><input type="hidden" name="do" value="password"><input type="hidden" name="id" value="<?= $u['id'] ?>">
      <input type="password" name="password" placeholder="min. 8 characters" style="padding:.4rem .6rem;border:1px solid var(--line);border-radius:8px"><button class="btn sm">Change</button></form></td>
    <td><?php if ($u['id'] != $ME['id']): ?><form method="post" data-confirm="Delete this admin user?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn sm danger">Delete</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<form class="card" method="post" style="margin-top:1.2rem"><h2>Add admin user</h2><?= csrf_field() ?><input type="hidden" name="do" value="add">
  <div class="cols"><div class="f"><label>Name</label><input type="text" name="name" required></div><div class="f"><label>Email</label><input type="email" name="email" required></div>
  <div class="f"><label>Password</label><input type="password" name="password" required></div></div><button class="btn accent">Add user</button></form>
<?php admin_foot(); ?>
