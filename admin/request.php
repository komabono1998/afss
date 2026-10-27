<?php
// Staff ask for an admin account (for example to host live lessons). An Administrator reviews every request before any account exists.
require_once __DIR__ . '/_auth.php';
if (admin_user()) redirect('admin/index.php');
$sent = false; $err = null;

if (is_post()) {
    csrf_check();
    $name = post('name'); $email = strtolower(post('email')); $phone = post('phone'); $subj = post('subject'); $msg = post('message');
    if (post('website') !== '') { $sent = true; }                                   // honeypot: pretend it worked
    elseif (!spam_guard('staffreq', 4, 3600)) $err = 'Too many requests. Please try again later.';
    elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Please enter your full name and a valid email address.';
    elseif (post('confirm') === '') $err = 'Please confirm that you work at Archbishop Flynn Secondary School.';
    elseif (val("SELECT COUNT(*) FROM users WHERE email = ?", [$email])) $err = 'An account with this email already exists. Use "Forgot your password?" on the sign-in page.';
    elseif (val("SELECT COUNT(*) FROM staff_requests WHERE email = ? AND status = 'new'", [$email])) { $sent = true; }
    else {
        q('INSERT INTO staff_requests (name,email,phone,subject_taught,message) VALUES (?,?,?,?,?)', [$name, $email, $phone, $subj, $msg]);
        notify('Staff account request: ' . $name, "$name <$email> asked for a staff account" . ($subj ? " (teaches $subj)" : '') . ".\nReview it in the admin panel under Admin Users → Staff account requests.", $email);
        $sent = true;
    }
}

auth_start('Request a staff account');
if ($sent): ?>
    <div class="lg-alert lg-ok" role="status">Thank you. Your request has been sent to the school administrator. If it is approved, you will receive an email with a link to set your password.</div>
    <div class="lg-links"><a href="login.php">← Back to sign in</a></div>
<?php else: ?>
    <?php if ($err): ?><div class="lg-alert lg-err" role="alert"><?= e($err) ?></div><?php endif; ?>
    <p class="lg-sub">For AFSS teachers and staff. Tell us who you are. An administrator checks every request and sets your access, such as hosting live online lessons.</p>
    <form method="post" novalidate><?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">
      <div class="lg-field"><label for="r-name">Full name</label><input id="r-name" name="name" value="<?= old('name') ?>" required autocomplete="name"></div>
      <div class="lg-field"><label for="r-email">Email address</label><input id="r-email" type="email" name="email" value="<?= old('email') ?>" required autocomplete="email"></div>
      <div class="lg-field"><label for="r-phone">Phone / WhatsApp (optional)</label><input id="r-phone" name="phone" value="<?= old('phone') ?>" autocomplete="tel"></div>
      <div class="lg-field"><label for="r-subj">Subject(s) you teach or your role</label><input id="r-subj" name="subject" value="<?= old('subject') ?>" placeholder="e.g. Physics, A level"></div>
      <div class="lg-field"><label for="r-msg">Anything else? (optional)</label><input id="r-msg" name="message" value="<?= old('message') ?>" placeholder="e.g. I would like to host revision lessons"></div>
      <div class="lg-field"><label style="font-weight:500"><input type="checkbox" name="confirm" value="1" style="width:auto;margin-right:.5rem"> I work at Archbishop Flynn Secondary School</label></div>
      <button class="lg-btn" type="submit">Send request</button>
    </form>
    <div class="lg-links"><a href="login.php">← Back to sign in</a></div>
<?php endif; auth_end(); ?>
