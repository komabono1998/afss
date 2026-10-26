<?php
require_once __DIR__ . '/_layout.php';
require_perm('donate_page');

$fields = [
    'donate_intro' => ['Introduction', 'textarea', 'Shown at the top of the Donate page.'],
    'partnership_text' => ['Partnership text (HTML allowed)', 'html', 'Explain how companies, churches, NGOs and individuals can partner with the school.'],
    'partnership_types' => ['Ways to help (one per line)', 'textarea', 'These fill the “I would like to…” dropdown and the list on the page.'],
    'donate_thanks' => ['Thank-you message', 'textarea', 'Shown after someone sends the form.'],
];
if (is_post()) {
    csrf_check();
    foreach ($fields as $k => $_) save_setting($k, trim((string) ($_POST[$k] ?? '')));
    save_setting('donate_button', isset($_POST['donate_button']) ? '1' : '0');
    flash('Donate page saved.');
    redirect('admin/donate_page.php');
}
admin_head('Donate Page Text');
$active = (int) val('SELECT COUNT(*) FROM donation_methods WHERE active = 1');
?>
<div class="card">
  <h2>Giving options on the page</h2>
  <p class="<?= $active ? 'muted' : 'notice-card' ?>"><?= $active ? "$active giving option(s) are showing on the public page." : 'No giving option is switched on yet, so the page tells visitors your details are being updated.' ?></p>
  <div class="actions"><a class="btn accent" href="crud.php?e=donation_methods">Bank accounts, merchant codes &amp; payment links</a><a class="btn" href="crud.php?e=donation_causes">Causes donors can support</a><a class="btn ghost" href="inbox.php?t=donor_pledges">Donor enquiries</a><a class="btn ghost" href="<?= e(url('donate.php')) ?>" target="_blank">View page ↗</a></div>
</div>
<form class="card" method="post"><?= csrf_field() ?>
  <h2>Page text</h2>
  <?php foreach ($fields as $k => [$label, $type, $help]): $v = setting($k) !== '' ? setting($k) : donate_default($k); ?>
    <div class="f"><label><?= e($label) ?></label>
      <?php if ($type === 'html'): ?><textarea class="html" name="<?= e($k) ?>" style="min-height:160px"><?= e($v) ?></textarea>
      <?php else: ?><textarea name="<?= e($k) ?>"><?= e($v) ?></textarea><?php endif; ?>
      <small><?= e($help) ?></small></div>
  <?php endforeach; ?>
  <div class="f"><label><input type="checkbox" name="donate_button" value="1" <?= setting('donate_button') !== '0' ? 'checked' : '' ?>> Show the “Donate” button in the website header</label></div>
  <button class="btn accent">Save</button>
</form>
<?php admin_foot(); ?>
