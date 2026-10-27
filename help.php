<?php
require_once __DIR__ . '/includes/bootstrap.php';

$faqs = rows('SELECT * FROM faqs WHERE active = 1 ORDER BY category_sort, sort, id');
$byCat = [];
foreach ($faqs as $f) $byCat[$f['category']][] = $f;
$order = array_flip(faq_categories());
uksort($byCat, fn($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99) ?: strcmp($a, $b));
$slugify = fn(string $s): string => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-');

$ld = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
foreach (array_slice($faqs, 0, 60) as $f) $ld['mainEntity'][] = ['@type' => 'Question', 'name' => $f['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags(faq_tokens($f['answer'])))]];

$pageTitle = 'FAQ & Help Centre';
$pageDesc = 'Answers to common questions about admissions, fees, scholarships, boarding, academics, student welfare, alumni, giving, careers and contacting Archbishop Flynn Secondary School.';
$bannerTitle = 'FAQ & Help Centre';
$bannerSub = 'Everything you need to know about AFSS, in one place.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
$wa = whatsapp_link('Hello, I have a question.');
?>
<section class="help-search"><div class="container">
  <label for="hq" class="sr-only">Search the help centre</label>
  <div class="hq-box"><span aria-hidden="true">🔎</span><input id="hq" type="search" placeholder="Search for fees, boarding, apply, scholarships, alumni card…" autocomplete="off"><button type="button" id="hq-clear" hidden aria-label="Clear search">✕</button></div>
  <div class="help-chips" id="help-chips" role="tablist" aria-label="Topics">
    <button type="button" class="chip on" data-cat="">All topics <i><?= count($faqs) ?></i></button>
    <?php foreach ($byCat as $cat => $list): ?><button type="button" class="chip" data-cat="<?= e($slugify($cat)) ?>"><?= e($cat) ?> <i><?= count($list) ?></i></button><?php endforeach; ?>
  </div>
</div></section>

<section class="section help-quick"><div class="container">
  <div class="section-head"><span class="eyebrow">Quick links</span><h2>Jump straight to what you need</h2></div>
  <div class="qk-grid">
    <?php foreach ([
      ['🎓', 'Apply for admission', 'Start your online application', 'apply.php'],
      ['💳', 'Fees & payment', 'How fees work, who to ask', 'page.php?slug=tuition-and-fees'],
      ['🏅', 'Scholarships', 'Awards and how to apply', 'scholarships.php'],
      ['📅', 'Events & calendar', 'Dates, holidays, countdowns', 'events.php'],
      ['🕊', 'Report a concern', 'Confidential or anonymous', 'whistleblower.php'],
      ['🪪', 'Alumni card', 'Get or verify a card', 'alumni.php#card'],
      ['💼', 'Careers', 'Open vacancies', 'careers.php'],
      ['☎', 'Contact us', 'Phone, email, map', 'contact.php'],
    ] as [$ic, $t, $d, $l]): ?>
    <a class="qk" href="<?= url($l) ?>"><span><?= $ic ?></span><b><?= e($t) ?></b><small><?= e($d) ?></small></a>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section alt help-list"><div class="container help-in">
  <div id="help-results" role="status" aria-live="polite" class="help-count"></div>
  <?php foreach ($byCat as $cat => $list): $cs = $slugify($cat); ?>
  <div class="help-cat" data-cat="<?= e($cs) ?>" id="<?= e($cs) ?>">
    <h2><?= e($cat) ?></h2>
    <?php foreach ($list as $f): ?>
    <details class="faq" data-q="<?= e(strtolower($f['question'] . ' ' . strip_tags($f['answer']) . ' ' . $f['category'] . ' ' . $f['link_text'])) ?>">
      <summary><?= e($f['question']) ?></summary>
      <div class="faq-a"><?= faq_tokens($f['answer']) /* trusted admin-authored HTML */ ?>
        <?php if ($f['link_url']): ?><p><a class="btn btn-ghost btn-sm" href="<?= e(link_url($f['link_url'])) ?>"><?= e($f['link_text'] ?: 'Read more') ?> →</a></p><?php endif; ?>
      </div>
    </details>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
  <div id="help-none" class="help-none" hidden>
    <h3>No answer found</h3><p>We could not find a match. Try a shorter word, or ask us directly below.</p>
  </div>
  <?php if (!$faqs): ?><p>No questions have been added yet.</p><?php endif; ?>
</div></section>

<section class="section help-contact"><div class="container">
  <div class="section-head"><span class="eyebrow">Still need help?</span><h2>Talk to us</h2><p>Our team is happy to help with anything not answered above.</p></div>
  <div class="hc-grid">
    <?php if (setting('phone')): ?><a class="hc" href="tel:<?= e(preg_replace('/\s+/', '', setting('phone'))) ?>"><span>☎</span><b>Call</b><small><?= e(setting('phone')) ?></small></a><?php endif; ?>
    <?php if ($wa): ?><a class="hc hc-wa" href="<?= e($wa) ?>" target="_blank" rel="noopener"><span>💬</span><b>WhatsApp</b><small><?= e(whatsapp_display() ?: 'Chat with us') ?></small></a><?php endif; ?>
    <?php if (setting('email')): ?><a class="hc" href="mailto:<?= e(setting('email')) ?>"><span>✉</span><b>Email</b><small><?= e(setting('email')) ?></small></a><?php endif; ?>
    <a class="hc" href="<?= url('contact.php') ?>"><span>📍</span><b>Visit or message</b><small><?= e(setting('address')) ?></small></a>
  </div>
</div></section>

<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<script>
(function () {
  var q = document.getElementById('hq'), clear = document.getElementById('hq-clear'), chips = document.querySelectorAll('#help-chips .chip'),
      cats = document.querySelectorAll('.help-cat'), none = document.getElementById('help-none'), count = document.getElementById('help-results'), cat = '';
  function apply() {
    var term = q.value.trim().toLowerCase(), words = term.split(/\s+/).filter(Boolean), shown = 0;
    cats.forEach(function (c) {
      var inCat = !cat || c.dataset.cat === cat, any = false;
      c.querySelectorAll('.faq').forEach(function (f) {
        var ok = inCat && words.every(function (w) { return f.dataset.q.indexOf(w) > -1; });
        f.hidden = !ok; if (ok) { any = true; shown++; if (term) f.open = true; else f.open = false; }
      });
      c.hidden = !any;
    });
    none.hidden = shown > 0; clear.hidden = !term;
    count.textContent = term || cat ? shown + ' answer' + (shown === 1 ? '' : 's') + ' found' : '';
  }
  q.addEventListener('input', apply);
  clear.addEventListener('click', function () { q.value = ''; q.focus(); apply(); });
  chips.forEach(function (b) { b.addEventListener('click', function () {
    chips.forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on'); cat = b.dataset.cat; apply();
    var first = document.querySelector('.help-list'); if (first) first.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }); });
  if (location.hash) { var t = document.querySelector(location.hash); if (t) { var c = document.querySelector('.chip[data-cat="' + location.hash.slice(1) + '"]'); if (c) c.click(); } }
  document.addEventListener('keydown', function (e) { if (e.key === '/' && document.activeElement !== q && !/input|textarea|select/i.test(document.activeElement.tagName)) { e.preventDefault(); q.focus(); } });
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
