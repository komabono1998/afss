<?php
require_once __DIR__ . '/_auth.php';
if (admin_user()) redirect('admin/index.php');
if ((int) val('SELECT COUNT(*) FROM users') === 0) redirect('admin/login.php');
$sent = false; $err = null;

if (is_post()) {
    csrf_check();
    $email = strtolower(post('email'));
    if (!spam_guard('forgot', 5, 900)) {
        $err = 'Too many requests. Please wait a few minutes and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address.';
    } else {
        $u = row('SELECT id,name,email FROM users WHERE email = ? AND active = 1', [$email]);
        if ($u) {
            q('DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL', [$u['id']]);
            $token = bin2hex(random_bytes(32));
            q('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)', [$u['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600)]);
            $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . url('admin/reset.php?t=' . $token);
            $body = "Hello {$u['name']},\n\nSomeone asked to reset the password for your " . setting('short_name', 'AFSS') . " admin account.\n\nSet a new password here (the link works once and expires in 1 hour):\n$link\n\nIf you did not ask for this, ignore this email; your password has not changed.";
            $mailed = @mail($u['email'], 'Reset your ' . setting('short_name', 'AFSS') . ' admin password', $body, "Content-Type: text/plain; charset=UTF-8\r\nFrom: " . setting('short_name', 'AFSS') . ' <' . setting('email', 'noreply@localhost') . ">\r\n");
            // On a developer machine mail() usually is not configured: keep the link in a protected log so it can still be used.
            if (!$mailed && is_local_host()) @file_put_contents(ROOT . '/data/mail.log', '[' . date('Y-m-d H:i:s') . "] Password reset for {$u['email']}: $link\n", FILE_APPEND | LOCK_EX);
        }
        $sent = true;   // same answer whether or not the address exists
    }
}

auth_start('Reset your password');
if ($sent): ?>
    <div class="lg-alert lg-ok" role="status">If that email belongs to an active admin account, we have sent a link to reset the password. It works once and expires in 1 hour.</div>
    <p class="lg-sub">Nothing arrived after a few minutes? Check your spam folder, or ask an Administrator to set a new password for you under <strong>Admin Users</strong>.</p>
    <div class="lg-links"><a href="login.php">← Back to sign in</a></div>
<?php else: ?>
    <?php if ($err): ?><div class="lg-alert lg-err" role="alert"><?= e($err) ?></div><?php endif; ?>
    <p class="lg-sub">Enter the email address you use to sign in and we will email you a link to choose a new password.</p>
    <form method="post" novalidate><?= csrf_field() ?>
      <div class="lg-field"><label for="lg-email">Email address</label><input id="lg-email" type="email" name="email" value="<?= old('email') ?>" required autofocus autocomplete="username"></div>
      <button class="lg-btn" type="submit">Send reset link</button>
    </form>
    <div class="lg-links"><a href="login.php">← Back to sign in</a></div>
<?php endif; auth_end(); ?>
