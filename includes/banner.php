<?php /* expects $bannerTitle, optional $bannerSub, $crumbs */ ?>
<section class="banner">
  <div class="container">
    <div class="crumbs"><a href="<?= url() ?>">Home</a><?php foreach (($crumbs ?? []) as [$l, $h]): ?> / <a href="<?= e($h) ?>"><?= e($l) ?></a><?php endforeach; ?> / <?= e($bannerTitle) ?></div>
    <h1><?= e($bannerTitle) ?></h1>
    <?php if (!empty($bannerSub)): ?><p><?= e($bannerSub) ?></p><?php endif; ?>
  </div>
</section>
