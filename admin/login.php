<?php
require_once __DIR__ . '/_auth.php';
$hasUsers = (int) val('SELECT COUNT(*) FROM users') > 0;
if (admin_user()) redirect('admin/index.php');
$err = null;

if (is_post()) {
    csrf_check();
    $tries = array_filter($_SESSION['login_tries'] ?? [], fn($t) => $t > time() - 900);
    if (count($tries) >= 8) {
        $err = 'Too many attempts. Please wait 15 minutes and try again.';
    } elseif (!$hasUsers) {
        // first run: create the initial administrator
        $name = post('name'); $email = strtolower(post('email')); $pw = (string) ($_POST['password'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter your name and a valid email address.';
        elseif (strlen($pw) < 8) $err = 'Password must be at least 8 characters.';
        elseif ($pw !== ($_POST['password2'] ?? '')) $err = 'The two passwords do not match.';
        else {
            q('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)', [$name, $email, password_hash($pw, PASSWORD_DEFAULT), 'admin']);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) db()->lastInsertId();
            redirect('admin/index.php');
        }
    } else {
        $u = row('SELECT * FROM users WHERE email = ?', [strtolower(post('email'))]);
        $ok = $u && password_verify((string) ($_POST['password'] ?? ''), $u['password']);
        if ($ok && !$u['active']) {
            $err = 'This account has been disabled. Please contact an Administrator.';
        } elseif ($ok) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) $u['id'];
            unset($_SESSION['login_tries']);
            redirect('admin/index.php');
        }
        if (!$err) { $tries[] = time(); $_SESSION['login_tries'] = $tries; $err = 'Incorrect email or password.'; }
    }
}

auth_start($hasUsers ? 'Admin sign in' : 'Set up the administrator');
foreach ((flash() ?? []) as [$t, $m]): ?><div class="lg-alert lg-<?= e($t) ?>"><?= e($m) ?></div><?php endforeach;
if ($err): ?><div class="lg-alert lg-err" role="alert"><?= e($err) ?></div><?php endif; ?>
    <?php if (!$hasUsers): ?><p class="lg-sub">Create the first administrator account. It has full access, and you can add staff with other roles afterwards.</p><?php endif; ?>
    <form method="post" autocomplete="on" novalidate>
      <?= csrf_field() ?>
      <?php if (!$hasUsers): ?><div class="lg-field"><label for="lg-name">Your name</label><input id="lg-name" type="text" name="name" value="<?= old('name') ?>" required></div><?php endif; ?>
      <div class="lg-field"><label for="lg-email">Email address</label><input id="lg-email" type="email" name="email" value="<?= old('email') ?>" required autofocus autocomplete="username" placeholder="you@school.ac.ug"></div>
      <?php auth_password_field('lg-pw', 'password', 'Password', $hasUsers ? 'current-password' : 'new-password'); ?>
      <?php if (!$hasUsers): auth_password_field('lg-pw2', 'password2', 'Confirm password', 'new-password'); endif; ?>
      <button class="lg-btn" type="submit"><?= $hasUsers ? 'Sign in to Admin' : 'Create administrator' ?></button>
    </form>
    <?php if ($hasUsers): ?><div class="lg-links"><a href="forgot.php">Forgot your password?</a><a href="request.php">Staff? Request an account</a><a href="<?= e(url('alumni.php#card')) ?>">Alumni member card</a></div><?php endif; ?>
<?php auth_end(); ?>
