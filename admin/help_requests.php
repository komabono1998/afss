<?php
require_once __DIR__ . '/_layout.php';
require_perm('help_requests');

$scope = scope_for('help_requests');                 // nurse sees health only, counsellor sees counselling only
[$scopeSql, $scopeParams] = scope_sql($scope);
$statuses = ['new' => 'New', 'contacted' => 'Contacted', 'in_progress' => 'In progress / booked', 'closed' => 'Closed'];
$kinds = ['health' => 'Health', 'counselling' => 'Counselling'];
$canDelete = in_array($ME['role'], ['admin', 'headteacher'], true);
$id = (int) ($_GET['id'] ?? 0);
$topicOf = fn($r) => $r['topic'] === 'Other' && $r['topic_other'] ? 'Other: ' . $r['topic_other'] : $r['topic'];

if (is_post()) {
    csrf_check();
    $rid = (int) ($_POST['id'] ?? 0);
    $rec = row('SELECT * FROM help_requests WHERE id = ?', [$rid]);
    if ($rec && !in_scope($scope, $rec)) deny();
    if ($rec && ($_POST['do'] ?? '') === 'save' && isset($statuses[post('status')])) {
        q('UPDATE help_requests SET status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [post('status'), trim((string) $_POST['notes']), $rid]);
        flash('Request updated.');
        redirect("admin/help_requests.php?id=$rid");
    }
    if ($rec && ($_POST['do'] ?? '') === 'delete' && $canDelete) { q('DELETE FROM help_requests WHERE id = ?', [$rid]); flash('Request deleted.'); redirect('admin/help_requests.php'); }
    redirect('admin/help_requests.php');
}

$st = $_GET['status'] ?? ''; $kd = $_GET['kind'] ?? ''; $urg = !empty($_GET['urgent']);
$where = ['1=1']; $params = [];
if (isset($statuses[$st])) { $where[] = 'status = ?'; $params[] = $st; }
if (isset($kinds[$kd])) { $where[] = 'kind = ?'; $params[] = $kd; }
if ($urg) $where[] = 'urgent = 1';
$w = ' WHERE ' . implode(' AND ', $where) . $scopeSql;
$params = array_merge($params, $scopeParams);

if (!empty($_GET['export'])) {
    $data = rows('SELECT * FROM help_requests' . $w . ' ORDER BY urgent DESC, id DESC', $params);
    export_send((string) $_GET['export'], 'Help requests', ['Reference', 'Received', 'Service', 'Topic', 'Urgent', 'Anonymous', 'Name', 'Class', 'Role', 'Contact', 'Best time', 'Status', 'Message'],
        array_map(fn($r) => [$r['ref'], $r['created_at'], $kinds[$r['kind']] ?? $r['kind'], $topicOf($r), $r['urgent'] ? 'Yes' : 'No', $r['anonymous'] ? 'Yes' : 'No', $r['name'], $r['class_name'], $r['role_text'], $r['contact'], $r['preferred_time'], $statuses[$r['status']] ?? $r['status'], $r['message']], $data), 'help-requests');
}

if ($id) {
    $r = row('SELECT * FROM help_requests WHERE id = ?', [$id]);
    if (!$r) redirect('admin/help_requests.php');
    if (!in_scope($scope, $r)) deny();
    admin_head('Request ' . $r['ref']);
    ?>
    <p><a href="help_requests.php">← All requests</a></p>
    <div class="cols">
      <div class="card"><h2><?= e($topicOf($r)) ?> <?= $r['urgent'] ? '<span class="badge new">URGENT</span>' : '' ?></h2>
        <dl class="kv">
          <dt>Reference</dt><dd><?= e($r['ref']) ?></dd><dt>Service</dt><dd><?= e($kinds[$r['kind']] ?? $r['kind']) ?></dd>
          <dt>Received</dt><dd><?= e(date('j M Y H:i', strtotime((string) $r['created_at']))) ?></dd>
          <dt>From</dt><dd><?= $r['anonymous'] ? '<span class="badge">Anonymous — cannot be contacted</span>' : e(trim($r['name'] . ($r['class_name'] ? ' · ' . $r['class_name'] : '')) ?: '—') ?></dd>
          <?php if ($r['role_text']): ?><dt>Is a</dt><dd><?= e($r['role_text']) ?></dd><?php endif; ?>
          <?php if ($r['contact']): ?><dt>Contact</dt><dd><?= e($r['contact']) ?></dd><?php endif; ?>
          <?php if ($r['preferred_time']): ?><dt>Best time</dt><dd><?= e($r['preferred_time']) ?></dd><?php endif; ?>
          <dt>Message</dt><dd><?= e($r['message']) ?></dd>
        </dl></div>
      <form class="card" method="post" action="help_requests.php"><h2>Follow-up</h2><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $r['id'] ?>">
        <div class="f"><label>Status</label><select name="status"><?php foreach ($statuses as $k => $l): ?><option value="<?= $k ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="f"><label>Private notes</label><textarea name="notes" placeholder="Visible only to staff who handle these requests."><?= e($r['notes']) ?></textarea></div>
        <button class="btn accent">Save</button></form>
    </div>
    <?php if ($canDelete): ?><form method="post" action="help_requests.php" data-confirm="Delete this request permanently?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn danger">Delete request</button></form><?php endif;
    admin_foot(); exit;
}

$items = rows('SELECT * FROM help_requests' . $w . ' ORDER BY CASE WHEN status = \'new\' THEN 0 ELSE 1 END, urgent DESC, id DESC LIMIT 500', $params);
admin_head('Help & Counselling Requests');
$qs = http_build_query(array_filter(['status' => $st, 'kind' => $kd, 'urgent' => $urg ? 1 : '']));
?>
<div class="bar">
  <form method="get" style="display:flex;gap:.4rem;flex-wrap:wrap">
    <?php if (!$scope): ?><select name="kind" onchange="this.form.submit()"><option value="">Health &amp; counselling</option><?php foreach ($kinds as $k => $l): ?><option value="<?= $k ?>" <?= $kd === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select><?php endif; ?>
    <select name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach ($statuses as $k => $l): ?><option value="<?= $k ?>" <?= $st === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <label style="display:flex;align-items:center;gap:.3rem"><input type="checkbox" name="urgent" value="1" onchange="this.form.submit()" <?= $urg ? 'checked' : '' ?>> Urgent only</label>
  </form>
  <span class="muted"><?= count($items) ?> request(s)</span>
  <?= export_buttons('help_requests.php' . ($qs ? '?' . $qs : '')) ?>
</div>
<div class="tablewrap"><table>
  <thead><tr><th>Reference</th><th>Service</th><th>Topic</th><th>From</th><th>Received</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($items as $r): ?><tr>
    <td><a href="help_requests.php?id=<?= $r['id'] ?>"><strong><?= e($r['ref']) ?></strong></a> <?= $r['urgent'] ? '<span class="badge new">URGENT</span>' : '' ?></td>
    <td><?= e($kinds[$r['kind']] ?? $r['kind']) ?></td><td><?= e($topicOf($r)) ?></td>
    <td><?= $r['anonymous'] ? '<span class="badge">Anonymous</span>' : e($r['name'] ?: ($r['contact'] ?: '—')) ?></td>
    <td><?= e(date('j M Y', strtotime((string) $r['created_at']))) ?></td>
    <td><span class="badge <?= $r['status'] === 'new' ? 'new' : ($r['status'] === 'closed' ? 'no' : 'reviewing') ?>"><?= e($statuses[$r['status']] ?? $r['status']) ?></span></td></tr>
  <?php endforeach; if (!$items): ?><tr><td colspan="6" class="muted">No requests yet.</td></tr><?php endif; ?>
  </tbody></table></div>
<?php admin_foot(); ?>
