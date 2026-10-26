<?php
/**
 * Student data endpoint. Off by default; an administrator switches it on and issues a key in
 * Admin → Student Data API. Returns JSON, or CSV / Excel / PDF with ?format=csv|xlsx|pdf.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

function api_fail(int $code, string $msg): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $msg], JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') api_fail(405, 'Use GET.');
if (setting('api_enabled') !== '1' || setting('api_key_hash') === '') api_fail(503, 'The student data endpoint is switched off.');

$key = '';
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if ($auth === '' && function_exists('getallheaders')) foreach (getallheaders() as $hn => $hv) if (strcasecmp($hn, 'Authorization') === 0) $auth = (string) $hv;
if (preg_match('/^Bearer\s+(\S+)$/i', $auth, $m)) $key = $m[1];
elseif (!empty($_SERVER['HTTP_X_API_KEY'])) $key = (string) $_SERVER['HTTP_X_API_KEY'];
elseif (!empty($_GET['key'])) $key = (string) $_GET['key'];
if ($key === '' || !hash_equals(setting('api_key_hash'), hash('sha256', $key))) { usleep(400000); api_fail(401, 'Missing or invalid API key.'); }

[$w, $p] = student_filters($_GET + ['status' => 'present']);
$withGuardian = setting('api_guardian') === '1';
$format = strtolower((string) ($_GET['format'] ?? 'json'));
save_setting('api_last_access', date('Y-m-d H:i:s'));

if (in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
    $data = rows(STUDENT_SELECT . $w . ' ORDER BY c.sort, c.class_name, c.stream_name, s.last_name, s.first_name', $p);
    [$h, $r] = student_export_table($data, $withGuardian);
    export_send($format, 'Students', $h, $r, 'students');
}

$limit = max(1, min(5000, (int) ($_GET['limit'] ?? 1000)));
$page = max(1, (int) ($_GET['page'] ?? 1));
$total = (int) val('SELECT COUNT(*) FROM students s LEFT JOIN class_streams c ON c.id = s.class_stream_id' . $w, $p);
$data = rows(STUDENT_SELECT . $w . ' ORDER BY s.id LIMIT ' . $limit . ' OFFSET ' . (($page - 1) * $limit), $p);
$out = array_map(function ($s) use ($withGuardian) {
    $r = ['reg_no' => $s['reg_no'], 'surname' => $s['last_name'], 'first_name' => $s['first_name'], 'other_names' => $s['other_names'], 'gender' => $s['gender'],
        'date_of_birth' => $s['dob'], 'district' => $s['district'], 'class' => $s['class_name'], 'stream' => $s['stream_name'], 'status' => $s['status'],
        'status_note' => $s['status_reason'], 'lin' => $s['external_id'], 'registered' => substr((string) $s['created_at'], 0, 10)];
    if ($withGuardian) $r += ['guardian_name' => $s['guardian_name'], 'guardian_phone' => $s['guardian_phone'], 'guardian_email' => $s['guardian_email']];
    return $r;
}, $data);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['school' => setting('school_name'), 'generated_at' => date('c'), 'total' => $total, 'page' => $page, 'limit' => $limit, 'count' => count($out), 'students' => $out],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
