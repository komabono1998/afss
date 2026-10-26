<?php
require_once __DIR__ . '/includes/bootstrap.php';
$cat = trim($_GET['cat'] ?? '');
$items = $cat !== '' ? rows('SELECT * FROM gallery WHERE category = ? ORDER BY sort, id DESC', [$cat]) : rows('SELECT * FROM gallery ORDER BY sort, id DESC');
$cats = rows('SELECT DISTINCT category FROM gallery ORDER BY category');
$pageTitle = 'Photo Gallery';
$bannerTitle = 'Photo Gallery';
$bannerSub = 'Life at Archbishop Flynn Secondary School.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container">
  <?php if ($cats): ?><div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:2rem">
    <a class="btn btn-sm <?= $cat === '' ? 'btn-primary' : 'btn-ghost' ?>" href="gallery.php">All</a>
    <?php foreach ($cats as $c): ?><a class="btn btn-sm <?= $cat === $c['category'] ? 'btn-primary' : 'btn-ghost' ?>" href="?cat=<?= urlencode($c['category']) ?>"><?= e($c['category']) ?></a><?php endforeach; ?></div><?php endif; ?>
  <div class="gal"><?php foreach ($items as $g): ?><a href="<?= e(media($g['image'])) ?>"><img src="<?= e(media($g['image'])) ?>" alt="<?= e($g['title']) ?>" loading="lazy"></a><?php endforeach; ?></div>
  <?php if (!$items): ?><p style="text-align:center">Photos will appear here soon.</p><?php endif; ?>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
