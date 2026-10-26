<?php
require_once __DIR__ . '/includes/bootstrap.php';
$open = "active = 1";
$sch = !empty($_GET['s']) ? row("SELECT * FROM scholarships WHERE slug = ? AND $open", [$_GET['s']]) : null;
if (!empty($_GET['s']) && !$sch) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
$errors = [];

if ($sch && is_post()) {
    csrf_check();
    $deadlinePassed = $sch['deadline'] && $sch['deadline'] < date('Y-m-d');
    if (!$sch['accepting'] || $deadlinePassed) $errors[] = 'Applications for this award are closed.';
    if (!spam_guard('scholarship', 5)) $errors[] = 'Too many submissions. Please try again later.';
    foreach (['name' => 'Learner name', 'current_class' => 'Class', 'guardian_name' => 'Parent/guardian name', 'phone' => 'Phone', 'statement' => 'Your statement'] as $k => $l) if (post($k) === '') $errors[] = "$l is required.";
    if (post('email') !== '' && !filter_var(post('email'), FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is not valid.';
    $file = null;
    if (!$errors) {
        try { $file = handle_upload('supporting_file', ['pdf' => 'application/pdf'], PRIVATE_DIR, 'sch_'); }
        catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }
    if (!$errors) {
        q('INSERT INTO scholarship_apps (scholarship_id,scholarship_title,name,current_class,school,guardian_name,phone,email,statement,supporting_file) VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$sch['id'], $sch['title'], post('name'), post('current_class'), post('school'), post('guardian_name'), post('phone'), post('email'), post('statement'), $file]);
        notify('New application: ' . $sch['title'], post('name') . ' (' . post('current_class') . ') applied for ' . $sch['title'] . ".\nView it in the admin panel.", post('email'));
        flash('Thank you. Your application for the ' . $sch['title'] . ' has been received.');
        redirect('scholarships.php?s=' . $sch['slug']);
    }
}

if ($sch) {
    $closed = !$sch['accepting'] || ($sch['deadline'] && $sch['deadline'] < date('Y-m-d'));
    $pageTitle = $sch['title'];
    $pageDesc = $sch['summary'];
    $bannerTitle = $sch['title'];
    $bannerSub = $sch['summary'];
    $crumbs = [['Admissions', url('admissions.php')], ['Scholarships & Bursaries', url('scholarships.php')]];
    $others = rows("SELECT title,slug FROM scholarships WHERE $open AND id != ? ORDER BY sort,title", [$sch['id']]);
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/banner.php';
    ?>
    <div class="container content-wrap">
      <article class="prose">
        <p><span class="tag"><?= e($sch['type']) ?></span>
          <?php if ($sch['coverage']): ?> <strong>Covers:</strong> <?= e($sch['coverage']) ?><?php endif; ?>
          <?php if ($sch['sponsor']): ?> · <strong>Offered by:</strong> <?= e($sch['sponsor']) ?><?php endif; ?></p>
        <?php if ($sch['deadline']): ?><p class="notice">Application deadline: <strong><?= fmt_date($sch['deadline'], 'j F Y') ?></strong></p><?php endif; ?>
        <h2>Eligibility criteria</h2><?= $sch['eligibility'] ?>
        <h2>How to apply</h2><?= $sch['how_to_apply'] ?>
        <?php if ($sch['documents']): ?><h2>Documents required</h2><?= $sch['documents'] ?><?php endif; ?>
        <h2 id="apply">Apply online</h2>
        <?php if ($closed): ?><div class="notice">Applications for this award are currently closed.</div>
        <?php else: ?>
          <?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>
          <form class="form" method="post" enctype="multipart/form-data" action="#apply">
            <?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
            <div class="row">
              <div class="field"><label class="req">Learner's full name</label><input name="name" value="<?= old('name') ?>" required></div>
              <div class="field"><label class="req">Class</label><input name="current_class" value="<?= old('current_class') ?>" placeholder="e.g. Senior 3" required></div>
              <div class="field"><label>Current / previous school</label><input name="school" value="<?= old('school') ?>"></div>
              <div class="field"><label class="req">Parent / guardian name</label><input name="guardian_name" value="<?= old('guardian_name') ?>" required></div>
              <div class="field"><label class="req">Phone</label><input name="phone" value="<?= old('phone') ?>" required></div>
              <div class="field"><label>Email</label><input type="email" name="email" value="<?= old('email') ?>"></div>
            </div>
            <div class="field"><label class="req">Why should the learner receive this award?</label><textarea name="statement" rows="5" required><?= old('statement') ?></textarea></div>
            <div class="field"><label>Supporting documents (single PDF, max 5MB)</label><input type="file" name="supporting_file" accept=".pdf,application/pdf"><small>Combine the documents listed above into one PDF. You must re-attach it if the form shows an error.</small></div>
            <button class="btn btn-primary" type="submit">Submit application</button>
          </form>
        <?php endif; ?>
      </article>
      <aside class="sidebar">
        <div class="box"><h4>Other awards</h4><ul><?php foreach ($others as $o): ?><li><a href="?s=<?= e($o['slug']) ?>"><?= e($o['title']) ?></a></li><?php endforeach; ?><li><a href="scholarships.php">All scholarships &amp; bursaries</a></li></ul></div>
        <div class="box"><h4>Need help?</h4><p><?= e(setting('phone')) ?><br><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p></div>
      </aside>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; exit;
}

$items = rows("SELECT * FROM scholarships WHERE $open ORDER BY type, sort, title");
$pageTitle = 'Scholarships & Bursaries';
$bannerTitle = 'Scholarships & Bursaries';
$bannerSub = setting('scholarships_intro');
$crumbs = [['Admissions', url('admissions.php')]];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container">
  <div class="section-head"><span class="eyebrow">Financial support</span><h2>Available awards</h2></div>
  <div class="grid g3">
    <?php foreach ($items as $it):
        $closed = !$it['accepting'] || ($it['deadline'] && $it['deadline'] < date('Y-m-d')); ?>
    <a class="card reveal" href="?s=<?= e($it['slug']) ?>"><div class="card-body">
      <span class="tag"><?= e($it['type']) ?></span><h3><?= e($it['title']) ?></h3><p><?= e($it['summary']) ?></p>
      <?php if ($it['coverage']): ?><p><strong>Covers:</strong> <?= e($it['coverage']) ?></p><?php endif; ?>
      <span class="meta"><?= $closed ? 'Applications closed' : ($it['deadline'] ? 'Deadline ' . fmt_date($it['deadline']) : 'Open for applications') ?></span><br>
      <span class="more">Eligibility &amp; how to apply →</span></div></a>
    <?php endforeach; if (!$items): ?><p>Details will be published soon.</p><?php endif; ?>
  </div>
</div></section>
<section class="section alt"><div class="container prose wide" style="max-width:860px">
  <h2>How to apply</h2><?= setting('scholarships_apply') ?>
  <p><a class="btn btn-primary" href="<?= url('contact.php') ?>">Contact the school office</a></p>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
