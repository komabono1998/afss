<?php
/**
 * Alumni membership card. Members open it with their private link (?t=TOKEN);
 * staff preview it from the admin panel (?id=N&preview=1). ?photo=1 serves the member's photo.
 */
require_once __DIR__ . '/includes/bootstrap.php';
header('Referrer-Policy: no-referrer');

$m = null; $staff = false;
if (!empty($_GET['t']) && preg_match('/^[a-f0-9]{24}$/', (string) $_GET['t'])) {
    $m = row('SELECT * FROM alumni_members WHERE card_token = ? AND member_no IS NOT NULL', [$_GET['t']]);
} elseif (!empty($_GET['id']) && admin_user() && can('alumni_members')) {
    $m = row('SELECT * FROM alumni_members WHERE id = ? AND member_no IS NOT NULL', [(int) $_GET['id']]);
    $staff = true;
}

// photo (private file, only released together with a valid card link or staff session)
if ($m && !empty($_GET['photo'])) {
    $f = basename((string) $m['photo']);
    $path = PRIVATE_DIR . '/' . $f;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if ($f === '' || !is_file($path) || !isset(IMG_TYPES[$ext])) { http_response_code(404); exit; }
    header('Content-Type: ' . IMG_TYPES[$ext]);
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

if (!$m) {
    http_response_code(404);
    $pageTitle = 'Membership card';
    $bannerTitle = 'Membership card not found';
    $bannerSub = 'The link may be incomplete, or the card has not been issued yet.';
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/banner.php';
    echo '<div class="container section" style="text-align:center"><p>Use the link from your approval email, or request your card from the alumni page.</p><a class="btn btn-primary" href="' . e(url('alumni.php#card')) . '">Get my card</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$state = alumni_state($m);
$active = in_array($state, ['valid', 'expiring'], true);
$logo = media(setting('logo') ?: 'assets/img/logo.webp');
$assoc = setting('alumni_name') ?: alumni_default('alumni_name');
$photoUrl = $m['photo'] ? url('card.php?' . ($staff ? 'id=' . (int) $m['id'] . '&preview=1' : 't=' . $m['card_token']) . '&photo=1') : '';
$verify = alumni_verify_url($m['member_no']);
$ini = implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($m['name'])), 0, 2)));
$ribbon = $active ? '' : strtoupper(alumni_state_label($state));
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Membership card · <?= e($m['name']) ?></title>
<link rel="icon" href="<?= e($logo) ?>">
<style>
:root{--primary:<?= e(setting('primary_color', '#0b4a24')) ?>;--accent:<?= e(setting('accent_color', '#f2b705')) ?>;--secondary:<?= e(setting('secondary_color', '#d7141a')) ?>;--u:4.4px}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,sans-serif;background:#eef1ee;color:#1c2733}
.toolbar{background:#fff;border-bottom:1px solid #dfe5ec;padding:.7rem 1rem;display:flex;gap:.6rem;align-items:center;flex-wrap:wrap}
.toolbar h1{font-size:1.05rem;margin:0;flex:1}
.btn{display:inline-block;background:var(--primary);color:#fff;border:0;border-radius:8px;padding:.5rem 1rem;font:inherit;font-weight:600;font-size:.9rem;cursor:pointer;text-decoration:none}
.btn.alt{background:#fff;color:var(--primary);border:1px solid var(--primary)}
.stage{display:flex;gap:2rem;flex-wrap:wrap;justify-content:center;padding:2rem 1rem}
.side-label{text-align:center;font-size:.8rem;color:#6b7785;margin:.5rem 0 0}
/* card: everything is sized in --u (1 unit = 1mm when printing) */
.idc{position:relative;width:calc(var(--u)*85.6);height:calc(var(--u)*54);border-radius:calc(var(--u)*3.2);overflow:hidden;background:#fff;box-shadow:0 10px 30px rgba(0,0,0,.18);font-size:calc(var(--u)*2.6);line-height:1.25;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.front{background:linear-gradient(135deg,var(--primary) 0%,color-mix(in srgb,var(--primary) 70%,#000) 100%);color:#fff}
.front::before{content:"";position:absolute;right:calc(var(--u)*-18);top:calc(var(--u)*-18);width:calc(var(--u)*55);height:calc(var(--u)*55);border-radius:50%;background:rgba(242,183,5,.16)}
.front .stripe{position:absolute;left:0;right:0;bottom:0;height:calc(var(--u)*2.2);background:linear-gradient(90deg,var(--secondary) 0 55%,var(--accent) 55% 100%)}
.hdr{position:absolute;left:calc(var(--u)*4);top:calc(var(--u)*3.2);right:calc(var(--u)*4);display:flex;align-items:center;gap:calc(var(--u)*2.4)}
.hdr img{height:calc(var(--u)*9.5);width:auto;background:#fff;border-radius:calc(var(--u)*1.4);padding:calc(var(--u)*.5)}
.hdr b{display:block;font-size:calc(var(--u)*2.9);letter-spacing:.02em;line-height:1.1}
.hdr small{display:block;font-size:calc(var(--u)*2);color:var(--accent);font-weight:600;letter-spacing:.14em;text-transform:uppercase}
.photo{position:absolute;left:calc(var(--u)*4);top:calc(var(--u)*16.5);width:calc(var(--u)*21);height:calc(var(--u)*26);border-radius:calc(var(--u)*2);background:#e8efe9 center/cover no-repeat;border:calc(var(--u)*.6) solid #fff;display:flex;align-items:center;justify-content:center;color:var(--primary);font-weight:800;font-size:calc(var(--u)*8)}
.info{position:absolute;left:calc(var(--u)*28);top:calc(var(--u)*16.5);right:calc(var(--u)*4)}
.info .nm{font-size:calc(var(--u)*4);font-weight:800;line-height:1.1;text-transform:uppercase;margin-bottom:calc(var(--u)*1.4);word-break:break-word}
.info .row{display:flex;gap:calc(var(--u)*1.2);margin-top:calc(var(--u)*.7)}
.info .row span{color:var(--accent);min-width:calc(var(--u)*13);font-size:calc(var(--u)*2.1);text-transform:uppercase;letter-spacing:.05em;padding-top:.15em}
.info .row b{font-weight:700}
.info .row.duo{gap:calc(var(--u)*1.2)}.info .row.duo span{min-width:0}.info .row.duo span:nth-of-type(2){margin-left:calc(var(--u)*2)}.info .row.duo b{margin-right:calc(var(--u)*.4)}
.info .row{margin-top:calc(var(--u)*.5)}
.no{position:absolute;left:calc(var(--u)*4);bottom:calc(var(--u)*5.2);font-size:calc(var(--u)*2.4);color:#e8f3ec}
.no b{font-size:calc(var(--u)*3.3);letter-spacing:.07em;color:#fff}
.valid{position:absolute;right:calc(var(--u)*4);bottom:calc(var(--u)*5.2);text-align:right;font-size:calc(var(--u)*2.1);color:#e8f3ec}
.valid b{display:block;font-size:calc(var(--u)*2.8);color:var(--accent)}
.back{background:#fff;color:#1c2733}
.back .band{height:calc(var(--u)*8);background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;letter-spacing:.14em;font-size:calc(var(--u)*2.4)}
.back .terms{position:absolute;left:calc(var(--u)*4);right:calc(var(--u)*30);top:calc(var(--u)*11);font-size:calc(var(--u)*2.2);color:#3a4654}
.back .terms p{margin:0 0 calc(var(--u)*1.4)}
.back .qr{position:absolute;right:calc(var(--u)*4);top:calc(var(--u)*11);width:calc(var(--u)*23);text-align:center;font-size:calc(var(--u)*1.7);color:#5b6877}
.back .qr .box{width:calc(var(--u)*23);height:calc(var(--u)*23);border:calc(var(--u)*.4) solid #dfe5ec;border-radius:calc(var(--u)*1.4);padding:calc(var(--u)*1);background:#fff}
.back .qr .box img,.back .qr .box canvas{width:100%!important;height:100%!important}
.sign{position:absolute;left:calc(var(--u)*4);bottom:calc(var(--u)*5);font-size:calc(var(--u)*2.1)}
.sign i{display:block;width:calc(var(--u)*30);border-top:calc(var(--u)*.3) solid #1c2733;margin-bottom:calc(var(--u)*.6)}
.back .stripe{position:absolute;left:0;right:0;bottom:0;height:calc(var(--u)*2.2);background:linear-gradient(90deg,var(--secondary) 0 55%,var(--accent) 55% 100%)}
.ribbon{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none}
.ribbon span{transform:rotate(-18deg);border:calc(var(--u)*.9) solid var(--secondary);color:var(--secondary);background:rgba(255,255,255,.88);padding:calc(var(--u)*1) calc(var(--u)*5);font-size:calc(var(--u)*6.5);font-weight:800;letter-spacing:.1em;border-radius:calc(var(--u)*1.4)}
.note{max-width:720px;margin:0 auto 2rem;padding:0 1rem;text-align:center;color:#4a5866;font-size:.9rem}
.warn{max-width:720px;margin:1rem auto 0;padding:.8rem 1rem;background:#fdeceb;border:1px solid #f3c2bd;color:#9b2218;border-radius:10px;text-align:center}
@media print{
  :root{--u:1mm}@page{size:85.6mm 54mm;margin:0}
  body{background:#fff}.toolbar,.side-label,.note,.warn{display:none}
  .stage{display:block;padding:0}.idc{box-shadow:none;border-radius:0;page-break-after:always;break-after:page}
}
</style></head><body>
<div class="toolbar">
  <h1>🪪 Membership card — <?= e($m['name']) ?></h1>
  <button class="btn" onclick="window.print()">Print / Save as PDF</button>
  <?php if ($staff): ?><a class="btn alt" href="admin/alumni_db.php?a=view&id=<?= (int) $m['id'] ?>">← Back to record</a><?php else: ?><a class="btn alt" href="<?= e(url('alumni.php')) ?>">Alumni page</a><?php endif; ?>
</div>
<?php if (!$active): ?><div class="warn"><strong>This card is not currently valid</strong> (<?= e(alumni_state_label($state)) ?>). Contact the alumni office to renew.</div><?php endif; ?>
<div class="stage">
  <div>
    <div class="idc front">
      <div class="hdr"><img src="<?= e($logo) ?>" alt=""><div><b><?= e($assoc) ?></b><small>Alumni membership</small></div></div>
      <div class="photo" <?= $photoUrl ? 'style="background-image:url(\'' . e($photoUrl) . '\')"' : '' ?>><?= $photoUrl ? '' : e($ini) ?></div>
      <div class="info">
        <div class="nm"><?= e($m['name']) ?></div>
        <div class="row"><span>Cohort</span><b><?= e(alumni_cohort($m) ?: '—') ?></b></div>
        <?php $age = alumni_age($m['dob'] ?? null); if ($age !== null || !empty($m['district'])): ?><div class="row duo"><?php if ($age !== null): ?><span>Age</span><b><?= $age ?></b><?php endif; ?><?php if (!empty($m['district'])): ?><span>District</span><b><?= e($m['district']) ?></b><?php endif; ?></div><?php endif; ?>
        <?php if ($m['profession']): ?><div class="row"><span>Profession</span><b><?= e($m['profession']) ?></b></div><?php endif; ?>
        <?php if ($m['workplace']): ?><div class="row"><span>Works at</span><b><?= e($m['workplace']) ?></b></div><?php endif; ?>
        <?php if ($m['phone']): ?><div class="row"><span>Phone</span><b><?= e($m['phone']) ?></b></div><?php endif; ?>
      </div>
      <div class="no">Member no.<br><b><?= e($m['member_no']) ?></b></div>
      <div class="valid">Valid <?= e(date('M Y', strtotime((string) $m['valid_from']))) ?> – <b><?= e(date('d M Y', strtotime((string) $m['valid_to']))) ?></b></div>
      <div class="stripe"></div>
      <?php if ($ribbon): ?><div class="ribbon"><span><?= e($ribbon) ?></span></div><?php endif; ?>
    </div>
    <p class="side-label">Front</p>
  </div>
  <div>
    <div class="idc back">
      <div class="band"><?= e(strtoupper(setting('short_name', 'AFSS'))) ?> · <?= e(strtoupper(setting('tagline') ?: 'Sapientia et Servitium')) ?></div>
      <div class="terms"><p><?= e(setting('alumni_card_note') ?: alumni_default('alumni_card_note')) ?></p>
        <p><strong><?= e(setting('school_name')) ?></strong><br><?= e(setting('address')) ?><br><?= e(setting('phone')) ?><?= setting('email') ? ' · ' . e(setting('email')) : '' ?></p></div>
      <div class="qr"><div class="box" id="qrbox"></div>Scan to verify<br><?= e($m['member_no']) ?></div>
      <div class="sign"><i></i><?= e(setting('alumni_card_signatory') ?: alumni_default('alumni_card_signatory')) ?>, <?= e(setting('alumni_name') ?: 'AFSSOSA') ?></div>
      <div class="stripe"></div>
      <?php if ($ribbon): ?><div class="ribbon"><span><?= e($ribbon) ?></span></div><?php endif; ?>
    </div>
    <p class="side-label">Back</p>
  </div>
</div>
<p class="note">Print on card stock or save as PDF, sized 85.6 × 54 mm (standard ID card). Verify this card at <a href="<?= e($verify) ?>"><?= e($verify) ?></a></p>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
  var box = document.getElementById('qrbox'), url = <?= json_encode($verify, JSON_UNESCAPED_SLASHES) ?>;
  if (window.QRCode) { new QRCode(box, { text: url, width: 200, height: 200, correctLevel: QRCode.CorrectLevel.M }); }
  else { box.style.display = 'flex'; box.style.alignItems = 'center'; box.style.justifyContent = 'center'; box.style.fontSize = '1.4em'; box.textContent = '✔'; }
})();
</script>
</body></html>
