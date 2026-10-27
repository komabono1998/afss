<?php
require_once __DIR__ . '/includes/bootstrap.php';

$res = rows('SELECT * FROM elib_resources WHERE active = 1 ORDER BY featured DESC, sort, title');
$live = array_values(array_filter(rows('SELECT * FROM live_sessions WHERE active = 1 AND session_date >= date(\'now\', \'-1 day\') ORDER BY session_date, start_time LIMIT 30'), fn($s) => live_state($s) !== 'ended'));
$live = array_slice($live, 0, 3);

$pageTitle = 'e-Library';
$pageDesc = 'Free O and A level learning materials online: official curriculum resources, past papers, textbooks, notes, videos and simulations, plus live online lessons.';
$bannerTitle = 'e-Library';
$bannerSub = 'Free O and A level learning materials, in one place, open to everyone.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';

$kindIcon = ['Official curriculum' => '🏛', 'Past papers & exams' => '📝', 'Textbooks' => '📚', 'Notes & revision' => '🗒', 'Videos & lessons' => '▶️', 'Simulations & tools' => '🧪', 'Free courses' => '🎓', 'Reading & books' => '📖'];
$levels = array_values(array_unique(array_column($res, 'level')));
$subjects = array_values(array_unique(array_column($res, 'subject')));
$kinds = array_values(array_unique(array_column($res, 'kind')));
?>
<section class="section el-tools"><div class="container">
  <div class="el-intro">
    <p>Everything here is <strong>free to use</strong>. Choose your level, pick a subject, and open the resource. The materials are published by their own organisations and open on their own websites, so please check that they match your Ugandan syllabus, and ask your teacher if you are unsure.</p>
  </div>
  <div class="el-filters">
    <label class="sr-only" for="elq">Search resources</label>
    <input id="elq" type="search" placeholder="Search resources: physics, past papers, graphs…" autocomplete="off">
    <select id="el-level" aria-label="Level"><option value="">All levels</option><?php foreach ($levels as $l): ?><option><?= e($l) ?></option><?php endforeach; ?></select>
    <select id="el-subject" aria-label="Subject"><option value="">All subjects</option><?php foreach ($subjects as $s): ?><option><?= e($s) ?></option><?php endforeach; ?></select>
    <select id="el-kind" aria-label="Type of material"><option value="">All types</option><?php foreach ($kinds as $k): ?><option><?= e($k) ?></option><?php endforeach; ?></select>
  </div>
  <p id="el-count" class="el-count" role="status" aria-live="polite"></p>
</div></section>

<?php if ($live): ?>
<section class="el-live"><div class="container">
  <div class="el-live-head"><h2>🔴 Live online lessons</h2><a href="<?= url('live.php') ?>">See all lessons →</a></div>
  <div class="el-live-grid">
    <?php foreach ($live as $s): $st = live_state($s); [$t0] = live_times($s); ?>
    <a class="el-l el-l-<?= $st ?>" href="<?= url('live.php') ?>"><span class="el-l-st"><?= e(live_state_label($st)) ?></span><b><?= e($s['title']) ?></b><small><?= e($s['subject']) ?> · <?= e($s['level']) ?></small><small><?= e(date('D j M, g:i A', $t0)) ?> · <?= e($s['host_name']) ?></small></a>
    <?php endforeach; ?>
  </div>
</div></section>
<?php endif; ?>

<section class="section alt el-list"><div class="container">
  <div class="el-grid" id="el-grid">
    <?php foreach ($res as $r): ?>
    <a class="el-card reveal" href="<?= e($r['url']) ?>" target="_blank" rel="noopener noreferrer"
       data-level="<?= e($r['level']) ?>" data-subject="<?= e($r['subject']) ?>" data-kind="<?= e($r['kind']) ?>" data-q="<?= e(strtolower($r['title'] . ' ' . $r['description'] . ' ' . $r['source'] . ' ' . $r['subject'] . ' ' . $r['kind'])) ?>">
      <span class="el-ico"><?= $kindIcon[$r['kind']] ?? '📘' ?></span>
      <span class="el-tags"><span class="tag"><?= e($r['level']) ?></span><span class="tag tag-2"><?= e($r['kind']) ?></span></span>
      <h3><?= e($r['title']) ?></h3>
      <p><?= e($r['description']) ?></p>
      <span class="el-foot"><small><?= e($r['source']) ?><?= $r['subject'] !== 'All subjects' ? ' · ' . e($r['subject']) : '' ?></small><b>Open ↗</b></span>
    </a>
    <?php endforeach; ?>
  </div>
  <div id="el-none" class="help-none" hidden><h3>No resources match</h3><p>Try fewer filters or a shorter search word.</p></div>
  <?php if (!$res): ?><p>No resources have been added yet.</p><?php endif; ?>
</div></section>

<section class="section"><div class="container narrow">
  <div class="section-head"><span class="eyebrow">Good to know</span><h2>Study well, stay safe online</h2></div>
  <ul class="checks">
    <li>Check each resource against your <strong>Ugandan syllabus</strong> (NCDC) and your teacher's notes.</li>
    <li>Get <strong>past papers</strong> and marking guides from UNEB or from your teachers, so you know they are genuine.</li>
    <li>Do not share personal details, passwords or phone numbers on any website.</li>
    <li>Without internet at school? Ask your teacher about <strong>Kolibri</strong> or downloaded simulations that work offline.</li>
    <li>Know a good free resource? <a href="<?= url('contact.php') ?>">Tell us</a> and we will consider adding it.</li>
  </ul>
  <p class="meta">The school is not the publisher of these resources and is not responsible for the content of external websites.</p>
</div></section>

<section class="cta-band"><div class="container">
  <h2>Learn together, live</h2>
  <p>Join a live online lesson led by AFSS teachers, or visit the library and e-learning pages to learn more.</p>
  <a class="btn btn-accent" href="<?= url('live.php') ?>">Live online lessons</a>
  <a class="btn btn-outline" href="<?= url('page.php?slug=library-services') ?>">Library services</a>
</div></section>

<script>
(function () {
  var q = document.getElementById('elq'), lv = document.getElementById('el-level'), sb = document.getElementById('el-subject'), kd = document.getElementById('el-kind'),
      cards = document.querySelectorAll('.el-card'), none = document.getElementById('el-none'), count = document.getElementById('el-count');
  function apply() {
    var words = q.value.trim().toLowerCase().split(/\s+/).filter(Boolean), n = 0;
    cards.forEach(function (c) {
      var okLevel = !lv.value || c.dataset.level === lv.value || (lv.value === 'O Level' || lv.value === 'A Level') && c.dataset.level === 'O & A Level';
      var ok = okLevel && (!sb.value || c.dataset.subject === sb.value || c.dataset.subject === 'All subjects' && sb.value !== '') && (!kd.value || c.dataset.kind === kd.value)
        && words.every(function (w) { return c.dataset.q.indexOf(w) > -1; });
      c.hidden = !ok; if (ok) n++;
    });
    none.hidden = n > 0;
    count.textContent = (q.value || lv.value || sb.value || kd.value) ? n + ' resource' + (n === 1 ? '' : 's') + ' found' : cards.length + ' free resources';
  }
  [q, lv, sb, kd].forEach(function (el) { el.addEventListener('input', apply); el.addEventListener('change', apply); });
  apply();
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
