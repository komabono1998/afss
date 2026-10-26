<?php
require_once __DIR__ . '/_layout.php';
require_perm('population');

$fields = [
    'Students' => [
        'pop_students' => ['Total students', 'Leave empty to add boys and girls automatically.'],
        'pop_boys' => ['Boys', ''], 'pop_girls' => ['Girls', ''],
        'pop_o_level' => ['O Level students (S.1–S.4)', ''], 'pop_a_level' => ['A Level students (S.5–S.6)', ''],
        'pop_boarding' => ['Boarding students', ''], 'pop_day' => ['Day students', ''],
    ],
    'Staff' => [
        'pop_teaching' => ['Teaching staff', ''],
        'pop_support' => ['Other staff (non-teaching: administration, bursary, kitchen, security, maintenance)', ''],
    ],
];
if (is_post()) {
    csrf_check();
    foreach ($fields as $group) foreach ($group as $k => $_) {
        $v = preg_replace('/\D/', '', post($k));
        save_setting($k, $v === '' ? '' : (string) (int) $v);
    }
    save_setting('pop_updated', date('Y-m-d'));
    flash('School population figures saved.');
    redirect('admin/population.php');
}
admin_head('School Population');
$p = population();
?>
<div class="kpis" style="margin-bottom:1.2rem">
  <div class="kpi"><span class="kpi-ico">🎓</span><b><?= number_format($p['students']) ?></b><span>Students</span></div>
  <div class="kpi"><span class="kpi-ico">👩‍🏫</span><b><?= number_format($p['teaching']) ?></b><span>Teaching staff</span></div>
  <div class="kpi"><span class="kpi-ico">🧰</span><b><?= number_format($p['support']) ?></b><span>Other staff</span></div>
  <div class="kpi"><span class="kpi-ico">👥</span><b><?= number_format($p['staff']) ?></b><span>Total staff</span></div>
</div>
<form method="post"><?= csrf_field() ?>
<?php foreach ($fields as $g => $list): ?>
  <div class="card"><h2><?= e($g) ?></h2><div class="cols">
  <?php foreach ($list as $k => [$label, $help]): ?>
    <div class="f"><label><?= e($label) ?></label><input type="number" min="0" name="<?= e($k) ?>" value="<?= e(setting($k)) ?>">
      <?php if ($help): ?><small><?= e($help) ?></small><?php endif; ?></div>
  <?php endforeach; ?></div></div>
<?php endforeach; ?>
<p class="muted">These figures feed the admin dashboard and the headline numbers on the home page. <?= $p['updated'] ? 'Last updated ' . e(fmt_date($p['updated'], 'j F Y')) . '.' : '' ?></p>
<button class="btn accent">Save figures</button>
</form>
<?php admin_foot(); ?>
