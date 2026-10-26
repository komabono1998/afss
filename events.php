<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_GET['id'])) {
    $ev = row('SELECT * FROM events WHERE id = ?', [(int) $_GET['id']]);
    if (!$ev) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
    $pageTitle = $ev['title'];
    $bannerTitle = $ev['title'];
    $crumbs = [['Events', url('events.php')]];
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/banner.php';
    ?>
    <div class="container content-wrap"><article class="prose">
      <?php if ($ev['image']): ?><img src="<?= e(media($ev['image'])) ?>" alt=""><?php endif; ?>
      <p><span class="tag"><?= e($ev['category']) ?></span></p>
      <p><strong>When:</strong> <?= fmt_date($ev['start_date'], 'l, j F Y') ?><?= $ev['end_date'] && $ev['end_date'] !== $ev['start_date'] ? ' – ' . fmt_date($ev['end_date'], 'l, j F Y') : '' ?>
        <?= $ev['time_text'] ? ' · ' . e($ev['time_text']) : '' ?><br>
        <?php if ($ev['location']): ?><strong>Where:</strong> <?= e($ev['location']) ?><?php endif; ?></p>
      <div><?= nl2br(e($ev['description'])) ?></div>
      <p><a href="<?= url('events.php') ?>">← Back to calendar</a></p>
    </article></div>
    <?php include __DIR__ . '/includes/footer.php'; exit;
}

// month grid
$m = preg_match('/^\d{4}-\d{2}$/', $_GET['m'] ?? '') ? $_GET['m'] : date('Y-m');
$first = strtotime($m . '-01');
$days = (int) date('t', $first);
$startDow = (int) date('N', $first); // 1 = Monday
$monthStart = date('Y-m-01', $first);
$monthEnd = date('Y-m-t', $first);
$monthEvents = rows('SELECT * FROM events WHERE start_date <= ? AND COALESCE(end_date,start_date) >= ? ORDER BY start_date', [$monthEnd, $monthStart]);
$byDay = [];
foreach ($monthEvents as $ev) {
    $s = max(strtotime($ev['start_date']), $first);
    $en = min(strtotime($ev['end_date'] ?: $ev['start_date']), strtotime($monthEnd));
    for ($t = $s; $t <= $en; $t += 86400) $byDay[(int) date('j', $t)][] = $ev;
}
$upcoming = rows("SELECT * FROM events WHERE date(COALESCE(end_date,start_date)) >= date('now') ORDER BY start_date LIMIT 12");
$prev = date('Y-m', strtotime('-1 month', $first));
$next = date('Y-m', strtotime('+1 month', $first));

$pageTitle = 'Events & Calendar';
$bannerTitle = 'Events & Calendar';
$bannerSub = 'Key dates in the AFSS school year.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/banner.php';
?>
<section class="section">
  <div class="container split split-cal">
    <div>
      <div class="cal-nav">
        <a class="btn btn-ghost btn-sm" href="?m=<?= $prev ?>">← <?= date('M', strtotime($prev . '-01')) ?></a>
        <h2 style="margin:0"><?= date('F Y', $first) ?></h2>
        <a class="btn btn-ghost btn-sm" href="?m=<?= $next ?>"><?= date('M', strtotime($next . '-01')) ?> →</a>
      </div>
      <table class="cal">
        <thead><tr><?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d) echo "<th>$d</th>"; ?></tr></thead>
        <tbody>
        <?php
        $cell = 1 - ($startDow - 1);
        while ($cell <= $days):
            echo '<tr>';
            for ($c = 0; $c < 7; $c++, $cell++):
                if ($cell < 1 || $cell > $days) { echo '<td class="out"></td>'; continue; }
                $isToday = date('Y-m-d') === date('Y-m-d', strtotime("$m-$cell"));
                echo '<td class="' . ($isToday ? 'today' : '') . '"><span class="d">' . $cell . '</span>';
                foreach (array_slice($byDay[$cell] ?? [], 0, 3) as $ev) echo '<a class="ev" href="' . url('events.php?id=' . $ev['id']) . '" title="' . e($ev['title']) . '">' . e($ev['title']) . '</a>';
                echo '</td>';
            endfor;
            echo '</tr>';
        endwhile; ?>
        </tbody>
      </table>
    </div>
    <aside>
      <h3>Upcoming</h3>
      <div class="grid" style="gap:.8rem">
        <?php foreach ($upcoming as $ev): ?>
        <a class="event-row" href="<?= url('events.php?id=' . $ev['id']) ?>"><div class="date-box"><b><?= fmt_date($ev['start_date'], 'j') ?></b><small><?= fmt_date($ev['start_date'], 'M') ?></small></div>
          <div><h3><?= e($ev['title']) ?></h3><span class="meta"><?= e($ev['category']) ?><?= $ev['time_text'] ? ' · ' . e($ev['time_text']) : '' ?></span></div></a>
        <?php endforeach; if (!$upcoming): ?><p>No upcoming events.</p><?php endif; ?>
      </div>
    </aside>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
