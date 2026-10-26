<?php
require_once __DIR__ . '/_layout.php';
require_perm('welfare_page');

// which role may edit which block: nurse -> health, counsellor -> counselling, others -> everything
$only = $ME['role'] === 'school_nurse' ? 'health' : ($ME['role'] === 'counsellor' ? 'counselling' : null);
$blocks = [
    'health' => ['Health Services page', [
        'health_intro' => ['Introduction', 'textarea', 'Shown at the top of the page (this also updates the “Health Services” page text).'],
        'health_location' => ['Where the health unit is', 'text', ''],
        'health_hours' => ['Opening hours', 'textarea', ''],
        'health_contact' => ['Contact number', 'text', 'Leave empty to use the school phone.'],
        'health_parents_note' => ['Note for parents and guardians', 'textarea', ''],
        'health_topics' => ['“What do you need help with?” choices (one per line)', 'textarea', 'The “Other (type your own)” choice is always added automatically.'],
    ]],
    'counselling' => ['Counselling page', [
        'counselling_intro' => ['Introduction', 'textarea', 'Shown at the top of the page (this also updates the “Counselling and Career Centre” page text).'],
        'counselling_location' => ['Where the centre is', 'text', ''],
        'counselling_hours' => ['Opening hours', 'textarea', ''],
        'counselling_contact' => ['Contact number', 'text', 'Leave empty to use the school phone.'],
        'counselling_promise' => ['Confidentiality promise', 'textarea', 'Shown to learners before they ask for help.'],
        'counselling_topics' => ['“What would you like to talk about?” choices (one per line)', 'textarea', ''],
    ]],
    'shared' => ['Emergency information (both pages)', [
        'emergency_info' => ['Emergency numbers and advice', 'textarea', 'Shown in a highlighted box above both request forms. Keep numbers up to date.'],
    ]],
    'clubs' => ['Clubs page', ['clubs_intro' => ['Introduction', 'textarea', 'Shown at the top of the Clubs and Societies page.']]],
];
$show = array_filter($blocks, fn($k) => !$only || $k === $only || $k === 'shared', ARRAY_FILTER_USE_KEY);
if ($only) unset($show['clubs']);

if (is_post()) {
    csrf_check();
    foreach ($show as $b) foreach ($b[1] as $k => $_) save_setting($k, trim((string) ($_POST[$k] ?? '')));
    // keep the short intro on the standalone pages in step with the page text
    $allowed = [];
    foreach ($show as $b) foreach ($b[1] as $k => $_) $allowed[] = $k;     // only the blocks this role may edit
    foreach (['health_intro' => 'health-services', 'counselling_intro' => 'counselling', 'clubs_intro' => 'clubs'] as $k => $slug)
        if (in_array($k, $allowed, true) && isset($_POST[$k]) && trim($_POST[$k]) !== '') q('UPDATE pages SET body = ? WHERE slug = ?', ['<p>' . htmlspecialchars(trim($_POST[$k]), ENT_QUOTES, 'UTF-8') . '</p>', $slug]);
    flash('Saved.');
    redirect('admin/welfare_page.php');
}
admin_head('Welfare Page Text');
?>
<p class="muted">Edit the wording on the public Clubs, Health Services and Counselling pages. The list of services is under <a href="crud.php?e=welfare_services">Health &amp; Counselling Services</a>; clubs are under <a href="crud.php?e=clubs">Clubs &amp; Societies</a>.
  <a href="<?= e(url('health.php')) ?>" target="_blank">Health ↗</a> · <a href="<?= e(url('counselling.php')) ?>" target="_blank">Counselling ↗</a> · <a href="<?= e(url('clubs.php')) ?>" target="_blank">Clubs ↗</a></p>
<form method="post"><?= csrf_field() ?>
<?php foreach ($show as $b): ?>
  <div class="card"><h2><?= e($b[0]) ?></h2>
  <?php foreach ($b[1] as $k => [$label, $type, $help]): $v = setting($k) !== '' ? setting($k) : (welfare_settings()[$k] ?? ''); ?>
    <div class="f"><label><?= e($label) ?></label>
      <?php if ($type === 'textarea'): ?><textarea name="<?= e($k) ?>"><?= e($v) ?></textarea><?php else: ?><input type="text" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endif; ?>
      <?php if ($help): ?><small><?= e($help) ?></small><?php endif; ?></div>
  <?php endforeach; ?></div>
<?php endforeach; ?>
<button class="btn accent">Save all</button>
</form>
<?php admin_foot(); ?>
