<?php
require_once __DIR__ . '/_layout.php';
$all = require __DIR__ . '/entities.php';
$key = $_GET['e'] ?? '';
if (!isset($all[$key])) redirect('admin/index.php');
$E = $all[$key];
require_perm($key);
$scope = scope_for($key);
function scoped_options(array $opts, ?array $scope, string $field): array
{
    return ($scope && $scope['col'] === $field) ? array_intersect_key($opts, array_flip($scope['values'])) : $opts;
}
$table = $E['table']; // from trusted config only, never user input
$action = $_GET['a'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);
$self = 'crud.php?e=' . $key;

function field_default($f)
{
    $d = $f['default'] ?? '';
    return $d === 'today' ? date('Y-m-d') : $d;
}

// ----- POST handlers -----
if (is_post()) {
    csrf_check();
    if (($_POST['do'] ?? '') === 'delete') {
        $rec = row("SELECT * FROM $table WHERE id = ?", [(int) $_POST['id']]);
        if ($rec && in_scope($scope, $rec)) {
            foreach ($E['fields'] as $fk => $f) if ($f[1] === 'image' && !empty($rec[$fk]) && str_starts_with($rec[$fk], 'uploads/')) @unlink(ROOT . '/' . $rec[$fk]);
            q("DELETE FROM $table WHERE id = ?", [$rec['id']]);
            flash(ucfirst($E['singular']) . ' deleted.');
        }
        redirect("admin/$self");
    }
    // save
    $rec = $id ? row("SELECT * FROM $table WHERE id = ?", [$id]) : null;
    if ($id && !in_scope($scope, $rec)) deny();
    $data = [];
    $errors = [];
    foreach ($E['fields'] as $fk => $f) {
        $type = $f[1];
        if ($type === 'image') {
            try { $new = upload_image($fk . '_file'); } catch (RuntimeException $ex) { $errors[] = $f[0] . ': ' . $ex->getMessage(); $new = null; }
            $cur = $rec[$fk] ?? '';
            if (!empty($_POST[$fk . '_remove'])) { if ($cur && str_starts_with($cur, 'uploads/')) @unlink(ROOT . '/' . $cur); $cur = ''; }
            if ($new) { if ($cur && str_starts_with($cur, 'uploads/')) @unlink(ROOT . '/' . $cur); $cur = $new; }
            $data[$fk] = $cur;
            if (!empty($f['required']) && $cur === '') $errors[] = $f[0] . ' is required.';
            continue;
        }
        if ($type === 'check') { $data[$fk] = isset($_POST[$fk]) ? 1 : 0; continue; }
        $v = $type === 'html' || $type === 'textarea' ? trim((string) ($_POST[$fk] ?? '')) : post($fk);
        if ($type === 'number') $v = (int) $v;
        if ($type === 'date' && $v !== '' && !strtotime($v)) { $errors[] = $f[0] . ' is not a valid date.'; }
        if ($type === 'select' && !isset($f['options'][$v])) $v = array_key_first($f['options']);
        if (!empty($f['required']) && $v === '') $errors[] = $f[0] . ' is required.';
        if ($fk === 'slug') continue;
        $data[$fk] = ($v === '' && in_array($type, ['date'], true)) ? null : $v;
    }
    if ($scope && !in_array($data[$scope['col']] ?? '', $scope['values'], true)) $data[$scope['col']] = $scope['values'][0];
    if (isset($E['fields']['slug'])) {
        $s = slugify(post('slug') !== '' ? post('slug') : (string) ($data[$E['slug']] ?? ''));
        $data['slug'] = unique_slug($table, $s, $id);
    } elseif (!empty($E['slug'])) {
        $data['slug'] = unique_slug($table, (string) $data[$E['slug']], $id);
    }
    if ($errors) {
        foreach ($errors as $er) flash($er, 'err');
        $action = $id ? 'edit' : 'new';
        $formData = $_POST + ($rec ?? []);
    } else {
        $cols = array_keys($data);
        if ($id && $rec) {
            q("UPDATE $table SET " . implode(',', array_map(fn($c) => "$c = ?", $cols)) . ' WHERE id = ?', array_merge(array_values($data), [$id]));
            flash('Saved changes.');
        } else {
            q("INSERT INTO $table (" . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', array_values($data));
            flash(ucfirst($E['singular']) . ' created.');
        }
        redirect("admin/$self");
    }
}

// ----- views -----
admin_head($E['label']);

if ($action === 'new' || $action === 'edit') {
    $rec = $action === 'edit' ? row("SELECT * FROM $table WHERE id = ?", [$id]) : null;
    if ($rec && !in_scope($scope, $rec)) deny();
    if ($action === 'edit' && !$rec) { echo '<div class="alert alert-err">Record not found.</div>'; admin_foot(); exit; }
    $d = $formData ?? $rec ?? [];
    $fields = $E['fields'];
    ?>
    <p><a href="<?= e($self) ?>">← Back to <?= e($E['label']) ?></a></p>
    <form class="card" method="post" enctype="multipart/form-data" action="<?= e($self . ($rec ? '&a=edit&id=' . $rec['id'] : '&a=new')) ?>">
      <h2><?= $rec ? 'Edit' : 'New' ?> <?= e($E['singular']) ?></h2>
      <?= csrf_field() ?>
      <div class="cols">
      <?php foreach ($fields as $fk => $f):
          [$label, $type] = $f;
          $v = $d[$fk] ?? field_default($f);
          $wide = in_array($type, ['html', 'textarea'], true) || $fk === 'title' || $type === 'image';
          if ($wide) echo '</div>'; ?>
        <div class="f"<?= $wide ? '' : '' ?>>
          <?php if ($type === 'check'): ?>
            <label><input type="checkbox" name="<?= e($fk) ?>" value="1" <?= $v ? 'checked' : '' ?>> <?= e($label) ?></label>
          <?php else: ?>
            <label for="f_<?= e($fk) ?>"><?= e($label) ?><?= !empty($f['required']) ? ' *' : '' ?></label>
            <?php if ($type === 'text' || $type === 'url'): ?><input type="text" id="f_<?= e($fk) ?>" name="<?= e($fk) ?>" value="<?= e($v) ?>">
            <?php elseif ($type === 'number'): ?><input type="number" id="f_<?= e($fk) ?>" name="<?= e($fk) ?>" value="<?= e($v) ?>">
            <?php elseif ($type === 'date'): ?><input type="date" id="f_<?= e($fk) ?>" name="<?= e($fk) ?>" value="<?= e($v) ?>">
            <?php elseif ($type === 'textarea'): ?><textarea id="f_<?= e($fk) ?>" name="<?= e($fk) ?>"><?= e($v) ?></textarea>
            <?php elseif ($type === 'html'): ?><textarea class="html" id="f_<?= e($fk) ?>" name="<?= e($fk) ?>"><?= e($v) ?></textarea><small>HTML allowed — use the toolbar for headings, bold, lists and links.</small><br>
            <?php elseif ($type === 'select'): ?><select id="f_<?= e($fk) ?>" name="<?= e($fk) ?>"><?php foreach (scoped_options($f['options'], $scope, $fk) as $ov => $ol): ?><option value="<?= e($ov) ?>" <?= (string) $v === (string) $ov ? 'selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?></select>
            <?php elseif ($type === 'image'): ?>
              <?php if (!empty($rec[$fk])): ?><img class="preview" src="<?= e(media($rec[$fk])) ?>" alt=""><label style="font-weight:400"><input type="checkbox" name="<?= e($fk) ?>_remove" value="1"> Remove current image</label><?php endif; ?>
              <input type="file" name="<?= e($fk) ?>_file" accept="image/*">
            <?php endif; ?>
            <?php if (!empty($f['help'])): ?><small><?= e($f['help']) ?></small><?php endif; ?>
          <?php endif; ?>
        </div>
        <?php if ($wide) echo '<div class="cols">'; ?>
      <?php endforeach; ?>
      </div>
      <div class="actions"><button class="btn accent">Save</button><a class="btn ghost" href="<?= e($self) ?>">Cancel</a></div>
    </form>
    <?php
    admin_foot();
    exit;
}

// list view
$search = trim($_GET['q'] ?? '');
$where = '1=1';
$params = [];
if ($search !== '') {
    $where = '(' . implode(' OR ', array_map(fn($c) => "$c LIKE ?", $E['search'])) . ')';
    $params = array_fill(0, count($E['search']), "%$search%");
}
[$scopeSql, $scopeParams] = scope_sql($scope);
$items = rows("SELECT * FROM $table WHERE $where$scopeSql ORDER BY {$E['order']} LIMIT 500", array_merge($params, $scopeParams));
?>
<div class="bar">
  <a class="btn accent" href="<?= e($self) ?>&a=new">+ New <?= e($E['singular']) ?></a>
  <form method="get" style="display:flex;gap:.4rem"><input type="hidden" name="e" value="<?= e($key) ?>"><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search…"><button class="btn ghost">Search</button></form>
  <span class="muted"><?= count($items) ?> item(s)</span>
</div>
<div class="tablewrap"><table>
  <thead><tr><?php foreach ($E['list'] as $c => $l) echo '<th>' . e($l) . '</th>'; ?><th></th></tr></thead>
  <tbody>
  <?php foreach ($items as $it): ?>
    <tr>
      <?php foreach ($E['list'] as $c => $l): $f = $E['fields'][$c] ?? ['', 'text']; $v = $it[$c] ?? ''; ?>
        <td><?php
          if ($f[1] === 'image') echo $v ? '<img class="thumb" src="' . e(media($v)) . '" alt="">' : '<span class="muted">—</span>';
          elseif ($f[1] === 'check') echo '<span class="badge ' . ($v ? 'yes' : 'no') . '">' . ($v ? 'Yes' : 'No') . '</span>';
          elseif ($f[1] === 'select' && in_array($c, ['status'], true)) echo '<span class="badge ' . e($v) . '">' . e($f['options'][$v] ?? $v) . '</span>';
          elseif ($f[1] === 'select') echo e($f['options'][$v] ?? $v);
          elseif ($f[1] === 'date') echo e(fmt_date($v));
          elseif ($c === array_key_first($E['list'])|| $c === 'title' || $c === 'name') echo '<a href="' . e($self) . '&a=edit&id=' . $it['id'] . '"><strong>' . e($v) . '</strong></a>';
          else echo e($v); ?></td>
      <?php endforeach; ?>
      <td class="actions" style="justify-content:flex-end">
        <a class="btn sm ghost" href="<?= e($self) ?>&a=edit&id=<?= $it['id'] ?>">Edit</a>
        <form method="post" action="<?= e($self) ?>" data-confirm="Delete this <?= e($E['singular']) ?>? This cannot be undone.">
          <?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $it['id'] ?>"><button class="btn sm danger">Delete</button></form>
      </td>
    </tr>
  <?php endforeach; if (!$items): ?><tr><td colspan="<?= count($E['list']) + 1 ?>" class="muted">Nothing here yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php admin_foot(); ?>
