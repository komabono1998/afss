<?php
require_once __DIR__ . '/includes/bootstrap.php';
$groups = [
    'board' => ['Board of Governors', 'The Board provides policy direction, oversight and accountability for the school.'],
    'team' => ['School Management Team', 'The team that leads the day-to-day academic and administrative life of the school.', 'administration'],
    'pta' => ['PTA Committee', 'Parents and teachers working together for the wellbeing and progress of every learner.'],
];
$g = $_GET['g'] ?? 'board';
if (!isset($groups[$g])) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
[$title, $desc] = $groups[$g];
$dbGroup = $groups[$g][2] ?? $g;
$members = rows('SELECT * FROM team WHERE grp = ? ORDER BY sort, id', [$dbGroup]);
$pageTitle = $title;
$bannerTitle = $title;
$bannerSub = $desc;
$crumbs = [['Management', url('governance.php')]];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container">
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:center;margin-bottom:2.5rem">
    <?php foreach ($groups as $k => $gr): ?><a class="btn btn-sm <?= $k === $g ? 'btn-primary' : 'btn-ghost' ?>" href="management.php?g=<?= $k ?>"><?= e($gr[0]) ?></a><?php endforeach; ?>
  </div>
  <div class="grid g4" style="justify-content:center">
    <?php foreach ($members as $m):
        $initials = implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($m['name'])), 0, 2))); ?>
    <a class="card person reveal" href="<?= url('profile.php?id=' . $m['id']) ?>">
      <div class="avatar"><?php if ($m['photo']): ?><img src="<?= e(media($m['photo'])) ?>" alt="<?= e($m['name']) ?>"><?php else: ?><?= e($initials) ?><?php endif; ?></div>
      <h3><?= e($m['name']) ?></h3><div class="role"><?= e($m['role']) ?></div>
      <span class="more" style="display:block;margin-top:.6rem">View profile →</span>
    </a>
    <?php endforeach; if (!$members): ?><p style="text-align:center">Profiles will be added soon.</p><?php endif; ?>
  </div>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
