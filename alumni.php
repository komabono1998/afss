<?php
require_once __DIR__ . '/includes/bootstrap.php';

$AL = fn(string $k) => setting($k) !== '' ? setting($k) : alumni_default($k);
$contactEmail = setting('alumni_email') ?: setting('email');
$contactPhone = setting('alumni_phone') ?: setting('phone');
$icons = ['League' => '⚽', 'AGM' => '🗳', 'Dinner' => '🍽', 'Reunion' => '🤝', 'Fundraising' => '💚', 'Outreach' => '🌍', 'Event' => '🎉'];

// ---------- single activity / event ----------
if (!empty($_GET['e'])) {
    $ev = row('SELECT * FROM alumni_events WHERE slug = ? AND active = 1', [$_GET['e']]);
    if (!$ev) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
    $pageTitle = $ev['title'];
    $pageDesc = $ev['summary'] ?: excerpt((string) $ev['description']);
    $pageImage = $ev['image'];
    $bannerTitle = $ev['title'];
    $bannerSub = $ev['summary'];
    $crumbs = [['Alumni', url('alumni.php')], ['Activities & Events', url('alumni.php#events')]];
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/banner.php';
    ?>
    <div class="container content-wrap">
      <article class="prose">
        <?php if ($ev['image']): ?><img src="<?= e(media($ev['image'])) ?>" alt="<?= e($ev['title']) ?>"><?php endif; ?>
        <p><span class="tag"><?= e($ev['category']) ?></span></p>
        <?php if ($ev['event_date'] || $ev['venue'] || $ev['time_text']): ?><p class="notice">
          <?php if ($ev['event_date']): ?><strong>When:</strong> <?= e(fmt_date($ev['event_date'], 'l, j F Y')) ?><?= $ev['end_date'] && $ev['end_date'] !== $ev['event_date'] ? ' – ' . e(fmt_date($ev['end_date'], 'l, j F Y')) : '' ?><?= $ev['time_text'] ? ' · ' . e($ev['time_text']) : '' ?><br><?php endif; ?>
          <?php if ($ev['venue']): ?><strong>Where:</strong> <?= e($ev['venue']) ?><?php endif; ?></p><?php endif; ?>
        <?= $ev['description'] ?>
        <?php if ($ev['contact_info']): ?><h2>How to take part</h2><p><?= nl2br(e($ev['contact_info'])) ?></p><?php endif; ?>
        <p><a class="btn btn-primary" href="alumni.php#join">Join the alumni network</a></p>
        <p><a href="alumni.php#events">← All activities &amp; events</a></p>
      </article>
      <aside class="sidebar"><div class="box"><h4>Contact the alumni office</h4><p><?= e($contactPhone) ?><br><a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></p></div></aside>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; exit;
}

// ---------- join form ----------
$errors = [];
if (is_post()) {
    csrf_check();
    if (!spam_guard('alumni', 5)) $errors[] = 'Too many submissions. Please try again later.';
    if (post('name') === '') $errors[] = 'Your name is required.';
    if (post('email') === '' && post('phone') === '') $errors[] = 'Please give an email or a phone number so we can reach you.';
    if (post('email') !== '' && !filter_var(post('email'), FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is not valid.';
    if (post('newsletter') !== '' && post('email') === '') $errors[] = 'An email is needed to receive the alumni newsletter.';
    if (post('consent') === '') $errors[] = 'Please confirm that you have read the AFSSOSA constitution and are ready to abide by it.';
    $photo = $proof = null;
    if (!$errors) {
        try {
            $photo = handle_upload('photo', IMG_TYPES, PRIVATE_DIR, 'alp_');
            $proof = handle_upload('proof_file', ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'], PRIVATE_DIR, 'alf_');
            if (!$photo && setting('alumni_require_photo') === '1') $errors[] = 'Please upload your photo.';
            if (!$proof && setting('alumni_require_proof') === '1') $errors[] = 'Please attach your proof of payment or membership.';
        } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
        if ($errors) foreach ([$photo, $proof] as $fn) if ($fn) @unlink(PRIVATE_DIR . '/' . $fn);
    }
    if (!$errors) {
        q('INSERT INTO alumni_members (name,email,phone,year_left,last_class,profession,workplace,location,message,newsletter,token,photo,proof_file,consent,consent_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [post('name'), strtolower(post('email')), post('phone'), post('year_left'), post('last_class'), post('profession'), post('workplace'), post('location'), post('message'), post('newsletter') !== '' ? 1 : 0, bin2hex(random_bytes(12)), $photo, $proof, 1, date('Y-m-d H:i:s')]);
        notify('New alumni sign-up: ' . post('name'), post('name') . ' (left ' . post('year_left') . ') joined the alumni network.\nView it in the admin panel.', post('email'));
        flash('Welcome back, ' . post('name') . '! You have joined the AFSS alumni network.');
        redirect('alumni.php#join');
    }
}

// ---------- data ----------
$patron = rows("SELECT * FROM team WHERE grp = 'alumni_patron' ORDER BY sort, id");
$exec = rows("SELECT * FROM team WHERE grp = 'alumni_exec' ORDER BY sort, id");
$acts = rows('SELECT * FROM alumni_events WHERE active = 1 ORDER BY sort, event_date, id');
$today = date('Y-m-d');
$upcoming = array_values(array_filter($acts, fn($a) => $a['event_date'] && ($a['end_date'] ?: $a['event_date']) >= $today));
usort($upcoming, fn($a, $b) => strcmp($a['event_date'], $b['event_date']));
$annual = array_values(array_filter($acts, fn($a) => !$a['event_date']));
$past = array_values(array_filter($acts, fn($a) => $a['event_date'] && ($a['end_date'] ?: $a['event_date']) < $today));
usort($past, fn($a, $b) => strcmp($b['event_date'], $a['event_date']));
$issues = rows("SELECT * FROM newsletter_issues WHERE status = 'published' AND audience = 'alumni' AND issue_date <= date('now') ORDER BY issue_date DESC, id DESC LIMIT 6");

function alumni_person(array $m, bool $big = false): string
{
    $ini = implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($m['name'])), 0, 2)));
    $av = $m['photo'] ? '<img src="' . e(media($m['photo'])) . '" alt="' . e($m['name']) . '">' : e($ini);
    return '<a class="card person reveal' . ($big ? ' patron' : '') . '" href="' . e(url('profile.php?id=' . $m['id'])) . '"><div class="avatar"' . ($big ? ' style="width:140px;height:140px;font-size:2.4rem"' : '') . '>' . $av . '</div>'
        . '<h3>' . e($m['name']) . '</h3><div class="role">' . e($m['role']) . '</div><span class="more" style="display:block;margin-top:.5rem">View profile →</span></a>';
}
function activity_card(array $a, array $icons): string
{
    $when = $a['event_date'] ? e(fmt_date($a['event_date'], 'j M Y')) . ($a['time_text'] ? ' · ' . e($a['time_text']) : '') : 'Annual activity';
    $img = $a['image'] ? '<img src="' . e(media($a['image'])) . '" alt="" loading="lazy">' : '<span style="font-size:3rem">' . ($icons[$a['category']] ?? '🎉') . '</span>';
    return '<a class="card reveal" href="' . e(url('alumni.php?e=' . $a['slug'])) . '"><div class="thumb">' . $img . '</div><div class="card-body"><span class="tag">' . e($a['category']) . '</span>'
        . '<h3>' . e($a['title']) . '</h3><p>' . e($a['summary'] ?: excerpt((string) $a['description'], 120)) . '</p><span class="meta">' . $when . ($a['venue'] ? ' · ' . e($a['venue']) : '') . '</span></div></a>';
}

$pageTitle = 'Alumni — ' . setting('short_name');
$pageDesc = $AL('alumni_intro');
$bannerTitle = 'Alumni';
$bannerSub = $AL('alumni_name');
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
$tools = array_filter([
    can('alumni_events') ? ['Activities & events', 'admin/crud.php?e=alumni_events'] : null,
    can('team') ? ['Leadership', 'admin/crud.php?e=team'] : null,
    can('issues') ? ['Alumni newsletter', 'admin/issues.php'] : null,
    can('alumni_members') ? ['Members', 'admin/inbox.php?t=alumni_members'] : null,
    can('alumni_page') ? ['Page text', 'admin/alumni_page.php'] : null,
]);
?>
<?php if ($tools): ?><div class="adminbar no-print"><div class="container"><span>✏ Editing tools:</span><?php foreach ($tools as [$l, $h]): ?><a href="<?= e(url($h)) ?>"><?= e($l) ?></a><?php endforeach; ?></div></div><?php endif; ?>
<section class="section">
  <div class="container split">
    <div class="reveal"><span class="eyebrow"><?= e($AL('alumni_name')) ?></span><h2>Once AFSS, always AFSS</h2>
      <p style="font-size:1.1rem"><?= e($AL('alumni_intro')) ?></p>
      <div class="prose wide"><?= $AL('alumni_about') ?></div>
      <p><a class="btn btn-accent" href="#join">Join the network</a> <a class="btn btn-ghost" href="#events">Activities &amp; events</a></p></div>
    <div class="about-img reveal"><img class="crest-big" src="<?= e(media(setting('logo') ?: 'assets/img/logo.webp')) ?>" alt="<?= e(setting('school_name')) ?> crest"><div class="badge">Sapientia et Servitium</div></div>
  </div>
</section>

<?php if ($patron || $exec): ?>
<section class="section alt" id="leadership"><div class="container">
  <div class="section-head"><span class="eyebrow">Leadership</span><h2>Patron &amp; Executive Committee</h2><p>The people guiding the association and its activities.</p></div>
  <?php if ($patron): ?><div class="grid" style="justify-content:center;grid-template-columns:repeat(auto-fit,minmax(240px,320px));margin-bottom:2rem"><?php foreach ($patron as $m) echo alumni_person($m, true); ?></div><?php endif; ?>
  <?php if ($exec): ?><h3 style="text-align:center;margin-bottom:1.2rem">Executive Committee</h3>
  <div class="grid g4" style="justify-content:center"><?php foreach ($exec as $m) echo alumni_person($m); ?></div><?php endif; ?>
</div></section>
<?php endif; ?>

<section class="section" id="events"><div class="container">
  <div class="section-head"><span class="eyebrow">Activities &amp; Events</span><h2>What alumni do together</h2><p>From the AFSSOSA League to the AGM and the Alumni Dinner.</p></div>
  <?php if ($upcoming): ?><h3>Upcoming</h3><div class="grid g3" style="margin-bottom:2.5rem"><?php foreach ($upcoming as $a) echo activity_card($a, $icons); ?></div><?php endif; ?>
  <?php if ($annual): ?><?php if ($upcoming): ?><h3>Regular activities</h3><?php endif; ?><div class="grid g3" style="margin-bottom:2.5rem"><?php foreach ($annual as $a) echo activity_card($a, $icons); ?></div><?php endif; ?>
  <?php if ($past): ?><h3>Past events</h3><div class="grid g3"><?php foreach (array_slice($past, 0, 6) as $a) echo activity_card($a, $icons); ?></div><?php endif; ?>
  <?php if (!$acts): ?><p style="text-align:center">Activities will be announced soon.</p><?php endif; ?>
</div></section>

<section class="section alt" id="newsletter"><div class="container">
  <div class="section-head"><span class="eyebrow">Newsletter</span><h2>Alumni newsletter</h2><p>News from the association, class updates and stories from fellow alumni.</p></div>
  <?php if ($issues): ?><div class="grid g3">
    <?php foreach ($issues as $i): ?>
    <a class="card nl-card reveal" href="<?= url('newsletter.php?s=' . $i['slug']) ?>">
      <div class="thumb"><?php if ($i['cover']): ?><img src="<?= e(media($i['cover'])) ?>" alt="" loading="lazy"><?php else: ?><span><?= e(date('F', strtotime($i['issue_date']))) ?></span><small><?= e(date('Y', strtotime($i['issue_date']))) ?></small><?php endif; ?></div>
      <div class="card-body"><h3 style="font-size:1.05rem"><?= e($i['title']) ?></h3><p><?= e($i['summary']) ?></p><span class="meta"><?= e(date('F Y', strtotime($i['issue_date']))) ?></span></div></a>
    <?php endforeach; ?></div>
  <?php else: ?><p style="text-align:center">The first alumni newsletter is on its way. Join the network below and tick the newsletter box to receive it.</p><?php endif; ?>
</div></section>

<section class="section" id="join"><div class="container" style="max-width:820px">
  <div class="section-head"><span class="eyebrow">Stay connected</span><h2>Join the alumni network</h2><p>Tell us where you are now so we can keep you informed about events and news.</p></div>
  <?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>
  <form class="form" method="post" action="#join" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
    <div class="row">
      <div class="field"><label class="req">Full name</label><input name="name" value="<?= old('name') ?>" required></div>
      <div class="field"><label>Year you left AFSS</label><input name="year_left" value="<?= old('year_left') ?>" placeholder="e.g. 2015"></div>
      <div class="field"><label>Email</label><input type="email" name="email" value="<?= old('email') ?>"></div>
      <div class="field"><label>Phone / WhatsApp</label><input name="phone" value="<?= old('phone') ?>"></div>
      <div class="field"><label>Last class attended</label><input name="last_class" value="<?= old('last_class') ?>" placeholder="e.g. Senior 6"></div>
      <div class="field"><label>Profession</label><input name="profession" value="<?= old('profession') ?>" placeholder="e.g. Nurse, Engineer, Teacher"></div>
      <div class="field"><label>Place of work (if any)</label><input name="workplace" value="<?= old('workplace') ?>" placeholder="Employer or organisation"></div>
      <div class="field"><label>Where do you live?</label><input name="location" value="<?= old('location') ?>"></div>
      <div class="field"><label<?= setting('alumni_require_photo') === '1' ? ' class="req"' : '' ?>>Your photo</label><input type="file" name="photo" accept="image/*"<?= setting('alumni_require_photo') === '1' ? ' required' : '' ?>><small>A clear passport-style picture (JPG, PNG or WebP, max 5MB).</small></div>
    </div>
    <?php if (setting('alumni_payment_info') !== ''): ?><div class="notice" style="margin-bottom:1rem"><strong>Membership &amp; payment</strong><br><?= nl2br(e(setting('alumni_payment_info'))) ?></div><?php endif; ?>
    <div class="field"><label<?= setting('alumni_require_proof') === '1' ? ' class="req"' : '' ?>>Proof of payment or membership</label><input type="file" name="proof_file" accept=".pdf,.jpg,.jpeg,.png"<?= setting('alumni_require_proof') === '1' ? ' required' : '' ?>><small>Receipt, mobile-money confirmation or membership card (PDF, JPG or PNG, max 5MB). You must re-attach your files if the form shows an error.</small></div>
    <div class="field"><label>Message (optional)</label><textarea name="message" rows="3"><?= old('message') ?></textarea></div>
    <label style="display:flex;gap:.6rem;align-items:flex-start;margin-bottom:.8rem"><input type="checkbox" name="consent" value="1" required <?= isset($_POST['consent']) ? 'checked' : '' ?>> <span>I have read the AFSSOSA constitution<?php if (setting('alumni_constitution')): ?> (<a href="<?= e(media(setting('alumni_constitution'))) ?>" target="_blank" rel="noopener">read it here</a>)<?php endif; ?> and I am ready to abide by it. *</span></label>
    <label style="display:flex;gap:.6rem;align-items:flex-start;margin-bottom:1.2rem"><input type="checkbox" name="newsletter" value="1" <?= isset($_POST['newsletter']) || !is_post() ? 'checked' : '' ?>> Send me the alumni newsletter and event updates by email.</label>
    <button class="btn btn-primary" type="submit">Join the network</button>
    <p class="meta" style="margin-top:1rem">Questions? <?= e($contactPhone) ?> · <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></p>
  </form>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
