<?php
require_once __DIR__ . '/includes/bootstrap.php';

$no = trim((string) ($_GET['m'] ?? ''));
$m = null; $limited = false;
if ($no !== '') {
    if (!spam_guard('verify', 40, 600)) $limited = true;
    else $m = row('SELECT name,year_left,member_no,status,valid_from,valid_to FROM alumni_members WHERE upper(member_no) = upper(?)', [$no]);
}
$state = $m ? alumni_state($m + ['status' => $m['status']]) : null;
$ok = $state === 'valid' || $state === 'expiring';

$pageTitle = 'Verify alumni membership';
$bannerTitle = 'Verify alumni membership';
$bannerSub = 'Check that a membership card is genuine and still valid.';
$crumbs = [['Alumni', url('alumni.php')]];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section"><div class="container" style="max-width:640px">
  <form class="form" method="get" style="margin-bottom:1.5rem">
    <div class="field"><label>Membership number</label><input name="m" value="<?= e($no) ?>" placeholder="e.g. AFSSOSA/2026/0001" required></div>
    <button class="btn btn-primary">Verify</button>
  </form>
  <?php if ($limited): ?><div class="alert alert-err">Too many checks in a short time. Please try again in a few minutes.</div>
  <?php elseif ($no !== '' && !$m): ?><div class="alert alert-err"><strong>Not found.</strong> No member has this number. Check the number on the card and try again.</div>
  <?php elseif ($m): ?>
    <div class="card" style="border-top:6px solid <?= $ok ? 'var(--primary)' : 'var(--secondary)' ?>"><div class="card-body" style="text-align:center">
      <div style="font-size:3rem"><?= $ok ? '✅' : '⚠️' ?></div>
      <h2 style="margin-bottom:.2rem"><?= $ok ? 'Valid member' : 'Not currently valid' ?></h2>
      <p style="font-size:1.2rem;margin:.2rem 0"><strong><?= e($m['name']) ?></strong></p>
      <p class="meta"><?= e($m['member_no']) ?><?= $m['year_left'] ? ' · Class of ' . e($m['year_left']) : '' ?></p>
      <p><span class="tag"><?= e(alumni_state_label($state)) ?></span></p>
      <?php if ($m['valid_to'] && in_array($state, ['valid', 'expiring', 'expired'], true)): ?><p>Valid until <strong><?= e(date('j F Y', strtotime((string) $m['valid_to']))) ?></strong></p><?php endif; ?>
      <p class="meta">Compare the photo and name on the card with the person presenting it.</p>
    </div></div>
  <?php endif; ?>
</div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
