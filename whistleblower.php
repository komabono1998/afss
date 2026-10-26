<?php
require_once __DIR__ . '/includes/bootstrap.php';

$cats = array_values(array_filter(array_map('trim', preg_split('/\R/', setting('whistle_categories') ?: whistle_default('whistle_categories')))));
$relations = ['Parent / guardian', 'Student', 'Teacher or staff member', 'Alumnus / alumna', 'Community member', 'Board / PTA member', 'Other'];
$errors = [];
$statusLabels = ['new' => 'Received', 'under_review' => 'Under review', 'investigating' => 'Being investigated', 'resolved' => 'Resolved', 'closed' => 'Closed'];

function wb_code(int $len, string $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'): string
{
    $s = '';
    for ($i = 0; $i < $len; $i++) $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    return $s;
}

// ---------- submit a report ----------
if (is_post() && ($_POST['do'] ?? '') === 'report') {
    csrf_check();
    if (!spam_guard('whistle', 5, 900)) $errors[] = 'Too many reports from this device in a short time. Please try again later.';
    $cat = post('category'); $other = post('category_other');
    if ($cat === '__other') { if ($other === '') $errors[] = 'Please describe the type of concern in the “Other” box.'; $cat = 'Other'; }
    elseif (!in_array($cat, $cats, true)) $errors[] = 'Please choose the type of concern.';
    $details = trim((string) ($_POST['details'] ?? ''));
    if (mb_strlen($details) < 20) $errors[] = 'Please describe what happened in a few sentences (at least 20 characters).';
    $anon = post('identity') !== 'named';
    if (!$anon && post('reporter_name') === '' && post('reporter_contact') === '') $errors[] = 'Give your name or a way to contact you, or choose to stay anonymous.';
    $file = null;
    if (!$errors) {
        try { $file = handle_upload('evidence', ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'], PRIVATE_DIR, 'wb_'); }
        catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }
    if (!$errors) {
        $ref = 'WB-' . wb_code(6); $secret = wb_code(4) . '-' . wb_code(4) . '-' . wb_code(4);
        q('INSERT INTO whistle_reports (ref,secret_hash,category,category_other,details,incident_date,location,people_involved,anonymous,reporter_name,reporter_contact,relationship,attachment) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$ref, hash('sha256', $secret), $cat, $cat === 'Other' ? $other : '', $details, post('incident_date'), post('location'), post('people_involved'), $anon ? 1 : 0,
                $anon ? '' : post('reporter_name'), $anon ? '' : post('reporter_contact'), post('relationship'), $file]);
        notify('New whistle-blower report ' . $ref, "A new report ($cat) was submitted" . ($anon ? ' anonymously' : '') . ".\nOpen the admin panel → Whistle-blower Reports to read it.");
        $_SESSION['wb_receipt'] = ['ref' => $ref, 'secret' => $secret];
        redirect('whistleblower.php?sent=1');
    }
}

// ---------- reporter follow-up from the tracking page ----------
$tracked = null; $trackErr = null;
if (is_post() && ($_POST['do'] ?? '') === 'track') {
    csrf_check();
    if (!spam_guard('wbtrack', 12, 900)) $trackErr = 'Too many attempts. Please wait a few minutes.';
    else {
        $r = row('SELECT * FROM whistle_reports WHERE ref = ?', [strtoupper(post('ref'))]);
        if ($r && hash_equals($r['secret_hash'], hash('sha256', strtoupper(preg_replace('/\s+/', '', post('secret')))))) {
            $tracked = $r;
            if (trim((string) ($_POST['followup'] ?? '')) !== '') {
                q('UPDATE whistle_reports SET followups = COALESCE(followups,\'\') || ?, updated_at = CURRENT_TIMESTAMP, status = CASE WHEN status IN (\'resolved\',\'closed\') THEN \'under_review\' ELSE status END WHERE id = ?',
                    ["\n[" . date('j M Y H:i') . '] ' . trim((string) $_POST['followup']), $r['id']]);
                $tracked = row('SELECT * FROM whistle_reports WHERE id = ?', [$r['id']]);
                flash('Your additional information was added to the report.');
            }
        } else { usleep(400000); $trackErr = 'We could not find a report with that reference and key.'; }
    }
}

$receipt = null;
if (!empty($_GET['sent']) && !empty($_SESSION['wb_receipt'])) { $receipt = $_SESSION['wb_receipt']; unset($_SESSION['wb_receipt']); }

$mode = isset($_GET['track']) || $tracked || $trackErr ? 'track' : 'report';
$pageTitle = 'Report a Concern';
$pageDesc = 'Confidential whistle-blower reporting for parents, students, staff and the community. Anonymous reports are welcome.';
$bannerTitle = 'Report a Concern';
$bannerSub = 'Confidential. You can stay anonymous.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container" style="max-width:860px">
<?php if ($receipt): ?>
  <div class="card"><div class="card-body" style="text-align:center;padding:2.5rem 1.5rem">
    <div style="font-size:3rem">✅</div><h2>Thank you. Your report has been received.</h2>
    <p>Keep these two codes. They are the <strong>only way</strong> to check progress or add information, especially if you reported anonymously. We cannot recover them for you.</p>
    <p style="font-size:1.1rem">Reference: <strong style="font-size:1.4rem"><?= e($receipt['ref']) ?></strong></p>
    <p style="font-size:1.1rem">Secret key: <strong style="font-size:1.4rem;letter-spacing:.08em"><?= e($receipt['secret']) ?></strong></p>
    <p class="meta">This page is shown only once. Take a screenshot or write the codes down.</p>
    <a class="btn btn-primary" href="whistleblower.php?track=1">Check a report</a> <a class="btn btn-ghost" href="<?= url() ?>">Back to home</a></div></div>
<?php elseif ($mode === 'track'): ?>
  <h2>Check the progress of a report</h2>
  <?php if ($trackErr): ?><div class="alert alert-err"><?= e($trackErr) ?></div><?php endif; ?>
  <?php if ($tracked): ?>
    <div class="card" style="margin-bottom:1.5rem"><div class="card-body">
      <p><span class="tag"><?= e($statusLabels[$tracked['status']] ?? $tracked['status']) ?></span> <strong><?= e($tracked['ref']) ?></strong> · <?= e($tracked['category'] === 'Other' && $tracked['category_other'] ? 'Other: ' . $tracked['category_other'] : $tracked['category']) ?></p>
      <p class="meta">Submitted <?= e(fmt_date($tracked['created_at'], 'j F Y')) ?> · last updated <?= e(fmt_date($tracked['updated_at'], 'j F Y')) ?></p>
      <h3>Response from the school</h3>
      <p><?= $tracked['response'] !== null && trim($tracked['response']) !== '' ? nl2br(e($tracked['response'])) : '<em>No response yet. The school is reviewing your report.</em>' ?></p>
      <?php if (trim((string) $tracked['followups']) !== ''): ?><h3>Information you added</h3><p class="meta"><?= nl2br(e(trim($tracked['followups']))) ?></p><?php endif; ?>
      <form method="post" action="whistleblower.php?track=1" class="form" style="margin-top:1rem;box-shadow:none"><?= csrf_field() ?><input type="hidden" name="do" value="track">
        <input type="hidden" name="ref" value="<?= e($tracked['ref']) ?>"><input type="hidden" name="secret" value="<?= e(post('secret')) ?>">
        <div class="field"><label>Add more information</label><textarea name="followup" rows="3" placeholder="Anything new that could help the investigation"></textarea></div>
        <button class="btn btn-primary btn-sm">Send</button></form>
    </div></div>
  <?php endif; ?>
  <form class="form" method="post" action="whistleblower.php?track=1"><?= csrf_field() ?><input type="hidden" name="do" value="track">
    <div class="row"><div class="field"><label class="req">Reference</label><input name="ref" value="<?= old('ref') ?>" placeholder="WB-XXXXXX" required></div>
      <div class="field"><label class="req">Secret key</label><input name="secret" placeholder="XXXX-XXXX-XXXX" required autocomplete="off"></div></div>
    <button class="btn btn-primary">Check report</button> <a class="btn btn-ghost" href="whistleblower.php">Make a new report</a></form>
<?php else: ?>
  <div class="notice" style="margin-bottom:1.5rem"><?= nl2br(e(setting('whistle_intro') ?: whistle_default('whistle_intro'))) ?></div>
  <div class="grid g3" style="margin-bottom:1.5rem">
    <div class="card"><div class="card-body"><strong>🔒 Anonymous by choice</strong><p>We do not store your name, IP address or device details unless you choose to share them.</p></div></div>
    <div class="card"><div class="card-body"><strong>🧾 You get a secret key</strong><p>Use it to check progress or add information, without revealing who you are.</p></div></div>
    <div class="card"><div class="card-body"><strong>👥 Reviewed by leadership</strong><p>Only a small, authorised group at the school can read reports.</p></div></div>
  </div>
  <?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>
  <form class="form" method="post" enctype="multipart/form-data" id="wbform"><?= csrf_field() ?><input type="hidden" name="do" value="report"><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
    <fieldset><legend>What is your concern about?</legend>
      <div class="field"><label class="req">Type of concern</label>
        <select name="category" id="wbcat" required><option value="">Choose…</option>
          <?php foreach ($cats as $c): ?><option <?= ($_POST['category'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
          <option value="__other" <?= ($_POST['category'] ?? '') === '__other' ? 'selected' : '' ?>>Other (type your own)</option></select></div>
      <div class="field" id="wbother" style="display:none"><label class="req">Describe the type of concern</label><input name="category_other" value="<?= old('category_other') ?>" maxlength="120" placeholder="e.g. Misuse of school transport"></div>
      <div class="field"><label class="req">What happened?</label><textarea name="details" rows="6" required placeholder="Describe what happened, with as much detail as you can."><?= old('details') ?></textarea></div>
      <div class="row"><div class="field"><label>When did it happen? (optional)</label><input name="incident_date" value="<?= old('incident_date') ?>" placeholder="date or period"></div>
        <div class="field"><label>Where? (optional)</label><input name="location" value="<?= old('location') ?>" placeholder="place on or off campus"></div></div>
      <div class="field"><label>People involved (optional)</label><input name="people_involved" value="<?= old('people_involved') ?>" placeholder="names or roles, if you know them"></div>
      <div class="field"><label>Evidence (optional)</label><input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png"><small>PDF, JPG or PNG, max 5MB. Photos can carry hidden details such as location or device name, so avoid attaching files that could identify you if you want to stay anonymous.</small></div>
    </fieldset>
    <fieldset><legend>Who is reporting?</legend>
      <label style="display:block;margin-bottom:.5rem"><input type="radio" name="identity" value="anon" <?= ($_POST['identity'] ?? 'anon') !== 'named' ? 'checked' : '' ?>> <strong>Stay anonymous</strong> — we will not know who you are</label>
      <label style="display:block;margin-bottom:1rem"><input type="radio" name="identity" value="named" <?= ($_POST['identity'] ?? '') === 'named' ? 'checked' : '' ?>> <strong>I will give my details</strong> — so the school can contact me</label>
      <div id="wbnamed" style="display:none"><div class="row"><div class="field"><label>Your name</label><input name="reporter_name" value="<?= old('reporter_name') ?>"></div>
        <div class="field"><label>Phone or email</label><input name="reporter_contact" value="<?= old('reporter_contact') ?>"></div></div></div>
      <div class="field"><label>I am a… (optional — skip if it could identify you)</label><select name="relationship"><option value="">—</option><?php foreach ($relations as $r): ?><option <?= ($_POST['relationship'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select></div>
    </fieldset>
    <button class="btn btn-primary" type="submit">Submit report</button>
    <a class="btn btn-ghost" href="whistleblower.php?track=1">I already have a reference</a>
  </form>
  <script>(function(){var c=document.getElementById('wbcat'),o=document.getElementById('wbother'),oi=o.querySelector('input'),n=document.getElementById('wbnamed');
    function u(){var other=c.value==='__other';o.style.display=other?'block':'none';oi.required=other;var named=document.querySelector('input[name=identity]:checked').value==='named';n.style.display=named?'block':'none';}
    c.addEventListener('change',u);document.querySelectorAll('input[name=identity]').forEach(function(r){r.addEventListener('change',u)});u();})();</script>
<?php endif; ?>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
