<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Governance';
$bannerTitle = 'Governance & Leadership';
$bannerSub = 'How Archbishop Flynn Secondary School is governed and led.';
$crumbs = [['About', url('page.php?slug=about-us')]];
$groups = [
    'board' => ['Board of Governors', 'The Board provides policy direction, oversight and accountability for the school.'],
    'director' => ['Director', 'Leads the overall strategic direction and stewardship of the institution.'],
    'administration' => ['School Administration Team', 'Manages the day-to-day academic and administrative life of the school.'],
];
function person_card(array $m): string
{
    $initials = implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($m['name'])), 0, 2)));
    $h = '<div class="card person reveal"><div class="avatar">' . ($m['photo'] ? '<img src="' . e(media($m['photo'])) . '" alt="' . e($m['name']) . '">' : e($initials)) . '</div>';
    $h .= '<h3>' . e($m['name']) . '</h3><div class="role">' . e($m['role']) . '</div>';
    if ($m['bio']) $h .= '<p style="margin-top:.6rem">' . e($m['bio']) . '</p>';
    return $h . '</div>';
}
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section">
  <div class="container">
    <div class="section-head"><span class="eyebrow">Structure</span><h2>Governance structure</h2>
      <p>Accountability flows from the Board of Governors, through the Director, to the School Administration Team.</p></div>
    <div class="org" aria-label="Governance structure chart">
      <div class="org-level"><?php foreach ($groups as $k => [$label]): ?>
        <?php if ($k !== 'board') echo '</div><div class="org-line"></div><div class="org-level">'; ?>
        <div class="btn btn-primary" style="cursor:default"><?= e($label) ?></div>
      <?php endforeach; ?></div>
    </div>
  </div>
</section>
<?php foreach ($groups as $k => [$label, $desc]):
    $members = rows('SELECT * FROM team WHERE grp = ? ORDER BY sort, id', [$k]);
    if (!$members) continue; ?>
<section class="section <?= $k === 'director' ? 'alt' : '' ?>" id="<?= e($k) ?>">
  <div class="container">
    <div class="section-head"><h2><?= e($label) ?></h2><p><?= e($desc) ?></p></div>
    <div class="grid g4" style="justify-content:center"><?php foreach ($members as $m) echo person_card($m); ?></div>
  </div>
</section>
<?php endforeach; ?>
<section class="section alt"><div class="container" style="text-align:center">
  <h3>Messages from our leaders</h3>
  <a class="btn btn-ghost" href="<?= url('page.php?slug=administrators-message') ?>">Administrator's Message</a>
  <a class="btn btn-ghost" href="<?= url('page.php?slug=headteachers-message') ?>">Head Teacher's Message</a>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
