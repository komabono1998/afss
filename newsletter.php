<?php
require_once __DIR__ . '/includes/bootstrap.php';
$visible = "status = 'published' AND issue_date <= date('now')";
$preview = !empty($_GET['preview']) && admin_user();
$is = row('SELECT * FROM newsletter_issues WHERE slug = ?' . ($preview ? '' : " AND $visible"), [$_GET['s'] ?? '']);
if (!$is) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
$sections = rows('SELECT * FROM newsletter_sections WHERE issue_id = ? ORDER BY sort, id', [$is['id']]);
$aud = $is['audience'] ?? 'school';
$prev = row("SELECT title,slug FROM newsletter_issues WHERE $visible AND audience = '$aud' AND (issue_date < ? OR (issue_date = ? AND id < ?)) ORDER BY issue_date DESC, id DESC LIMIT 1", [$is['issue_date'], $is['issue_date'], $is['id']]);
$next = row("SELECT title,slug FROM newsletter_issues WHERE $visible AND audience = '$aud' AND (issue_date > ? OR (issue_date = ? AND id > ?)) ORDER BY issue_date, id LIMIT 1", [$is['issue_date'], $is['issue_date'], $is['id']]);

$pageTitle = $is['title'];
$pageDesc = $is['summary'] ?: excerpt((string) $is['intro']);
$pageImage = $is['cover'];
$bannerTitle = $is['title'];
$bannerSub = date('F Y', strtotime($is['issue_date'])) . ($is['edition'] ? ' · ' . $is['edition'] : '');
$crumbs = ($is['audience'] ?? 'school') === 'alumni' ? [['Alumni', url('alumni.php')]] : [['News', url('news.php')], ['Monthly Newsletter', url('newsletters.php')]];
include __DIR__ . '/includes/header.php';
if ($preview && $is['status'] !== 'published') echo '<div class="container"><div class="notice" style="margin-top:1rem">Preview — this issue is a draft and not visible to the public.</div></div>';
?>
<article class="container nl-wrap">
  <header class="nl-head">
    <div class="issue"><?= e(setting('school_name')) ?> · <?= e(date('F Y', strtotime($is['issue_date']))) ?><?= $is['edition'] ? ' · ' . e($is['edition']) : '' ?></div>
    <h1 style="margin-top:.4rem"><?= e($is['title']) ?></h1>
    <div class="nl-actions no-print">
      <?php if ($is['pdf']): ?><a class="btn btn-accent btn-sm" href="<?= e(media($is['pdf'])) ?>" target="_blank" rel="noopener">⬇ Download PDF</a><?php endif; ?>
      <button class="btn btn-ghost btn-sm" onclick="window.print()" type="button">Print</button>
    </div>
  </header>
  <?php if ($is['cover']): ?><img class="nl-cover" src="<?= e(media($is['cover'])) ?>" alt="<?= e($is['title']) ?>"><?php endif; ?>
  <?php if (trim((string) $is['intro']) !== ''): ?><div class="nl-intro prose wide"><?= $is['intro'] ?></div><?php endif; ?>

  <?php foreach ($sections as $s): $layout = in_array($s['layout'], ['image-left', 'image-right', 'full-width', 'text-only'], true) ? $s['layout'] : 'image-right';
      $hasImg = $s['image'] && $layout !== 'text-only'; ?>
  <section class="nl-sec <?= $hasImg ? e($layout) : 'text-only' ?>">
    <div class="prose wide"><?php if ($s['heading']): ?><h2><?= e($s['heading']) ?></h2><?php endif; ?><?= $s['body'] ?></div>
    <?php if ($hasImg): ?><figure class="nl-img"><img src="<?= e(media($s['image'])) ?>" alt="<?= e($s['caption'] ?: $s['heading']) ?>" loading="lazy"><?php if ($s['caption']): ?><figcaption><?= e($s['caption']) ?></figcaption><?php endif; ?></figure><?php endif; ?>
  </section>
  <?php endforeach; ?>
  <?php if (!$sections && !$is['pdf'] && !trim((string) $is['intro'])): ?><p>This issue has no content yet.</p><?php endif; ?>

  <nav class="no-print" style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;border-top:1px solid var(--line);padding-top:1.5rem;margin-top:2rem">
    <span><?php if ($prev): ?><a href="newsletter.php?s=<?= e($prev['slug']) ?>">← <?= e($prev['title']) ?></a><?php endif; ?></span>
    <a href="<?= $aud === 'alumni' ? 'alumni.php#newsletter' : 'newsletters.php' ?>">All issues</a>
    <span><?php if ($next): ?><a href="newsletter.php?s=<?= e($next['slug']) ?>"><?= e($next['title']) ?> →</a><?php endif; ?></span>
  </nav>
</article>
<section class="cta-band no-print"><div class="container"><h2>Never miss an issue</h2>
  <form class="subscribe" method="post" action="<?= url('subscribe.php') ?>" style="max-width:460px;margin:1rem auto 0;flex-direction:row"><?= csrf_field() ?>
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
    <input type="email" name="email" placeholder="Your email address" required><button class="btn btn-accent">Subscribe</button></form></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
