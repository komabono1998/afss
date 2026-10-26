<?php
require_once __DIR__ . '/_layout.php';
require_perm('alumni_page');

$fields = [
    'alumni_name' => ['Association name', 'text', ''],
    'alumni_intro' => ['Page introduction', 'textarea', 'Shown at the top of the Alumni page.'],
    'alumni_about' => ['About the association (HTML allowed)', 'html', 'Who the alumni are and what the association does.'],
    'alumni_email' => ['Alumni contact email', 'text', 'Leave empty to use the school email.'],
    'alumni_phone' => ['Alumni contact phone', 'text', 'Leave empty to use the school phone.'],
    'alumni_prefix' => ['Membership number prefix', 'text', 'Numbers look like PREFIX/2026/0001, e.g. AFSSOSA/2026/0001.'],
    'alumni_validity_months' => ['Membership validity (months)', 'text', 'How long a card is valid from approval or renewal. Default 12.'],
    'alumni_card_signatory' => ['Signatory title on the card', 'text', 'e.g. Chairperson'],
    'alumni_card_note' => ['Text on the back of the card', 'textarea', 'Terms or a short note printed on the card back.'],
    'alumni_payment_info' => ['Membership fee & payment instructions', 'textarea', 'Shown on the join form, e.g. the fee and mobile-money or bank details. Leave empty to hide.'],
];
if (is_post()) {
    csrf_check();
    foreach ($fields as $k => $_) save_setting($k, in_array($k, ['alumni_about', 'alumni_intro', 'alumni_payment_info', 'alumni_card_note'], true) ? trim((string) ($_POST[$k] ?? '')) : post($k));
    save_setting('alumni_validity_months', (string) max(1, min(120, (int) post('alumni_validity_months') ?: 12)));
    save_setting('alumni_prefix', strtoupper(preg_replace('/[^A-Za-z0-9]/', '', post('alumni_prefix'))) ?: 'AFSSOSA');
    save_setting('alumni_require_photo', isset($_POST['alumni_require_photo']) ? '1' : '0');
    save_setting('alumni_require_proof', isset($_POST['alumni_require_proof']) ? '1' : '0');
    try {
        $new = handle_upload('constitution_file', ['pdf' => 'application/pdf'], UPLOAD_DIR, 'constitution_', 15);
        if (!empty($_POST['constitution_remove']) && setting('alumni_constitution')) { @unlink(ROOT . '/' . setting('alumni_constitution')); save_setting('alumni_constitution', ''); }
        if ($new) { if (setting('alumni_constitution')) @unlink(ROOT . '/' . setting('alumni_constitution')); save_setting('alumni_constitution', 'uploads/' . $new); }
    } catch (RuntimeException $ex) { flash('Constitution: ' . $ex->getMessage(), 'err'); redirect('admin/alumni_page.php'); }
    flash('Alumni page text saved.');
    redirect('admin/alumni_page.php');
}
admin_head('Alumni Page Text');
?>
<p class="muted">Edit the headline text of the public Alumni page. Leadership, activities, newsletters and members have their own screens in the Alumni section.
  <a href="<?= e(url('alumni.php')) ?>" target="_blank">View the page ↗</a></p>
<form class="card" method="post" enctype="multipart/form-data"><?= csrf_field() ?>
  <?php foreach ($fields as $k => [$label, $type, $help]): $v = setting($k) !== '' ? setting($k) : alumni_default($k); ?>
    <div class="f"><label><?= e($label) ?></label>
      <?php if ($type === 'textarea'): ?><textarea name="<?= e($k) ?>"><?= e($v) ?></textarea>
      <?php elseif ($type === 'html'): ?><textarea class="html" name="<?= e($k) ?>" style="min-height:180px"><?= e($v) ?></textarea>
      <?php else: ?><input type="text" name="<?= e($k) ?>" value="<?= e($k === 'alumni_email' || $k === 'alumni_phone' ? setting($k) : $v) ?>"><?php endif; ?>
      <?php if ($help): ?><small><?= e($help) ?></small><?php endif; ?></div>
  <?php endforeach; ?>
  <div class="f"><label>AFSSOSA constitution (PDF, max 15MB)</label>
    <?php if (setting('alumni_constitution')): ?><p><a href="<?= e(media(setting('alumni_constitution'))) ?>" target="_blank">Current constitution ↗</a> <label style="font-weight:400"><input type="checkbox" name="constitution_remove" value="1"> Remove</label></p><?php endif; ?>
    <input type="file" name="constitution_file" accept=".pdf,application/pdf"><small>Linked from the "I have read the constitution" tick-box on the join form.</small></div>
  <div class="f"><label><input type="checkbox" name="alumni_require_photo" value="1" <?= setting('alumni_require_photo') === '1' ? 'checked' : '' ?>> Require a photo to join</label></div>
  <div class="f"><label><input type="checkbox" name="alumni_require_proof" value="1" <?= setting('alumni_require_proof') === '1' ? 'checked' : '' ?>> Require proof of payment / membership to join</label></div>
  <button class="btn accent">Save</button>
</form>
<?php admin_foot(); ?>
