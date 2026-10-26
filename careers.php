<?php
require_once __DIR__ . '/includes/bootstrap.php';
$openCond = "status='open' AND (deadline IS NULL OR deadline='' OR deadline >= date('now'))";
$job = !empty($_GET['j']) ? row("SELECT * FROM jobs WHERE slug = ? AND $openCond", [$_GET['j']]) : null;
$errors = [];

if ($job && is_post()) {
    csrf_check();
    if (!spam_guard('jobapp', 5)) $errors[] = 'Too many submissions. Please try again later.';
    $name = post('name'); $email = post('email'); $phone = post('phone');
    if ($name === '') $errors[] = 'Your name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($phone === '') $errors[] = 'Your phone number is required.';
    $cv = null;
    if (!$errors) {
        try {
            $cv = handle_upload('cv', DOC_TYPES, PRIVATE_DIR, 'cv_');
            if (!$cv) $errors[] = 'Please attach your CV (PDF or Word).';
        } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }
    if (!$errors) {
        q('INSERT INTO job_apps (job_id,job_title,name,email,phone,qualification,cover_letter,cv_file) VALUES (?,?,?,?,?,?,?,?)',
            [$job['id'], $job['title'], $name, $email, $phone, post('qualification'), post('cover_letter'), $cv]);
        notify('New job application: ' . $job['title'], "$name <$email> / $phone applied for {$job['title']}.\nView it in the admin panel.", $email);
        flash('Thank you, ' . $name . '. Your application has been received.');
        redirect('careers.php');
    }
}

if ($job) {
    $pageTitle = $job['title'];
    $bannerTitle = $job['title'];
    $bannerSub = trim($job['department'] . ' · ' . $job['type'] . ' · ' . $job['location'], ' ·');
    $crumbs = [['Careers', url('careers.php')]];
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/banner.php';
    ?>
    <div class="container content-wrap">
      <article class="prose">
        <?php if ($job['deadline']): ?><p class="notice">Application deadline: <strong><?= fmt_date($job['deadline'], 'j F Y') ?></strong></p><?php endif; ?>
        <h2>About the role</h2><?= $job['description'] ?>
        <?php if ($job['requirements']): ?><h2>Requirements</h2><?= $job['requirements'] ?><?php endif; ?>
        <h2 id="apply">Apply now</h2>
        <?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>
        <form class="form" method="post" enctype="multipart/form-data" action="#apply">
          <?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
          <div class="row">
            <div class="field"><label class="req">Full name</label><input name="name" value="<?= old('name') ?>" required></div>
            <div class="field"><label class="req">Email</label><input type="email" name="email" value="<?= old('email') ?>" required></div>
            <div class="field"><label class="req">Phone</label><input name="phone" value="<?= old('phone') ?>" required></div>
            <div class="field"><label>Highest qualification</label><input name="qualification" value="<?= old('qualification') ?>"></div>
          </div>
          <div class="field"><label>Cover letter</label><textarea name="cover_letter" rows="5"><?= old('cover_letter') ?></textarea></div>
          <div class="field"><label class="req">CV (PDF or Word, max 5MB)</label><input type="file" name="cv" accept=".pdf,.doc,.docx" required></div>
          <button class="btn btn-primary" type="submit">Submit application</button>
        </form>
      </article>
      <aside class="sidebar"><div class="box"><h4>Other vacancies</h4><ul>
        <?php foreach (rows("SELECT title,slug FROM jobs WHERE $openCond AND id != ?", [$job['id']]) as $o): ?><li><a href="?j=<?= e($o['slug']) ?>"><?= e($o['title']) ?></a></li><?php endforeach; ?>
        <li><a href="careers.php">All vacancies</a></li></ul></div></aside>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; exit;
}

$jobs = rows("SELECT * FROM jobs WHERE $openCond ORDER BY created_at DESC");
$pageTitle = 'Jobs & Careers';
$bannerTitle = 'Jobs & Careers';
$bannerSub = 'Join a team committed to wisdom and service.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container">
  <div class="split" style="margin-bottom:3rem">
    <div class="prose"><span class="eyebrow">Work with us</span><h2><?= e(setting('careers_heading')) ?></h2>
      <p><?= nl2br(e(setting('careers_intro'))) ?></p>
      <ul class="checks"><?php foreach (array_filter(array_map('trim', preg_split('/\R/', (string) setting('careers_benefits')))) as $b): ?><li><?= e($b) ?></li><?php endforeach; ?></ul></div>
    <div class="notice"><strong>How to apply:</strong> <?= e(setting('careers_how')) ?></div>
  </div>
  <h2>Open vacancies</h2>
  <div class="grid" style="gap:1rem">
    <?php foreach ($jobs as $j): ?>
    <a class="card" href="?j=<?= e($j['slug']) ?>"><div class="card-body" style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center">
      <div><span class="tag"><?= e($j['type']) ?></span><h3 style="margin:0"><?= e($j['title']) ?></h3>
        <span class="meta"><?= e(trim($j['department'] . ' · ' . $j['location'], ' ·')) ?><?= $j['deadline'] ? ' · Deadline ' . fmt_date($j['deadline']) : '' ?></span></div>
      <span class="btn btn-primary btn-sm">View &amp; apply</span></div></a>
    <?php endforeach; if (!$jobs): ?><p>There are no open vacancies right now. Please check again soon.</p><?php endif; ?>
  </div>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
