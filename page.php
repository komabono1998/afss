<?php
require_once __DIR__ . '/includes/bootstrap.php';
$p = row('SELECT * FROM pages WHERE slug = ? AND active = 1', [$_GET['slug'] ?? '']);
if (!$p) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
foreach (['clubs' => 'clubs.php', 'health-services' => 'health.php', 'counselling' => 'counselling.php'] as $sl => $dest) if ($p['slug'] === $sl) redirect($dest);
$pageTitle = $p['title'];
$pageDesc = $p['summary'] ?: excerpt($p['body']);
$pageImage = $p['image'];
$bannerTitle = $p['title'];
$bannerSub = $p['summary'];
$siblings = $p['menu_group'] !== 'none' ? rows('SELECT slug,title FROM pages WHERE menu_group = ? AND active = 1 ORDER BY sort,title', [$p['menu_group']]) : [];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<div class="container content-wrap">
  <article class="prose">
    <?php if ($p['image']): ?><img src="<?= e(media($p['image'])) ?>" alt="<?= e($p['title']) ?>"><?php endif; ?>
    <?= $p['body'] /* trusted admin-authored HTML */ ?>
  </article>
  <aside class="sidebar">
    <?php if (count($siblings) > 1): ?>
    <div class="box"><h4>In this section</h4><ul>
      <?php foreach ($siblings as $s): ?><li><a href="<?= url('page.php?slug=' . $s['slug']) ?>"<?= $s['slug'] === $p['slug'] ? ' style="font-weight:700"' : '' ?>><?= e($s['title']) ?></a></li><?php endforeach; ?>
    </ul></div>
    <?php endif; ?>
    <div class="box cta"><h4>Join AFSS</h4><p><?= e(setting('intake_text')) ?></p><a class="btn btn-accent btn-sm" href="<?= url('apply.php') ?>">Apply Now</a></div>
    <div class="box"><h4>Questions?</h4><p><?= e(setting('phone')) ?><br><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p></div>
  </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
