<?php
require_once __DIR__ . '/includes/bootstrap.php';
$errors = [];
$done = null;

if (is_post()) {
    csrf_check();
    if (!spam_guard('apply', 5)) $errors[] = 'Too many submissions. Please try again later.';
    $isTransfer = post('admission_type') === 'Transfer';
    if (!in_array(post('admission_type'), ['New admission', 'Transfer'], true)) $errors[] = 'Please choose New admission or Transfer.';
    if ($isTransfer && post('transfer_reason') === '') $errors[] = 'Please give the reason for transfer.';
    $req = ['first_name' => 'First name', 'last_name' => 'Last name', 'gender' => 'Gender', 'dob' => 'Date of birth',
        'level_applied' => 'Level', 'entry_class' => 'Entry class', 'guardian_name' => 'Parent/guardian name', 'guardian_phone' => 'Parent/guardian phone'];
    foreach ($req as $k => $l) if (post($k) === '') $errors[] = "$l is required.";
    if (post('guardian_email') !== '' && !filter_var(post('guardian_email'), FILTER_VALIDATE_EMAIL)) $errors[] = 'Guardian email is not valid.';
    if (post('dob') !== '' && !strtotime(post('dob'))) $errors[] = 'Date of birth is not valid.';
    if (!isset($_POST['consent'])) $errors[] = 'Please confirm the information is accurate.';
    $results = $recommendation = null;
    if (!$errors) {
        try {
            $results = handle_upload('results_file', ['pdf' => 'application/pdf'], PRIVATE_DIR, 'res_');
            if (!$results) $errors[] = 'Please attach a copy of your results as a PDF.';
            $recommendation = handle_upload('recommendation_file', ['pdf' => 'application/pdf'], PRIVATE_DIR, 'rec_');
            if ($isTransfer && !$recommendation) $errors[] = 'Please attach the recommendation letter from your former school (PDF).';
            if (!$isTransfer) { if ($recommendation) @unlink(PRIVATE_DIR . '/' . $recommendation); $recommendation = null; }
        } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
        if ($errors) foreach ([$results, $recommendation] as $fn) if ($fn) @unlink(PRIVATE_DIR . '/' . $fn);
    }
    if (!$errors) {
        $ref = 'AFSS-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $cols = ['first_name', 'last_name', 'gender', 'dob', 'nationality', 'level_applied', 'entry_class', 'combination', 'previous_school',
            'index_number', 'aggregates', 'boarding', 'guardian_name', 'guardian_relation', 'guardian_phone', 'guardian_email', 'address', 'notes'];
        $vals = array_map('post', $cols);
        $cols = array_merge($cols, ['admission_type', 'transfer_reason', 'results_file', 'recommendation_file']);
        $vals = array_merge($vals, [post('admission_type'), $isTransfer ? post('transfer_reason') : '', $results, $recommendation]);
        q('INSERT INTO admissions_apps (ref,' . implode(',', $cols) . ') VALUES (?,' . implode(',', array_fill(0, count($cols), '?')) . ')', array_merge([$ref], $vals));
        notify("New admission application $ref", post('first_name') . ' ' . post('last_name') . ' applied for ' . post('entry_class') . ".\nGuardian: " . post('guardian_name') . ' ' . post('guardian_phone') . "\nView it in the admin panel.", post('guardian_email'));
        $done = ['ref' => $ref, 'name' => post('first_name')];
    }
}

$pageTitle = 'Apply Online';
$bannerTitle = 'Apply Online';
$bannerSub = 'Admission application form';
$crumbs = [['Admissions', url('admissions.php')]];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
$sel = fn($k, $opts) => implode('', array_map(fn($o) => '<option' . (($_POST[$k] ?? '') === $o ? ' selected' : '') . '>' . e($o) . '</option>', $opts));
?>
<section class="section"><div class="container" style="max-width:860px">
<?php if ($done): ?>
  <div class="card"><div class="card-body" style="text-align:center;padding:3rem 1.5rem">
    <div style="font-size:3rem">✅</div><h2>Application received</h2>
    <p>Thank you, <?= e($done['name']) ?>. Your reference number is <strong style="font-size:1.3rem"><?= e($done['ref']) ?></strong>. Please keep it and quote it in any communication with the school office.</p>
    <p class="meta">Please bring your results slip, report card and a passport photograph to the school office.</p>
    <a class="btn btn-primary" href="<?= url() ?>">Back to home</a></div></div>
<?php elseif (setting('admissions_open') !== '1'): ?>
  <div class="notice">Online applications are currently closed. Please contact the school office on <?= e(setting('phone')) ?>.</div>
<?php else: ?>
  <?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>
  <form class="form" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
    <fieldset><legend>Applicant details</legend>
      <div class="row">
        <div class="field"><label class="req">First name</label><input name="first_name" value="<?= old('first_name') ?>" required></div>
        <div class="field"><label class="req">Last name</label><input name="last_name" value="<?= old('last_name') ?>" required></div>
        <div class="field"><label class="req">Gender</label><select name="gender"><option value="">Select…</option><?= $sel('gender', ['Female', 'Male']) ?></select></div>
        <div class="field"><label class="req">Date of birth</label><input type="date" name="dob" value="<?= old('dob') ?>" required></div>
        <div class="field"><label>Nationality</label><input name="nationality" value="<?= old('nationality', 'Ugandan') ?>"></div>
        <div class="field"><label>Boarding / Day</label><select name="boarding"><?= $sel('boarding', ['Boarding', 'Day']) ?></select></div>
      </div>
    </fieldset>
    <fieldset><legend>Type of admission</legend>
      <div class="field"><label class="req">Are you applying as a new student or transferring from another school?</label>
        <label style="font-weight:400;display:block"><input type="radio" name="admission_type" value="New admission" <?= ($_POST['admission_type'] ?? 'New admission') === 'New admission' ? 'checked' : '' ?>> New admission</label>
        <label style="font-weight:400;display:block"><input type="radio" name="admission_type" value="Transfer" <?= ($_POST['admission_type'] ?? '') === 'Transfer' ? 'checked' : '' ?>> Transfer from another school</label></div>
      <div id="transfer-box" style="display:none">
        <div class="field"><label class="req">Reason for transfer</label><textarea name="transfer_reason" rows="3"><?= old('transfer_reason') ?></textarea></div>
        <div class="field"><label class="req">Recommendation letter from your former school (PDF, max 5MB)</label><input type="file" name="recommendation_file" accept=".pdf,application/pdf"></div>
      </div>
    </fieldset>
    <fieldset><legend>Admission sought</legend>
      <div class="row">
        <div class="field"><label class="req">Level</label><select name="level_applied"><option value="">Select…</option><?= $sel('level_applied', ['O Level', 'A Level']) ?></select></div>
        <div class="field"><label class="req">Class applying for</label><select name="entry_class"><option value="">Select…</option><?= $sel('entry_class', ['Senior 1', 'Senior 2', 'Senior 3', 'Senior 4', 'Senior 5', 'Senior 6']) ?></select></div>
        <div class="field"><label>Preferred combination (A Level)</label><input name="combination" value="<?= old('combination') ?>" placeholder="e.g. PCM, HEG"></div>
        <div class="field"><label>Previous school</label><input name="previous_school" value="<?= old('previous_school') ?>"></div>
        <div class="field"><label>Index number</label><input name="index_number" value="<?= old('index_number') ?>"></div>
        <div class="field"><label>Aggregates / Grade</label><input name="aggregates" value="<?= old('aggregates') ?>"></div>
      </div>
      <div class="field"><label class="req">Copy of your results (PDF, max 5MB)</label><input type="file" name="results_file" accept=".pdf,application/pdf" required><small>PLE or UCE results slip, scanned or photographed into a single PDF. Files must be re-attached if the form shows an error.</small></div>
    </fieldset>
    <fieldset><legend>Parent / guardian</legend>
      <div class="row">
        <div class="field"><label class="req">Full name</label><input name="guardian_name" value="<?= old('guardian_name') ?>" required></div>
        <div class="field"><label>Relationship</label><input name="guardian_relation" value="<?= old('guardian_relation') ?>"></div>
        <div class="field"><label class="req">Phone</label><input name="guardian_phone" value="<?= old('guardian_phone') ?>" required></div>
        <div class="field"><label>Email</label><input type="email" name="guardian_email" value="<?= old('guardian_email') ?>"></div>
      </div>
      <div class="field"><label>Home address</label><input name="address" value="<?= old('address') ?>"></div>
      <div class="field"><label>Anything else we should know? (health, special needs, bursary request)</label><textarea name="notes" rows="3"><?= old('notes') ?></textarea></div>
    </fieldset>
    <label style="display:flex;gap:.6rem;align-items:flex-start;margin-bottom:1.2rem"><input type="checkbox" name="consent" value="1" <?= isset($_POST['consent']) ? 'checked' : '' ?>> I confirm that the information provided is accurate.</label>
    <button class="btn btn-primary" type="submit">Submit application</button>
  </form>
<script>(function(){var b=document.getElementById('transfer-box'),r=document.querySelectorAll('input[name=admission_type]');function u(){var t=document.querySelector('input[name=admission_type]:checked');b.style.display=t&&t.value==='Transfer'?'block':'none';b.querySelector('input[type=file]').required=!!t&&t.value==='Transfer';b.querySelector('textarea').required=!!t&&t.value==='Transfer'}r.forEach(function(x){x.addEventListener('change',u)});u()})();</script>
<?php endif; ?>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
