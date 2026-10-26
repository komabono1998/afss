<?php
require_once __DIR__ . '/includes/bootstrap.php';
$m = row('SELECT * FROM team WHERE id = ?', [(int) ($_GET['id'] ?? 0)]);
if (!$m) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
$map = ['board' => ['Board of Governors', 'board'], 'administration' => ['School Management Team', 'team'], 'pta' => ['PTA Committee', 'pta'], 'director' => ['Director', 'board'], 'alumni_patron' => ['Alumni Leadership', 'alumni'], 'alumni_exec' => ['Alumni Leadership', 'alumni']];
[$gl, $gk] = $map[$m['grp']] ?? ['Management', 'board'];
$pageTitle = $m['name'];
$bannerTitle = $m['name'];
$bannerSub = $m['role'];
$backUrl = $gk === 'alumni' ? url('alumni.php#leadership') : url('management.php?g=' . $gk);
$crumbs = [[$gk === 'alumni' ? 'Alumni' : 'Management', $gk === 'alumni' ? url('alumni.php') : url('governance.php')], [$gl, $backUrl]];
$initials = implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($m['name'])), 0, 2)));
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container split" style="grid-template-columns:300px 1fr;align-items:start">
  <div class="card person"><div class="avatar" style="width:180px;height:180px;font-size:3rem"><?php if ($m['photo']): ?><img src="<?= e(media($m['photo'])) ?>" alt="<?= e($m['name']) ?>"><?php else: ?><?= e($initials) ?><?php endif; ?></div>
    <h3><?= e($m['name']) ?></h3><div class="role"><?= e($m['role']) ?></div>
    <?php if ($m['term']): ?><p class="meta" style="margin-top:.5rem">Term: <?= e($m['term']) ?></p><?php endif; ?>
    <?php if ($m['email']): ?><p><a href="mailto:<?= e($m['email']) ?>">✉ <?= e($m['email']) ?></a></p><?php endif; ?>
    <?php if ($m['phone']): ?><p>☎ <?= e($m['phone']) ?></p><?php endif; ?></div>
  <div class="prose"><h2>About <?= e($m['name']) ?></h2>
    <?= $m['bio'] ? nl2br(e($m['bio'])) : '<p class="meta">Profile details will be added soon.</p>' ?>
    <p style="margin-top:2rem"><a href="<?= e($backUrl) ?>">← Back to <?= e($gl) ?></a></p></div>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
