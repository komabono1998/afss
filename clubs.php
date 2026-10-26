<?php
require_once __DIR__ . '/includes/bootstrap.php';

// ---------- one club ----------
if (!empty($_GET['s'])) {
    $c = row('SELECT * FROM clubs WHERE slug = ? AND active = 1', [$_GET['s']]);
    if (!$c) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
    $others = rows('SELECT name,slug FROM clubs WHERE active = 1 AND id != ? ORDER BY sort, name', [$c['id']]);
    $acts = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $c['activities']))));
    $pageTitle = $c['name'];
    $pageDesc = $c['summary'] ?: excerpt((string) $c['description']);
    $pageImage = $c['image'];
    $bannerTitle = $c['name'];
    $bannerSub = $c['summary'];
    $crumbs = [['Student Life', url('page.php?slug=clubs')], ['Clubs & Societies', url('clubs.php')]];
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/banner.php';
    ?>
    <div class="container content-wrap">
      <article class="prose">
        <?php if ($c['image']): ?><img src="<?= e(media($c['image'])) ?>" alt="<?= e($c['name']) ?>"><?php endif; ?>
        <h2>What we do</h2><?= $c['description'] ?>
        <?php if ($acts): ?><h2>Main activities</h2><ul class="checks"><?php foreach ($acts as $a): ?><li><?= e($a) ?></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if ($c['how_to_join']): ?><h2>How to join</h2><p><?= nl2br(e($c['how_to_join'])) ?></p><?php endif; ?>
        <p><a href="<?= url('clubs.php') ?>">← All clubs and societies</a></p>
      </article>
      <aside class="sidebar">
        <div class="box"><h4>Club details</h4><ul>
          <?php if ($c['meeting_time']): ?><li><strong>When:</strong> <?= e($c['meeting_time']) ?></li><?php endif; ?>
          <?php if ($c['venue']): ?><li><strong>Where:</strong> <?= e($c['venue']) ?></li><?php endif; ?>
          <?php if ($c['patron']): ?><li><strong>Patron:</strong> <?= e($c['patron']) ?></li><?php endif; ?>
          <?php if ($c['leaders']): ?><li><strong>Student leaders:</strong> <?= e($c['leaders']) ?></li><?php endif; ?>
        </ul></div>
        <div class="box"><h4>Other clubs</h4><ul><?php foreach ($others as $o): ?><li><a href="clubs.php?s=<?= e($o['slug']) ?>"><?= e($o['name']) ?></a></li><?php endforeach; ?></ul></div>
      </aside>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; exit;
}

// ---------- all clubs ----------
$page = row("SELECT * FROM pages WHERE slug = 'clubs'");
$clubs = rows('SELECT * FROM clubs WHERE active = 1 ORDER BY sort, name');
$pageTitle = $page['title'] ?? 'School Clubs and Societies';
$pageDesc = setting('clubs_intro') ?: welfare_settings()['clubs_intro'];
$bannerTitle = $pageTitle;
$bannerSub = 'Find your passion, build friendships and learn to lead.';
$crumbs = [['Student Life', url('page.php?slug=clubs')]];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container">
  <div class="section-head"><span class="eyebrow">Beyond the classroom</span><h2>Clubs and societies</h2>
    <div class="prose wide" style="text-align:center"><?= $page['body'] ?? '<p>' . e($pageDesc) . '</p>' ?></div></div>
  <div class="grid g3">
    <?php foreach ($clubs as $c): ?>
    <a class="card reveal" href="clubs.php?s=<?= e($c['slug']) ?>">
      <div class="thumb"><?php if ($c['image']): ?><img src="<?= e(media($c['image'])) ?>" alt="" loading="lazy"><?php else: ?><span style="font-size:2.6rem"><?= e(mb_substr($c['name'], 0, 1)) ?></span><?php endif; ?></div>
      <div class="card-body"><h3><?= e($c['name']) ?></h3><p><?= e($c['summary']) ?></p>
        <?php if ($c['meeting_time']): ?><span class="meta">🕓 <?= e($c['meeting_time']) ?></span><br><?php endif; ?><span class="more">What we do →</span></div>
    </a>
    <?php endforeach; if (!$clubs): ?><p style="text-align:center;grid-column:1/-1">Clubs will be listed here soon.</p><?php endif; ?>
  </div>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
