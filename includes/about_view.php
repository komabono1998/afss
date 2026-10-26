<?php
/** Designed About Us page. Text comes from Site Settings → About page / Home page, and from the page body (registration details). */
$heroImg = $p['image'] ?: 'uploads/campus-uniform.jpg';
$lines = fn(string $k): array => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) setting($k)))));
$values = [];
foreach ($lines('about_values') as $ln) { [$t, $d] = array_pad(explode(':', $ln, 2), 2, ''); $values[] = [trim($t), trim($d)]; }
$journey = [];
foreach ($lines('about_journey') as $ln) { [$t, $d] = array_pad(explode('|', $ln, 2), 2, ''); $journey[] = [trim($t), trim($d)]; }
$icons = ['🙏', '🤝', '⚖️', '✔️', '📋', '🎯', '🌍', '⭐', '📚'];
?>
<section class="ab-hero" style="background-image:url('<?= e(media($heroImg)) ?>')">
  <div class="ab-hero-in container">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url() ?>">Home</a> / About</nav>
    <span class="eyebrow">About us</span>
    <h1>Archbishop Flynn Secondary School</h1>
    <p><?= e(setting('about_intro')) ?></p>
    <div class="hero-actions">
      <a class="btn btn-accent" href="<?= url('apply.php') ?>">Apply for admission</a>
      <a class="btn btn-outline-light" href="<?= url('governance.php') ?>">Meet our leadership</a>
    </div>
  </div>
</section>

<section class="ab-facts"><div class="container"><ul>
  <li><b>2009</b><span>Year established</span></li>
  <li><b>Pader</b><span>Northern Uganda</span></li>
  <li><b>O &amp; A Level</b><span>Secondary education</span></li>
  <li><b>Boarding</b><span>Co-educational</span></li>
  <?php if (setting('uneb_centre')): ?><li><b><?= e(setting('uneb_centre')) ?></b><span>UNEB centre no.</span></li><?php endif; ?>
</ul></div></section>

<section class="section ab-story"><div class="container ab-story-in">
  <figure class="ab-photo reveal">
    <?php if (setting('founders_image')): ?><img src="<?= e(media(setting('founders_image'))) ?>" alt="<?= e(setting('founders_caption')) ?>" loading="lazy"><?php else: ?><div class="founders-ph"><span>Founder's photo<br>coming soon</span></div><?php endif; ?>
    <figcaption><?= e(setting('founders_caption')) ?></figcaption>
  </figure>
  <div class="reveal">
    <span class="eyebrow">Our story</span>
    <h2><?= e(setting('founders_title')) ?></h2>
    <?php foreach (preg_split('/\R{2,}/', trim((string) setting('founders_story'))) as $para): ?><p><?= nl2br(e(trim($para))) ?></p><?php endforeach; ?>
  </div>
</div></section>

<?php if ($journey): ?>
<section class="section alt ab-journey"><div class="container">
  <div class="section-head"><span class="eyebrow">Our journey</span><h2>From a vision to a school community</h2></div>
  <ol class="timeline">
    <?php foreach ($journey as [$t, $d]): ?><li class="reveal"><span class="tl-dot"></span><b><?= e($t) ?></b><p><?= e($d) ?></p></li><?php endforeach; ?>
  </ol>
</div></section>
<?php endif; ?>

<section class="ab-vm"><div class="container ab-vm-in">
  <div class="vm-card vm-v reveal"><span class="vm-ico">👁</span><h3>Our vision</h3><p><?= e(setting('vision')) ?></p></div>
  <div class="vm-card vm-m reveal"><span class="vm-ico">🎯</span><h3>Our mission</h3><p><?= e(setting('mission')) ?></p></div>
</div></section>

<section class="ab-motto"><div class="container">
  <span>Our motto</span>
  <blockquote>Sapientia et Servitium</blockquote>
  <p>Wisdom and Service. Knowledge carries responsibility: we encourage learners to grow in understanding and to put their abilities to constructive use in their families, workplaces, churches and communities.</p>
</div></section>

<?php if ($values): ?>
<section class="section ab-values"><div class="container">
  <div class="section-head"><span class="eyebrow">What we stand for</span><h2>Our core values</h2></div>
  <div class="val-grid">
    <?php foreach ($values as $i => [$t, $d]): ?>
    <div class="val reveal"><span class="val-ico"><?= $icons[$i % count($icons)] ?></span><h3><?= e($t) ?></h3><?php if ($d): ?><p><?= e($d) ?></p><?php endif; ?></div>
    <?php endforeach; ?>
  </div>
</div></section>
<?php endif; ?>

<section class="section alt ab-explore"><div class="container">
  <div class="section-head"><span class="eyebrow">Learn more</span><h2>Explore AFSS</h2></div>
  <div class="ex-grid">
    <?php foreach ([
      ['governance.php', '🏛', 'Governance', 'Board of Governors and school leadership'],
      ['page.php?slug=headteachers-message', '✍', "Head Teacher's message", 'A word to learners and parents'],
      ['page.php?slug=administrators-message', '📜', "Administrator's message", 'Stewardship and school life'],
      ['page.php?slug=teaching-and-learning', '📚', 'Teaching and learning', 'How we teach and assess'],
      ['staff.php', '👩‍🏫', 'Our staff', 'Meet the teaching team'],
      ['page.php?slug=afrudec', '🌱', 'AFRUDEC', 'Agriculture and sustainability'],
      ['page.php?slug=partnerships', '🤝', 'Partnerships', 'Partners and supporters'],
      ['alumni.php', '🎓', 'Alumni', 'AFSSOSA and the old students'],
    ] as [$l, $ic, $t, $d]): ?>
    <a class="ex reveal" href="<?= url($l) ?>"><span><?= $ic ?></span><b><?= e($t) ?></b><small><?= e($d) ?></small></a>
    <?php endforeach; ?>
  </div>
</div></section>

<?php if (trim(strip_tags((string) $p['body'])) !== ''): ?>
<section class="section ab-reg"><div class="container narrow prose"><?= page_tokens($p['body']) /* trusted admin-authored HTML */ ?></div></section>
<?php endif; ?>

<section class="cta-band"><div class="container">
  <h2>Come and be part of the AFSS family</h2>
  <p><?= e(setting('intake_text')) ?></p>
  <a class="btn btn-accent" href="<?= url('apply.php') ?>">Apply for admission</a>
  <a class="btn btn-outline" href="<?= url('contact.php') ?>">Contact the school</a>
</div></section>
