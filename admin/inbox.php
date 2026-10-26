<?php
require_once __DIR__ . '/_layout.php';

// Table whitelist: label, statuses, list columns, searchable columns
$T = [
    'admissions' => ['table' => 'admissions_apps', 'label' => 'Admission Applications', 'statuses' => ['new', 'reviewing', 'accepted', 'rejected'],
        'cols' => ['ref' => 'Reference', 'first_name' => 'Applicant', 'entry_class' => 'Class', 'admission_type' => 'Type', 'guardian_phone' => 'Guardian phone', 'created_at' => 'Received', 'status' => 'Status'],
        'search' => ['ref', 'first_name', 'last_name', 'guardian_name', 'guardian_phone']],
    'scholarship_apps' => ['table' => 'scholarship_apps', 'label' => 'Scholarship Applications', 'statuses' => ['new', 'reviewing', 'awarded', 'declined'],
        'cols' => ['name' => 'Learner', 'scholarship_title' => 'Award', 'current_class' => 'Class', 'phone' => 'Phone', 'created_at' => 'Received', 'status' => 'Status'],
        'search' => ['name', 'scholarship_title', 'guardian_name', 'phone']],
    'job_apps' => ['table' => 'job_apps', 'label' => 'Job Applications', 'statuses' => ['new', 'reviewing', 'shortlisted', 'rejected'],
        'cols' => ['name' => 'Applicant', 'job_title' => 'Position', 'email' => 'Email', 'created_at' => 'Received', 'status' => 'Status'],
        'search' => ['name', 'email', 'job_title']],
    'messages' => ['table' => 'messages', 'label' => 'Messages', 'statuses' => ['new', 'read', 'replied'],
        'cols' => ['name' => 'From', 'subject' => 'Subject', 'email' => 'Email', 'created_at' => 'Received', 'status' => 'Status'],
        'search' => ['name', 'email', 'subject', 'message']],
];
$t = $_GET['t'] ?? '';
if (!isset($T[$t])) redirect('admin/index.php');
$C = $T[$t];
$table = $C['table'];
$self = 'inbox.php?t=' . $t;

if (is_post()) {
    csrf_check();
    $pid = (int) ($_POST['id'] ?? 0);
    $do = $_POST['do'] ?? '';
    if ($do === 'status' && in_array($_POST['status'] ?? '', $C['statuses'], true)) {
        q("UPDATE $table SET status = ? WHERE id = ?", [$_POST['status'], $pid]);
        flash('Status updated.');
        redirect("admin/$self&id=$pid");
    }
    if ($do === 'delete') {
        if ($t === 'job_apps') {
            $cv = val('SELECT cv_file FROM job_apps WHERE id = ?', [$pid]);
            if ($cv) @unlink(PRIVATE_DIR . '/' . basename($cv));
        }
        if ($t === 'scholarship_apps') { $fn = val('SELECT supporting_file FROM scholarship_apps WHERE id = ?', [$pid]); if ($fn) @unlink(PRIVATE_DIR . '/' . basename($fn)); }
        if ($t === 'admissions') foreach (['results_file', 'recommendation_file'] as $cf) { $fn = val("SELECT $cf FROM admissions_apps WHERE id = ?", [$pid]); if ($fn) @unlink(PRIVATE_DIR . '/' . basename($fn)); }
        q("DELETE FROM $table WHERE id = ?", [$pid]);
        flash('Deleted.');
        redirect("admin/$self");
    }
}

// CSV export
if (($_GET['export'] ?? '') === '1') {
    $data = rows("SELECT * FROM $table ORDER BY id DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $t . '-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    if ($data) {
        fputcsv($out, array_keys($data[0]));
        foreach ($data as $r) fputcsv($out, array_map(fn($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v, $r));
    }
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    $r = row("SELECT * FROM $table WHERE id = ?", [$id]);
    if ($r && $r['status'] === 'new') { q("UPDATE $table SET status = ? WHERE id = ?", [$t === 'messages' ? 'read' : 'reviewing', $id]); $r['status'] = $t === 'messages' ? 'read' : 'reviewing'; }
    admin_head($C['label']);
    if (!$r) { echo '<div class="alert alert-err">Record not found.</div>'; admin_foot(); exit; }
    ?>
    <p class="no-print"><a href="<?= e($self) ?>">← Back to <?= e($C['label']) ?></a></p>
    <div class="card">
      <dl class="kv">
        <?php foreach ($r as $k => $v): if ($v === null || $v === '' || $k === 'id') continue; ?>
          <dt><?= e(ucwords(str_replace('_', ' ', $k))) ?></dt>
          <dd><?php
            if (in_array($k, ['cv_file', 'results_file', 'recommendation_file', 'supporting_file'], true)) echo '<a href="download.php?f=' . urlencode($v) . '">⬇ Download ' . ucwords(str_replace('_file', '', $k)) . ' (PDF)</a>';
            elseif ($k === 'email' || $k === 'guardian_email') echo '<a href="mailto:' . e($v) . '">' . e($v) . '</a>';
            elseif ($k === 'status') echo '<span class="badge ' . e($v) . '">' . e($v) . '</span>';
            else echo e($v); ?></dd>
        <?php endforeach; ?>
      </dl>
    </div>
    <div class="card no-print"><div class="actions">
      <form method="post" action="<?= e($self) ?>" style="display:flex;gap:.5rem"><?= csrf_field() ?><input type="hidden" name="do" value="status"><input type="hidden" name="id" value="<?= $id ?>">
        <select name="status" style="padding:.4rem;border-radius:8px"><?php foreach ($C['statuses'] as $s): ?><option <?= $r['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
        <button class="btn">Update status</button></form>
      <button class="btn ghost" onclick="window.print()">Print</button>
      <form method="post" action="<?= e($self) ?>" data-confirm="Delete this record permanently?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger">Delete</button></form>
    </div></div>
    <?php admin_foot(); exit;
}

$q = trim($_GET['q'] ?? '');
$st = $_GET['status'] ?? '';
$where = []; $params = [];
if ($q !== '') { $where[] = '(' . implode(' OR ', array_map(fn($c) => "$c LIKE ?", $C['search'])) . ')'; foreach ($C['search'] as $_) $params[] = "%$q%"; }
if (in_array($st, $C['statuses'], true)) { $where[] = 'status = ?'; $params[] = $st; }
$items = rows("SELECT * FROM $table" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT 500', $params);
admin_head($C['label']);
?>
<div class="bar">
  <form method="get" style="display:flex;gap:.4rem;flex-wrap:wrap"><input type="hidden" name="t" value="<?= e($t) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search…">
    <select name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach ($C['statuses'] as $s): ?><option <?= $st === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
    <button class="btn ghost">Filter</button></form>
  <span class="grow"></span><a class="btn ghost" href="<?= e($self) ?>&export=1">⬇ Export CSV</a>
</div>
<div class="tablewrap"><table>
  <thead><tr><?php foreach ($C['cols'] as $l) echo '<th>' . e($l) . '</th>'; ?></tr></thead><tbody>
  <?php foreach ($items as $it): ?><tr>
    <?php foreach ($C['cols'] as $c => $l): $v = $it[$c]; ?><td><?php
      if ($c === 'status') echo '<span class="badge ' . e($v) . '">' . e($v) . '</span>';
      elseif ($c === 'created_at') echo e(date('j M Y, H:i', strtotime($v)));
      elseif ($c === 'first_name') echo '<a href="' . e($self) . '&id=' . $it['id'] . '"><strong>' . e($v . ' ' . $it['last_name']) . '</strong></a>';
      elseif ($c === array_key_first($C['cols']) && $c !== 'ref' || $c === 'ref') echo '<a href="' . e($self) . '&id=' . $it['id'] . '"><strong>' . e($v) . '</strong></a>';
      else echo e($v); ?></td><?php endforeach; ?>
  </tr><?php endforeach; if (!$items): ?><tr><td colspan="<?= count($C['cols']) ?>" class="muted">Nothing here yet.</td></tr><?php endif; ?>
  </tbody></table></div>
<?php admin_foot(); ?>
