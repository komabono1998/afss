<?php
/** Shared page shell for the admin sign-in, "forgot password" and "set new password" screens. */
require_once __DIR__ . '/../includes/bootstrap.php';

function auth_start(string $title): void
{
    $logo = media(setting('logo') ?: 'assets/img/logo.webp');
    ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> · <?= e(setting('short_name', 'AFSS')) ?></title>
<link rel="icon" href="<?= e($logo) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
<style>:root{--primary:<?= e(setting('primary_color', '#0b4a24')) ?>;--accent:<?= e(setting('accent_color', '#f2b705')) ?>;--secondary:<?= e(setting('secondary_color', '#d7141a')) ?>}</style></head>
<body class="lg-body">
<main class="lg-wrap">
  <section class="lg-card" aria-labelledby="lg-title">
    <header class="lg-brand">
      <img src="<?= e($logo) ?>" alt="">
      <span><?= e(setting('school_name')) ?></span>
    </header>
    <span class="lg-badge"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6z"/><path d="M9.5 12l2 2 3.5-4"/></svg> Staff only</span>
    <h1 id="lg-title"><?= e($title) ?></h1>
<?php
}

function auth_end(array $links = []): void
{
    ?>
  </section>
  <a class="lg-back" href="<?= e(url()) ?>">← Back to the website</a>
</main>
<script>
document.querySelectorAll('[data-toggle-pw]').forEach(function (b) {
  b.addEventListener('click', function () {
    var i = document.getElementById(b.getAttribute('data-toggle-pw')), show = i.type === 'password';
    i.type = show ? 'text' : 'password'; b.textContent = show ? 'Hide' : 'Show'; b.setAttribute('aria-pressed', show);
  });
});
</script>
</body></html>
<?php
}

/** Password field with a Show/Hide button. */
function auth_password_field(string $id, string $name, string $label, string $autocomplete = 'current-password'): void
{
    ?>
    <div class="lg-field"><label for="<?= e($id) ?>"><?= e($label) ?></label>
      <div class="lg-pw"><input id="<?= e($id) ?>" type="password" name="<?= e($name) ?>" required autocomplete="<?= e($autocomplete) ?>">
        <button type="button" class="lg-show" data-toggle-pw="<?= e($id) ?>" aria-pressed="false">Show</button></div></div>
<?php
}

function is_local_host(): bool
{
    $h = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    return in_array($h, ['localhost', '127.0.0.1', '[::1]'], true) || str_ends_with($h, '.localhost') || str_ends_with($h, '.test');
}
