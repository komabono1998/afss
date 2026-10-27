<?php
require_once __DIR__ . '/includes/bootstrap.php';

$title = setting('anthem_title') ?: 'Our School Anthem';
$audio = setting('anthem_audio');
$lyrics = trim((string) setting('anthem_lyrics'));
$verses = $lyrics !== '' ? preg_split('/\R{2,}/', $lyrics) : [];
$ytId = preg_match('#(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/))([A-Za-z0-9_-]{11})#', (string) setting('anthem_video'), $m) ? $m[1] : '';
$img = setting('anthem_image');

$pageTitle = $title;
$pageDesc = 'Listen to and read the school anthem of Archbishop Flynn Secondary School, Pader.';
$bannerTitle = $title;
$bannerSub = setting('anthem_intro') ?: 'Sapientia et Servitium: wisdom and service.';
$crumbs = [['About', url('page.php?slug=about-us')]];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section an-page"><div class="container an-in">
  <div class="an-main">
    <?php if ($audio || $ytId): ?>
    <div class="an-player">
      <?php if ($img): ?><img class="an-cover" src="<?= e(media($img)) ?>" alt="" loading="lazy"><?php else: ?><img class="an-cover an-crest" src="<?= e(media(setting('logo') ?: 'assets/img/logo.webp')) ?>" alt=""><?php endif; ?>
      <div class="an-pl">
        <span class="eyebrow">Listen</span>
        <h2><?= e($title) ?></h2>
        <?php if ($audio): ?>
          <audio controls preload="metadata" src="<?= e(media($audio)) ?>">Your browser cannot play this recording.</audio>
          <p><a class="btn btn-ghost btn-sm" href="<?= e(media($audio)) ?>" download>⬇ Download the recording</a></p>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($ytId): ?><div class="an-video"><iframe src="https://www.youtube-nocookie.com/embed/<?= e($ytId) ?>" title="<?= e($title) ?> video" loading="lazy" allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div><?php endif; ?>
    <?php endif; ?>

    <?php if ($verses): ?>
    <div class="an-lyrics-head"><h2>The words</h2><div class="an-tools no-print"><button type="button" id="an-smaller" aria-label="Smaller text">A−</button><button type="button" id="an-bigger" aria-label="Bigger text">A+</button><button type="button" onclick="window.print()">🖨 Print</button></div></div>
    <div class="an-lyrics" id="an-lyrics"><?php foreach ($verses as $v): ?><p><?= nl2br(e(trim($v))) ?></p><?php endforeach; ?></div>
    <?php else: ?>
    <div class="an-soon"><div class="an-note">🎵</div><h2>The anthem is coming soon</h2><p>The school anthem, with the recording and the words, will be published here. Please check back soon.</p></div>
    <?php endif; ?>
    <?php if (setting('anthem_credits')): ?><p class="an-credits"><?= e(setting('anthem_credits')) ?></p><?php endif; ?>
  </div>
  <aside class="an-side">
    <div class="an-motto"><img src="<?= e(media(setting('logo') ?: 'assets/img/logo.webp')) ?>" alt=""><b class="motto-hl">Sapientia et Servitium</b><span>Wisdom and Service</span></div>
    <div class="box"><h4>More about AFSS</h4><ul>
      <li><a href="<?= url('page.php?slug=about-us') ?>">About the school</a></li>
      <li><a href="<?= url('governance.php') ?>">Governance</a></li>
      <li><a href="<?= url('gallery.php') ?>">Photo gallery</a></li>
      <li><a href="<?= url('events.php') ?>">Events &amp; calendar</a></li>
    </ul></div>
  </aside>
</div></section>
<script>
(function () {
  var box = document.getElementById('an-lyrics'); if (!box) return;
  var size = 1.25; function set() { box.style.fontSize = size + 'rem'; }
  document.getElementById('an-bigger').onclick = function () { size = Math.min(2.2, size + .15); set(); };
  document.getElementById('an-smaller').onclick = function () { size = Math.max(.95, size - .15); set(); };
  set();
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
