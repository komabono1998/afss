<?php
require_once __DIR__ . '/_layout.php';
$tab = $_GET['tab'] ?? 'subscribers';

if (is_post()) {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'delete_sub') { q('DELETE FROM subscribers WHERE id = ?', [(int) $_POST['id']]); flash('Subscriber removed.'); redirect('admin/newsletter.php'); }
    if ($do === 'add_sub') {
        $em = strtolower(post('email'));
        if (filter_var($em, FILTER_VALIDATE_EMAIL)) { q('INSERT OR IGNORE INTO subscribers (email,token) VALUES (?,?)', [$em, bin2hex(random_bytes(12))]); flash('Subscriber added.'); }
        else flash('Invalid email address.', 'err');
        redirect('admin/newsletter.php');
    }
    if ($do === 'send') {
        $subject = post('subject'); $body = trim((string) $_POST['body']);
        if ($subject === '' || $body === '') { flash('Subject and message are required.', 'err'); redirect('admin/newsletter.php?tab=compose'); }
        $subs = rows('SELECT email,token FROM subscribers WHERE active = 1');
        $host = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        $ok = 0;
        foreach ($subs as $s) {
            $unsub = $host . url('unsubscribe.php?t=' . $s['token']);
            $html = '<div style="font-family:Arial,sans-serif;max-width:620px;margin:auto"><h2 style="color:' . e(setting('primary_color')) . '">' . e(setting('school_name')) . '</h2>' . $body
                . '<hr><p style="font-size:12px;color:#777">You receive this because you subscribed to the ' . e(setting('short_name')) . ' newsletter. <a href="' . e($unsub) . '">Unsubscribe</a></p></div>';
            $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: " . setting('short_name') . ' <' . setting('email') . ">\r\n";
            if (@mail($s['email'], $subject, $html, $headers)) $ok++;
        }
        q('INSERT INTO newsletters (subject,body,recipients,sent_ok) VALUES (?,?,?,?)', [$subject, $body, count($subs), $ok]);
        flash("Newsletter processed: $ok of " . count($subs) . ' delivered to the mail server.' . ($ok < count($subs) ? ' (If this is a local XAMPP install, configure sendmail/SMTP in php.ini to send real email.)' : ''), $ok ? 'ok' : 'err');
        redirect('admin/newsletter.php?tab=history');
    }
}
if (($_GET['export'] ?? '') === '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="subscribers-' . date('Ymd') . '.csv"');
    $o = fopen('php://output', 'w'); fputcsv($o, ['email', 'active', 'subscribed']);
    foreach (rows('SELECT email,active,created_at FROM subscribers ORDER BY id DESC') as $r) fputcsv($o, $r);
    exit;
}

admin_head('Newsletter');
$active = (int) val('SELECT COUNT(*) FROM subscribers WHERE active=1');
?>
<div class="bar">
  <?php foreach (['subscribers' => "Subscribers ($active)", 'compose' => 'Compose & send', 'history' => 'Sent history'] as $k => $l): ?>
    <a class="btn <?= $tab === $k ? '' : 'ghost' ?>" href="?tab=<?= $k ?>"><?= e($l) ?></a><?php endforeach; ?>
</div>
<?php if ($tab === 'compose'): ?>
  <form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="do" value="send">
    <h2>New newsletter → <?= $active ?> active subscriber(s)</h2>
    <div class="f"><label>Subject</label><input type="text" name="subject" required></div>
    <div class="f"><label>Message (HTML)</label><textarea class="html" name="body" required><p>Dear friends of AFSS,</p>
<p></p></textarea></div>
    <button class="btn accent" onclick="return confirm('Send to all active subscribers?')">Send newsletter</button>
  </form>
<?php elseif ($tab === 'history'): ?>
  <div class="tablewrap"><table><thead><tr><th>Subject</th><th>Recipients</th><th>Delivered to mail server</th><th>Sent</th></tr></thead><tbody>
  <?php foreach (rows('SELECT * FROM newsletters ORDER BY id DESC') as $n): ?><tr><td><?= e($n['subject']) ?></td><td><?= $n['recipients'] ?></td><td><?= $n['sent_ok'] ?></td><td><?= e(date('j M Y H:i', strtotime($n['sent_at']))) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
<?php else: ?>
  <div class="bar"><form method="post" style="display:flex;gap:.4rem"><?= csrf_field() ?><input type="hidden" name="do" value="add_sub"><input type="search" name="email" placeholder="Add email…" style="padding:.5rem .8rem;border:1px solid var(--line);border-radius:8px"><button class="btn">Add</button></form>
    <span class="grow"></span><a class="btn ghost" href="?export=1">⬇ Export CSV</a></div>
  <div class="tablewrap"><table><thead><tr><th>Email</th><th>Status</th><th>Subscribed</th><th></th></tr></thead><tbody>
  <?php foreach (rows('SELECT * FROM subscribers ORDER BY id DESC') as $s): ?><tr><td><?= e($s['email']) ?></td><td><span class="badge <?= $s['active'] ? 'yes' : 'no' ?>"><?= $s['active'] ? 'Active' : 'Unsubscribed' ?></span></td><td><?= e(fmt_date($s['created_at'])) ?></td>
    <td><form method="post" data-confirm="Remove this subscriber?"><?= csrf_field() ?><input type="hidden" name="do" value="delete_sub"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn sm danger">Remove</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div>
<?php endif; admin_foot(); ?>
