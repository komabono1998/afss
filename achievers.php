<?php
require_once __DIR__ . '/includes/bootstrap.php';

$all = rows("SELECT * FROM achievers WHERE active = 1 AND month <= date('now','start of month','+1 month','-1 day') ORDER BY month DESC, kind, sort, id");
$latest = $all ? substr($all[0]['month'], 0, 7) : '';
$feat = ['staff' => [], 'student' => []];
$archive = [];
foreach ($all as $a) {
    $ym = substr($a['month'], 0, 7);
    if ($ym === $latest) $feat[$a['kind']][] = $a; else $archive[$ym][] = $a;
}
$year = preg_match('/^\d{4}$/', $_GET['y'] ?? '') ? $_GET['y'] : '';
$years = array_values(array_unique(array_map(fn($a) => substr($a['month'], 0, 4), $all)));

function achiever_card(array $a, bool $big = false): string
{
    $ini = implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($a['name'])), 0, 2)));
    $img = $a['photo'] ? '<img src="' . e(media($a['photo'])) . '" alt="' . e($a['name']) . '" loading="lazy">' : '<span class="ach-ini">' . e($ini) . '</span>';
    return '<article class="ach-card' . ($big ? ' ach-big' : '') . ' ach-' . e($a['kind']) . ' reveal"><div class="ach-pic">' . $img . '<span class="ach-badge">' . ($a['kind'] === 'staff' ? '★ Staff of the Month' : '★ Student of the Month') . '</span></div>'
        . '<div class="ach-body"><span class="ach-month">' . e(date('F Y', strtotime($a['month']))) . '</span><h3>' . e($a['name']) . '</h3>'
        . ($a['role_class'] ? '<div class="ach-role">' . e($a['role_class']) . '</div>' : '')
        . ($a['achievement'] ? '<p>' . nl2br(e($a['achievement'])) . '</p>' : '') . '</div></article>';
}

$pageTitle = 'Achievers Corner';
$pageDesc = 'Celebrating the staff and students of the month at Archbishop Flynn Secondary School.';
$bannerTitle = 'Achievers Corner';
$bannerSub = 'Celebrating the staff and students who make AFSS shine.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container">
  <?php if ($all): ?>
  <div class="section-head"><span class="eyebrow">This month</span><h2><?= e(date('F Y', strtotime($latest . '-01'))) ?> achievers</h2><p>Recognising excellence, service and good character in our school community.</p></div>
  <div class="ach-feature">
    <?php foreach (['staff', 'student'] as $k): ?>
    <div class="ach-col">
      <?php if ($feat[$k]): foreach ($feat[$k] as $a) echo achiever_card($a, true); else: ?>
      <article class="ach-card ach-big ach-<?= $k ?> ach-empty"><div class="ach-pic"><span class="ach-ini">★</span></div><div class="ach-body"><h3><?= $k === 'staff' ? 'Staff of the Month' : 'Student of the Month' ?></h3><p>To be announced soon.</p></div></article>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="section-head"><span class="eyebrow">Coming soon</span><h2>Staff and students of the month</h2><p>Our first achievers will be announced here. Check back soon.</p></div>
  <div class="ach-feature">
    <article class="ach-card ach-big ach-staff ach-empty"><div class="ach-pic"><span class="ach-ini">★</span></div><div class="ach-body"><h3>Staff of the Month</h3><p>To be announced soon.</p></div></article>
    <article class="ach-card ach-big ach-student ach-empty"><div class="ach-pic"><span class="ach-ini">★</span></div><div class="ach-body"><h3>Student of the Month</h3><p>To be announced soon.</p></div></article>
  </div>
  <?php endif; ?>
</div></section>

<?php if ($archive): ?>
<section class="section alt"><div class="container">
  <div class="section-head"><span class="eyebrow">Hall of fame</span><h2>Previous achievers</h2></div>
  <?php if (count($years) > 1): ?><div class="ach-years"><a class="<?= $year === '' ? 'on' : '' ?>" href="achievers.php">All</a><?php foreach ($years as $y): ?><a class="<?= $year === $y ? 'on' : '' ?>" href="achievers.php?y=<?= e($y) ?>"><?= e($y) ?></a><?php endforeach; ?></div><?php endif; ?>
  <?php foreach ($archive as $ym => $list): if ($year !== '' && substr($ym, 0, 4) !== $year) continue; ?>
    <h3 class="ach-mh"><?= e(date('F Y', strtotime($ym . '-01'))) ?></h3>
    <div class="ach-grid"><?php foreach ($list as $a) echo achiever_card($a); ?></div>
  <?php endforeach; ?>
</div></section>
<?php endif; ?>

<section class="cta-band"><div class="container">
  <h2>Know someone who deserves recognition?</h2>
  <p>Teachers, parents and learners can recommend a staff member or student through the school office.</p>
  <a class="btn btn-accent" href="<?= url('contact.php') ?>">Send a nomination</a>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
