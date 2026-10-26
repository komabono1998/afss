<?php
require_once __DIR__ . '/includes/bootstrap.php';
$errors = [];
if (is_post()) {
    csrf_check();
    if (!spam_guard('contact', 5)) $errors[] = 'Too many messages. Please try again later.';
    if (post('name') === '') $errors[] = 'Your name is required.';
    if (!filter_var(post('email'), FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (post('message') === '') $errors[] = 'Please write a message.';
    if (!$errors) {
        q('INSERT INTO messages (name,email,phone,subject,message) VALUES (?,?,?,?,?)', [post('name'), post('email'), post('phone'), post('subject'), post('message')]);
        notify('Website message: ' . (post('subject') ?: 'No subject'), post('name') . ' <' . post('email') . ">\n\n" . post('message'), post('email'));
        flash('Thank you! Your message has been sent. We will reply shortly.');
        redirect('contact.php');
    }
}
$pageTitle = 'Contact Us';
$bannerTitle = 'Contact Us';
$bannerSub = 'We would love to hear from you.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container split" style="align-items:start">
  <div>
    <h2>Get in touch</h2>
    <div class="grid" style="gap:1rem;margin-bottom:1.5rem">
      <div class="card"><div class="card-body"><strong>📍 Address</strong><br><?= e(setting('address')) ?></div></div>
      <div class="card"><div class="card-body"><strong>☎ Phone</strong><br><?= e(setting('phone')) ?><?= setting('phone2') ? '<br>' . e(setting('phone2')) : '' ?></div></div>
      <div class="card"><div class="card-body"><strong>✉ Email</strong><br><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></div></div>
    </div>
    <?php if (setting('map_embed')): ?><iframe title="Map" src="<?= e(setting('map_embed')) ?>" style="width:100%;height:280px;border:0;border-radius:14px" loading="lazy"></iframe><?php endif; ?>
  </div>
  <div>
    <h2>Send a message</h2>
    <?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>
    <form class="form" method="post">
      <?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
      <div class="row">
        <div class="field"><label class="req">Name</label><input name="name" value="<?= old('name') ?>" required></div>
        <div class="field"><label class="req">Email</label><input type="email" name="email" value="<?= old('email') ?>" required></div>
        <div class="field"><label>Phone</label><input name="phone" value="<?= old('phone') ?>"></div>
        <div class="field"><label>Subject</label><input name="subject" value="<?= old('subject') ?>"></div>
      </div>
      <div class="field"><label class="req">Message</label><textarea name="message" rows="6" required><?= old('message') ?></textarea></div>
      <button class="btn btn-primary" type="submit">Send message</button>
    </form>
  </div>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
