<?php
require_once __DIR__ . '/_layout.php';

// Table whitelist: label, statuses, list columns, searchable columns
$T = [
    'admissions' => ['table' => 'admissions_apps', 'label' => 'Admission Applications', 'statuses' => ['new', 'reviewing', 'accepted', 'rejected'],
        'cols' => ['ref' => 'Reference', 'first_name' => 'Applicant', 'district' => 'District', 'entry_class' => 'Class', 'admission_type' => 'Type', 'guardian_phone' => 'Guardian phone', 'created_at' => 'Received', 'status' => 'Status'],
        'pdf' => ['ref', 'first_name', 'last_name', 'gender', 'dob', 'district', 'level_applied', 'entry_class', 'admission_type', 'guardian_name', 'guardian_phone', 'created_at', 'status'],
        'search' => ['ref', 'first_name', 'last_name', 'guardian_name', 'guardian_phone']],
    'scholarship_apps' => ['table' => 'scholarship_apps', 'label' => 'Scholarship Applications', 'statuses' => ['new', 'reviewing', 'awarded', 'declined'],
        'cols' => ['name' => 'Learner', 'scholarship_title' => 'Award', 'current_class' => 'Class', 'phone' => 'Phone', 'created_at' => 'Received', 'status' => 'Status'],
        'pdf' => ['name', 'scholarship_title', 'current_class', 'school', 'guardian_name', 'phone', 'created_at', 'status'],
        'search' => ['name', 'scholarship_title', 'guardian_name', 'phone']],
    'alumni_members' => ['table' => 'alumni_members', 'label' => 'Alumni Members', 'statuses' => ['new', 'verified', 'ignored'],
        'cols' => ['name' => 'Name', 'year_left' => 'Year left', 'profession' => 'Profession', 'email' => 'Email', 'phone' => 'Phone', 'created_at' => 'Joined', 'status' => 'Status'],
        'pdf' => ['name', 'year_left', 'profession', 'workplace', 'email', 'phone', 'location', 'newsletter', 'consent', 'created_at', 'status'],
        'search' => ['name', 'email', 'phone', 'year_left', 'profession', 'workplace', 'location']],
    'donor_pledges' => ['table' => 'donor_pledges', 'label' => 'Donor & Partner Enquiries', 'statuses' => ['new', 'contacted', 'received', 'closed'],
        'cols' => ['name' => 'Name', 'kind' => 'Interest', 'amount_text' => 'Amount / offer', 'email' => 'Email', 'phone' => 'Phone', 'created_at' => 'Received', 'status' => 'Status'],
        'pdf' => ['name', 'organisation', 'kind', 'cause', 'amount_text', 'reference', 'email', 'phone', 'created_at', 'status'],
        'search' => ['name', 'organisation', 'email', 'phone', 'kind', 'cause', 'message']],
    'job_apps' => ['table' => 'job_apps', 'label' => 'Job Applications', 'statuses' => ['new', 'reviewing', 'shortlisted', 'rejected'],
        'cols' => ['name' => 'Applicant', 'job_title' => 'Position', 'email' => 'Email', 'created_at' => 'Received', 'status' => 'Status'],
        'pdf' => ['name', 'job_title', 'email', 'phone', 'qualification', 'created_at', 'status'],
        'search' => ['name', 'email', 'job_title']],
    'messages' => ['table' => 'messages', 'label' => 'Messages', 'statuses' => ['new', 'read', 'replied'],
        'cols' => ['name' => 'From', 'subject' => 'Subject', 'email' => 'Email', 'created_at' => 'Received', 'status' => 'Status'],
        'pdf' => ['name', 'email', 'phone', 'subject', 'message', 'created_at', 'status'],
        'search' => ['name', 'email', 'subject', 'message']],
];
$t = $_GET['t'] ?? '';
if (!isset($T[$t])) redirect('admin/index.php');
require_perm($t);
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
    if ($do === 'enrol' && $t === 'admissions') {
        if (!can('students_manage')) deny();
        $app = row('SELECT * FROM admissions_apps WHERE id = ?', [$pid]);
        $cs = (int) ($_POST['cs'] ?? 0);
        if (!$app) redirect("admin/$self");
        if (val('SELECT COUNT(*) FROM students WHERE application_ref = ?', [$app['ref']])) { flash('This applicant is already in the student database.', 'err'); redirect("admin/$self&id=$pid"); }
        if (!$cs || !can_manage_student($cs)) { flash('Choose a class/stream to place the student in.', 'err'); redirect("admin/$self&id=$pid"); }
        [$sid, $reg] = add_student(['first_name' => $app['first_name'], 'last_name' => $app['last_name'], 'gender' => $app['gender'], 'dob' => $app['dob'] ?: null, 'district' => $app['district'] ?? '',
            'guardian_name' => $app['guardian_name'], 'guardian_phone' => $app['guardian_phone'], 'guardian_email' => $app['guardian_email'], 'application_ref' => $app['ref']], $cs, 'admission application ' . $app['ref']);
        q("UPDATE admissions_apps SET status = 'accepted' WHERE id = ?", [$pid]);
        flash("{$app['first_name']} {$app['last_name']} was registered as a student: $reg.");
        redirect("admin/students.php?a=view&id=$sid");
    }
    if ($do === 'delete') {
        if ($t === 'job_apps') {
            $cv = val('SELECT cv_file FROM job_apps WHERE id = ?', [$pid]);
            if ($cv) @unlink(PRIVATE_DIR . '/' . basename($cv));
        }
        if ($t === 'alumni_members') foreach (['photo', 'proof_file'] as $cf) { $fn = val("SELECT $cf FROM alumni_members WHERE id = ?", [$pid]); if ($fn) @unlink(PRIVATE_DIR . '/' . basename($fn)); }
        if ($t === 'scholarship_apps') { $fn = val('SELECT supporting_file FROM scholarship_apps WHERE id = ?', [$pid]); if ($fn) @unlink(PRIVATE_DIR . '/' . basename($fn)); }
        if ($t === 'admissions') foreach (['results_file', 'recommendation_file'] as $cf) { $fn = val("SELECT $cf FROM admissions_apps WHERE id = ?", [$pid]); if ($fn) @unlink(PRIVATE_DIR . '/' . basename($fn)); }
        q("DELETE FROM $table WHERE id = ?", [$pid]);
        flash('Deleted.');
        redirect("admin/$self");
    }
}

// filters (shared by the list and the exports)
$q = trim($_GET['q'] ?? '');
$st = $_GET['status'] ?? '';
$where = []; $params = [];
if ($q !== '') { $where[] = '(' . implode(' OR ', array_map(fn($c) => "$c LIKE ?", $C['search'])) . ')'; foreach ($C['search'] as $_) $params[] = "%$q%"; }
if (in_array($st, $C['statuses'], true)) { $where[] = 'status = ?'; $params[] = $st; }
$fileKeys = ['cv_file', 'results_file', 'recommendation_file', 'supporting_file', 'photo', 'proof_file'];

// export: CSV / Excel / PDF (respects the current search and status filter)
if (!empty($_GET['export'])) {
    $fmt = $_GET['export'] === '1' ? 'csv' : (string) $_GET['export'];
    $data = rows("SELECT * FROM $table" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC', $params);
    $keys = $fmt === 'pdf' ? ($C['pdf'] ?? array_keys($C['cols'])) : array_values(array_diff(array_keys($data[0] ?? row("SELECT * FROM $table") ?? $C['cols']), ['id', 'token']));
    if (!$data && $fmt !== 'pdf') $keys = array_keys($C['cols']);
    $cell = function ($k, $v) use ($fileKeys) {
        if (in_array($k, $fileKeys, true)) return $v ? 'Attached' : '';
        if (in_array($k, ['newsletter', 'consent'], true)) return $v ? 'Yes' : 'No';
        return $v;
    };
    export_send($fmt, $C['label'], array_map('export_label', $keys), array_map(fn($r) => array_map(fn($k) => $cell($k, $r[$k] ?? ''), $keys), $data), $t);
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
        <?php foreach ($r as $k => $v): if ($v === null || $v === '' || $k === 'id' || $k === 'token' || ($v === 0 && !in_array($k, ['newsletter', 'consent'], true))) continue; ?>
          <dt><?= e(ucwords(str_replace('_', ' ', $k))) ?></dt>
          <dd><?php
            if (in_array($k, $fileKeys, true)) {
                $lab = ['cv_file' => 'CV', 'results_file' => 'results', 'recommendation_file' => 'recommendation letter', 'supporting_file' => 'supporting documents', 'photo' => 'photo', 'proof_file' => 'proof of payment / membership'][$k];
                if ($k === 'photo') echo '<img src="download.php?f=' . urlencode($v) . '&inline=1" alt="" style="max-width:160px;max-height:200px;border-radius:10px;display:block;margin-bottom:.4rem">';
                elseif ($k === 'proof_file' && preg_match('/\.(jpe?g|png)$/i', $v)) echo '<a href="download.php?f=' . urlencode($v) . '&inline=1" target="_blank"><img src="download.php?f=' . urlencode($v) . '&inline=1" alt="" style="max-width:220px;max-height:160px;border-radius:8px;display:block;margin-bottom:.4rem"></a>';
                echo '<a href="download.php?f=' . urlencode($v) . '">⬇ Download ' . $lab . '</a>';
            }
            elseif (in_array($k, ['newsletter', 'consent'], true)) echo $v ? 'Yes' : 'No';
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
      <?php if ($t === 'admissions' && can('students_manage')): $enrolled = row('SELECT id,reg_no FROM students WHERE application_ref = ?', [$r['ref']]); ?>
        <?php if ($enrolled): ?><a class="btn" href="students.php?a=view&id=<?= $enrolled['id'] ?>">Student <?= e($enrolled['reg_no']) ?> →</a>
        <?php else: ?><form method="post" action="<?= e($self) ?>" style="display:flex;gap:.4rem"><?= csrf_field() ?><input type="hidden" name="do" value="enrol"><input type="hidden" name="id" value="<?= $id ?>">
          <select name="cs" required style="padding:.4rem;border-radius:8px"><option value="">Enrol in class…</option><?php foreach (class_streams() as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['label']) ?></option><?php endforeach; ?></select><button class="btn accent">Register as student</button></form><?php endif; ?>
      <?php endif; ?>
      <button class="btn ghost" onclick="window.print()">Print</button>
      <form method="post" action="<?= e($self) ?>" data-confirm="Delete this record permanently?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger">Delete</button></form>
    </div></div>
    <?php admin_foot(); exit;
}

$items = rows("SELECT * FROM $table" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT 500', $params);
admin_head($C['label']);
?>
<div class="bar">
  <form method="get" style="display:flex;gap:.4rem;flex-wrap:wrap"><input type="hidden" name="t" value="<?= e($t) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search…">
    <select name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach ($C['statuses'] as $s): ?><option <?= $st === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
    <button class="btn ghost">Filter</button></form>
  <span class="grow"></span><?= export_buttons($self . ($q !== '' ? '&q=' . urlencode($q) : '') . ($st !== '' ? '&status=' . urlencode($st) : '')) ?>
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
