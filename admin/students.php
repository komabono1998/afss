<?php
require_once __DIR__ . '/_layout.php';
require_perm('students');

$a = $_GET['a'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);
$streams = class_streams();
$byId = [];
foreach ($streams as $c) $byId[(int) $c['id']] = $c;
$canManage = can('students_manage');
$mine = manageable_streams();
$mineIds = array_map(fn($c) => (int) $c['id'], $mine);
$isClassTeacher = $ME['role'] === 'class_teacher';
$canDelete = in_array($ME['role'], ['admin', 'headteacher'], true);
$sheetTypes = ['xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'csv' => 'text/csv'];

function stream_options(array $list, $selected = null, bool $allowNone = false, string $placeholder = 'Select class / stream…'): string
{
    $o = '<option value="">' . e($placeholder) . '</option>';
    if ($allowNone) $o .= '<option value="none"' . ($selected === 'none' ? ' selected' : '') . '>No class assigned</option>';
    foreach ($list as $c) $o .= '<option value="' . $c['id'] . '"' . ((string) $selected === (string) $c['id'] ? ' selected' : '') . '>' . e($c['label']) . '</option>';
    return $o;
}
function status_badge(string $s): string
{
    $cls = ['present' => 'yes', 'dismissed' => 'rejected', 'transferred' => 'reviewing', 'completed' => 'no'][$s] ?? '';
    return '<span class="badge ' . $cls . '">' . e(STUDENT_STATUSES[$s] ?? $s) . '</span>';
}
function set_status(int $sid, string $status, string $reason): void
{
    $old = val('SELECT status FROM students WHERE id = ?', [$sid]);
    q('UPDATE students SET status = ?, status_reason = ?, status_date = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$status, $status === 'present' ? '' : $reason, date('Y-m-d'), $sid]);
    student_log($sid, 'status', (STUDENT_STATUSES[$old] ?? $old) . ' → ' . STUDENT_STATUSES[$status] . ($reason !== '' ? ": $reason" : ''));
}
function move_student(int $sid, ?int $to, array $byId): void
{
    $from = val('SELECT class_stream_id FROM students WHERE id = ?', [$sid]);
    q('UPDATE students SET class_stream_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$to, $sid]);
    student_log($sid, 'class', ($from ? ($byId[(int) $from]['label'] ?? "#$from") : 'none') . ' → ' . ($to ? ($byId[$to]['label'] ?? "#$to") : 'none'));
}

// ------------------------------------------------------------------ exports + template (before any output)
if ($a === 'template') {
    export_send('xlsx', 'Student upload template', ['Surname', 'First Name', 'Other Names', 'Gender', 'Date of Birth', 'District', 'Guardian Name', 'Guardian Phone', 'Guardian Email', 'LIN / ID Number'], [], 'student-upload-template');
}
if (!empty($_GET['export'])) {
    [$w, $p] = student_filters($_GET);
    $data = rows(STUDENT_SELECT . $w . ' ORDER BY c.sort, c.class_name, c.stream_name, s.last_name, s.first_name', $p);
    [$h, $r] = student_export_table($data, true);
    export_send($_GET['export'] === '1' ? 'csv' : (string) $_GET['export'], 'Students', $h, $r, 'students');
}

// ------------------------------------------------------------------ POST actions
if (is_post()) {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if (!$canManage) deny();
    $back = 'admin/students.php';

    if ($do === 'save') {
        $sid = (int) ($_POST['id'] ?? 0);
        $rec = $sid ? row('SELECT * FROM students WHERE id = ?', [$sid]) : null;
        if ($sid && !$rec) redirect($back);
        if ($rec && !can_manage_student($rec['class_stream_id'] !== null ? (int) $rec['class_stream_id'] : null)) deny();
        $to = (int) ($_POST['class_stream_id'] ?? 0) ?: null;
        if (($to === null && $isClassTeacher) || ($to !== null && !can_manage_student($to))) { flash('You can only place students in your own class.', 'err'); redirect($back . ($sid ? "?a=edit&id=$sid" : '?a=new')); }
        $d = ['first_name' => post('first_name'), 'last_name' => post('last_name'), 'other_names' => post('other_names'), 'gender' => in_array(post('gender'), ['Male', 'Female'], true) ? post('gender') : '',
            'dob' => post('dob') !== '' && strtotime(post('dob')) ? date('Y-m-d', strtotime(post('dob'))) : null, 'district' => post('district'),
            'guardian_name' => post('guardian_name'), 'guardian_phone' => post('guardian_phone'), 'guardian_email' => post('guardian_email'), 'external_id' => post('external_id')];
        if ($d['first_name'] === '') { flash('First name is required.', 'err'); redirect($back . ($sid ? "?a=edit&id=$sid" : '?a=new')); }
        if ($rec) {
            q('UPDATE students SET first_name=?,last_name=?,other_names=?,gender=?,dob=?,district=?,guardian_name=?,guardian_phone=?,guardian_email=?,external_id=?,notes=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',
                [$d['first_name'], $d['last_name'], $d['other_names'], $d['gender'], $d['dob'], $d['district'], $d['guardian_name'], $d['guardian_phone'], $d['guardian_email'], $d['external_id'], trim((string) $_POST['notes']), $sid]);
            student_log($sid, 'edited', 'Details updated');
            if ((int) $rec['class_stream_id'] !== (int) $to) move_student($sid, $to, $byId);
            $st = post('status');
            if (isset(STUDENT_STATUSES[$st]) && ($st !== $rec['status'] || post('status_reason') !== (string) $rec['status_reason'])) set_status($sid, $st, post('status_reason'));
            flash('Student updated.');
            redirect("$back?a=view&id=$sid");
        }
        [$newId, $reg] = add_student($d, $to, 'added manually');
        if (trim((string) $_POST['notes']) !== '') q('UPDATE students SET notes = ? WHERE id = ?', [trim((string) $_POST['notes']), $newId]);
        flash("Registered {$d['first_name']} {$d['last_name']} — registration number $reg.");
        redirect("$back?a=view&id=$newId");
    }

    if ($do === 'status') {
        $sid = (int) $_POST['id'];
        $rec = row('SELECT class_stream_id FROM students WHERE id = ?', [$sid]);
        if (!$rec || !can_manage_student($rec['class_stream_id'] !== null ? (int) $rec['class_stream_id'] : null)) deny();
        $st = post('status');
        if (!isset(STUDENT_STATUSES[$st])) { flash('Choose a status.', 'err'); redirect("$back?a=view&id=$sid"); }
        set_status($sid, $st, post('status_reason'));
        flash('Status changed to ' . STUDENT_STATUSES[$st] . '.');
        redirect("$back?a=view&id=$sid");
    }

    if ($do === 'bulk') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $op = post('op'); $n = 0;
        foreach ($ids as $sid) {
            $rec = row('SELECT class_stream_id FROM students WHERE id = ?', [$sid]);
            if (!$rec || !can_manage_student($rec['class_stream_id'] !== null ? (int) $rec['class_stream_id'] : null)) continue;
            if ($op === 'move') {
                $to = (int) ($_POST['to_cs'] ?? 0);
                if (!$to || !can_manage_student($to)) continue;
                move_student($sid, $to, $byId); $n++;
            } elseif ($op === 'status' && isset(STUDENT_STATUSES[post('to_status')])) {
                set_status($sid, post('to_status'), post('status_reason')); $n++;
            }
        }
        flash($n ? "$n student(s) updated." : 'Nothing was changed. Tick students and choose an action you are allowed to do.', $n ? 'ok' : 'err');
        redirect($back . (!empty($_POST['return']) ? '?' . ltrim((string) $_POST['return'], '?') : ''));
    }

    if ($do === 'delete' && $canDelete) {
        $sid = (int) $_POST['id'];
        q('DELETE FROM student_log WHERE student_id = ?', [$sid]);
        q('DELETE FROM students WHERE id = ?', [$sid]);
        flash('Student record deleted permanently.');
        redirect($back);
    }

    if ($do === 'import_preview') {
        $cs = (int) ($_POST['cs'] ?? 0);
        if (!$cs || !can_manage_student($cs)) { flash('Choose one of your classes first.', 'err'); redirect("$back?a=upload"); }
        if (!is_dir(ROOT . '/data/imports')) mkdir(ROOT . '/data/imports', 0775, true);
        foreach (glob(ROOT . '/data/imports/imp_*') ?: [] as $old) if (filemtime($old) < time() - 86400) @unlink($old);   // tidy leftovers
        $ext = strtolower(pathinfo((string) ($_FILES['sheet']['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === 'xls') { flash('Old .xls files are not supported. In Excel choose File → Save As → “Excel Workbook (.xlsx)” or “CSV”, then upload that.', 'err'); redirect("$back?a=upload"); }
        try { $stored = handle_upload('sheet', $sheetTypes, ROOT . '/data/imports', 'imp_', 15); }
        catch (RuntimeException $ex) { flash($ex->getMessage(), 'err'); redirect("$back?a=upload"); }
        if (!$stored) { flash('Choose a spreadsheet to upload.', 'err'); redirect("$back?a=upload"); }
        redirect("$back?a=upload&token=" . urlencode($stored) . "&cs=$cs");
    }

    if ($do === 'import_confirm') {
        $cs = (int) ($_POST['cs'] ?? 0); $token = (string) ($_POST['token'] ?? '');
        if (!$cs || !can_manage_student($cs) || !preg_match('/^imp_[a-f0-9]{16}\.(xlsx|csv)$/', $token) || !is_file(ROOT . '/data/imports/' . $token)) { flash('The upload expired. Please upload the file again.', 'err'); redirect("$back?a=upload"); }
        $path = ROOT . '/data/imports/' . $token;
        try { $sheet = import_read($path, pathinfo($token, PATHINFO_EXTENSION)); } catch (RuntimeException $ex) { flash($ex->getMessage(), 'err'); redirect("$back?a=upload"); }
        $map = student_import_map($sheet[0] ?? []);
        if (!isset($map['first_name']) && !isset($map['full_name'])) { flash('The first row must contain column names such as Surname and First Name.', 'err'); redirect("$back?a=upload"); }
        $added = $dups = $bad = 0; $seen = [];
        set_time_limit(300);
        db()->beginTransaction();
        try {
            foreach (array_slice($sheet, 1) as $row) {
                if (!array_filter($row, fn($v) => trim((string) $v) !== '')) continue;
                [$d, $err] = student_import_row($row, $map);
                if (!$d) { $bad++; continue; }
                $key = mb_strtolower($d['first_name'] . '|' . $d['last_name'] . '|' . $d['dob']);
                if (isset($seen[$key]) || student_exists($d)) { $dups++; continue; }
                $seen[$key] = true;
                add_student($d, $cs, 'spreadsheet upload');
                $added++;
            }
            db()->commit();
        } catch (Throwable $ex) { db()->rollBack(); flash('Import stopped and nothing was saved: ' . $ex->getMessage(), 'err'); redirect("$back?a=upload"); }
        @unlink($path);
        flash("Import finished for {$byId[$cs]['label']}: $added student(s) registered" . ($dups ? ", $dups duplicate(s) skipped" : '') . ($bad ? ", $bad row(s) without a name skipped" : '') . '.');
        redirect("$back?cs=$cs&status=all");
    }
    redirect($back);
}

// ------------------------------------------------------------------ views
$filterKeys = ['q', 'cs', 'district', 'gender', 'status'];
$filters = array_filter(array_intersect_key($_GET, array_flip($filterKeys)), fn($v) => $v !== '');
$qs = http_build_query($filters);

// ---- upload (step 1 form, step 2 preview)
if ($a === 'upload') {
    if (!$canManage) deny();
    admin_head('Upload Students');
    $token = (string) ($_GET['token'] ?? ''); $cs = (int) ($_GET['cs'] ?? 0);
    ?>
    <p><a href="students.php">← Student database</a></p>
    <?php if ($token !== '' && $cs && can_manage_student($cs) && preg_match('/^imp_[a-f0-9]{16}\.(xlsx|csv)$/', $token) && is_file(ROOT . '/data/imports/' . $token)):
        try { $sheet = import_read(ROOT . '/data/imports/' . $token, pathinfo($token, PATHINFO_EXTENSION)); } catch (RuntimeException $ex) { $sheet = []; echo '<div class="alert alert-err">' . e($ex->getMessage()) . '</div>'; }
        $header = $sheet[0] ?? []; $map = student_import_map($header);
        $ok = isset($map['first_name']) || isset($map['full_name']);
        $valid = $dups = $bad = 0; $issues = []; $preview = []; $seen = [];
        foreach (array_slice($sheet, 1) as $i => $row) {
            if (!array_filter($row, fn($v) => trim((string) $v) !== '')) continue;
            $line = $i + 2;
            [$d, $err, $warn] = student_import_row($row, $map);
            if (!$d) { $bad++; if (count($issues) < 30) $issues[] = "Row $line: no name — skipped"; continue; }
            $key = mb_strtolower($d['first_name'] . '|' . $d['last_name'] . '|' . $d['dob']);
            if (isset($seen[$key]) || student_exists($d)) { $dups++; if (count($issues) < 30) $issues[] = "Row $line: {$d['last_name']} {$d['first_name']} already exists — skipped"; continue; }
            $seen[$key] = true; $valid++;
            foreach ($warn as $wmsg) if (count($issues) < 30) $issues[] = "Row $line: {$d['last_name']} {$d['first_name']} — $wmsg";
            if (count($preview) < 12) $preview[] = $d;
        }
        ?>
        <div class="card"><h2>Check before importing → <?= e($byId[$cs]['label']) ?></h2>
          <?php if (!$ok): ?><div class="alert alert-err">Could not find name columns. The first row must contain headings such as <strong>Surname</strong> and <strong>First Name</strong>. Download the template below, fill it in and upload again.</div>
          <?php else: ?>
          <div class="kpis" style="margin-bottom:1rem">
            <div class="kpi"><b><?= $valid ?></b><span>Ready to register</span></div>
            <div class="kpi"><b><?= $dups ?></b><span>Duplicates (skipped)</span></div>
            <div class="kpi"><b><?= $bad ?></b><span>Rows without a name</span></div>
          </div>
          <p class="muted">Columns recognised: <?= e(implode(', ', array_map(fn($f) => export_label($f), array_keys($map)))) ?>.</p>
          <div class="tablewrap" style="margin-bottom:1rem"><table><thead><tr><th>Surname</th><th>First name</th><th>Gender</th><th>Date of birth</th><th>District</th><th>Guardian</th><th>Phone</th></tr></thead><tbody>
            <?php foreach ($preview as $d): ?><tr><td><?= e($d['last_name']) ?></td><td><?= e($d['first_name'] . ' ' . $d['other_names']) ?></td><td><?= e($d['gender']) ?></td><td><?= e($d['dob']) ?></td><td><?= e($d['district']) ?></td><td><?= e($d['guardian_name']) ?></td><td><?= e($d['guardian_phone']) ?></td></tr><?php endforeach; ?>
          </tbody></table></div>
          <?php if ($valid > count($preview)): ?><p class="muted">Showing the first <?= count($preview) ?> of <?= $valid ?>.</p><?php endif; ?>
          <?php if ($issues): ?><details style="margin-bottom:1rem"><summary><strong><?= count($issues) ?> note(s)</strong> (these do not stop the import)</summary><ul><?php foreach ($issues as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></details><?php endif; ?>
          <?php endif; ?>
          <div class="actions">
            <?php if ($ok && $valid): ?><form method="post" action="students.php"><?= csrf_field() ?><input type="hidden" name="do" value="import_confirm"><input type="hidden" name="token" value="<?= e($token) ?>"><input type="hidden" name="cs" value="<?= $cs ?>">
              <button class="btn accent">Register <?= $valid ?> student(s)</button></form><?php endif; ?>
            <a class="btn ghost" href="students.php?a=upload">Cancel</a>
            <a class="btn ghost" href="students.php?a=template">⬇ Download template</a>
          </div></div>
    <?php else: ?>
        <div class="cols">
          <form class="card" method="post" enctype="multipart/form-data" action="students.php?a=upload"><h2>Upload a class list</h2><?= csrf_field() ?><input type="hidden" name="do" value="import_preview">
            <div class="f"><label>Class / stream *</label><select name="cs" required><?= stream_options($mine, $_GET['cs'] ?? null, false, $mine ? 'Choose the class these students belong to…' : 'No classes assigned to you') ?></select>
              <small><?= $isClassTeacher ? 'You can only upload for the class and streams assigned to you.' : 'Every student in the file will be placed in this class.' ?></small></div>
            <div class="f"><label>Spreadsheet (.xlsx or .csv, max 15MB) *</label><input type="file" name="sheet" accept=".xlsx,.csv" required></div>
            <button class="btn accent" <?= $mine ? '' : 'disabled' ?>>Upload & preview</button>
          </form>
          <div class="card"><h2>How it works</h2>
            <ol class="muted"><li>Download the template and fill in one student per row.</li><li>Upload it and choose the class/stream.</li><li>Check the preview, then confirm. Each student gets an automatic registration number.</li></ol>
            <p><a class="btn" href="students.php?a=template">⬇ Download Excel template</a></p>
            <p class="muted">Required columns: <strong>Surname</strong> and <strong>First Name</strong>. Optional: Other Names, Gender, Date of Birth, District, Guardian Name, Guardian Phone, Guardian Email, LIN / ID Number. Students already in the database (same name and date of birth, or same LIN) are skipped, so re-uploading a list is safe.</p></div>
        </div>
    <?php endif;
    admin_foot(); exit;
}

// ---- new / edit form
if ($a === 'new' || $a === 'edit') {
    if (!$canManage) deny();
    $rec = $a === 'edit' ? row('SELECT * FROM students WHERE id = ?', [$id]) : null;
    if ($a === 'edit' && (!$rec || !can_manage_student($rec['class_stream_id'] !== null ? (int) $rec['class_stream_id'] : null))) { if (!$rec) redirect('admin/students.php'); deny(); }
    admin_head($rec ? 'Edit student' : 'Register a student');
    $v = fn($k, $d = '') => e($rec[$k] ?? $d);
    $stOptions = $isClassTeacher ? $mine : $streams;
    ?>
    <p><a href="students.php<?= $rec ? '?a=view&id=' . $rec['id'] : '' ?>">← Back</a></p>
    <form class="card" method="post" action="students.php"><h2><?= $rec ? 'Edit ' . e(student_name($rec)) . ' · ' . e($rec['reg_no']) : 'Register a student' ?></h2>
      <?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= (int) ($rec['id'] ?? 0) ?>">
      <div class="cols">
        <div class="f"><label>Surname</label><input type="text" name="last_name" value="<?= $v('last_name') ?>"></div>
        <div class="f"><label>First name *</label><input type="text" name="first_name" value="<?= $v('first_name') ?>" required></div>
        <div class="f"><label>Other names</label><input type="text" name="other_names" value="<?= $v('other_names') ?>"></div>
        <div class="f"><label>Gender</label><select name="gender"><option value="">—</option><?php foreach (['Male', 'Female'] as $g): ?><option <?= ($rec['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?></select></div>
        <div class="f"><label>Date of birth</label><input type="date" name="dob" value="<?= $v('dob') ?>"></div>
        <div class="f"><label>District</label><select name="district"><?= district_options($rec['district'] ?? '') ?></select></div>
        <div class="f"><label>Class / stream</label><select name="class_stream_id"><?= stream_options($stOptions, $rec['class_stream_id'] ?? ($_GET['cs'] ?? null), false, $isClassTeacher ? 'Choose…' : 'No class assigned') ?></select></div>
        <div class="f"><label>LIN / ID number</label><input type="text" name="external_id" value="<?= $v('external_id') ?>"></div>
        <div class="f"><label>Guardian name</label><input type="text" name="guardian_name" value="<?= $v('guardian_name') ?>"></div>
        <div class="f"><label>Guardian phone</label><input type="text" name="guardian_phone" value="<?= $v('guardian_phone') ?>"></div>
        <div class="f"><label>Guardian email</label><input type="email" name="guardian_email" value="<?= $v('guardian_email') ?>"></div>
        <?php if ($rec): ?><div class="f"><label>Status</label><select name="status"><?php foreach (STUDENT_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $rec['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="f"><label>Reason / note for status</label><input type="text" name="status_reason" value="<?= $v('status_reason') ?>" placeholder="e.g. transferred to St. Joseph's SS"></div><?php endif; ?>
      </div>
      <div class="f"><label>Notes</label><textarea name="notes"><?= $v('notes') ?></textarea></div>
      <button class="btn accent"><?= $rec ? 'Save changes' : 'Register student' ?></button>
      <?php if (!$rec): ?><span class="muted"> A registration number is generated automatically.</span><?php endif; ?>
    </form>
    <?php admin_foot(); exit;
}

// ---- single student
if ($a === 'view') {
    $s = row(STUDENT_SELECT . ' WHERE s.id = ?', [$id]);
    if (!$s) redirect('admin/students.php');
    $manage = can_manage_student($s['class_stream_id'] !== null ? (int) $s['class_stream_id'] : null);
    admin_head(student_name($s));
    $log = rows('SELECT * FROM student_log WHERE student_id = ? ORDER BY id DESC LIMIT 50', [$id]);
    ?>
    <p><a href="students.php">← Student database</a></p>
    <div class="cols">
      <div class="card"><h2><?= e(student_name($s)) ?> <?= status_badge($s['status']) ?></h2>
        <p style="font-size:1.4rem;margin:.2rem 0 1rem"><strong><?= e($s['reg_no']) ?></strong> <small class="muted">registration number</small></p>
        <dl class="kv">
          <dt>Class</dt><dd><?= $s['class_name'] ? e(stream_label($s)) : '<span class="muted">Not assigned</span>' ?></dd>
          <dt>Gender</dt><dd><?= e($s['gender']) ?></dd><dt>Date of birth</dt><dd><?= $s['dob'] ? e(fmt_date($s['dob'], 'j F Y')) : '' ?></dd>
          <dt>District</dt><dd><?= e($s['district']) ?></dd><dt>LIN / ID</dt><dd><?= e($s['external_id']) ?></dd>
          <dt>Guardian</dt><dd><?= e($s['guardian_name']) ?></dd><dt>Guardian phone</dt><dd><?= e($s['guardian_phone']) ?></dd><dt>Guardian email</dt><dd><?= e($s['guardian_email']) ?></dd>
          <?php if ($s['status'] !== 'present'): ?><dt>Status note</dt><dd><?= e(trim(($s['status_date'] ? fmt_date($s['status_date']) . ' — ' : '') . $s['status_reason'], ' —')) ?></dd><?php endif; ?>
          <?php if ($s['notes']): ?><dt>Notes</dt><dd><?= e($s['notes']) ?></dd><?php endif; ?>
          <?php if ($s['application_ref']): ?><dt>Application</dt><dd><?= e($s['application_ref']) ?></dd><?php endif; ?>
          <dt>Registered</dt><dd><?= e(fmt_date($s['created_at'], 'j M Y')) ?></dd>
        </dl>
        <?php if ($manage): ?><p class="actions"><a class="btn" href="students.php?a=edit&id=<?= $s['id'] ?>">Edit details</a>
          <?php if ($canDelete): ?><form method="post" action="students.php" data-confirm="Delete this student record permanently? This cannot be undone."><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn danger">Delete</button></form><?php endif; ?></p><?php endif; ?>
      </div>
      <div>
        <?php if ($manage): ?>
        <form class="card" method="post" action="students.php"><h2>Change status</h2><?= csrf_field() ?><input type="hidden" name="do" value="status"><input type="hidden" name="id" value="<?= $s['id'] ?>">
          <div class="f"><label>Status</label><select name="status"><?php foreach (STUDENT_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $s['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
          <div class="f"><label>Reason / note</label><input type="text" name="status_reason" value="<?= e($s['status_reason']) ?>" placeholder="e.g. dismissed for indiscipline; transferred to …"></div>
          <button class="btn">Update status</button></form>
        <?php endif; ?>
        <div class="card"><h2>History</h2><table><?php foreach ($log as $l): ?><tr><td style="white-space:nowrap"><small class="muted"><?= e(date('j M Y H:i', strtotime($l['created_at']))) ?></small></td><td><strong><?= e($l['action']) ?></strong> — <?= e($l['detail']) ?><br><small class="muted"><?= e($l['user_name']) ?></small></td></tr><?php endforeach; ?></table></div>
      </div>
    </div>
    <?php admin_foot(); exit;
}

// ---- list
$per = 50;
[$w, $p] = student_filters($_GET);
$total = (int) val('SELECT COUNT(*) FROM students s LEFT JOIN class_streams c ON c.id = s.class_stream_id' . $w, $p);
$pages = max(1, (int) ceil($total / $per));
$pg = min($pages, max(1, (int) ($_GET['p'] ?? 1)));
$items = rows(STUDENT_SELECT . $w . ' ORDER BY c.sort, c.class_name, c.stream_name, s.last_name, s.first_name LIMIT ' . $per . ' OFFSET ' . (($pg - 1) * $per), $p);
$classNames = array_values(array_unique(array_map(fn($c) => $c['class_name'], $streams)));
$districts = array_column(rows("SELECT DISTINCT district FROM students WHERE district != '' ORDER BY district"), 'district');
$totalAll = (int) val('SELECT COUNT(*) FROM students'); $presentAll = (int) val("SELECT COUNT(*) FROM students WHERE status = 'present'");
admin_head('Student Database');
$st = $_GET['status'] ?? 'present';
?>
<div class="kpis" style="margin-bottom:1rem">
  <div class="kpi"><b><?= number_format($presentAll) ?></b><span>Students present</span><small><?= number_format($totalAll) ?> on record in total</small></div>
  <div class="kpi"><b><?= number_format($total) ?></b><span>Match your filters</span></div>
</div>
<div class="bar">
  <?php if ($canManage): ?><a class="btn accent" href="students.php?a=new<?= !empty($_GET['cs']) && ctype_digit((string) $_GET['cs']) ? '&cs=' . (int) $_GET['cs'] : '' ?>">+ Register student</a><a class="btn" href="students.php?a=upload">⬆ Upload Excel / CSV</a><?php endif; ?>
  <?php if ($isClassTeacher && $mine): ?><?php foreach ($mine as $c): ?><a class="btn ghost" href="students.php?cs=<?= $c['id'] ?>">My class: <?= e($c['label']) ?></a><?php endforeach; ?><?php endif; ?>
  <?= export_buttons('students.php' . ($qs ? '?' . $qs : '')) ?>
</div>
<form method="get" class="card" style="display:grid;grid-template-columns:2fr repeat(4,1fr) auto;gap:.6rem;align-items:end">
  <div class="f" style="margin:0"><label>Search</label><input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Name, reg. no, guardian, phone…" style="width:100%;padding:.55rem;border:1px solid #cdd5de;border-radius:8px"></div>
  <div class="f" style="margin:0"><label>Class / stream</label><select name="cs"><option value="">All classes</option><?php foreach ($classNames as $cn): ?><option value="c:<?= e($cn) ?>" <?= ($_GET['cs'] ?? '') === 'c:' . $cn ? 'selected' : '' ?>>All <?= e($cn) ?></option><?php endforeach; ?>
    <?php foreach ($streams as $c): ?><option value="<?= $c['id'] ?>" <?= (string) ($_GET['cs'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['label']) ?></option><?php endforeach; ?><option value="none" <?= ($_GET['cs'] ?? '') === 'none' ? 'selected' : '' ?>>No class</option></select></div>
  <div class="f" style="margin:0"><label>District</label><select name="district"><option value="">All</option><?php foreach ($districts as $d): ?><option <?= ($_GET['district'] ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select></div>
  <div class="f" style="margin:0"><label>Gender</label><select name="gender"><option value="">All</option><?php foreach (['Male', 'Female'] as $g): ?><option <?= ($_GET['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?></select></div>
  <div class="f" style="margin:0"><label>Status</label><select name="status"><option value="all" <?= $st === 'all' ? 'selected' : '' ?>>All</option><?php foreach (STUDENT_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $st === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <button class="btn">Filter</button>
</form>
<form method="post" action="students.php" id="bulk"><?= csrf_field() ?><input type="hidden" name="do" value="bulk"><input type="hidden" name="return" value="<?= e($qs) ?>">
<?php if ($canManage): ?>
<div class="bar card" style="padding:.7rem 1rem;gap:.6rem">
  <strong>With selected:</strong>
  <select name="to_cs" style="padding:.4rem;border-radius:8px;border:1px solid var(--line)"><?= stream_options($isClassTeacher ? $mine : $streams, null, false, 'Move to class…') ?></select>
  <button class="btn sm" name="op" value="move">Move</button>
  <select name="to_status" style="padding:.4rem;border-radius:8px;border:1px solid var(--line)"><option value="">Set status…</option><?php foreach (STUDENT_STATUSES as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select>
  <input type="text" name="status_reason" placeholder="Reason (optional)" style="padding:.4rem;border:1px solid var(--line);border-radius:8px">
  <button class="btn sm" name="op" value="status">Apply</button>
</div>
<?php endif; ?>
<div class="tablewrap"><table>
  <thead><tr><?php if ($canManage): ?><th style="width:34px"><input type="checkbox" onclick="document.querySelectorAll('.pick').forEach(c=>c.checked=this.checked)"></th><?php endif; ?><th>Reg No</th><th>Name</th><th>Gender</th><th>Class</th><th>District</th><th>Guardian phone</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($items as $s): ?>
  <tr><?php if ($canManage): ?><td><?php if (can_manage_student($s['class_stream_id'] !== null ? (int) $s['class_stream_id'] : null)): ?><input class="pick" type="checkbox" name="ids[]" value="<?= $s['id'] ?>"><?php endif; ?></td><?php endif; ?>
    <td><a href="students.php?a=view&id=<?= $s['id'] ?>"><strong><?= e($s['reg_no']) ?></strong></a></td>
    <td><?= e(student_name($s)) ?></td><td><?= e($s['gender']) ?></td><td><?= $s['class_name'] ? e(stream_label($s)) : '<span class="muted">—</span>' ?></td><td><?= e($s['district']) ?></td><td><?= e($s['guardian_phone']) ?></td><td><?= status_badge($s['status']) ?></td></tr>
  <?php endforeach; if (!$items): ?><tr><td colspan="8" class="muted">No students match. <?= $totalAll ? 'Try clearing the filters.' : ($canManage ? 'Upload a class list to get started.' : '') ?></td></tr><?php endif; ?>
  </tbody></table></div>
</form>
<?php if ($pages > 1): ?><div class="bar" style="justify-content:center;margin-top:1rem"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="btn sm <?= $i === $pg ? '' : 'ghost' ?>" href="students.php?<?= e(http_build_query($filters + ['p' => $i])) ?>"><?= $i ?></a><?php endfor; ?></div><?php endif; ?>
<?php admin_foot(); ?>
