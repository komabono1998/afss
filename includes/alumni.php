<?php
/** Alumni database helpers: statuses, membership numbers, validity, filters, card links. */

const ALUMNI_STATUSES = ['new' => 'Pending approval', 'approved' => 'Approved', 'rejected' => 'Rejected', 'suspended' => 'Suspended'];

function alumni_log(int $id, string $action, string $detail = ''): void
{
    q('INSERT INTO alumni_log (member_id,user_name,action,detail) VALUES (?,?,?,?)', [$id, admin_user()['name'] ?? 'member', $action, $detail]);
}

function alumni_validity_months(): int { return max(1, min(120, (int) (setting('alumni_validity_months') ?: 12))); }

/** Next membership number, e.g. AFSSOSA/2026/0001 (prefix / year of approval / running number). */
function next_member_no(): array
{
    $prefix = preg_replace('/[^A-Za-z0-9]/', '', setting('alumni_prefix') ?: 'AFSSOSA') ?: 'AFSSOSA';
    $year = (int) date('Y');
    $seq = (int) val('SELECT COALESCE(MAX(member_seq),0) FROM alumni_members WHERE member_year = ?', [$year]) + 1;
    return [sprintf('%s/%d/%04d', $prefix, $year, $seq), $year, $seq];
}

/** Approve a member: issue a number (once), set validity dates, make sure there is a card token. Returns the membership number. */
function alumni_approve(int $id, ?int $months = null): string
{
    $m = row('SELECT * FROM alumni_members WHERE id = ?', [$id]);
    if (!$m) throw new RuntimeException('Member not found.');
    $months = $months ?: alumni_validity_months();
    $no = $m['member_no'];
    for ($try = 0; $try < 6; $try++) {
        $year = $m['member_year']; $seq = $m['member_seq'];
        if (!$no) [$no, $year, $seq] = next_member_no();
        try {
            q("UPDATE alumni_members SET status='approved', member_no=?, member_year=?, member_seq=?, approved_at=?, approved_by=?, valid_from=?, valid_to=?, reject_reason=NULL, card_token=COALESCE(NULLIF(card_token,''),?) WHERE id=?",
                [$no, $year, $seq, date('Y-m-d H:i:s'), admin_user()['name'] ?? '', date('Y-m-d'), date('Y-m-d', strtotime("+$months months")), bin2hex(random_bytes(12)), $id]);
            alumni_log($id, 'approved', "Membership $no, valid to " . date('j M Y', strtotime("+$months months")));
            return $no;
        } catch (PDOException $ex) {
            if (!str_contains($ex->getMessage(), 'UNIQUE')) throw $ex;
            $no = $m['member_no'] ? $m['member_no'] : null;   // number taken meanwhile: draw the next one
        }
    }
    throw new RuntimeException('Could not allocate a membership number.');
}

/** Extend validity by $months from today, or from the current expiry if that is later. */
function alumni_renew(int $id, ?int $months = null): string
{
    $months = $months ?: alumni_validity_months();
    $cur = (string) val('SELECT valid_to FROM alumni_members WHERE id = ?', [$id]);
    $base = ($cur !== '' && $cur > date('Y-m-d')) ? $cur : date('Y-m-d');
    $to = date('Y-m-d', strtotime("$base +$months months"));
    q("UPDATE alumni_members SET valid_to = ?, valid_from = CASE WHEN valid_from IS NULL OR valid_from = '' THEN ? ELSE valid_from END WHERE id = ?", [$to, date('Y-m-d'), $id]);
    alumni_log($id, 'renewed', "Valid to " . date('j M Y', strtotime($to)));
    return $to;
}

/** pending | rejected | suspended | valid | expiring | expired */
function alumni_state(array $m): string
{
    if ($m['status'] === 'new') return 'pending';
    if ($m['status'] !== 'approved') return $m['status'];
    $to = (string) $m['valid_to'];
    if ($to === '' || $to < date('Y-m-d')) return 'expired';
    return $to <= date('Y-m-d', strtotime('+60 days')) ? 'expiring' : 'valid';
}
function alumni_state_label(string $s): string
{
    return ['pending' => 'Pending approval', 'rejected' => 'Rejected', 'suspended' => 'Suspended', 'valid' => 'Valid', 'expiring' => 'Expiring soon', 'expired' => 'Expired'][$s] ?? $s;
}
function alumni_state_badge(string $s): string
{
    $cls = ['pending' => 'new', 'rejected' => 'rejected', 'suspended' => 'rejected', 'valid' => 'yes', 'expiring' => 'reviewing', 'expired' => 'no'][$s] ?? '';
    return '<span class="badge ' . $cls . '">' . e(alumni_state_label($s)) . '</span>';
}

function alumni_card_url(array $m): string
{
    $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $host . url('card.php?t=' . $m['card_token']);
}
function alumni_verify_url(string $memberNo): string
{
    $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $host . url('verify.php?m=' . rawurlencode($memberNo));
}

/** Email the member their card link. Returns true if the mail server accepted it. */
function alumni_email_card(array $m): bool
{
    if (empty($m['email']) || empty($m['card_token'])) return false;
    $body = "Dear {$m['name']},\n\nYour membership of " . setting('alumni_name', 'the AFSS Old Students Association') . " has been approved.\n\n"
        . "Membership number: {$m['member_no']}\nValid until: " . date('j F Y', strtotime((string) $m['valid_to'])) . "\n\n"
        . "View and print your membership card here:\n" . alumni_card_url($m) . "\n\nWelcome back to the family!\n" . setting('school_name');
    $headers = "Content-Type: text/plain; charset=UTF-8\r\nFrom: " . setting('short_name', 'AFSS') . ' <' . setting('email', 'noreply@localhost') . ">\r\n";
    return (bool) @mail($m['email'], 'Your ' . setting('short_name', 'AFSS') . ' alumni membership card', $body, $headers);
}

/** Filters for the alumni database: q, status, state, year, profession. */
function alumni_filters(array $g): array
{
    $w = []; $p = [];
    foreach (array_filter(preg_split('/\s+/', trim((string) ($g['q'] ?? '')))) as $tok) {
        $w[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR member_no LIKE ? OR profession LIKE ? OR workplace LIKE ? OR location LIKE ? OR year_left LIKE ? OR last_class LIKE ?)';
        for ($i = 0; $i < 9; $i++) $p[] = "%$tok%";
    }
    $st = (string) ($g['status'] ?? '');
    if (isset(ALUMNI_STATUSES[$st])) { $w[] = 'status = ?'; $p[] = $st; }
    $state = (string) ($g['state'] ?? '');
    $today = date('Y-m-d');
    if ($state === 'valid') { $w[] = "status = 'approved' AND valid_to >= ?"; $p[] = $today; }
    elseif ($state === 'expiring') { $w[] = "status = 'approved' AND valid_to >= ? AND valid_to <= ?"; $p[] = $today; $p[] = date('Y-m-d', strtotime('+60 days')); }
    elseif ($state === 'expired') { $w[] = "status = 'approved' AND (valid_to IS NULL OR valid_to < ?)"; $p[] = $today; }
    if (!empty($g['year'])) { $w[] = 'year_left = ?'; $p[] = (string) $g['year']; }
    if (!empty($g['profession'])) { $w[] = 'profession LIKE ?'; $p[] = '%' . $g['profession'] . '%'; }
    if (!empty($g['newsletter'])) { $w[] = 'newsletter = 1'; }
    return [$w ? ' WHERE ' . implode(' AND ', $w) : '', $p];
}

/** Full export table (CSV/Excel) or the compact PDF version. */
function alumni_export_table(array $data, bool $pdf = false): array
{
    $h = $pdf ? ['Member No', 'Name', 'Class of', 'Profession', 'Workplace', 'Location', 'Phone', 'Email', 'Status', 'Valid until']
              : ['Member No', 'Name', 'Email', 'Phone', 'Year left', 'Last class', 'Profession', 'Workplace', 'Location', 'Newsletter', 'Consent to constitution', 'Registration status', 'Membership state', 'Approved on', 'Valid from', 'Valid until', 'Registered', 'Notes'];
    $rows = [];
    foreach ($data as $m) {
        $state = alumni_state_label(alumni_state($m));
        $rows[] = $pdf
            ? [$m['member_no'], $m['name'], $m['year_left'], $m['profession'], $m['workplace'], $m['location'], $m['phone'], $m['email'], $state, $m['valid_to']]
            : [$m['member_no'], $m['name'], $m['email'], $m['phone'], $m['year_left'], $m['last_class'], $m['profession'], $m['workplace'], $m['location'], $m['newsletter'] ? 'Yes' : 'No', $m['consent'] ? 'Yes' : 'No',
                ALUMNI_STATUSES[$m['status']] ?? $m['status'], $state, substr((string) $m['approved_at'], 0, 10), $m['valid_from'], $m['valid_to'], substr((string) $m['created_at'], 0, 10), $m['notes'] ?? ''];
    }
    return [$h, $rows];
}
