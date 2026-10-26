<?php
require_once __DIR__ . '/_layout.php';
require_perm('students_api');

$newKey = null;
if (is_post()) {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'generate') {
        $newKey = 'afss_' . bin2hex(random_bytes(20));
        save_setting('api_key_hash', hash('sha256', $newKey));
        save_setting('api_key_hint', substr($newKey, -4));
        save_setting('api_enabled', '1');
    } elseif ($do === 'toggle') {
        save_setting('api_enabled', setting('api_enabled') === '1' ? '0' : '1');
        flash(setting('api_enabled') === '1' ? 'The endpoint is now ON.' : 'The endpoint is now OFF. Requests are refused.');
        redirect('admin/student_api.php');
    } elseif ($do === 'options') {
        save_setting('api_guardian', isset($_POST['api_guardian']) ? '1' : '0');
        flash('Settings saved.');
        redirect('admin/student_api.php');
    } elseif ($do === 'revoke') {
        save_setting('api_key_hash', ''); save_setting('api_key_hint', ''); save_setting('api_enabled', '0');
        flash('The key was revoked and the endpoint switched off.');
        redirect('admin/student_api.php');
    }
}
admin_head('Student Data API');
$on = setting('api_enabled') === '1' && setting('api_key_hash') !== '';
$endpoint = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . url('api/students.php');
?>
<?php if ($newKey): ?>
<div class="card" style="border:2px solid var(--accent)"><h2>Your new API key</h2>
  <p>Copy it now — <strong>it will not be shown again</strong>. Any previous key stops working.</p>
  <p><code style="font-size:1.05rem;background:#f3f6f4;padding:.6rem .8rem;border-radius:8px;display:block;word-break:break-all"><?= e($newKey) ?></code></p></div>
<?php endif; ?>
<div class="cols">
  <div class="card"><h2>Endpoint status</h2>
    <p><span class="badge <?= $on ? 'yes' : 'no' ?>" style="font-size:.9rem"><?= $on ? 'ON — available' : 'OFF — refusing requests' ?></span></p>
    <p class="muted">Key: <?= setting('api_key_hint') ? '•••• ' . e(setting('api_key_hint')) : 'none issued yet' ?><?= setting('api_last_access') ? ' · last used ' . e(date('j M Y H:i', strtotime(setting('api_last_access')))) : '' ?></p>
    <div class="actions">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="generate"><button class="btn accent" onclick="return confirm('<?= setting('api_key_hash') ? 'Create a new key? The old key will stop working.' : 'Create an API key and switch the endpoint on?' ?>')"><?= setting('api_key_hash') ? 'Generate a new key' : 'Create key & switch on' ?></button></form>
      <?php if (setting('api_key_hash')): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><button class="btn"><?= $on ? 'Switch OFF' : 'Switch ON' ?></button></form>
        <form method="post" data-confirm="Revoke the key and switch the endpoint off?"><?= csrf_field() ?><input type="hidden" name="do" value="revoke"><button class="btn danger">Revoke key</button></form>
      <?php endif; ?>
    </div>
  </div>
  <form class="card" method="post"><h2>What it shares</h2><?= csrf_field() ?><input type="hidden" name="do" value="options">
    <p class="muted">Always shared: registration number, names, gender, date of birth, district, class, stream, status and LIN.</p>
    <div class="f"><label><input type="checkbox" name="api_guardian" value="1" <?= setting('api_guardian') === '1' ? 'checked' : '' ?>> Also share guardian name, phone and email</label><small>Off by default to limit exposure of personal contact details.</small></div>
    <button class="btn">Save</button></form>
</div>
<div class="card"><h2>How the school (or another system) uses it</h2>
  <p>Send a <code>GET</code> request with the key in the <code>Authorization</code> header. Only turn this on for systems you trust, and always use HTTPS on the live site.</p>
  <pre style="background:#10231a;color:#e8f3ec;padding:1rem;border-radius:10px;overflow:auto;font-size:.85rem">curl -H "Authorization: Bearer YOUR_KEY" "<?= e($endpoint) ?>"
curl -H "Authorization: Bearer YOUR_KEY" "<?= e($endpoint) ?>?format=xlsx&amp;class=Senior%201&amp;stream=A" -o s1a.xlsx</pre>
  <div class="tablewrap"><table><thead><tr><th>Parameter</th><th>Meaning</th></tr></thead><tbody>
    <tr><td><code>format</code></td><td><code>json</code> (default), <code>csv</code>, <code>xlsx</code> (Excel) or <code>pdf</code></td></tr>
    <tr><td><code>q</code></td><td>Search by name, registration number, LIN or guardian</td></tr>
    <tr><td><code>class</code>, <code>stream</code></td><td>Class name (e.g. Senior 1) and stream (e.g. A)</td></tr>
    <tr><td><code>district</code>, <code>gender</code></td><td>Filter by district; gender is Male or Female</td></tr>
    <tr><td><code>status</code></td><td><code>present</code> (default), <code>dismissed</code>, <code>transferred</code>, <code>completed</code> or <code>all</code></td></tr>
    <tr><td><code>limit</code>, <code>page</code></td><td>JSON paging (up to 5000 per page)</td></tr>
  </tbody></table></div>
</div>
<?php admin_foot(); ?>
