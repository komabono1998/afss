<?php
require_once __DIR__ . '/includes/bootstrap.php';
$t = (string) ($_GET['t'] ?? '');
$done = $t !== '' && (str_starts_with($t, 'a_')
    ? q('UPDATE alumni_members SET newsletter = 0 WHERE token = ?', [substr($t, 2)])->rowCount() > 0
    : q('UPDATE subscribers SET active = 0 WHERE token = ?', [$t])->rowCount() > 0);
$pageTitle = 'Unsubscribe';
$bannerTitle = 'Newsletter';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<div class="container section" style="text-align:center">
  <p><?= $done ? 'You have been unsubscribed from the AFSS newsletter.' : 'This unsubscribe link is invalid.' ?></p>
  <a class="btn btn-primary" href="<?= url() ?>">Back to home</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
