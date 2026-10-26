<?php
require_once __DIR__ . '/includes/bootstrap.php';
http_response_code(404);
$pageTitle = 'Page not found';
$bannerTitle = 'Page not found';
$bannerSub = 'The page you are looking for does not exist or has moved.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<div class="container section" style="text-align:center"><a class="btn btn-primary" href="<?= url() ?>">Back to home</a></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
