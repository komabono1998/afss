<?php
require_once __DIR__ . '/includes/bootstrap.php';

// ---- single album ----
if (!empty($_GET['a'])) {
    $al = row('SELECT * FROM albums WHERE slug = ? AND active = 1', [$_GET['a']]);
    if (!$al) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
    $photos = rows('SELECT * FROM gallery WHERE album_id = ? ORDER BY sort, id DESC', [$al['id']]);
    $pageTitle = $al['title'];
    $pageDesc = $al['description'] ?: $al['title'];
    $pageImage = $al['cover'] ?: ($photos[0]['image'] ?? '');
    $bannerTitle = $al['title'];
    $bannerSub = trim($al['category'] . ($al['event_date'] ? ' · ' . fmt_date($al['event_date'], 'j F Y') : '') . ($al['term'] ? ' · ' . $al['term'] : ''), ' ·');
    $crumbs = [['Photo Gallery', url('gallery.php')]];
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/banner.php';
    ?>
    <section class="section"><div class="container">
      <?php if ($al['description']): ?><p class="prose" style="margin-bottom:2rem"><?= nl2br(e($al['description'])) ?></p><?php endif; ?>
      <div class="gal"><?php foreach ($photos as $g): ?><a href="<?= e(media($g['image'])) ?>"><img src="<?= e(media($g['image'])) ?>" alt="<?= e($g['title'] ?: $al['title']) ?>" loading="lazy"></a><?php endforeach; ?></div>
      <?php if (!$photos): ?><p style="text-align:center">Photos will be added soon.</p><?php endif; ?>
      <p style="margin-top:2rem"><a href="<?= url('gallery.php') ?>">← All albums</a></p>
    </div></section>
    <?php include __DIR__ . '/includes/footer.php'; exit;
}

// ---- album index with year + event filters ----
$year = $_GET['year'] ?? '';
$cat = trim($_GET['cat'] ?? '');
$base = 'FROM albums a WHERE a.active = 1 AND EXISTS (SELECT 1 FROM gallery g WHERE g.album_id = a.id)';
$years = array_column(rows("SELECT DISTINCT strftime('%Y', event_date) y $base AND event_date IS NOT NULL ORDER BY y DESC"), 'y');
$cats = array_column(rows("SELECT DISTINCT category c $base ORDER BY c"), 'c');
$where = ''; $params = [];
if (preg_match('/^\d{4}$/', $year)) { $where .= " AND strftime('%Y', a.event_date) = ?"; $params[] = $year; }
if ($cat !== '') { $where .= ' AND a.category = ?'; $params[] = $cat; }
$albums = rows("SELECT a.*, (SELECT COUNT(*) FROM gallery g WHERE g.album_id = a.id) n,
    (SELECT image FROM gallery g WHERE g.album_id = a.id ORDER BY sort, id DESC LIMIT 1) first_img $base $where ORDER BY a.event_date DESC, a.id DESC", $params);

$pageTitle = 'Photo Gallery';
$bannerTitle = 'Photo Gallery';
$bannerSub = 'Sports days, MDD, visitation days and more — life at AFSS by event and year.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
$qs = fn(array $o) => 'gallery.php?' . http_build_query(array_filter(array_merge(['year' => $year, 'cat' => $cat], $o), fn($v) => $v !== ''));
?>
<section class="section"><div class="container">
  <div class="no-print" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;margin-bottom:1.2rem">
    <a class="btn btn-sm <?= $cat === '' ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e($qs(['cat' => ''])) ?>">All events</a>
    <?php foreach ($cats as $c): ?><a class="btn btn-sm <?= $cat === $c ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e($qs(['cat' => $c])) ?>"><?= e($c) ?></a><?php endforeach; ?>
  </div>
  <?php if ($years): ?><div class="no-print" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;margin-bottom:2rem"><strong style="font-size:.9rem">Year:</strong>
    <a class="btn btn-sm <?= $year === '' ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e($qs(['year' => ''])) ?>">All</a>
    <?php foreach ($years as $y): ?><a class="btn btn-sm <?= $year === $y ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e($qs(['year' => $y])) ?>"><?= e($y) ?></a><?php endforeach; ?></div><?php endif; ?>
  <div class="grid g3">
    <?php foreach ($albums as $al): $img = $al['cover'] ?: $al['first_img']; ?>
    <a class="card reveal" href="<?= url('gallery.php?a=' . $al['slug']) ?>">
      <div class="thumb"><?php if ($img): ?><img src="<?= e(media($img)) ?>" alt="" loading="lazy"><?php endif; ?></div>
      <div class="card-body"><span class="tag"><?= e($al['category']) ?></span><h3><?= e($al['title']) ?></h3>
        <span class="meta"><?= $al['event_date'] ? e(fmt_date($al['event_date'], 'j F Y')) : '' ?><?= $al['term'] ? ' · ' . e($al['term']) : '' ?> · <?= (int) $al['n'] ?> photo<?= $al['n'] == 1 ? '' : 's' ?></span></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php if (!$albums): ?><p style="text-align:center">No albums found. Photos will appear here soon.</p><?php endif; ?>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
