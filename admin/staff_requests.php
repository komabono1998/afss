<?php
require_once __DIR__ . '/_layout.php';
require_perm('users');
$self = 'staff_requests.php';
$link = null;

if (is_post()) {
    csrf_check();
    $id = (int) post('id'); $do = post('do');
    $r = $id ? row('SELECT * FROM staff_requests WHERE id = ?', [$id]) : null;
    if ($r && $do === 'reject') { q("UPDATE staff_requests SET status='rejected', decided_by=?, decided_at=? WHERE id=?", [$ME['name'], date('Y-m-d H:i:s'), $id]); flash('Request declined.'); redirect("admin/$self"); }
    if ($r && $do === 'delete') { q('DELETE FROM staff_requests WHERE id = ?', [$id]); flash('Request deleted.'); redirect("admin/$self"); }
    if ($r && $do === 'approve') {
        $role = array_key_exists(post('role'), roles()) && post('role') !== 'admin' ? post('role') : 'teacher';
        if (val('SELECT COUNT(*) FROM users WHERE email = ?', [$r['email']])) { flash('An account with that email already exists.', 'err'); redirect("admin/$self"); }
        q('INSERT INTO users (name,email,password,role,active) VALUES (?,?,?,?,1)', [$r['name'], $r['email'], password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), $role]);   // nobody knows this password; a reset link sets the real one
        $uid = (int) db()->lastInsertId();
        $token = bin2hex(random_bytes(32));
        q('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)', [$uid, hash('sha256', $token), date('Y-m-d H:i:s', time() + 7 * 86400)]);
        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . url('admin/reset.php?t=' . $token);
        $body = "Hello {$r['name']},\n\nYour " . setting('short_name', 'AFSS') . " staff account has been approved (role: " . role_label($role) . ").\n\nSet your password here (the link works once and expires in 7 days):\n$link\n\nThen sign in at " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . url('admin/login.php') . "\n";
        $mailed = @mail($r['email'], 'Your ' . setting('short_name', 'AFSS') . ' staff account', $body, "Content-Type: text/plain; charset=UTF-8\r\nFrom: " . setting('short_name', 'AFSS') . ' <' . setting('email', 'noreply@localhost') . ">\r\n");
        q("UPDATE staff_requests SET status='approved', decided_by=?, decided_at=?, user_id=? WHERE id=?", [$ME['name'], date('Y-m-d H:i:s'), $uid, $id]);
        $_SESSION['staff_link'] = [$r['name'], $link, $mailed];
        redirect("admin/$self");
    }
}
$shown = $_SESSION['staff_link'] ?? null; unset($_SESSION['staff_link']);
admin_head('Staff account requests');
$list = rows("SELECT * FROM staff_requests ORDER BY CASE status WHEN 'new' THEN 0 ELSE 1 END, id DESC LIMIT 200");
$staffRoles = array_filter(roles(), fn($k) => $k !== 'admin', ARRAY_FILTER_USE_KEY);
?>
<?php if ($shown): ?>
<div class="alert alert-ok"><strong><?= e($shown[0]) ?> now has an account.</strong> <?= $shown[2] ? 'An email with a link to set a password was sent.' : 'Email could not be sent from this computer, so copy this one-time link and send it to them yourself (it expires in 7 days):' ?>
  <br><input type="text" readonly value="<?= e($shown[1]) ?>" onclick="this.select()" style="width:100%;margin-top:.5rem;padding:.5rem;border:1px solid #b5e2c4;border-radius:8px"></div>
<?php endif; ?>
<div class="card"><h2>Staff account requests</h2>
  <p class="muted">Teachers and staff ask for an account from the sign-in page. Nothing is created until you approve. Approving makes the account and gives them a link to choose their own password. The default role, <strong>Teacher (online lessons)</strong>, can only schedule live lessons and manage e-Library links. You can change a person's role later under Admin Users.</p></div>
<div class="tablewrap"><table>
  <thead><tr><th>Who</th><th>Teaches / role</th><th>Note</th><th>Status</th><th>Decide</th></tr></thead><tbody>
  <?php foreach ($list as $r): ?>
  <tr>
    <td><strong><?= e($r['name']) ?></strong><br><small class="muted"><?= e($r['email']) ?><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></small><br><small class="muted"><?= e(date('j M Y', strtotime($r['created_at']))) ?></small></td>
    <td><?= e($r['subject_taught']) ?></td><td><?= e($r['message']) ?></td>
    <td><span class="badge <?= $r['status'] === 'new' ? 'new' : ($r['status'] === 'approved' ? 'published' : 'closed') ?>"><?= e(ucfirst($r['status'])) ?></span><?= $r['decided_by'] ? '<br><small class="muted">by ' . e($r['decided_by']) . '</small>' : '' ?></td>
    <td class="actions">
      <?php if ($r['status'] === 'new'): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="do" value="approve">
        <select name="role" aria-label="Role"><?php foreach ($staffRoles as $k => $ro): ?><option value="<?= e($k) ?>"<?= $k === 'teacher' ? ' selected' : '' ?>><?= e($ro['label']) ?></option><?php endforeach; ?></select>
        <button class="btn sm accent">Approve</button></form>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="do" value="reject"><button class="btn sm danger">Decline</button></form>
      <?php else: ?>
      <form method="post" onsubmit="return confirm('Delete this record?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="do" value="delete"><button class="btn sm ghost">Delete</button></form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; if (!$list): ?><tr><td colspan="5" class="muted">No requests yet.</td></tr><?php endif; ?>
</tbody></table></div>
<?php admin_foot(); ?>
