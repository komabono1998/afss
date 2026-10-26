<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$hasUsers = (int) val('SELECT COUNT(*) FROM users') > 0;
if (admin_user()) redirect('admin/index.php');
$err = null;

if (is_post()) {
    csrf_check();
    $tries = array_filter($_SESSION['login_tries'] ?? [], fn($t) => $t > time() - 900);
    if (count($tries) >= 8) {
        $err = 'Too many attempts. Please wait 15 minutes.';
    } elseif (!$hasUsers) {
        // first-run: create the initial administrator
        $name = post('name'); $email = strtolower(post('email')); $pw = (string) ($_POST['password'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter your name and a valid email.';
        elseif (strlen($pw) < 8) $err = 'Password must be at least 8 characters.';
        elseif ($pw !== ($_POST['password2'] ?? '')) $err = 'Passwords do not match.';
        else {
            q('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)', [$name, $email, password_hash($pw, PASSWORD_DEFAULT), 'admin']);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) db()->lastInsertId();
            redirect('admin/index.php');
        }
    } else {
        $u = row('SELECT * FROM users WHERE email = ?', [strtolower(post('email'))]);
        if ($u && password_verify((string) ($_POST['password'] ?? ''), $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) $u['id'];
            unset($_SESSION['login_tries']);
            redirect('admin/index.php');
        }
        $tries[] = time();
        $_SESSION['login_tries'] = $tries;
        $err = 'Incorrect email or password.';
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= $hasUsers ? 'Admin Login' : 'Set up Administrator' ?> · <?= e(setting('short_name', 'AFSS')) ?></title>
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
<style>:root{--primary:<?= e(setting('primary_color', '#0b2a4a')) ?>;--accent:<?= e(setting('accent_color', '#c9962b')) ?>}</style></head>
<body class="center"><div class="login"><div class="card">
  <h2 style="font-size:1.4rem;margin-bottom:.2rem"><?= e(setting('school_name')) ?></h2>
  <p class="muted" style="margin-top:0"><?= $hasUsers ? 'Administrator sign in' : 'First-time setup: create the administrator account' ?></p>
  <?php if ($err): ?><div class="alert alert-err"><?= e($err) ?></div><?php endif; ?>
  <form method="post" autocomplete="on">
    <?= csrf_field() ?>
    <?php if (!$hasUsers): ?><div class="f"><label>Your name</label><input type="text" name="name" value="<?= old('name') ?>" required></div><?php endif; ?>
    <div class="f"><label>Email</label><input type="email" name="email" value="<?= old('email') ?>" required autofocus></div>
    <div class="f"><label>Password</label><input type="password" name="password" required></div>
    <?php if (!$hasUsers): ?><div class="f"><label>Confirm password</label><input type="password" name="password2" required><small>At least 8 characters.</small></div><?php endif; ?>
    <button class="btn accent" style="width:100%"><?= $hasUsers ? 'Sign in' : 'Create administrator' ?></button>
  </form>
  <p style="text-align:center;margin-bottom:0"><a href="<?= url() ?>">← Back to website</a></p>
</div></div></body></html>
