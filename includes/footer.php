</main>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <h4><?= e(setting('school_name')) ?></h4>
      <p><?= e(setting('intro')) ?></p>
      <div class="social">
        <?php foreach (['facebook' => 'Facebook', 'twitter' => 'X / Twitter', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'whatsapp' => 'WhatsApp'] as $k => $label): if (setting($k)): ?>
          <a href="<?= e(setting($k)) ?>" target="_blank" rel="noopener"><?= e($label) ?></a>
        <?php endif; endforeach; ?>
      </div>
    </div>
    <div>
      <h4>Quick Links</h4>
      <ul class="flinks">
        <li><a href="<?= url('page.php?slug=about-us') ?>">About Us</a></li>
        <li><a href="<?= url('governance.php') ?>">Governance</a></li>
        <li><a href="<?= url('management.php?g=pta') ?>">PTA Committee</a></li>
        <li><a href="<?= url('admissions.php') ?>">Admissions</a></li>
        <li><a href="<?= url('apply.php') ?>">Apply Online</a></li>
        <li><a href="<?= url('scholarships.php') ?>">Scholarships &amp; Bursaries</a></li>
        <li><a href="<?= url('news.php') ?>">News &amp; Stories</a></li>
        <li><a href="<?= url('events.php') ?>">Events &amp; Calendar</a></li>
        <li><a href="<?= url('careers.php') ?>">Careers</a></li>
        <li><a href="<?= url('contact.php') ?>">Contact</a></li>
      </ul>
    </div>
    <div>
      <h4>Contact</h4>
      <ul class="flinks">
        <li>📍 <?= e(setting('address')) ?></li>
        <?php if (setting('phone')): ?><li>☎ <?= e(setting('phone')) ?></li><?php endif; ?>
        <?php if (setting('phone2')): ?><li>☎ <?= e(setting('phone2')) ?></li><?php endif; ?>
        <?php if (setting('email')): ?><li>✉ <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></li><?php endif; ?>
      </ul>
    </div>
    <div>
      <h4>Newsletter</h4>
      <p>News, events and stories from AFSS in your inbox.</p>
      <form class="subscribe" method="post" action="<?= url('subscribe.php') ?>">
        <?= csrf_field() ?>
        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="email" name="email" placeholder="Your email address" required aria-label="Email address">
        <button class="btn btn-accent" type="submit">Subscribe</button>
      </form>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container">
      <span>© <?= date('Y') ?> <?= e(setting('school_name')) ?>. <?= e(setting('footer_text')) ?></span>
      <span>
        <?php if (setting('moes_reg')): ?>MoES Reg: <?= e(setting('moes_reg')) ?> · <?php endif; ?>
        <?php if (setting('uneb_centre')): ?>UNEB Centre: <?= e(setting('uneb_centre')) ?> · <?php endif; ?>
        <a href="<?= url('page.php?slug=privacy') ?>">Privacy</a> · <a href="<?= url('admin/') ?>">Staff Login</a>
      </span>
    </div>
  </div>
</footer>
<a href="#top" class="to-top" aria-label="Back to top">↑</a>
<script src="<?= asset('assets/js/main.js') ?>"></script>
</body>
</html>
