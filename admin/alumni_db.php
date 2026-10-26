<?php
require_once __DIR__ . '/_layout.php';
require_perm('alumni_members');

$a = $_GET['a'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);
$canDelete = in_array($ME['role'], ['admin', 'headteacher'], true);
$self = 'alumni_db.php';

function rm_private(?string $f): void { if ($f) @unlink(PRIVATE_DIR . '/' . basename($f)); }
function member_form_data(): array
{
    return ['name' => post('name'), 'email' => strtolower(post('email')), 'phone' => post('phone'), 'year_left' => post('year_left'), 'last_class' => post('last_class'),
        'profession' => post('profession'), 'workplace' => post('workplace'), 'location' => post('location'), 'newsletter' => isset($_POST['newsletter']) ? 1 : 0, 'notes' => trim((string) ($_POST['notes'] ?? ''))];
}

// ------------------------------------------------------------ exports (before any output)
if (!empty($_GET['export'])) {
    [$w, $p] = alumni_filters($_GET);
    $data = rows('SELECT * FROM alumni_members' . $w . ' ORDER BY COALESCE(member_no, \'~\'), name', $p);
    $fmt = $_GET['export'] === '1' ? 'csv' : (string) $_GET['export'];
    [$h, $r] = alumni_export_table($data, $fmt === 'pdf');
    export_send($fmt, 'Alumni database', $h, $r, 'alumni');
}

// ------------------------------------------------------------ POST actions
if (is_post()) {
    csrf_check();
    $do = $_POST['do'] ?? '';
    $mid = (int) ($_POST['id'] ?? 0);
    $m = $mid ? row('SELECT * FROM alumni_members WHERE id = ?', [$mid]) : null;

    if ($do === 'save') {
        $d = member_form_data();
        if ($d['name'] === '') { flash('Name is required.', 'err'); redirect("admin/$self?a=" . ($m ? "edit&id=$mid" : 'new')); }
        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) { flash('Email is not valid.', 'err'); redirect("admin/$self?a=" . ($m ? "edit&id=$mid" : 'new')); }
        try {
            $photo = handle_upload('photo', IMG_TYPES, PRIVATE_DIR, 'alp_');
            $proof = handle_upload('proof_file', ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'], PRIVATE_DIR, 'alf_');
        } catch (RuntimeException $ex) { flash($ex->getMessage(), 'err'); redirect("admin/$self?a=" . ($m ? "edit&id=$mid" : 'new')); }
        if ($m) {
            if ($photo) rm_private($m['photo']); if ($proof) rm_private($m['proof_file']);
            if (!empty($_POST['photo_remove']) && !$photo) { rm_private($m['photo']); $photo = ''; }
            q('UPDATE alumni_members SET name=?,email=?,phone=?,year_left=?,last_class=?,profession=?,workplace=?,location=?,newsletter=?,notes=?,photo=?,proof_file=? WHERE id=?',
                [$d['name'], $d['email'], $d['phone'], $d['year_left'], $d['last_class'], $d['profession'], $d['workplace'], $d['location'], $d['newsletter'], $d['notes'],
                    $photo !== null ? $photo : $m['photo'], $proof ?: $m['proof_file'], $mid]);
            alumni_log($mid, 'edited', 'Details updated');
            flash('Alumni record saved.');
            redirect("admin/$self?a=view&id=$mid");
        }
        q('INSERT INTO alumni_members (name,email,phone,year_left,last_class,profession,workplace,location,newsletter,notes,photo,proof_file,token,consent,consent_at,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1,?,?)',
            [$d['name'], $d['email'], $d['phone'], $d['year_left'], $d['last_class'], $d['profession'], $d['workplace'], $d['location'], $d['newsletter'], $d['notes'], $photo, $proof, bin2hex(random_bytes(12)), date('Y-m-d H:i:s'), 'new']);
        $nid = (int) db()->lastInsertId();
        alumni_log($nid, 'created', 'Added by staff');
        if (!empty($_POST['approve_now'])) { $no = alumni_approve($nid); flash("Added and approved — membership number $no."); }
        else flash('Alumnus added as pending approval.');
        redirect("admin/$self?a=view&id=$nid");
    }

    if ($do === 'approve' && $m) {
        $no = alumni_approve($mid);
        $mm = row('SELECT * FROM alumni_members WHERE id = ?', [$mid]);
        $sent = !empty($_POST['send_email']) && alumni_email_card($mm);
        flash("{$m['name']} approved — membership number $no." . ($sent ? ' The card link was emailed.' : ($m['email'] ? ' Email not sent (mail server not set up); copy the card link below.' : '')));
        redirect("admin/$self?a=view&id=$mid");
    }
    if ($do === 'reject' && $m) {
        q("UPDATE alumni_members SET status='rejected', reject_reason=? WHERE id=?", [post('reason'), $mid]);
        alumni_log($mid, 'rejected', post('reason'));
        flash('Registration rejected.');
        redirect("admin/$self?a=view&id=$mid");
    }
    if ($do === 'suspend' && $m) {
        q("UPDATE alumni_members SET status='suspended', reject_reason=? WHERE id=?", [post('reason'), $mid]);
        alumni_log($mid, 'suspended', post('reason'));
        flash('Membership suspended. The card no longer verifies.');
        redirect("admin/$self?a=view&id=$mid");
    }
    if ($do === 'renew' && $m && $m['status'] === 'approved') {
        $to = alumni_renew($mid, max(1, (int) ($_POST['months'] ?? alumni_validity_months())));
        flash('Membership renewed until ' . date('j F Y', strtotime($to)) . '.');
        redirect("admin/$self?a=view&id=$mid");
    }
    if ($do === 'email_card' && $m) {
        flash(alumni_email_card($m) ? 'Card link emailed.' : 'Could not email the link (no email address, or the mail server is not set up). Copy the link instead.', alumni_email_card($m) ? 'ok' : 'err');
        redirect("admin/$self?a=view&id=$mid");
    }
    if ($do === 'bulk') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $op = post('op'); $n = 0;
        foreach ($ids as $bid) {
            $bm = row('SELECT * FROM alumni_members WHERE id = ?', [$bid]);
            if (!$bm) continue;
            if ($op === 'approve' && $bm['status'] !== 'approved') { alumni_approve($bid); $n++; }
            elseif ($op === 'renew' && $bm['status'] === 'approved') { alumni_renew($bid); $n++; }
            elseif ($op === 'reject' && $bm['status'] === 'new') { q("UPDATE alumni_members SET status='rejected' WHERE id=?", [$bid]); alumni_log($bid, 'rejected', 'Bulk action'); $n++; }
        }
        flash($n ? "$n record(s) updated." : 'Tick some alumni and choose an action that applies to them.', $n ? 'ok' : 'err');
        redirect("admin/$self" . (!empty($_POST['return']) ? '?' . ltrim((string) $_POST['return'], '?') : ''));
    }
    if ($do === 'delete' && $m && $canDelete) {
        rm_private($m['photo']); rm_private($m['proof_file']);
        q('DELETE FROM alumni_log WHERE member_id = ?', [$mid]);
        q('DELETE FROM alumni_members WHERE id = ?', [$mid]);
        flash('Alumni record deleted permanently.');
        redirect("admin/$self");
    }
    redirect("admin/$self");
}

$filterKeys = ['q', 'status', 'state', 'year', 'profession', 'newsletter'];
$filters = array_filter(array_intersect_key($_GET, array_flip($filterKeys)), fn($v) => $v !== '');
$qs = http_build_query($filters);

// ------------------------------------------------------------ add / edit form
if ($a === 'new' || $a === 'edit') {
    $m = $a === 'edit' ? row('SELECT * FROM alumni_members WHERE id = ?', [$id]) : null;
    if ($a === 'edit' && !$m) redirect("admin/$self");
    admin_head($m ? 'Edit alumnus' : 'Add an alumnus');
    $v = fn($k) => e($m[$k] ?? '');
    ?>
    <p><a href="<?= $m ? "$self?a=view&id={$m['id']}" : $self ?>">← Back</a></p>
    <form class="card" method="post" enctype="multipart/form-data" action="<?= $self ?>"><h2><?= $m ? 'Edit ' . e($m['name']) : 'Add an alumnus manually' ?></h2>
      <?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= (int) ($m['id'] ?? 0) ?>">
      <div class="cols">
        <div class="f"><label>Full name *</label><input type="text" name="name" value="<?= $v('name') ?>" required></div>
        <div class="f"><label>Year left AFSS</label><input type="text" name="year_left" value="<?= $v('year_left') ?>" placeholder="e.g. 2015"></div>
        <div class="f"><label>Email</label><input type="email" name="email" value="<?= $v('email') ?>"></div>
        <div class="f"><label>Phone / WhatsApp</label><input type="text" name="phone" value="<?= $v('phone') ?>"></div>
        <div class="f"><label>Last class attended</label><input type="text" name="last_class" value="<?= $v('last_class') ?>"></div>
        <div class="f"><label>Profession</label><input type="text" name="profession" value="<?= $v('profession') ?>"></div>
        <div class="f"><label>Place of work</label><input type="text" name="workplace" value="<?= $v('workplace') ?>"></div>
        <div class="f"><label>Where they live</label><input type="text" name="location" value="<?= $v('location') ?>"></div>
        <div class="f"><label>Photo (for the membership card)</label><?php if (!empty($m['photo'])): ?><img src="download.php?f=<?= urlencode($m['photo']) ?>&inline=1" alt="" style="max-width:90px;border-radius:8px;display:block;margin:.3rem 0"><label style="font-weight:400"><input type="checkbox" name="photo_remove" value="1"> Remove</label><?php endif; ?><input type="file" name="photo" accept="image/*"></div>
        <div class="f"><label>Proof of payment / membership</label><?php if (!empty($m['proof_file'])): ?><p><a href="download.php?f=<?= urlencode($m['proof_file']) ?>">Current file</a></p><?php endif; ?><input type="file" name="proof_file" accept=".pdf,.jpg,.jpeg,.png"></div>
      </div>
      <div class="f"><label>Internal notes</label><textarea name="notes"><?= $v('notes') ?></textarea></div>
      <div class="f"><label><input type="checkbox" name="newsletter" value="1" <?= !empty($m['newsletter']) ? 'checked' : '' ?>> Receives the alumni newsletter</label></div>
      <?php if (!$m): ?><div class="f"><label><input type="checkbox" name="approve_now" value="1"> Approve now and issue a membership number</label><small>Use this for alumni you already know and trust.</small></div><?php endif; ?>
      <button class="btn accent"><?= $m ? 'Save changes' : 'Add alumnus' ?></button>
    </form>
    <?php admin_foot(); exit;
}

// ------------------------------------------------------------ single record
if ($a === 'view') {
    $m = row('SELECT * FROM alumni_members WHERE id = ?', [$id]);
    if (!$m) redirect("admin/$self");
    admin_head($m['name']);
    $state = alumni_state($m);
    $log = rows('SELECT * FROM alumni_log WHERE member_id = ? ORDER BY id DESC LIMIT 40', [$id]);
    $hasCard = $m['status'] === 'approved' && $m['card_token'];
    ?>
    <p><a href="<?= $self ?>">← Alumni database</a></p>
    <div class="cols">
      <div class="card"><h2><?= e($m['name']) ?> <?= alumni_state_badge($state) ?></h2>
        <div style="display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap">
          <?php if ($m['photo']): ?><img src="download.php?f=<?= urlencode($m['photo']) ?>&inline=1" alt="" style="width:130px;height:160px;object-fit:cover;border-radius:10px"><?php endif; ?>
          <dl class="kv" style="flex:1;min-width:240px">
            <dt>Membership no.</dt><dd><strong><?= e($m['member_no'] ?: '— not yet approved —') ?></strong></dd>
            <dt>Class of</dt><dd><?= e($m['year_left']) ?><?= $m['last_class'] ? ' (' . e($m['last_class']) . ')' : '' ?></dd>
            <dt>Profession</dt><dd><?= e($m['profession']) ?></dd><dt>Place of work</dt><dd><?= e($m['workplace']) ?></dd><dt>Lives in</dt><dd><?= e($m['location']) ?></dd>
            <dt>Email</dt><dd><?= $m['email'] ? '<a href="mailto:' . e($m['email']) . '">' . e($m['email']) . '</a>' : '' ?></dd><dt>Phone</dt><dd><?= e($m['phone']) ?></dd>
            <dt>Newsletter</dt><dd><?= $m['newsletter'] ? 'Yes' : 'No' ?></dd>
            <dt>Constitution</dt><dd><?= $m['consent'] ? 'Accepted ' . e(date('j M Y', strtotime((string) $m['consent_at']))) : 'Not recorded' ?></dd>
            <?php if ($m['proof_file']): ?><dt>Proof</dt><dd><a href="download.php?f=<?= urlencode($m['proof_file']) ?>">⬇ Download proof of payment / membership</a></dd><?php endif; ?>
            <?php if ($m['message']): ?><dt>Their message</dt><dd><?= e($m['message']) ?></dd><?php endif; ?>
            <?php if ($m['reject_reason']): ?><dt>Reason</dt><dd><?= e($m['reject_reason']) ?></dd><?php endif; ?>
            <?php if ($m['notes']): ?><dt>Notes</dt><dd><?= e($m['notes']) ?></dd><?php endif; ?>
            <dt>Registered</dt><dd><?= e(date('j M Y', strtotime((string) $m['created_at']))) ?></dd>
          </dl>
        </div>
        <p class="actions"><a class="btn" href="<?= $self ?>?a=edit&id=<?= $m['id'] ?>">Edit details</a>
          <?php if ($canDelete): ?><form method="post" action="<?= $self ?>" data-confirm="Delete this alumni record permanently?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="btn danger">Delete</button></form><?php endif; ?></p>
      </div>
      <div>
        <div class="card"><h2>Membership</h2>
          <?php if ($m['status'] === 'approved'): ?>
            <dl class="kv"><dt>Valid from</dt><dd><?= e(date('j M Y', strtotime((string) $m['valid_from']))) ?></dd><dt>Valid until</dt><dd><strong><?= e(date('j M Y', strtotime((string) $m['valid_to']))) ?></strong> — <?= e(alumni_state_label($state)) ?></dd>
              <dt>Approved</dt><dd><?= e(substr((string) $m['approved_at'], 0, 10)) ?> by <?= e($m['approved_by']) ?></dd></dl>
            <p class="actions"><a class="btn accent" href="../card.php?id=<?= $m['id'] ?>&preview=1" target="_blank">🪪 View &amp; print card</a>
              <form method="post" action="<?= $self ?>"><?= csrf_field() ?><input type="hidden" name="do" value="email_card"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="btn ghost">✉ Email card link</button></form></p>
            <div class="f"><label>Member's private card link</label><input type="text" readonly value="<?= e(alumni_card_url($m)) ?>" onclick="this.select()"></div>
            <form method="post" action="<?= $self ?>" style="display:flex;gap:.5rem;align-items:end;flex-wrap:wrap"><?= csrf_field() ?><input type="hidden" name="do" value="renew"><input type="hidden" name="id" value="<?= $m['id'] ?>">
              <div class="f" style="margin:0"><label>Renew for (months)</label><input type="number" name="months" min="1" max="120" value="<?= alumni_validity_months() ?>" style="width:110px"></div><button class="btn">Renew</button></form>
          <?php else: ?><p class="muted">No membership card yet. Approving issues a membership number, validity dates and a printable card.</p><?php endif; ?>
          <hr style="border:0;border-top:1px solid var(--line);margin:1rem 0">
          <?php if ($m['status'] !== 'approved'): ?>
            <form method="post" action="<?= $self ?>" style="margin-bottom:.8rem"><?= csrf_field() ?><input type="hidden" name="do" value="approve"><input type="hidden" name="id" value="<?= $m['id'] ?>">
              <label style="display:block;margin-bottom:.5rem"><input type="checkbox" name="send_email" value="1" <?= $m['email'] ? 'checked' : 'disabled' ?>> Email the card link to the member</label>
              <button class="btn accent">✔ Approve &amp; issue card</button></form>
          <?php endif; ?>
          <?php if ($m['status'] === 'new'): ?>
            <form method="post" action="<?= $self ?>" style="display:flex;gap:.4rem"><?= csrf_field() ?><input type="hidden" name="do" value="reject"><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="text" name="reason" placeholder="Reason (optional)" style="flex:1;padding:.45rem;border:1px solid var(--line);border-radius:8px"><button class="btn danger">Reject</button></form>
          <?php elseif ($m['status'] === 'approved'): ?>
            <form method="post" action="<?= $self ?>" style="display:flex;gap:.4rem" data-confirm="Suspend this membership? The card will stop verifying."><?= csrf_field() ?><input type="hidden" name="do" value="suspend"><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="text" name="reason" placeholder="Reason" style="flex:1;padding:.45rem;border:1px solid var(--line);border-radius:8px"><button class="btn danger">Suspend</button></form>
          <?php endif; ?>
        </div>
        <div class="card"><h2>History</h2><table><?php foreach ($log as $l): ?><tr><td style="white-space:nowrap"><small class="muted"><?= e(date('j M Y H:i', strtotime((string) $l['created_at']))) ?></small></td><td><strong><?= e($l['action']) ?></strong> <?= e($l['detail']) ?><br><small class="muted"><?= e($l['user_name']) ?></small></td></tr><?php endforeach; if (!$log): ?><tr><td class="muted">No history yet.</td></tr><?php endif; ?></table></div>
      </div>
    </div>
    <?php admin_foot(); exit;
}

// ------------------------------------------------------------ list / query
$per = 50;
[$w, $p] = alumni_filters($_GET);
$total = (int) val('SELECT COUNT(*) FROM alumni_members' . $w, $p);
$pages = max(1, (int) ceil($total / $per));
$pg = min($pages, max(1, (int) ($_GET['p'] ?? 1)));
$items = rows('SELECT * FROM alumni_members' . $w . ' ORDER BY CASE status WHEN \'new\' THEN 0 ELSE 1 END, COALESCE(member_no, \'~\'), name LIMIT ' . $per . ' OFFSET ' . (($pg - 1) * $per), $p);
$years = array_column(rows("SELECT DISTINCT year_left FROM alumni_members WHERE year_left != '' ORDER BY year_left DESC"), 'year_left');
$today = date('Y-m-d');
$c = fn($sql) => (int) val($sql);
$counts = ['all' => $c('SELECT COUNT(*) FROM alumni_members'), 'new' => $c("SELECT COUNT(*) FROM alumni_members WHERE status='new'"),
    'valid' => $c("SELECT COUNT(*) FROM alumni_members WHERE status='approved' AND valid_to >= '$today'"),
    'expired' => $c("SELECT COUNT(*) FROM alumni_members WHERE status='approved' AND (valid_to IS NULL OR valid_to < '$today')"), 'rejected' => $c("SELECT COUNT(*) FROM alumni_members WHERE status IN ('rejected','suspended')")];
admin_head('Alumni Database');
?>
<div class="kpis" style="margin-bottom:1rem">
  <a class="kpi link" href="<?= $self ?>"><b><?= $counts['all'] ?></b><span>Alumni on record</span></a>
  <a class="kpi link <?= $counts['new'] ? 'hot' : '' ?>" href="<?= $self ?>?status=new"><b><?= $counts['new'] ?></b><span>Awaiting approval</span></a>
  <a class="kpi link" href="<?= $self ?>?state=valid"><b><?= $counts['valid'] ?></b><span>Valid members</span></a>
  <a class="kpi link" href="<?= $self ?>?state=expired"><b><?= $counts['expired'] ?></b><span>Expired memberships</span></a>
</div>
<div class="bar">
  <a class="btn accent" href="<?= $self ?>?a=new">+ Add alumnus</a>
  <a class="btn ghost" href="<?= e(url('alumni.php#join')) ?>" target="_blank">Public registration form ↗</a>
  <?= export_buttons($self . ($qs ? '?' . $qs : '')) ?>
</div>
<form method="get" class="card" style="display:grid;grid-template-columns:2fr repeat(4,1fr) auto;gap:.6rem;align-items:end">
  <div class="f" style="margin:0"><label>Search</label><input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Name, member no., profession, workplace, phone…" style="width:100%;padding:.55rem;border:1px solid #cdd5de;border-radius:8px"></div>
  <div class="f" style="margin:0"><label>Registration</label><select name="status"><option value="">All</option><?php foreach (ALUMNI_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= ($_GET['status'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <div class="f" style="margin:0"><label>Validity</label><select name="state"><option value="">Any</option><?php foreach (['valid' => 'Valid', 'expiring' => 'Expiring in 60 days', 'expired' => 'Expired'] as $k => $l): ?><option value="<?= $k ?>" <?= ($_GET['state'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <div class="f" style="margin:0"><label>Year left</label><select name="year"><option value="">All</option><?php foreach ($years as $y): ?><option <?= ($_GET['year'] ?? '') === $y ? 'selected' : '' ?>><?= e($y) ?></option><?php endforeach; ?></select></div>
  <div class="f" style="margin:0"><label>Profession</label><input type="text" name="profession" value="<?= e($_GET['profession'] ?? '') ?>" placeholder="e.g. nurse" style="width:100%;padding:.55rem;border:1px solid #cdd5de;border-radius:8px"></div>
  <button class="btn">Filter</button>
</form>
<form method="post" action="<?= $self ?>"><?= csrf_field() ?><input type="hidden" name="do" value="bulk"><input type="hidden" name="return" value="<?= e($qs) ?>">
<div class="bar card" style="padding:.7rem 1rem;gap:.6rem"><strong>With selected:</strong>
  <button class="btn sm accent" name="op" value="approve">Approve &amp; issue cards</button><button class="btn sm" name="op" value="renew">Renew</button><button class="btn sm danger" name="op" value="reject" onclick="return confirm('Reject the selected pending registrations?')">Reject pending</button>
  <span class="muted"><?= number_format($total) ?> match</span></div>
<div class="tablewrap"><table>
  <thead><tr><th style="width:34px"><input type="checkbox" onclick="document.querySelectorAll('.pick').forEach(c=>c.checked=this.checked)"></th><th>Member no.</th><th>Name</th><th>Class of</th><th>Profession / workplace</th><th>Contact</th><th>Status</th><th>Valid until</th></tr></thead><tbody>
  <?php foreach ($items as $m): $state = alumni_state($m); ?>
  <tr><td><input class="pick" type="checkbox" name="ids[]" value="<?= $m['id'] ?>"></td>
    <td><?= $m['member_no'] ? '<strong>' . e($m['member_no']) . '</strong>' : '<span class="muted">—</span>' ?></td>
    <td><a href="<?= $self ?>?a=view&id=<?= $m['id'] ?>"><strong><?= e($m['name']) ?></strong></a></td><td><?= e($m['year_left']) ?></td>
    <td><?= e($m['profession']) ?><?= $m['workplace'] ? '<br><small class="muted">' . e($m['workplace']) . '</small>' : '' ?></td>
    <td><?= e($m['phone']) ?><?= $m['email'] ? '<br><small class="muted">' . e($m['email']) . '</small>' : '' ?></td>
    <td><?= alumni_state_badge($state) ?></td><td><?= $m['valid_to'] ? e(date('j M Y', strtotime((string) $m['valid_to']))) : '' ?></td></tr>
  <?php endforeach; if (!$items): ?><tr><td colspan="8" class="muted">No alumni match. They appear here when someone registers on the public alumni page, or when you add them.</td></tr><?php endif; ?>
  </tbody></table></div>
</form>
<?php if ($pages > 1): ?><div class="bar" style="justify-content:center;margin-top:1rem"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="btn sm <?= $i === $pg ? '' : 'ghost' ?>" href="<?= $self ?>?<?= e(http_build_query($filters + ['p' => $i])) ?>"><?= $i ?></a><?php endfor; ?></div><?php endif; ?>
<?php admin_foot(); ?>
