<?php
require_once __DIR__ . '/_layout.php';
$a = $_GET['a'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);
require_perm('issues');
const LAYOUTS = ['image-right' => 'Text with image on the right', 'image-left' => 'Text with image on the left', 'full-width' => 'Large image above text', 'text-only' => 'Text only'];

function rm_file(?string $p): void { if ($p && str_starts_with($p, 'uploads/')) @unlink(ROOT . '/' . $p); }
function upload_pdf(string $field): ?string
{
    $n = handle_upload($field, ['pdf' => 'application/pdf'], UPLOAD_DIR, 'nl_', 15);
    return $n ? 'uploads/' . $n : null;
}

if (is_post()) {
    csrf_check();
    $do = $_POST['do'] ?? '';
    $issue = $id ? row('SELECT * FROM newsletter_issues WHERE id = ?', [$id]) : null;

    if ($do === 'save') {
        $title = post('title'); $date = post('issue_date');
        $err = [];
        if ($title === '') $err[] = 'Title is required.';
        if ($date === '' || !strtotime($date)) $err[] = 'A valid issue date is required.';
        $cover = $issue['cover'] ?? ''; $pdf = $issue['pdf'] ?? '';
        try { $newCover = upload_image('cover_file'); $newPdf = upload_pdf('pdf_file'); }
        catch (RuntimeException $ex) { $err[] = $ex->getMessage(); $newCover = $newPdf = null; }
        if ($err) { foreach ($err as $x) flash($x, 'err'); redirect('admin/issues.php?a=' . ($id ? "edit&id=$id" : 'new')); }
        if (!empty($_POST['cover_remove'])) { rm_file($cover); $cover = ''; }
        if (!empty($_POST['pdf_remove'])) { rm_file($pdf); $pdf = ''; }
        if ($newCover) { rm_file($cover); $cover = $newCover; }
        if ($newPdf) { rm_file($pdf); $pdf = $newPdf; }
        $status = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
        $aud = ($_POST['audience'] ?? '') === 'alumni' ? 'alumni' : 'school';
        $vals = [$title, $date, post('edition'), trim((string) $_POST['summary']), trim((string) $_POST['intro']), $cover, $pdf, $status, $aud];
        if ($issue) {
            q('UPDATE newsletter_issues SET title=?,issue_date=?,edition=?,summary=?,intro=?,cover=?,pdf=?,status=?,audience=? WHERE id=?', array_merge($vals, [$id]));
            flash('Issue saved.');
            redirect("admin/issues.php?a=edit&id=$id");
        }
        q('INSERT INTO newsletter_issues (title,issue_date,edition,summary,intro,cover,pdf,status,audience,slug) VALUES (?,?,?,?,?,?,?,?,?,?)', array_merge($vals, [unique_slug('newsletter_issues', $title)]));
        flash('Issue created. Add stories below.');
        redirect('admin/issues.php?a=edit&id=' . db()->lastInsertId());
    }
    if ($do === 'delete_issue') {
        $iid = (int) $_POST['id'];
        foreach (rows('SELECT image FROM newsletter_sections WHERE issue_id = ?', [$iid]) as $s) rm_file($s['image']);
        $i = row('SELECT cover,pdf FROM newsletter_issues WHERE id = ?', [$iid]);
        if ($i) { rm_file($i['cover']); rm_file($i['pdf']); }
        q('DELETE FROM newsletter_sections WHERE issue_id = ?', [$iid]);
        q('DELETE FROM newsletter_issues WHERE id = ?', [$iid]);
        flash('Issue deleted.');
        redirect('admin/issues.php');
    }
    if ($issue && ($do === 'section_save' || $do === 'section_add')) {
        $sid = (int) ($_POST['sid'] ?? 0);
        $sec = $do === 'section_save' ? row('SELECT * FROM newsletter_sections WHERE id = ? AND issue_id = ?', [$sid, $id]) : null;
        $img = $sec['image'] ?? '';
        try { $new = upload_image('image_file'); } catch (RuntimeException $ex) { flash($ex->getMessage(), 'err'); redirect("admin/issues.php?a=edit&id=$id"); }
        if (!empty($_POST['image_remove'])) { rm_file($img); $img = ''; }
        if ($new) { rm_file($img); $img = $new; }
        $layout = isset(LAYOUTS[$_POST['layout'] ?? '']) ? $_POST['layout'] : 'image-right';
        $v = [post('heading'), trim((string) $_POST['body']), $img, post('caption'), $layout, (int) ($_POST['sort'] ?? 0)];
        if ($sec) q('UPDATE newsletter_sections SET heading=?,body=?,image=?,caption=?,layout=?,sort=? WHERE id=?', array_merge($v, [$sid]));
        else q('INSERT INTO newsletter_sections (heading,body,image,caption,layout,sort,issue_id) VALUES (?,?,?,?,?,?,?)', array_merge($v, [$id]));
        flash($sec ? 'Story saved.' : 'Story added.');
        redirect("admin/issues.php?a=edit&id=$id#sections");
    }
    if ($issue && $do === 'section_delete') {
        $sec = row('SELECT * FROM newsletter_sections WHERE id = ? AND issue_id = ?', [(int) $_POST['sid'], $id]);
        if ($sec) { rm_file($sec['image']); q('DELETE FROM newsletter_sections WHERE id = ?', [$sec['id']]); flash('Story removed.'); }
        redirect("admin/issues.php?a=edit&id=$id#sections");
    }
    if ($issue && $do === 'send') {
        if ($issue['status'] !== 'published') { flash('Publish the issue before emailing it.', 'err'); redirect("admin/issues.php?a=edit&id=$id"); }
        $host = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        $link = $host . url('newsletter.php?s=' . $issue['slug']);
        $subs = ($issue['audience'] ?? 'school') === 'alumni'
            ? rows("SELECT email, 'a_' || token AS token FROM alumni_members WHERE newsletter = 1 AND status != 'ignored' AND email != ''")
            : rows('SELECT email,token FROM subscribers WHERE active = 1');
        $ok = 0;
        foreach ($subs as $s) {
            $unsub = $host . url('unsubscribe.php?t=' . $s['token']);
            $body = '<div style="font-family:Arial,sans-serif;max-width:620px;margin:auto"><h2 style="color:' . e(setting('primary_color')) . '">' . e($issue['title']) . '</h2>'
                . ($issue['cover'] ? '<img src="' . e($host . media($issue['cover'])) . '" alt="" style="max-width:100%;border-radius:8px">' : '')
                . '<p>' . e($issue['summary'] ?: 'Our latest monthly newsletter is out.') . '</p><p><a href="' . e($link) . '" style="background:' . e(setting('accent_color')) . ';color:#111;padding:10px 18px;border-radius:6px;text-decoration:none;font-weight:bold">Read the newsletter</a></p>'
                . '<hr><p style="font-size:12px;color:#777">You receive this because you subscribed to the ' . e(setting('short_name')) . ' newsletter. <a href="' . e($unsub) . '">Unsubscribe</a></p></div>';
            $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: " . setting('short_name') . ' <' . setting('email') . ">\r\n";
            if (@mail($s['email'], $issue['title'], $body, $headers)) $ok++;
        }
        q('UPDATE newsletter_issues SET sent_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), $id]);
        q('INSERT INTO newsletters (subject,body,recipients,sent_ok) VALUES (?,?,?,?)', ['[Issue] ' . $issue['title'], $link, count($subs), $ok]);
        flash("Emailed: $ok of " . count($subs) . ' subscribers accepted by the mail server.' . ($ok < count($subs) ? ' (On local XAMPP, configure sendmail/SMTP in php.ini to send real email.)' : ''), $ok || !$subs ? 'ok' : 'err');
        redirect("admin/issues.php?a=edit&id=$id");
    }
    redirect('admin/issues.php');
}

$issue = $id ? row('SELECT * FROM newsletter_issues WHERE id = ?', [$id]) : null;

if ($a === 'new' || $a === 'edit') {
    if ($a === 'edit' && !$issue) redirect('admin/issues.php');
    admin_head($issue ? 'Edit issue' : 'New issue');
    $sections = $issue ? rows('SELECT * FROM newsletter_sections WHERE issue_id = ? ORDER BY sort, id', [$id]) : [];
    ?>
    <p><a href="issues.php">← All issues</a>
      <?php if ($issue): ?> · <a href="<?= e(url('newsletter.php?s=' . $issue['slug'] . '&preview=1')) ?>" target="_blank">Preview ↗</a><?php endif; ?></p>
    <form class="card" method="post" enctype="multipart/form-data" action="issues.php?a=<?= $issue ? 'edit&id=' . $id : 'new' ?>">
      <h2>Issue details</h2><?= csrf_field() ?><input type="hidden" name="do" value="save">
      <div class="f"><label>Title *</label><input type="text" name="title" value="<?= e($issue['title'] ?? '') ?>" placeholder="e.g. AFSS Monthly — March 2026" required></div>
      <div class="cols">
        <div class="f"><label>Issue date *</label><input type="date" name="issue_date" value="<?= e($issue['issue_date'] ?? date('Y-m-d')) ?>" required><small>Used for the month and year. A future date schedules publishing.</small></div>
        <div class="f"><label>Edition label (optional)</label><input type="text" name="edition" value="<?= e($issue['edition'] ?? '') ?>" placeholder="e.g. Vol. 4, No. 3"></div>
        <div class="f"><label>Status</label><select name="status"><option value="draft" <?= ($issue['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft (hidden)</option><option value="published" <?= ($issue['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option></select></div>
        <div class="f"><label>Audience</label><select name="audience"><option value="school" <?= ($issue['audience'] ?? 'school') === 'school' ? 'selected' : '' ?>>School newsletter (News page)</option><option value="alumni" <?= ($issue['audience'] ?? '') === 'alumni' ? 'selected' : '' ?>>Alumni newsletter (Alumni page)</option></select></div>
      </div>
      <div class="f"><label>Short summary</label><textarea name="summary" placeholder="One or two sentences for the archive and the email."><?= e($issue['summary'] ?? '') ?></textarea></div>
      <div class="f"><label>Editor's note / introduction (HTML)</label><textarea class="html" name="intro" style="min-height:150px"><?= e($issue['intro'] ?? '') ?></textarea></div>
      <div class="cols">
        <div class="f"><label>Cover image</label>
          <?php if (!empty($issue['cover'])): ?><img class="preview" src="<?= e(media($issue['cover'])) ?>" alt=""><label style="font-weight:400"><input type="checkbox" name="cover_remove" value="1"> Remove</label><?php endif; ?>
          <input type="file" name="cover_file" accept="image/*"></div>
        <div class="f"><label>Designed newsletter (PDF, optional, max 15MB)</label>
          <?php if (!empty($issue['pdf'])): ?><p><a href="<?= e(media($issue['pdf'])) ?>" target="_blank">Current PDF ↗</a> <label style="font-weight:400"><input type="checkbox" name="pdf_remove" value="1"> Remove</label></p><?php endif; ?>
          <input type="file" name="pdf_file" accept=".pdf,application/pdf"><small>Upload a design made in Canva/Word, or write the stories below — or do both.</small></div>
      </div>
      <button class="btn accent">Save issue</button>
    </form>

    <?php if ($issue): ?>
    <div class="card" id="sections"><h2>Stories &amp; pictures (<?= count($sections) ?>)</h2>
      <p class="muted">Each story becomes a block in the online newsletter. Lower "order" numbers appear first.</p>
      <?php foreach ($sections as $s): ?>
        <form method="post" enctype="multipart/form-data" action="issues.php?a=edit&id=<?= $id ?>" style="border:1px solid var(--line);border-radius:10px;padding:1rem;margin-bottom:1rem">
          <?= csrf_field() ?><input type="hidden" name="do" value="section_save"><input type="hidden" name="sid" value="<?= $s['id'] ?>">
          <div class="cols"><div class="f"><label>Heading</label><input type="text" name="heading" value="<?= e($s['heading']) ?>"></div>
            <div class="f"><label>Order</label><input type="number" name="sort" value="<?= (int) $s['sort'] ?>"></div></div>
          <div class="f"><label>Story text (HTML)</label><textarea class="html" name="body" style="min-height:130px"><?= e($s['body']) ?></textarea></div>
          <div class="cols"><div class="f"><label>Picture</label><?php if ($s['image']): ?><img class="preview" src="<?= e(media($s['image'])) ?>" alt=""><label style="font-weight:400"><input type="checkbox" name="image_remove" value="1"> Remove</label><?php endif; ?><input type="file" name="image_file" accept="image/*"></div>
            <div class="f"><label>Caption</label><input type="text" name="caption" value="<?= e($s['caption']) ?>"><label style="margin-top:.6rem">Layout</label>
              <select name="layout"><?php foreach (LAYOUTS as $k => $l): ?><option value="<?= $k ?>" <?= $s['layout'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div></div>
          <div class="actions"><button class="btn sm">Save story</button>
            <button class="btn sm danger" name="do" value="section_delete" formnovalidate onclick="return confirm('Remove this story?')">Remove</button></div>
        </form>
      <?php endforeach; ?>
      <form method="post" enctype="multipart/form-data" action="issues.php?a=edit&id=<?= $id ?>" style="border:2px dashed var(--accent);border-radius:10px;padding:1rem">
        <h3 style="margin-top:0">+ Add a story</h3><?= csrf_field() ?><input type="hidden" name="do" value="section_add">
        <div class="cols"><div class="f"><label>Heading</label><input type="text" name="heading" placeholder="e.g. Sports Day highlights"></div>
          <div class="f"><label>Order</label><input type="number" name="sort" value="<?= count($sections) + 1 ?>"></div></div>
        <div class="f"><label>Story text (HTML)</label><textarea class="html" name="body" style="min-height:130px"></textarea></div>
        <div class="cols"><div class="f"><label>Picture</label><input type="file" name="image_file" accept="image/*"></div>
          <div class="f"><label>Caption</label><input type="text" name="caption"><label style="margin-top:.6rem">Layout</label>
            <select name="layout"><?php foreach (LAYOUTS as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></div></div>
        <button class="btn accent">Add story</button>
      </form>
    </div>
    <div class="card"><h2>Email this issue</h2>
      <p class="muted">Sends the cover, summary and a "Read the newsletter" link to all <?= (int) ($issue['audience'] === 'alumni' ? val("SELECT COUNT(*) FROM alumni_members WHERE newsletter=1 AND status != 'ignored' AND email != ''") : val('SELECT COUNT(*) FROM subscribers WHERE active=1')) ?> <?= $issue['audience'] === 'alumni' ? 'alumni who opted in' : 'active subscribers' ?>.<?= $issue['sent_at'] ? ' Last sent ' . e(date('j M Y H:i', strtotime($issue['sent_at']))) . '.' : '' ?></p>
      <form method="post" action="issues.php?a=edit&id=<?= $id ?>" data-confirm="Email this issue to all active subscribers?"><?= csrf_field() ?><input type="hidden" name="do" value="send"><button class="btn" <?= $issue['status'] === 'published' ? '' : 'disabled title="Publish first"' ?>>✉ Send to subscribers</button></form>
    </div>
    <?php endif; admin_foot(); exit;
}

$items = rows('SELECT i.*, (SELECT COUNT(*) FROM newsletter_sections s WHERE s.issue_id = i.id) AS n FROM newsletter_issues i ORDER BY issue_date DESC, id DESC');
admin_head('Monthly Newsletter');
?>
<div class="bar"><a class="btn accent" href="issues.php?a=new">+ New issue</a><span class="muted"><?= count($items) ?> issue(s) · <a href="<?= e(url('newsletters.php')) ?>" target="_blank">View on site ↗</a></span></div>
<div class="tablewrap"><table>
  <thead><tr><th>Cover</th><th>Issue</th><th>Month</th><th>Stories</th><th>PDF</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($items as $it): ?>
  <tr><td><?= $it['cover'] ? '<img class="thumb" src="' . e(media($it['cover'])) . '" alt="">' : '<span class="muted">—</span>' ?></td>
    <td><a href="issues.php?a=edit&id=<?= $it['id'] ?>"><strong><?= e($it['title']) ?></strong></a><?= $it['edition'] ? '<br><small class="muted">' . e($it['edition']) . '</small>' : '' ?><?= ($it['audience'] ?? 'school') === 'alumni' ? ' <span class="badge">Alumni</span>' : '' ?></td>
    <td><?= e(date('F Y', strtotime($it['issue_date']))) ?></td><td><?= (int) $it['n'] ?></td><td><?= $it['pdf'] ? '✔' : '—' ?></td>
    <td><span class="badge <?= e($it['status']) ?>"><?= e($it['status']) ?></span></td>
    <td class="actions" style="justify-content:flex-end"><a class="btn sm" href="issues.php?a=edit&id=<?= $it['id'] ?>">Edit</a>
      <form method="post" action="issues.php" data-confirm="Delete this issue and all its stories?"><?= csrf_field() ?><input type="hidden" name="do" value="delete_issue"><input type="hidden" name="id" value="<?= $it['id'] ?>"><button class="btn sm danger">Delete</button></form></td></tr>
  <?php endforeach; if (!$items): ?><tr><td colspan="7" class="muted">No issues yet. Create your first monthly newsletter.</td></tr><?php endif; ?>
</tbody></table></div>
<?php admin_foot(); ?>
