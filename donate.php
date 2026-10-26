<?php
require_once __DIR__ . '/includes/bootstrap.php';

$types = array_values(array_filter(array_map('trim', preg_split('/\R/', setting('partnership_types') ?: donate_default('partnership_types')))));
$methods = rows('SELECT * FROM donation_methods WHERE active = 1 ORDER BY sort, id');
$causes = rows('SELECT * FROM donation_causes WHERE active = 1 ORDER BY sort, id');
$errors = [];

if (is_post()) {
    csrf_check();
    if (!spam_guard('donor', 5, 900)) $errors[] = 'Too many submissions. Please try again later.';
    if (post('name') === '') $errors[] = 'Your name is required.';
    if (post('email') === '' && post('phone') === '') $errors[] = 'Please give an email or phone number so we can reach you.';
    if (post('email') !== '' && !filter_var(post('email'), FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is not valid.';
    if (!in_array(post('kind'), $types, true)) $errors[] = 'Please choose how you would like to help.';
    if (!$errors) {
        q('INSERT INTO donor_pledges (name,organisation,email,phone,kind,cause,amount_text,reference,message) VALUES (?,?,?,?,?,?,?,?,?)',
            [post('name'), post('organisation'), strtolower(post('email')), post('phone'), post('kind'), post('cause'), post('amount_text'), post('reference'), post('message')]);
        notify('New donor / partner enquiry: ' . post('name'), post('name') . ' (' . post('kind') . ")" . (post('amount_text') !== '' ? ' — ' . post('amount_text') : '') . ".\nOpen the admin panel → Donor Enquiries.", post('email'));
        flash(setting('donate_thanks') ?: donate_default('donate_thanks'));
        redirect('donate.php#tell-us');
    }
}

function safe_link(string $u): string { return preg_match('#^https?://#i', $u) ? $u : ''; }
function copy_btn(string $text): string { return '<button type="button" class="copy-btn" data-copy="' . e($text) . '" title="Copy">Copy</button>'; }
$icons = ['bank' => '🏦', 'mtn' => '📱', 'airtel' => '📱', 'link' => '💳', 'other' => '💚'];
$accent = ['mtn' => '#ffcc00', 'airtel' => '#e40000'];

$pageTitle = 'Donate & Partner With Us';
$pageDesc = 'Support Archbishop Flynn Secondary School: donate by bank, mobile money or online, or partner with us.';
$bannerTitle = 'Donate & Partner With Us';
$bannerSub = 'Help us open doors for the next generation.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container">
  <div class="split" style="margin-bottom:3rem">
    <div class="reveal"><span class="eyebrow">Give</span><h2>Make a difference today</h2>
      <p style="font-size:1.1rem"><?= nl2br(e(setting('donate_intro') ?: donate_default('donate_intro'))) ?></p>
      <p><a class="btn btn-accent" href="#ways">Ways to give</a> <a class="btn btn-ghost" href="#partner">Become a partner</a></p></div>
    <div class="about-img reveal"><img class="crest-big" src="<?= e(media(setting('logo') ?: 'assets/img/logo.webp')) ?>" alt="<?= e(setting('school_name')) ?> crest"><div class="badge">Thank you</div></div>
  </div>

  <?php if ($causes): ?>
  <div class="section-head"><span class="eyebrow">Where your gift goes</span><h2>Causes you can support</h2><p>Mention your chosen cause as the payment reference, or tell us below.</p></div>
  <div class="grid g3" style="margin-bottom:3.5rem">
    <?php foreach ($causes as $c): ?>
    <div class="card reveal"><?php if ($c['image']): ?><div class="thumb"><img src="<?= e(media($c['image'])) ?>" alt="" loading="lazy"></div><?php endif; ?>
      <div class="card-body"><h3><?= e($c['title']) ?></h3><p><?= e($c['summary']) ?></p><?php if ($c['target']): ?><span class="tag">Target: <?= e($c['target']) ?></span><?php endif; ?></div></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div></section>

<section class="section alt" id="ways"><div class="container">
  <div class="section-head"><span class="eyebrow">Ways to give</span><h2>Choose what is easiest for you</h2></div>
  <?php if (!$methods): ?>
    <p style="text-align:center">Our giving details are being updated. Please <a href="<?= url('contact.php') ?>">contact the school</a> or use the form below and we will guide you.</p>
  <?php else: ?>
  <div class="grid g2 give-grid">
    <?php foreach ($methods as $m): $type = $m['type']; $link = safe_link((string) $m['link_url']); ?>
    <div class="card give-card reveal" style="border-top:5px solid <?= e($accent[$type] ?? 'var(--primary)') ?>"><div class="card-body">
      <div class="give-head"><span class="give-ico"><?= $icons[$type] ?? '💚' ?></span><div><h3 style="margin:0"><?= e($m['title']) ?></h3><?php if ($m['currency']): ?><span class="meta"><?= e($m['currency']) ?></span><?php endif; ?></div></div>
      <?php if ($type === 'bank' || ($type === 'other' && ($m['account_number'] || $m['bank_name']))): ?>
        <dl class="give-dl">
          <?php foreach (['bank_name' => 'Bank', 'account_name' => 'Account name', 'account_number' => 'Account number', 'branch' => 'Branch', 'swift' => 'SWIFT / BIC'] as $k => $lab): if ($m[$k] !== '' && $m[$k] !== null): ?>
            <dt><?= $lab ?></dt><dd><strong><?= e($m[$k]) ?></strong> <?= in_array($k, ['account_number', 'swift', 'account_name'], true) ? copy_btn($m[$k]) : '' ?></dd>
          <?php endif; endforeach; ?></dl>
      <?php endif; ?>
      <?php if (in_array($type, ['mtn', 'airtel'], true) || ($type === 'other' && $m['merchant_code'])): ?>
        <?php if ($m['merchant_code']): ?><p class="merchant"><span class="meta">Merchant code</span><strong><?= e($m['merchant_code']) ?></strong> <?= copy_btn($m['merchant_code']) ?></p><?php endif; ?>
        <?php if ($m['account_name']): ?><p class="meta">Registered name: <strong><?= e($m['account_name']) ?></strong></p><?php endif; ?>
      <?php endif; ?>
      <?php if ($link): ?><p><a class="btn btn-accent" href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer"><?= e($m['title']) ?> →</a></p><?php endif; ?>
      <?php if ($m['instructions']): ?><p class="meta" style="margin-top:.8rem"><?= nl2br(e($m['instructions'])) ?></p><?php endif; ?>
    </div></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="notice" style="margin-top:2rem">🔒 <strong>Stay safe.</strong> Only use the account numbers, merchant codes and links shown on this page. The school will never ask you to pay to a personal number. If you are unsure, call <?= e(setting('phone')) ?> before you pay.</div>
</div></section>

<section class="section" id="partner"><div class="container split" style="align-items:start">
  <div class="reveal"><span class="eyebrow">Partnership</span><h2>Partner with AFSS</h2><div class="prose wide"><?= setting('partnership_text') ?: donate_default('partnership_text') ?></div>
    <ul class="checks"><?php foreach ($types as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul></div>
  <div id="tell-us">
    <h3>Tell us about your gift or partnership</h3>
    <?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>
    <form class="form" method="post" action="#tell-us"><?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
      <div class="row">
        <div class="field"><label class="req">Your name</label><input name="name" value="<?= old('name') ?>" required></div>
        <div class="field"><label>Organisation (if any)</label><input name="organisation" value="<?= old('organisation') ?>"></div>
        <div class="field"><label>Email</label><input type="email" name="email" value="<?= old('email') ?>"></div>
        <div class="field"><label>Phone / WhatsApp</label><input name="phone" value="<?= old('phone') ?>"></div>
      </div>
      <div class="field"><label class="req">I would like to…</label><select name="kind" required><option value="">Choose…</option><?php foreach ($types as $t): ?><option <?= ($_POST['kind'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></div>
      <div class="row">
        <div class="field"><label>Cause (optional)</label><select name="cause"><option value="">No preference</option><?php foreach ($causes as $c): ?><option <?= ($_POST['cause'] ?? '') === $c['title'] ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Amount or what you plan to give</label><input name="amount_text" value="<?= old('amount_text') ?>" placeholder="e.g. UGX 500,000 or 50 textbooks"></div>
      </div>
      <div class="field"><label>Payment reference / transaction ID (if you have already paid)</label><input name="reference" value="<?= old('reference') ?>"></div>
      <div class="field"><label>Message</label><textarea name="message" rows="4"><?= old('message') ?></textarea></div>
      <button class="btn btn-primary" type="submit">Send</button>
    </form>
  </div>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
