<?php
require_once __DIR__ . '/_layout.php';

if (is_post()) {
    csrf_check();
    $u = row('SELECT * FROM users WHERE id = ?', [$ME['id']]);
    $new = (string) ($_POST['new_password'] ?? '');
    if (post('name') === '') flash('Name is required.', 'err');
    elseif ($new !== '' && !password_verify((string) ($_POST['current_password'] ?? ''), $u['password'])) flash('Current password is incorrect.', 'err');
    elseif ($new !== '' && strlen($new) < 8) flash('New password must be at least 8 characters.', 'err');
    elseif ($new !== '' && $new !== ($_POST['new_password2'] ?? '')) flash('New passwords do not match.', 'err');
    else {
        q('UPDATE users SET name = ? WHERE id = ?', [post('name'), $ME['id']]);
        if ($new !== '') q('UPDATE users SET password = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $ME['id']]);
        flash('Account updated.');
    }
    redirect('admin/account.php');
}
admin_head('My Account');
?>
<form class="card" method="post" style="max-width:560px"><?= csrf_field() ?>
  <h2>Profile</h2>
  <div class="f"><label>Name</label><input type="text" name="name" value="<?= e($ME['name']) ?>" required></div>
  <div class="f"><label>Email</label><input type="text" value="<?= e($ME['email']) ?>" disabled></div>
  <div class="f"><label>Role</label><input type="text" value="<?= e(role_label($ME['role'])) ?>" disabled><small><?= e(roles()[$ME['role']]['desc'] ?? '') ?></small></div>
  <h2 style="margin-top:1.5rem">Change password</h2>
  <div class="f"><label>Current password</label><input type="password" name="current_password" autocomplete="current-password"></div>
  <div class="f"><label>New password (min. 8 characters)</label><input type="password" name="new_password" autocomplete="new-password"></div>
  <div class="f"><label>Confirm new password</label><input type="password" name="new_password2" autocomplete="new-password"></div>
  <button class="btn accent">Save</button>
</form>
<?php admin_foot(); ?>
