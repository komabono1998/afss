<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Admissions';
$bannerTitle = 'Admissions';
$bannerSub = 'Your journey to AFSS starts here.';
$tabs = rows("SELECT slug,title,body FROM pages WHERE menu_group='admissions' AND active=1 ORDER BY sort,title");
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section">
  <div class="container">
    <div class="section-head"><span class="eyebrow">How it works</span><h2>Four simple steps</h2>
      <p><?= e(setting('intake_text')) ?></p></div>
    <div class="steps">
      <?php foreach ([['Check requirements', 'Review the entry requirements for your class.'], ['Apply online', 'Complete the online application form.'], ['Submit documents', 'Provide results slips and a passport photo.'], ['Receive decision', 'We contact you with the outcome and reporting details.']] as [$t, $d]): ?>
      <div class="card step reveal"><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div>
      <?php endforeach; ?>
    </div>
    <p style="text-align:center;margin-top:2rem">
      <?php if (setting('admissions_open') === '1'): ?><a class="btn btn-accent" href="<?= url('apply.php') ?>">Start your application</a>
      <?php else: ?><span class="notice">Admissions are currently closed. Please check back soon or contact the school office.</span><?php endif; ?>
    </p>
  </div>
</section>
<?php if ($tabs): ?>
<section class="section alt"><div class="container" data-tabs>
  <div class="tabs"><?php foreach ($tabs as $t): ?><button type="button"><?= e($t['title']) ?></button><?php endforeach; ?></div>
  <?php foreach ($tabs as $t): ?><div class="tabpane prose wide"><?= $t['body'] ?></div><?php endforeach; ?>
</div></section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
