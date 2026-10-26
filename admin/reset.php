<?php
require_once __DIR__ . '/_auth.php';
if (admin_user()) redirect('admin/index.php');

$token = (string) ($_GET['t'] ?? $_POST['t'] ?? '');
$rec = preg_match('/^[a-f0-9]{64}$/', $token)
    ? row('SELECT r.*, u.name, u.active FROM password_resets r JOIN users u ON u.id = r.user_id WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > ?', [hash('sha256', $token), date('Y-m-d H:i:s')])
    : null;
if ($rec && !$rec['active']) $rec = null;
$err = null;

if ($rec && is_post()) {
    csrf_check();
    $pw = (string) ($_POST['password'] ?? '');
    if (!spam_guard('reset', 10, 900)) $err = 'Too many attempts. Please wait a few minutes.';
    elseif (strlen($pw) < 8) $err = 'Password must be at least 8 characters.';
    elseif ($pw !== ($_POST['password2'] ?? '')) $err = 'The two passwords do not match.';
    else {
        q('UPDATE users SET password = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $rec['user_id']]);
        q('UPDATE password_resets SET used_at = ? WHERE user_id = ?', [date('Y-m-d H:i:s'), $rec['user_id']]);   // this and any other pending links stop working
        unset($_SESSION['login_tries']);
        flash('Your password has been changed. Please sign in with the new password.');
        redirect('admin/login.php');
    }
}

auth_start($rec ? 'Choose a new password' : 'Link not valid');
if (!$rec): ?>
    <div class="lg-alert lg-err" role="alert">This reset link is invalid, has already been used, or has expired.</div>
    <div class="lg-links"><a href="forgot.php">Request a new link</a><a href="login.php">Back to sign in</a></div>
<?php else: ?>
    <?php if ($err): ?><div class="lg-alert lg-err" role="alert"><?= e($err) ?></div><?php endif; ?>
    <p class="lg-sub">Hello <?= e(explode(' ', trim($rec['name']))[0]) ?>, choose a new password of at least 8 characters.</p>
    <form method="post" novalidate><?= csrf_field() ?><input type="hidden" name="t" value="<?= e($token) ?>">
      <?php auth_password_field('lg-pw', 'password', 'New password', 'new-password'); auth_password_field('lg-pw2', 'password2', 'Confirm new password', 'new-password'); ?>
      <button class="lg-btn" type="submit">Save new password</button>
    </form>
<?php endif; auth_end(); ?>
