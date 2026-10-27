</main>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <?php if (basename($_SERVER['SCRIPT_NAME']) !== 'index.php'): ?><img class="footer-logo" src="<?= e(media(setting('logo') ?: 'assets/img/logo.webp')) ?>" alt="<?= e(setting('school_name')) ?> crest"><?php endif; ?>
      <h4><?= e(setting('school_name')) ?></h4>
      <p><?= e(setting('intro')) ?></p>
      <div class="social">
        <?php foreach (['facebook' => 'Facebook', 'twitter' => 'X / Twitter', 'instagram' => 'Instagram', 'youtube' => 'YouTube'] as $k => $label): if (setting($k)): ?>
          <a href="<?= e(setting($k)) ?>" target="_blank" rel="noopener"><?= e($label) ?></a>
        <?php endif; endforeach; ?>
        <?php if (whatsapp_link()): ?><a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
      </div>
    </div>
    <div>
      <h4>Quick Links</h4>
      <ul class="flinks cols">
        <li><a href="<?= url('page.php?slug=about-us') ?>">About Us</a></li>
        <li><a href="<?= url('governance.php') ?>">Governance</a></li>
        <li><a href="<?= url('management.php?g=pta') ?>">PTA Committee</a></li>
        <li><a href="<?= url('alumni.php') ?>">Alumni</a></li>
        <li><a href="<?= url('admissions.php') ?>">Admissions</a></li>
        <li><a href="<?= url('apply.php') ?>">Apply Online</a></li>
        <li><a href="<?= url('scholarships.php') ?>">Scholarships &amp; Bursaries</a></li>
        <li><a href="<?= url('news.php') ?>">News &amp; Stories</a></li>
        <li><a href="<?= url('newsletters.php') ?>">Monthly Newsletter</a></li>
        <li><a href="<?= url('anthem.php') ?>">School Anthem</a></li>
        <li><a href="<?= url('elibrary.php') ?>">e-Library</a></li>
        <li><a href="<?= url('live.php') ?>">Live Online Lessons</a></li>
        <li><a href="<?= url('events.php') ?>">Events &amp; Calendar</a></li>
        <li><a href="<?= url('achievers.php') ?>">Achievers Corner</a></li>
        <li><a href="<?= url('staff.php') ?>">Our Staff</a></li>
        <li><a href="<?= url('careers.php') ?>">Careers</a></li>
        <li><a href="<?= url('donate.php') ?>">Donate &amp; Partner</a></li>
        <li><a href="<?= url('help.php') ?>">FAQ &amp; Help Centre</a></li>
        <li><a href="<?= url('contact.php') ?>">Contact</a></li>
        <li><a href="<?= url('whistleblower.php') ?>">Report a Concern</a></li>
      </ul>
    </div>
    <div>
      <h4>Contact</h4>
      <ul class="flinks">
        <li>📍 <?= e(setting('address')) ?></li>
        <?php if (setting('phone')): ?><li>☎ <?= e(setting('phone')) ?></li><?php endif; ?>
        <?php if (setting('phone2')): ?><li>☎ <?= e(setting('phone2')) ?></li><?php endif; ?>
        <?php if (whatsapp_link()): ?><li>💬 <a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener">WhatsApp support<?= whatsapp_display() ? ': ' . e(whatsapp_display()) : '' ?></a></li><?php endif; ?>
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
      <span>© <?= date('Y') ?> <?= (setting('footer_text') && stripos(setting('footer_text'), setting('school_name')) === 0) ? e(setting('footer_text')) : e(setting('school_name')) . '. ' . e(setting('footer_text')) ?></span>
      <span class="fb-meta">
        <?php if (setting('moes_reg')): ?><span>MoES Reg: <?= e(setting('moes_reg')) ?></span><?php endif; ?>
        <?php if (setting('uneb_centre')): ?><span>UNEB Centre: <?= e(setting('uneb_centre')) ?></span><?php endif; ?>
        <a href="<?= url('page.php?slug=privacy') ?>">Privacy</a><a href="<?= url('admin/') ?>">Staff Login</a>
      </span>
    </div>
  </div>
</footer>
<?php if (whatsapp_link()): ?><a class="wa-fab" href="<?= e(whatsapp_link('Hello ' . setting('short_name', 'AFSS') . ', I need some help.')) ?>" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp"><svg viewBox="0 0 32 32" width="26" height="26" aria-hidden="true"><path fill="currentColor" d="M16 3C9 3 3.4 8.6 3.4 15.5c0 2.3.6 4.4 1.7 6.3L3 29l7.4-2c1.8 1 3.8 1.5 5.6 1.500 7 0 12.6-5.6 12.6-12.500S23 3 16 3zm0 22.800c-1.800 0-3.500-.5-5-1.400l-.4-.2-4.400 1.200 1.200-4.300-.3-.4a10.300 10.300 0 0 1-1.600-5.500C5.500 9.800 10.200 5.200 16 5.200s10.500 4.600 10.500 10.300S21.800 25.800 16 25.800zm5.800-7.700c-.3-.2-1.900-.9-2.200-1-.3-.1-.5-.2-.7.200-.2.300-.8 1-1 1.200-.2.200-.4.200-.7.100-.3-.2-1.400-.5-2.600-1.600-1-.9-1.600-1.900-1.800-2.200-.2-.3 0-.5.100-.7l.5-.5c.1-.2.200-.3.300-.5.100-.2.100-.4 0-.5-.1-.2-.7-1.700-1-2.300-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.100-.8.400-.3.300-1 1-1 2.400s1 2.800 1.200 3c.1.200 2 3.100 4.900 4.300.7.300 1.200.5 1.600.6.700.2 1.300.2 1.800.1.500-.1 1.900-.8 2.100-1.500.3-.7.300-1.400.2-1.500-.1-.2-.3-.2-.6-.4z"/></svg><span>WhatsApp support</span></a><?php endif; ?>
<a href="#top" class="to-top" aria-label="Back to top">↑</a>
<script src="<?= asset('assets/js/main.js') ?>"></script>
</body>
</html>
