<?php
require_once __DIR__ . '/_layout.php';
$a = $_GET['a'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);
require_perm('albums');
$scope = scope_for('albums');
if ($scope) {
    $chkId = $id ?: (int) ($_POST['id'] ?? 0);
    if ($chkId && !in_scope($scope, row('SELECT category FROM albums WHERE id = ?', [$chkId]))) deny();
}

function rm_file(?string $p): void { if ($p && str_starts_with($p, 'uploads/')) @unlink(ROOT . '/' . $p); }

/** Save several files from one <input type=file multiple name="photos[]"> through the standard validator. */
function upload_many(string $field): array
{
    $saved = []; $errs = [];
    if (empty($_FILES[$field]['name'][0])) return [$saved, $errs];
    foreach ($_FILES[$field]['name'] as $i => $name) {
        $_FILES["_m$i"] = ['name' => $name, 'type' => $_FILES[$field]['type'][$i], 'tmp_name' => $_FILES[$field]['tmp_name'][$i],
            'error' => $_FILES[$field]['error'][$i], 'size' => $_FILES[$field]['size'][$i]];
        try { $p = upload_image("_m$i"); if ($p) $saved[] = $p; }
        catch (RuntimeException $ex) { $errs[] = "$name: " . $ex->getMessage(); }
    }
    return [$saved, $errs];
}

if (is_post()) {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'save') {
        $rec = $id ? row('SELECT * FROM albums WHERE id = ?', [$id]) : null;
        $title = post('title');
        $date = post('event_date');
        $errors = [];
        if ($title === '') $errors[] = 'Album title is required.';
        if ($date !== '' && !strtotime($date)) $errors[] = 'Event date is not valid.';
        $cover = $rec['cover'] ?? '';
        try { $new = upload_image('cover_file'); } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); $new = null; }
        if ($errors) { foreach ($errors as $er) flash($er, 'err'); redirect('admin/albums.php?a=' . ($id ? "edit&id=$id" : 'new')); }
        if (!empty($_POST['cover_remove'])) { rm_file($cover); $cover = ''; }
        if ($new) { rm_file($cover); $cover = $new; }
        $vals = [$title, ($scope && !in_array(post('category'), $scope['values'], true)) ? $scope['values'][0] : (post('category') ?: 'General'), $date ?: null, post('term'), trim((string) $_POST['description']), $cover, isset($_POST['active']) ? 1 : 0];
        if ($rec) {
            q('UPDATE albums SET title=?,category=?,event_date=?,term=?,description=?,cover=?,active=? WHERE id=?', array_merge($vals, [$id]));
            flash('Album saved.');
            redirect("admin/albums.php?a=photos&id=$id");
        }
        q('INSERT INTO albums (title,category,event_date,term,description,cover,active,slug) VALUES (?,?,?,?,?,?,?,?)', array_merge($vals, [unique_slug('albums', $title)]));
        flash('Album created. Now add photos.');
        redirect('admin/albums.php?a=photos&id=' . db()->lastInsertId());
    }
    if ($do === 'delete_album') {
        $aid = (int) $_POST['id'];
        foreach (rows('SELECT image FROM gallery WHERE album_id = ?', [$aid]) as $p) rm_file($p['image']);
        rm_file(val('SELECT cover FROM albums WHERE id = ?', [$aid]));
        q('DELETE FROM gallery WHERE album_id = ?', [$aid]);
        q('DELETE FROM albums WHERE id = ?', [$aid]);
        flash('Album and its photos deleted.');
        redirect('admin/albums.php');
    }
    if ($do === 'upload' && $id && row('SELECT id FROM albums WHERE id = ?', [$id])) {
        [$saved, $errs] = upload_many('photos');
        $cap = post('caption');
        foreach ($saved as $p) q('INSERT INTO gallery (title,category,image,album_id,sort) VALUES (?,?,?,?,0)', [$cap, val('SELECT category FROM albums WHERE id=?', [$id]), $p, $id]);
        if ($saved) flash(count($saved) . ' photo(s) uploaded.');
        foreach ($errs as $er) flash($er, 'err');
        if (!$saved && !$errs) flash('Choose at least one photo.', 'err');
        redirect("admin/albums.php?a=photos&id=$id");
    }
    if ($do === 'photo_delete') {
        $p = row('SELECT * FROM gallery WHERE id = ?', [(int) $_POST['pid']]);
        if ($p) { rm_file($p['image']); q('DELETE FROM gallery WHERE id = ?', [$p['id']]); flash('Photo deleted.'); }
        redirect("admin/albums.php?a=photos&id=$id");
    }
    if ($do === 'photo_caption') {
        q('UPDATE gallery SET title = ? WHERE id = ? AND album_id = ?', [post('caption'), (int) $_POST['pid'], $id]);
        flash('Caption saved.');
        redirect("admin/albums.php?a=photos&id=$id");
    }
    if ($do === 'photo_cover') {
        $p = row('SELECT image FROM gallery WHERE id = ? AND album_id = ?', [(int) $_POST['pid'], $id]);
        if ($p) { rm_file(val('SELECT cover FROM albums WHERE id = ?', [$id])); q('UPDATE albums SET cover = ? WHERE id = ?', ['', $id]); q('UPDATE albums SET cover = ? WHERE id = ?', [$p['image'], $id]); flash('Cover photo set.'); }
        redirect("admin/albums.php?a=photos&id=$id");
    }
    redirect('admin/albums.php');
}

$album = $id ? row('SELECT * FROM albums WHERE id = ?', [$id]) : null;

if ($a === 'new' || $a === 'edit') {
    if ($a === 'edit' && !$album) redirect('admin/albums.php');
    admin_head($album ? 'Edit album' : 'New album');
    $cats = $scope ? $scope['values'] : gallery_categories();
    if ($album && !in_array($album['category'], $cats, true)) array_unshift($cats, $album['category']);
    ?>
    <p><a href="albums.php">← All albums</a></p>
    <form class="card" method="post" enctype="multipart/form-data" action="albums.php?a=<?= $album ? 'edit&id=' . $album['id'] : 'new' ?>">
      <?= csrf_field() ?><input type="hidden" name="do" value="save">
      <div class="f"><label>Album title *</label><input type="text" name="title" value="<?= e($album['title'] ?? '') ?>" placeholder="e.g. Inter-House Sports Day 2026" required></div>
      <div class="cols">
        <div class="f"><label>Event / function</label><select name="category"><?php foreach ($cats as $c): ?><option <?= ($album['category'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
          <small>Manage this list in Site Settings → Photo gallery.</small></div>
        <div class="f"><label>Event date</label><input type="date" name="event_date" value="<?= e($album['event_date'] ?? date('Y-m-d')) ?>"><small>The year is used for the year filter on the website.</small></div>
        <div class="f"><label>Term / time (optional)</label><input type="text" name="term" value="<?= e($album['term'] ?? '') ?>" placeholder="e.g. Term II, 9:00 AM"></div>
      </div>
      <div class="f"><label>Description</label><textarea name="description"><?= e($album['description'] ?? '') ?></textarea></div>
      <div class="f"><label>Cover image (optional — first photo is used if empty)</label>
        <?php if (!empty($album['cover'])): ?><img class="preview" src="<?= e(media($album['cover'])) ?>" alt=""><label style="font-weight:400"><input type="checkbox" name="cover_remove" value="1"> Remove cover</label><?php endif; ?>
        <input type="file" name="cover_file" accept="image/*"></div>
      <div class="f"><label><input type="checkbox" name="active" value="1" <?= ($album['active'] ?? 1) ? 'checked' : '' ?>> Visible on website</label></div>
      <button class="btn accent">Save album</button>
    </form>
    <?php admin_foot(); exit;
}

if ($a === 'photos') {
    if (!$album) redirect('admin/albums.php');
    admin_head('Photos · ' . $album['title']);
    $photos = rows('SELECT * FROM gallery WHERE album_id = ? ORDER BY sort, id DESC', [$id]);
    ?>
    <p><a href="albums.php">← All albums</a> · <a href="albums.php?a=edit&id=<?= $id ?>">Edit album details</a> · <a href="<?= e(url('gallery.php?a=' . $album['slug'])) ?>" target="_blank">View on site ↗</a></p>
    <form class="card" method="post" enctype="multipart/form-data" action="albums.php?a=photos&id=<?= $id ?>">
      <h2>Upload photos to this album</h2><?= csrf_field() ?><input type="hidden" name="do" value="upload">
      <div class="cols"><div class="f"><label>Photos (select many, JPG/PNG/WebP, max 5MB each, up to 20 at a time)</label><input type="file" name="photos[]" accept="image/*" multiple required></div>
        <div class="f"><label>Caption for these photos (optional)</label><input type="text" name="caption"></div></div>
      <button class="btn accent">Upload</button>
    </form>
    <div class="card"><h2><?= count($photos) ?> photo(s)</h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:1rem">
      <?php foreach ($photos as $p): $isCover = $album['cover'] === $p['image']; ?>
        <div style="border:1px solid var(--line);border-radius:10px;padding:.6rem">
          <img src="<?= e(media($p['image'])) ?>" alt="" style="width:100%;height:130px;object-fit:cover;border-radius:8px"><?php if ($isCover): ?><span class="badge yes">Cover</span><?php endif; ?>
          <form method="post" action="albums.php?a=photos&id=<?= $id ?>" style="margin:.5rem 0"><?= csrf_field() ?><input type="hidden" name="do" value="photo_caption"><input type="hidden" name="pid" value="<?= $p['id'] ?>">
            <input type="text" name="caption" value="<?= e($p['title']) ?>" placeholder="Caption" style="width:100%;padding:.35rem .5rem;border:1px solid var(--line);border-radius:6px"><button class="btn sm ghost" style="margin-top:.3rem">Save caption</button></form>
          <div class="actions">
            <?php if (!$isCover): ?><form method="post" action="albums.php?a=photos&id=<?= $id ?>"><?= csrf_field() ?><input type="hidden" name="do" value="photo_cover"><input type="hidden" name="pid" value="<?= $p['id'] ?>"><button class="btn sm ghost">Make cover</button></form><?php endif; ?>
            <form method="post" action="albums.php?a=photos&id=<?= $id ?>" data-confirm="Delete this photo?"><?= csrf_field() ?><input type="hidden" name="do" value="photo_delete"><input type="hidden" name="pid" value="<?= $p['id'] ?>"><button class="btn sm danger">Delete</button></form>
          </div></div>
      <?php endforeach; if (!$photos): ?><p class="muted">No photos yet — upload some above.</p><?php endif; ?>
      </div></div>
    <?php admin_foot(); exit;
}

// list
$year = $_GET['year'] ?? ''; $cat = $_GET['cat'] ?? '';
$where = []; $params = [];
if (preg_match('/^\d{4}$/', $year)) { $where[] = "strftime('%Y', event_date) = ?"; $params[] = $year; }
if ($cat !== '') { $where[] = 'category = ?'; $params[] = $cat; }
if ($scope) { $where[] = 'category IN (' . implode(',', array_fill(0, count($scope['values']), '?')) . ')'; $params = array_merge($params, $scope['values']); }
$items = rows('SELECT a.*, (SELECT COUNT(*) FROM gallery g WHERE g.album_id = a.id) AS n, (SELECT image FROM gallery g WHERE g.album_id = a.id ORDER BY sort, id DESC LIMIT 1) AS first_img FROM albums a'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY event_date DESC, id DESC', $params);
$years = array_column(rows("SELECT DISTINCT strftime('%Y', event_date) y FROM albums WHERE event_date IS NOT NULL ORDER BY y DESC"), 'y');
admin_head('Photo Albums');
?>
<div class="bar">
  <a class="btn accent" href="albums.php?a=new">+ New album</a>
  <form method="get" style="display:flex;gap:.4rem;flex-wrap:wrap">
    <select name="year" onchange="this.form.submit()"><option value="">All years</option><?php foreach ($years as $y): ?><option <?= $year === $y ? 'selected' : '' ?>><?= e($y) ?></option><?php endforeach; ?></select>
    <select name="cat" onchange="this.form.submit()"><option value="">All events</option><?php foreach (gallery_categories() as $c): ?><option <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
  </form>
  <span class="muted"><?= count($items) ?> album(s)</span>
</div>
<div class="tablewrap"><table>
  <thead><tr><th>Cover</th><th>Album</th><th>Event</th><th>Date</th><th>Photos</th><th>Visible</th><th></th></tr></thead><tbody>
  <?php foreach ($items as $it): $img = $it['cover'] ?: $it['first_img']; ?>
  <tr><td><?= $img ? '<img class="thumb" src="' . e(media($img)) . '" alt="">' : '<span class="muted">—</span>' ?></td>
    <td><a href="albums.php?a=photos&id=<?= $it['id'] ?>"><strong><?= e($it['title']) ?></strong></a><?= $it['term'] ? '<br><small class="muted">' . e($it['term']) . '</small>' : '' ?></td>
    <td><?= e($it['category']) ?></td><td><?= e(fmt_date($it['event_date'])) ?></td><td><?= (int) $it['n'] ?></td>
    <td><span class="badge <?= $it['active'] ? 'yes' : 'no' ?>"><?= $it['active'] ? 'Yes' : 'Hidden' ?></span></td>
    <td class="actions" style="justify-content:flex-end"><a class="btn sm" href="albums.php?a=photos&id=<?= $it['id'] ?>">Photos</a><a class="btn sm ghost" href="albums.php?a=edit&id=<?= $it['id'] ?>">Edit</a>
      <form method="post" action="albums.php" data-confirm="Delete this album and ALL its photos?"><?= csrf_field() ?><input type="hidden" name="do" value="delete_album"><input type="hidden" name="id" value="<?= $it['id'] ?>"><button class="btn sm danger">Delete</button></form></td></tr>
  <?php endforeach; if (!$items): ?><tr><td colspan="7" class="muted">No albums yet. Create one for Sports Day, MDD Day, Visitation Day and more.</td></tr><?php endif; ?>
</tbody></table></div>
<?php admin_foot(); ?>
