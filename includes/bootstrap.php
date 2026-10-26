<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('DB_FILE', ROOT . '/data/afss.sqlite');
define('UPLOAD_DIR', ROOT . '/uploads');
define('PRIVATE_DIR', ROOT . '/data/cv');

date_default_timezone_set('Africa/Kampala');

// Base URL path (works whether the site lives in /afss or at the web root)
$__doc = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$__root = str_replace('\\', '/', ROOT);
define('BASE', ($__doc !== '' && str_starts_with($__root, $__doc)) ? substr($__root, strlen($__doc)) : '');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_name('afss_sess');
    session_start();
}

require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/export.php';
require_once __DIR__ . '/districts.php';
require_once __DIR__ . '/import.php';
require_once __DIR__ . '/students.php';
require_once __DIR__ . '/alumni.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $fresh = !file_exists(DB_FILE);
        $pdo = new PDO('sqlite:' . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL;');
        if ($fresh) {
            install_schema($pdo);
            seed_content($pdo);
        }
        migrate($pdo);
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function rows(string $sql, array $params = []): array { return q($sql, $params)->fetchAll(); }
function row(string $sql, array $params = []): ?array { $r = q($sql, $params)->fetch(); return $r ?: null; }
function val(string $sql, array $params = []) { $r = q($sql, $params)->fetchColumn(); return $r === false ? null : $r; }

// ---------- settings ----------
function settings(): array
{
    static $s = null;
    if ($s === null) {
        $s = [];
        foreach (rows('SELECT k,v FROM settings') as $r) $s[$r['k']] = $r['v'];
    }
    return $s;
}
function setting(string $k, string $default = ''): string
{
    $v = settings()[$k] ?? '';
    return $v === '' ? $default : $v;
}
function save_setting(string $k, string $v): void
{
    q('INSERT INTO settings (k,v) VALUES (?,?) ON CONFLICT(k) DO UPDATE SET v = excluded.v', [$k, $v]);
}

// ---------- output helpers ----------
function e($v): string { return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return BASE . '/' . ltrim($path, '/'); }
function asset(string $path): string { return url($path) . '?v=' . (@filemtime(ROOT . '/' . $path) ?: 1); }
function media(?string $path): string { return $path ? url($path) : ''; }
function redirect(string $to): never
{
    if (!preg_match('#^(https?:)?//#', $to)) $to = url($to);
    header('Location: ' . $to);
    exit;
}
function fmt_date(?string $d, string $f = 'j M Y'): string { return $d ? date($f, strtotime($d)) : ''; }
function excerpt(string $html, int $len = 160): string
{
    $t = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    return mb_strlen($t) > $len ? mb_substr($t, 0, $len - 1) . '…' : $t;
}
function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s === '' ? 'item' : $s;
}
function unique_slug(string $table, string $title, int $ignoreId = 0): string
{
    $base = $slug = slugify($title);
    $i = 2;
    while (val("SELECT COUNT(*) FROM $table WHERE slug = ? AND id != ?", [$slug, $ignoreId])) $slug = $base . '-' . $i++;
    return $slug;
}
function link_url(string $u): string
{
    return preg_match('#^(https?:)?//#', $u) ? $u : url($u);
}

// ---------- flash + CSRF ----------
function flash(?string $msg = null, string $type = 'ok')
{
    if ($msg !== null) { $_SESSION['flash'][] = [$type, $msg]; return null; }
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void
{
    $sess = (string) ($_SESSION['csrf'] ?? '');
    if ($sess === '' || !hash_equals($sess, (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(403);
        exit('Security token expired. Go back, refresh the page and try again.');
    }
}
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function old(string $k, string $default = ''): string { return e($_POST[$k] ?? $default); }
function post(string $k): string { return trim((string) ($_POST[$k] ?? '')); }

// Honeypot + simple rate limit for public forms
function spam_guard(string $bucket, int $max = 8, int $window = 600): bool
{
    if (!empty($_POST['website'])) return false; // honeypot field
    $now = time();
    $hits = array_filter($_SESSION['rl'][$bucket] ?? [], fn($t) => $t > $now - $window);
    if (count($hits) >= $max) return false;
    $hits[] = $now;
    $_SESSION['rl'][$bucket] = $hits;
    return true;
}

// ---------- uploads ----------
const IMG_TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
const DOC_TYPES = ['pdf' => 'application/pdf', 'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

/** Returns stored path (relative), null if no file, or throws RuntimeException on invalid upload. */
function handle_upload(string $field, array $types, string $dir, string $prefix, int $maxMb = 5): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed (error ' . $f['error'] . ').');
    if ($f['size'] > $maxMb * 1048576) throw new RuntimeException("File is larger than {$maxMb}MB.");
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!isset($types[$ext])) throw new RuntimeException('File type not allowed. Allowed: ' . implode(', ', array_keys($types)));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $okMimes = [$types[$ext]];
    if ($ext === 'doc') $okMimes[] = 'application/CDFV2';
    if ($ext === 'docx') $okMimes[] = 'application/zip';
    if ($ext === 'xlsx') $okMimes[] = 'application/zip';
    if ($ext === 'csv') array_push($okMimes, 'text/plain', 'application/csv', 'application/vnd.ms-excel', 'text/x-csv');
    if (!in_array($mime, $okMimes, true) && !($ext === 'doc' && str_contains($mime, 'ms'))) {
        throw new RuntimeException('File content does not match its extension.');
    }
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = $prefix . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Could not save file.');
    return $name;
}
function upload_image(string $field): ?string
{
    $n = handle_upload($field, IMG_TYPES, UPLOAD_DIR, 'img_');
    return $n ? 'uploads/' . $n : null;
}

// ---------- mail ----------
function notify(string $subject, string $body, ?string $replyTo = null): void
{
    $to = setting('notify_email', setting('email'));
    if (!$to) return;
    $headers = "Content-Type: text/plain; charset=UTF-8\r\nFrom: " . setting('short_name', 'AFSS') . ' <' . setting('email', 'noreply@localhost') . ">\r\n";
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $headers .= "Reply-To: $replyTo\r\n";
    @mail($to, $subject, $body, $headers);
}

// ---------- auth ----------
function admin_user(): ?array
{
    static $u = false;
    if ($u === false) $u = empty($_SESSION['uid']) ? null : row('SELECT id,name,email,role FROM users WHERE id = ? AND active = 1', [$_SESSION['uid']]);
    return $u;
}
function require_admin(): array
{
    $u = admin_user();
    if (!$u) redirect('admin/login.php');
    return $u;
}

/** Event/function categories for photo albums (one per line in Site Settings). */
function gallery_categories(): array
{
    $c = array_values(array_filter(array_map('trim', preg_split('/\R/', setting('gallery_categories')))));
    return $c ?: ['Campus Life'];
}

/** School population figures entered by staff (dashboard + home page). */
function population(): array
{
    $n = fn($k) => max(0, (int) preg_replace('/\D/', '', setting($k)));
    $boys = $n('pop_boys'); $girls = $n('pop_girls');
    $students = $n('pop_students') ?: $boys + $girls;
    $o = $n('pop_o_level'); $a = $n('pop_a_level');
    try {
        $live = (int) val("SELECT COUNT(*) FROM students WHERE status = 'present'");
        if ($live > 0) {   // real records beat typed-in figures
            $students = $live;
            $boys = (int) val("SELECT COUNT(*) FROM students WHERE status = 'present' AND gender = 'Male'");
            $girls = (int) val("SELECT COUNT(*) FROM students WHERE status = 'present' AND gender = 'Female'");
            $o = (int) val("SELECT COUNT(*) FROM students s JOIN class_streams c ON c.id = s.class_stream_id WHERE s.status = 'present' AND c.level = 'O Level'");
            $a = (int) val("SELECT COUNT(*) FROM students s JOIN class_streams c ON c.id = s.class_stream_id WHERE s.status = 'present' AND c.level = 'A Level'");
        }
    } catch (Throwable $e) { /* table not ready yet */ }
    $teaching = $n('pop_teaching'); $support = $n('pop_support');
    return [
        'students' => $students, 'boys' => $boys, 'girls' => $girls,
        'o_level' => $o, 'a_level' => $a, 'boarding' => $n('pop_boarding'), 'day' => $n('pop_day'),
        'teaching' => $teaching, 'support' => $support, 'staff' => $teaching + $support, 'updated' => setting('pop_updated'),
    ];
}
