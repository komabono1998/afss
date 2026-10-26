<?php
require_once __DIR__ . '/includes/bootstrap.php';
$issues = rows("SELECT * FROM newsletter_issues WHERE status = 'published' AND issue_date <= date('now') ORDER BY issue_date DESC, id DESC");
$byYear = [];
foreach ($issues as $i) $byYear[date('Y', strtotime($i['issue_date']))][] = $i;

$pageTitle = 'Monthly Newsletter';
$pageDesc = 'Read the AFSS monthly newsletter: stories, achievements and photos from school life.';
$bannerTitle = 'Monthly Newsletter';
$bannerSub = 'Stories, achievements and pictures from life at AFSS, every month.';
$crumbs = [['News', url('news.php')]];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container">
  <?php if ($issues): $latest = $issues[0]; ?>
  <div class="split" style="margin-bottom:3rem">
    <a class="about-img" href="<?= url('newsletter.php?s=' . $latest['slug']) ?>" style="min-height:320px;display:block">
      <?php if ($latest['cover']): ?><img src="<?= e(media($latest['cover'])) ?>" alt="<?= e($latest['title']) ?>"><?php endif; ?></a>
    <div><span class="eyebrow">Latest issue · <?= e(date('F Y', strtotime($latest['issue_date']))) ?></span>
      <h2><?= e($latest['title']) ?></h2><p><?= e($latest['summary'] ?: excerpt($latest['intro'] ?? '', 220)) ?></p>
      <a class="btn btn-primary" href="<?= url('newsletter.php?s=' . $latest['slug']) ?>">Read online</a>
      <?php if ($latest['pdf']): ?><a class="btn btn-ghost" href="<?= e(media($latest['pdf'])) ?>" target="_blank" rel="noopener">Download PDF</a><?php endif; ?></div>
  </div>
  <?php foreach ($byYear as $y => $list): ?>
    <h2 class="nl-year"><?= e($y) ?></h2>
    <div class="grid g4">
      <?php foreach ($list as $i): ?>
      <a class="card nl-card reveal" href="<?= url('newsletter.php?s=' . $i['slug']) ?>">
        <div class="thumb"><?php if ($i['cover']): ?><img src="<?= e(media($i['cover'])) ?>" alt="" loading="lazy"><?php else: ?><span><?= e(date('F', strtotime($i['issue_date']))) ?></span><small><?= e($y) ?></small><?php endif; ?></div>
        <div class="card-body"><h3 style="font-size:1.05rem"><?= e($i['title']) ?></h3>
          <span class="meta"><?= e(date('F Y', strtotime($i['issue_date']))) ?><?= $i['edition'] ? ' · ' . e($i['edition']) : '' ?></span></div>
      </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; else: ?>
    <p style="text-align:center">The first issue will be published soon. Subscribe below to be notified.</p>
  <?php endif; ?>
</div></section>
<section class="cta-band"><div class="container">
  <h2>Get every issue in your inbox</h2><p>Subscribe and we will email you when a new monthly newsletter is published.</p>
  <form class="subscribe" method="post" action="<?= url('subscribe.php') ?>" style="max-width:460px;margin:0 auto;flex-direction:row"><?= csrf_field() ?>
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
    <input type="email" name="email" placeholder="Your email address" required><button class="btn btn-accent">Subscribe</button></form>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
