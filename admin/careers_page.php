<?php
require_once __DIR__ . '/_layout.php';
require_perm('careers_page');

$fields = [
    'careers_heading' => ['Heading', 'text', 'The main heading at the top of the Careers page.'],
    'careers_intro' => ['Introduction', 'textarea', 'Why someone should work at the school.'],
    'careers_benefits' => ['Reasons to join (one per line)', 'textarea', 'Shown as a short tick list.'],
    'careers_how' => ['How to apply', 'textarea', 'Shown in the highlighted box next to the introduction.'],
];
if (is_post()) {
    csrf_check();
    foreach ($fields as $k => $_) if (array_key_exists($k, $_POST)) save_setting($k, trim((string) $_POST[$k]));
    flash('Careers page saved.');
    redirect('admin/careers_page.php');
}
admin_head('Careers Page Text');
$open = (int) val("SELECT COUNT(*) FROM jobs WHERE status='open'");
$apps = (int) val("SELECT COUNT(*) FROM job_apps WHERE status='new'");
?>
<div class="card">
  <h2>Recruitment</h2>
  <p class="muted"><?= $open ?> vacancy(ies) open · <?= $apps ?> new application(s).</p>
  <div class="actions"><a class="btn accent" href="crud.php?e=jobs">Manage job vacancies</a><a class="btn" href="inbox.php?t=job_apps">Review applications</a><a class="btn ghost" href="<?= url('careers.php') ?>" target="_blank">View careers page ↗</a></div>
</div>
<form class="card" method="post"><?= csrf_field() ?>
  <h2>Page text</h2>
  <?php foreach ($fields as $k => [$label, $type, $help]): ?>
    <div class="f"><label><?= e($label) ?></label>
      <?php if ($type === 'text'): ?><input type="text" name="<?= e($k) ?>" value="<?= e(setting($k)) ?>">
      <?php else: ?><textarea name="<?= e($k) ?>"><?= e(setting($k)) ?></textarea><?php endif; ?>
      <small><?= e($help) ?></small></div>
  <?php endforeach; ?>
  <button class="btn accent">Save</button>
</form>
<?php admin_foot(); ?>
