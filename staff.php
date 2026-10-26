<?php
require_once __DIR__ . '/includes/bootstrap.php';

$groups = [
    'leadership' => ['School Leadership', 'The team that leads the school day to day.', ['director', 'administration']],
    'teaching' => ['Teaching Staff', 'Qualified teachers who guide our learners in every subject.', ['teaching']],
    'support' => ['Support Staff', 'The people who keep our school running, safe and welcoming.', ['support']],
];
$all = rows("SELECT * FROM team WHERE grp IN ('director','administration','teaching','support') ORDER BY CASE grp WHEN 'director' THEN 1 WHEN 'administration' THEN 2 WHEN 'teaching' THEN 3 ELSE 4 END, sort, name");
$all = array_values(array_filter($all, fn($m) => $m['name'] !== 'Name to be updated'));   // hide unfilled placeholders on the public list
$subjects = [];
foreach ($all as $m) if ($m['grp'] === 'teaching') foreach (array_filter(array_map('trim', explode(',', (string) $m['subjects']))) as $s) $subjects[mb_strtolower($s)] = $s;
asort($subjects);

function staff_card(array $m): string
{
    $ini = implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($m['name'])), 0, 2)));
    $subs = array_filter(array_map('trim', explode(',', (string) $m['subjects'])));
    $h = '<a class="card staff-card reveal" href="' . e(url('profile.php?id=' . $m['id'])) . '" data-search="' . e(mb_strtolower($m['name'] . ' ' . $m['role'] . ' ' . $m['subjects'])) . '" data-subjects="|' . e(mb_strtolower(implode('|', $subs))) . '|">';
    $h .= '<div class="avatar">' . ($m['photo'] ? '<img src="' . e(media($m['photo'])) . '" alt="' . e($m['name']) . '" loading="lazy">' : e($ini)) . '</div>';
    $h .= '<h3>' . e($m['name']) . '</h3><div class="role">' . e($m['role']) . '</div>';
    if ($subs) { $h .= '<div class="subjects">'; foreach ($subs as $s) $h .= '<span class="tag">' . e($s) . '</span>'; $h .= '</div>'; }
    if ($m['bio']) $h .= '<p class="bio">' . e(excerpt($m['bio'], 110)) . '</p>';
    return $h . '<span class="more">View profile →</span></a>';
}

$pageTitle = 'Our Staff';
$pageDesc = 'Meet the leadership, teachers and support staff of ' . setting('school_name') . '.';
$bannerTitle = 'Our Staff';
$bannerSub = 'The people who teach, lead and care for our learners.';
$crumbs = [['About', url('page.php?slug=about-us')]];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container">
  <?php if (!$all): ?><p style="text-align:center">Staff profiles will be added soon.</p><?php else: ?>
  <div class="staff-tools no-print">
    <input type="search" id="staffq" placeholder="Search by name, role or subject…" aria-label="Search staff">
    <?php if ($subjects): ?><div class="chips-row" id="subjchips"><button type="button" class="btn btn-sm btn-primary" data-s="">All subjects</button>
      <?php foreach ($subjects as $k => $s): ?><button type="button" class="btn btn-sm btn-ghost" data-s="<?= e($k) ?>"><?= e($s) ?></button><?php endforeach; ?></div><?php endif; ?>
  </div>
  <?php foreach ($groups as $gk => [$title, $desc, $keys]):
      $list = array_values(array_filter($all, fn($m) => in_array($m['grp'], $keys, true)));
      if (!$list) continue; ?>
  <div class="staff-group" data-group="<?= e($gk) ?>">
    <div class="section-head left" style="margin-bottom:1.5rem"><h2><?= e($title) ?></h2><p><?= e($desc) ?></p></div>
    <div class="grid g4" style="margin-bottom:3rem"><?php foreach ($list as $m) echo staff_card($m); ?></div>
  </div>
  <?php endforeach; ?>
  <p id="staffnone" style="display:none;text-align:center">No staff match your search.</p>
  <script>(function(){
    var q=document.getElementById('staffq'),cards=[].slice.call(document.querySelectorAll('.staff-card')),chips=document.querySelectorAll('#subjchips button'),none=document.getElementById('staffnone'),sub='';
    function run(){var t=q.value.trim().toLowerCase(),shown=0;
      cards.forEach(function(c){var ok=(!t||c.dataset.search.indexOf(t)>-1)&&(!sub||c.dataset.subjects.indexOf('|'+sub+'|')>-1);c.style.display=ok?'':'none';if(ok)shown++;});
      document.querySelectorAll('.staff-group').forEach(function(g){g.style.display=g.querySelector('.staff-card:not([style*="none"])')?'':'none';});
      none.style.display=shown?'none':'block';}
    q.addEventListener('input',run);
    chips.forEach(function(b){b.addEventListener('click',function(){sub=b.dataset.s;chips.forEach(function(x){x.className='btn btn-sm '+(x===b?'btn-primary':'btn-ghost');});run();});});
  })();</script>
  <?php endif; ?>
</div></section>
<section class="cta-band"><div class="container"><h2>Join our team</h2><p>We are always glad to hear from committed educators and support staff.</p>
  <a class="btn btn-accent" href="<?= url('careers.php') ?>">See vacancies</a></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
