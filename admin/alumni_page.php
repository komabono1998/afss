<?php
require_once __DIR__ . '/_layout.php';
require_perm('alumni_page');

$fields = [
    'alumni_name' => ['Association name', 'text', ''],
    'alumni_intro' => ['Page introduction', 'textarea', 'Shown at the top of the Alumni page.'],
    'alumni_about' => ['About the association (HTML allowed)', 'html', 'Who the alumni are and what the association does.'],
    'alumni_email' => ['Alumni contact email', 'text', 'Leave empty to use the school email.'],
    'alumni_phone' => ['Alumni contact phone', 'text', 'Leave empty to use the school phone.'],
];
if (is_post()) {
    csrf_check();
    foreach ($fields as $k => $_) save_setting($k, $k === 'alumni_about' || $k === 'alumni_intro' ? trim((string) ($_POST[$k] ?? '')) : post($k));
    flash('Alumni page text saved.');
    redirect('admin/alumni_page.php');
}
admin_head('Alumni Page Text');
?>
<p class="muted">Edit the headline text of the public Alumni page. Leadership, activities, newsletters and members have their own screens in the Alumni section.
  <a href="<?= e(url('alumni.php')) ?>" target="_blank">View the page ↗</a></p>
<form class="card" method="post"><?= csrf_field() ?>
  <?php foreach ($fields as $k => [$label, $type, $help]): $v = setting($k) !== '' ? setting($k) : alumni_default($k); ?>
    <div class="f"><label><?= e($label) ?></label>
      <?php if ($type === 'textarea'): ?><textarea name="<?= e($k) ?>"><?= e($v) ?></textarea>
      <?php elseif ($type === 'html'): ?><textarea class="html" name="<?= e($k) ?>" style="min-height:180px"><?= e($v) ?></textarea>
      <?php else: ?><input type="text" name="<?= e($k) ?>" value="<?= e($k === 'alumni_email' || $k === 'alumni_phone' ? setting($k) : $v) ?>"><?php endif; ?>
      <?php if ($help): ?><small><?= e($help) ?></small><?php endif; ?></div>
  <?php endforeach; ?>
  <button class="btn accent">Save</button>
</form>
<?php admin_foot(); ?>
