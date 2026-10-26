<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = '';
$pageDesc = setting('intro');
$slides = rows('SELECT * FROM slides WHERE active = 1 ORDER BY sort, id');
$news = rows("SELECT * FROM news WHERE status='published' AND published_at <= date('now') ORDER BY featured DESC, published_at DESC LIMIT 3");
$events = rows("SELECT * FROM events WHERE date(COALESCE(end_date,start_date)) >= date('now') ORDER BY start_date LIMIT 4");
$about = row("SELECT * FROM pages WHERE slug='about-us'");
$jobs = (int) val("SELECT COUNT(*) FROM jobs WHERE status='open' AND (deadline IS NULL OR deadline='' OR deadline >= date('now'))");
include __DIR__ . '/includes/header.php';
?>
<section class="hero" aria-label="Welcome">
  <?php foreach ($slides ?: [['title' => setting('school_name'), 'subtitle' => setting('intro'), 'image' => '', 'link_text' => 'Apply Now', 'link_url' => 'apply.php']] as $s): ?>
  <div class="slide <?= $s['image'] ? '' : 'noimg' ?>" <?= $s['image'] ? 'style="background-image:url(\'' . e(media($s['image'])) . '\')"' : '' ?>>
    <div class="container"><div class="slide-body">
      <span class="eyebrow"><?= e(setting('tagline')) ?></span>
      <h1><?= e($s['title']) ?></h1>
      <p><?= e($s['subtitle']) ?></p>
      <div class="hero-actions">
        <?php if (!empty($s['link_text'])): ?><a class="btn btn-accent" href="<?= e(link_url($s['link_url'])) ?>"><?= e($s['link_text']) ?></a><?php endif; ?>
        <a class="btn btn-outline" href="<?= url('page.php?slug=about-us') ?>">Discover AFSS</a>
      </div>
    </div></div>
    <?php if (!$s['image']): ?><div class="crest-stage" aria-hidden="true"><img class="hero-crest" src="<?= e(media(setting('logo') ?: 'assets/img/logo.webp')) ?>" alt=""></div><?php endif; ?>
  </div>
  <?php endforeach; ?>
  <div class="hero-dots"></div>
</section>

<section class="stats"><div class="container"><div class="stats-grid">
  <?php $pop = population(); $over = ['students' => [$pop['students'], 'Learners'], 'teachers' => [$pop['staff'], 'Dedicated Staff']];
  foreach (['students', 'teachers', 'years', 'firstgrade'] as $k):
    $real = isset($over[$k]) && $over[$k][0] > 0;
    $val = $real ? number_format($over[$k][0]) : setting("stat_$k"); $lab = $real ? $over[$k][1] : setting("stat_{$k}_l");
    if ($val): ?>
  <div class="stat"><b><?= e($val) ?></b><span><?= e($lab) ?></span></div>
  <?php endif; endforeach; ?>
</div></div></section>

<section class="section">
  <div class="container split">
    <div class="reveal">
      <span class="eyebrow">Welcome to <?= e(setting('short_name')) ?></span>
      <h2>Educating for wisdom and service</h2>
      <p><?= e(setting('mission')) ?></p>
      <ul class="checks">
        <li>Co-educational boarding for O and A level</li>
        <li>Equal opportunities for boys and girls</li>
        <li>Bursaries for deserving learners</li>
        <li>Strong reading culture, library and e-learning</li>
      </ul>
      <a class="btn btn-primary" href="<?= url('page.php?slug=about-us') ?>">Learn more about us</a>
    </div>
    <div class="about-img reveal">
      <?php if (!empty($about['image'])): ?><img src="<?= e(media($about['image'])) ?>" alt="Archbishop Flynn Secondary School campus"><?php else: ?><img class="crest-big" src="<?= e(media(setting('logo') ?: 'assets/img/logo.webp')) ?>" alt="<?= e(setting('school_name')) ?> crest"><?php endif; ?>
      <div class="badge">Sapientia et Servitium</div>
    </div>
  </div>
</section>

<section class="section alt">
  <div class="container">
    <div class="section-head"><span class="eyebrow">Explore</span><h2>Everything you need to know</h2><p>Quick access to the key areas of school life.</p></div>
    <div class="grid g4">
      <?php foreach ([
        ['🏛', 'About & Governance', 'Our story, leadership and the Board of Governors.', 'governance.php'],
        ['📚', 'Academics', 'O and A level programmes, departments and library.', 'page.php?slug=academics'],
        ['🎓', 'Admissions', 'Entry requirements, fees and how to apply.', 'admissions.php'],
        ['⚽', 'Student Life', 'Clubs, health, counselling and alumni.', 'clubs.php'],
      ] as [$ic, $t, $d, $l]): ?>
      <a class="card feature reveal" href="<?= url($l) ?>"><div class="ico"><?= $ic ?></div><h3><?= e($t) ?></h3><p><?= e($d) ?></p><span class="more">Read more →</span></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head"><span class="eyebrow">News &amp; Stories</span><h2>Latest from our community</h2></div>
    <div class="grid g3">
      <?php foreach ($news as $n): ?>
      <a class="card reveal" href="<?= url('news.php?s=' . $n['slug']) ?>">
        <div class="thumb"><?php if ($n['image']): ?><img src="<?= e(media($n['image'])) ?>" alt="" loading="lazy"><?php else: ?>AFSS<?php endif; ?></div>
        <div class="card-body"><span class="tag"><?= e($n['category']) ?></span><h3><?= e($n['title']) ?></h3><p><?= e($n['excerpt'] ?: excerpt($n['body'])) ?></p><span class="meta"><?= fmt_date($n['published_at']) ?></span></div>
      </a>
      <?php endforeach; ?>
    </div>
    <p style="text-align:center;margin-top:2rem"><a class="btn btn-ghost" href="<?= url('news.php') ?>">All news &amp; stories</a></p>
  </div>
</section>

<section class="section alt">
  <div class="container split">
    <div class="reveal">
      <span class="eyebrow">Calendar</span><h2>Upcoming events</h2>
      <p>Stay in step with school life — open days, examinations, parents' meetings and more.</p>
      <a class="btn btn-primary" href="<?= url('events.php') ?>">Full events calendar</a>
    </div>
    <div class="grid reveal" style="gap:.9rem">
      <?php foreach ($events as $ev): ?>
      <div class="event-row"><div class="date-box"><b><?= fmt_date($ev['start_date'], 'j') ?></b><small><?= fmt_date($ev['start_date'], 'M') ?></small></div>
        <div><h3><?= e($ev['title']) ?></h3><span class="meta"><?= e(trim(($ev['time_text'] ?: '') . ($ev['location'] ? ' · ' . $ev['location'] : ''), ' ·')) ?></span></div></div>
      <?php endforeach; if (!$events): ?><p>No upcoming events at the moment.</p><?php endif; ?>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container">
    <h2>Begin your journey with <?= e(setting('short_name')) ?></h2>
    <p><?= e(setting('intake_text')) ?></p>
    <a class="btn btn-accent" href="<?= url('apply.php') ?>">Apply for Admission</a>
    <a class="btn btn-gold" href="<?= url('donate.php') ?>">♥ Donate or partner with us</a>
    <?php if ($jobs): ?><a class="btn btn-outline" href="<?= url('careers.php') ?>">Work with us (<?= $jobs ?> open)</a><?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
