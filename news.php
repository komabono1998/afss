<?php
require_once __DIR__ . '/includes/bootstrap.php';
$visible = "status='published' AND published_at <= date('now')";

if (!empty($_GET['s'])) {
    $n = row("SELECT * FROM news WHERE slug = ? AND $visible", [$_GET['s']]);
    if (!$n) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
    $pageTitle = $n['title'];
    $pageDesc = $n['excerpt'] ?: excerpt($n['body']);
    $pageImage = $n['image'];
    $bannerTitle = $n['title'];
    $crumbs = [['News & Stories', url('news.php')]];
    $related = rows("SELECT title,slug,published_at FROM news WHERE $visible AND id != ? ORDER BY published_at DESC LIMIT 5", [$n['id']]);
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/banner.php';
    $shareUrl = urlencode((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    ?>
    <div class="container content-wrap">
      <article class="prose">
        <p class="meta"><span class="tag"><?= e($n['category']) ?></span> · <?= fmt_date($n['published_at'], 'j F Y') ?></p>
        <?php if ($n['image']): ?><img src="<?= e(media($n['image'])) ?>" alt="<?= e($n['title']) ?>"><?php endif; ?>
        <?= $n['body'] ?>
        <p class="no-print"><strong>Share:</strong>
          <a href="https://wa.me/?text=<?= $shareUrl ?>" target="_blank" rel="noopener">WhatsApp</a> ·
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener">Facebook</a> ·
          <a href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>" target="_blank" rel="noopener">X</a></p>
        <p><a href="<?= url('news.php') ?>">← Back to all news</a></p>
      </article>
      <aside class="sidebar">
        <div class="box"><h4>More stories</h4><ul><?php foreach ($related as $r): ?>
          <li><a href="<?= url('news.php?s=' . $r['slug']) ?>"><?= e($r['title']) ?></a><br><span class="meta"><?= fmt_date($r['published_at']) ?></span></li>
        <?php endforeach; ?></ul></div>
        <div class="box cta"><h4>Newsletter</h4><p>Get stories like this by email.</p>
          <form class="subscribe" method="post" action="<?= url('subscribe.php') ?>"><?= csrf_field() ?>
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
            <input type="email" name="email" placeholder="Email address" required><button class="btn btn-accent btn-sm">Subscribe</button></form></div>
      </aside>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

$per = 9;
$cat = trim($_GET['cat'] ?? '');
$search = trim($_GET['q'] ?? '');
$where = $visible;
$params = [];
if ($cat !== '') { $where .= ' AND category = ?'; $params[] = $cat; }
if ($search !== '') { $where .= ' AND (title LIKE ? OR body LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$total = (int) val("SELECT COUNT(*) FROM news WHERE $where", $params);
$pages = max(1, (int) ceil($total / $per));
$pg = min($pages, max(1, (int) ($_GET['p'] ?? 1)));
$items = rows("SELECT * FROM news WHERE $where ORDER BY published_at DESC, id DESC LIMIT $per OFFSET " . (($pg - 1) * $per), $params);
$cats = rows("SELECT DISTINCT category FROM news WHERE $visible ORDER BY category");

$pageTitle = 'News & Stories';
$bannerTitle = 'News & Stories';
$bannerSub = 'Updates, achievements and stories from the AFSS community.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
$qs = fn(array $o) => '?' . http_build_query(array_filter(array_merge(['cat' => $cat, 'q' => $search], $o)));
?>
<section class="section">
  <div class="container">
    <form method="get" class="no-print" style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:2rem;align-items:center">
      <a class="btn btn-sm <?= $cat === '' ? 'btn-primary' : 'btn-ghost' ?>" href="news.php">All</a>
      <?php foreach ($cats as $c): ?><a class="btn btn-sm <?= $cat === $c['category'] ? 'btn-primary' : 'btn-ghost' ?>" href="news.php?cat=<?= urlencode($c['category']) ?>"><?= e($c['category']) ?></a><?php endforeach; ?>
      <span style="flex:1"></span>
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search news…" style="padding:.55rem .9rem;border:1px solid #cdd5de;border-radius:999px">
    </form>
    <div class="grid g3">
      <?php foreach ($items as $n): ?>
      <a class="card reveal" href="<?= url('news.php?s=' . $n['slug']) ?>">
        <div class="thumb"><?php if ($n['image']): ?><img src="<?= e(media($n['image'])) ?>" alt="" loading="lazy"><?php else: ?>AFSS<?php endif; ?></div>
        <div class="card-body"><span class="tag"><?= e($n['category']) ?></span><h3><?= e($n['title']) ?></h3><p><?= e($n['excerpt'] ?: excerpt($n['body'])) ?></p><span class="meta"><?= fmt_date($n['published_at']) ?></span></div>
      </a>
      <?php endforeach; if (!$items): ?><p>No stories found.</p><?php endif; ?>
    </div>
    <?php if ($pages > 1): ?><div class="pager"><?php for ($i = 1; $i <= $pages; $i++): ?>
      <?= $i === $pg ? "<span class='cur'>$i</span>" : '<a href="' . e($qs(['p' => $i])) . "\">$i</a>" ?>
    <?php endfor; ?></div><?php endif; ?>
  </div>
</section>
<section class="cta-band"><div class="container">
  <h2>Subscribe to our newsletter</h2><p>Be the first to read news, events and stories from AFSS.</p>
  <form class="subscribe" method="post" action="<?= url('subscribe.php') ?>" style="max-width:460px;margin:0 auto;flex-direction:row"><?= csrf_field() ?>
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
    <input type="email" name="email" placeholder="Your email address" required><button class="btn btn-accent">Subscribe</button></form>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
