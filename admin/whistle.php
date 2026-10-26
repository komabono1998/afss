<?php
require_once __DIR__ . '/_layout.php';
require_perm('whistleblower');

$statuses = ['new' => 'New', 'under_review' => 'Under review', 'investigating' => 'Investigating', 'resolved' => 'Resolved', 'closed' => 'Closed'];
$canDelete = in_array($ME['role'], ['admin', 'headteacher'], true);
$id = (int) ($_GET['id'] ?? 0);

if (is_post()) {
    csrf_check();
    $rid = (int) ($_POST['id'] ?? 0);
    if (($_POST['do'] ?? '') === 'save' && isset($statuses[post('status')])) {
        q('UPDATE whistle_reports SET status = ?, response = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [post('status'), trim((string) $_POST['response']), trim((string) $_POST['notes']), $rid]);
        flash('Report updated.');
        redirect("admin/whistle.php?id=$rid");
    }
    if (($_POST['do'] ?? '') === 'delete' && $canDelete) {
        $f = val('SELECT attachment FROM whistle_reports WHERE id = ?', [$rid]);
        if ($f) @unlink(PRIVATE_DIR . '/' . basename($f));
        q('DELETE FROM whistle_reports WHERE id = ?', [$rid]);
        flash('Report deleted permanently.');
        redirect('admin/whistle.php');
    }
    redirect('admin/whistle.php');
}

$cat = $_GET['cat'] ?? ''; $st = $_GET['status'] ?? '';
$where = []; $params = [];
if ($cat !== '') { $where[] = 'category = ?'; $params[] = $cat; }
if (isset($statuses[$st])) { $where[] = 'status = ?'; $params[] = $st; }
$w = $where ? ' WHERE ' . implode(' AND ', $where) : '';

if (!empty($_GET['export'])) {
    $data = rows('SELECT * FROM whistle_reports' . $w . ' ORDER BY id DESC', $params);
    export_send((string) $_GET['export'], 'Whistle-blower reports', ['Reference', 'Received', 'Type of concern', 'Other type', 'Anonymous', 'Reporter', 'Contact', 'Status', 'Incident date', 'Location', 'People involved', 'Details', 'Response'],
        array_map(fn($r) => [$r['ref'], $r['created_at'], $r['category'], $r['category_other'], $r['anonymous'] ? 'Yes' : 'No', $r['reporter_name'], $r['reporter_contact'], $statuses[$r['status']] ?? $r['status'], $r['incident_date'], $r['location'], $r['people_involved'], $r['details'], $r['response']], $data), 'whistleblower-reports');
}

if ($id) {
    $r = row('SELECT * FROM whistle_reports WHERE id = ?', [$id]);
    if (!$r) redirect('admin/whistle.php');
    if ($r['status'] === 'new') { q("UPDATE whistle_reports SET status = 'under_review' WHERE id = ?", [$id]); $r['status'] = 'under_review'; }
    admin_head('Report ' . $r['ref']);
    ?>
    <p><a href="whistle.php">← All reports</a></p>
    <div class="cols">
      <div class="card"><h2><?= e($r['category'] === 'Other' && $r['category_other'] ? 'Other: ' . $r['category_other'] : $r['category']) ?></h2>
        <dl class="kv">
          <dt>Reference</dt><dd><?= e($r['ref']) ?></dd><dt>Received</dt><dd><?= e(date('j M Y H:i', strtotime($r['created_at']))) ?></dd>
          <dt>Reporter</dt><dd><?= $r['anonymous'] ? '<span class="badge">Anonymous</span>' : e(trim($r['reporter_name'] . ' ' . ($r['reporter_contact'] ? '· ' . $r['reporter_contact'] : ''))) ?></dd>
          <?php if ($r['relationship']): ?><dt>Relationship</dt><dd><?= e($r['relationship']) ?></dd><?php endif; ?>
          <?php if ($r['incident_date']): ?><dt>When</dt><dd><?= e($r['incident_date']) ?></dd><?php endif; ?>
          <?php if ($r['location']): ?><dt>Where</dt><dd><?= e($r['location']) ?></dd><?php endif; ?>
          <?php if ($r['people_involved']): ?><dt>People involved</dt><dd><?= e($r['people_involved']) ?></dd><?php endif; ?>
          <dt>Details</dt><dd><?= e($r['details']) ?></dd>
          <?php if (trim((string) $r['followups']) !== ''): ?><dt>Added by reporter</dt><dd><?= e(trim($r['followups'])) ?></dd><?php endif; ?>
          <?php if ($r['attachment']): ?><dt>Evidence</dt><dd><a href="download.php?f=<?= urlencode($r['attachment']) ?>">⬇ Download attachment</a></dd><?php endif; ?>
        </dl>
      </div>
      <form class="card" method="post" action="whistle.php"><h2>Handle this report</h2><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $r['id'] ?>">
        <div class="f"><label>Status</label><select name="status"><?php foreach ($statuses as $k => $l): ?><option value="<?= $k ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="f"><label>Response to the reporter</label><textarea name="response" placeholder="Shown to the reporter when they check with their reference and secret key."><?= e($r['response']) ?></textarea><small>This is visible to the reporter, even if anonymous.</small></div>
        <div class="f"><label>Internal notes (private)</label><textarea name="notes"><?= e($r['notes']) ?></textarea></div>
        <button class="btn accent">Save</button>
      </form>
    </div>
    <?php if ($canDelete): ?><form method="post" action="whistle.php" data-confirm="Delete this report permanently?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn danger">Delete report</button></form><?php endif;
    admin_foot(); exit;
}

$items = rows('SELECT * FROM whistle_reports' . $w . ' ORDER BY id DESC LIMIT 500', $params);
$cats = array_column(rows('SELECT DISTINCT category FROM whistle_reports ORDER BY category'), 'category');
admin_head('Whistle-blower Reports');
?>
<div class="bar">
  <form method="get" style="display:flex;gap:.4rem;flex-wrap:wrap">
    <select name="cat" onchange="this.form.submit()"><option value="">All types</option><?php foreach ($cats as $c): ?><option <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
    <select name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach ($statuses as $k => $l): ?><option value="<?= $k ?>" <?= $st === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
  </form>
  <span class="muted"><?= count($items) ?> report(s) · edit the dropdown list under Site Settings → Whistle-blower page</span>
  <?= export_buttons('whistle.php' . ($where ? '?' . http_build_query(array_filter(['cat' => $cat, 'status' => $st])) : '')) ?>
</div>
<div class="tablewrap"><table>
  <thead><tr><th>Reference</th><th>Concern</th><th>Reporter</th><th>Received</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($items as $r): ?><tr>
    <td><a href="whistle.php?id=<?= $r['id'] ?>"><strong><?= e($r['ref']) ?></strong></a></td>
    <td><?= e($r['category'] === 'Other' && $r['category_other'] ? 'Other: ' . $r['category_other'] : $r['category']) ?><br><small class="muted"><?= e(excerpt($r['details'], 90)) ?></small></td>
    <td><?= $r['anonymous'] ? '<span class="badge">Anonymous</span>' : e($r['reporter_name'] ?: 'Named') ?></td>
    <td><?= e(date('j M Y', strtotime($r['created_at']))) ?></td>
    <td><span class="badge <?= $r['status'] === 'new' ? 'new' : ($r['status'] === 'resolved' ? 'yes' : '') ?>"><?= e($statuses[$r['status']] ?? $r['status']) ?></span></td></tr>
  <?php endforeach; if (!$items): ?><tr><td colspan="5" class="muted">No reports yet.</td></tr><?php endif; ?>
  </tbody></table></div>
<?php admin_foot(); ?>
