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

<section class="pillars" aria-label="Why choose us"><div class="container">
  <div class="pillar-row">
    <?php foreach ([
      ['📖', 'p-gold', 'Academic Excellence', 'Strong O and A level teaching, a reading culture and e-learning.'],
      ['✝', 'p-green', 'Faith &amp; Character', 'Moral formation rooted in wisdom, service and discipline.'],
      ['🏠', 'p-blue', 'Boarding Community', 'A safe, caring co-educational home away from home.'],
      ['🎓', 'p-gold', 'Bursaries &amp; Access', 'Scholarships and equal opportunities for deserving learners.'],
      ['🌿', 'p-leaf', 'Life Beyond Class', 'Clubs, sports, health care and counselling for every learner.'],
    ] as [$ic, $cl, $t, $d]): ?>
    <div class="pillar reveal"><span class="pillar-ico <?= $cl ?>"><?= $ic ?></span><h3><?= $t ?></h3><p><?= $d ?></p></div>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section programs">
  <div class="container">
    <div class="section-head"><span class="eyebrow">Explore</span><h2>Explore <?= e(setting('short_name')) ?></h2><p>Quick access to the key areas of school life.</p></div>
    <div class="prog-grid">
      <?php foreach ([
        ['🏛', 'pg-1', 'About &amp; Governance', 'Our story, leadership and the Board of Governors.', 'governance.php'],
        ['📚', 'pg-2', 'Academics', 'O and A level programmes, departments and library.', 'page.php?slug=academics'],
        ['🎓', 'pg-3', 'Admissions', 'Entry requirements, fees and how to apply.', 'admissions.php'],
        ['⚽', 'pg-4', 'Student Life', 'Clubs, health, counselling and sport.', 'clubs.php'],
        ['🤝', 'pg-5', 'Alumni', 'AFSSOSA leadership, events and membership.', 'alumni.php'],
      ] as [$ic, $cl, $t, $d, $l]): ?>
      <a class="prog reveal" href="<?= url($l) ?>"><span class="prog-img <?= $cl ?>"><i><?= $ic ?></i></span><h3><?= $t ?></h3><p><?= $d ?></p></a>
      <?php endforeach; ?>
    </div>
  </div>
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
    <?php if (!empty($about['image'])): ?>
    <div class="about-img reveal"><img src="<?= e(media($about['image'])) ?>" alt="<?= e(setting('school_name')) ?> campus"></div>
    <?php else: ?>
    <aside class="values-panel reveal" aria-label="About the school">
      <?php if (setting('vision')): ?><div class="vp-block"><span class="vp-label">Our vision</span><p><?= e(setting('vision')) ?></p></div><?php endif; ?>
      <?php if (setting('tagline')): ?><div class="vp-block vp-motto"><span class="vp-label">Our motto</span><p><?= e(setting('tagline')) ?></p></div><?php endif; ?>
      <ul class="vp-facts">
        <li><b>O &amp; A Level</b><span>Co-educational boarding</span></li>
        <li><b>Pader</b><span>Northern Uganda</span></li>
        <?php if (setting('uneb_centre')): ?><li><b><?= e(setting('uneb_centre')) ?></b><span>UNEB centre no.</span></li><?php endif; ?>
        <?php if (setting('moes_reg')): ?><li><b><?= e(setting('moes_reg')) ?></b><span>MoES registration</span></li><?php endif; ?>
      </ul>
    </aside>
    <?php endif; ?>
  </div>
</section>

<section class="section news-events">
  <div class="container ne-grid">
    <div class="reveal">
      <div class="ne-head"><h2>Latest News</h2><a href="<?= url('news.php') ?>">All news &amp; stories →</a></div>
      <?php foreach ($news as $n): ?>
      <a class="ne-item" href="<?= url('news.php?s=' . $n['slug']) ?>">
        <?php if ($n['image']): ?><img src="<?= e(media($n['image'])) ?>" alt="" loading="lazy"><?php endif; ?>
        <span><span class="tag"><?= e($n['category']) ?></span><b><?= e($n['title']) ?></b><small><?= fmt_date($n['published_at']) ?></small></span>
      </a>
      <?php endforeach; if (!$news): ?><p>No news published yet.</p><?php endif; ?>
    </div>
    <div class="reveal">
      <div class="ne-head"><h2>Upcoming Events</h2><a href="<?= url('events.php') ?>">Full calendar →</a></div>
      <?php foreach ($events as $ev): ?>
      <div class="ne-event"><div class="ne-date"><small><?= fmt_date($ev['start_date'], 'M') ?></small><b><?= fmt_date($ev['start_date'], 'j') ?></b></div>
        <span><b><?= e($ev['title']) ?></b><small><?= e(trim(($ev['time_text'] ?: '') . ($ev['location'] ? ' · ' . $ev['location'] : ''), ' ·')) ?></small></span></div>
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

<section class="stay"><div class="container stay-in">
  <div><h2>Stay Connected</h2><p>News, events and stories from <?= e(setting('short_name')) ?> in your inbox.</p></div>
  <form class="stay-form" method="post" action="<?= url('subscribe.php') ?>">
    <?= csrf_field() ?>
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <input type="email" name="email" placeholder="Email address" required aria-label="Email address">
    <button type="submit">Sign up</button>
  </form>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
