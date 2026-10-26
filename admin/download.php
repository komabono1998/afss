<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$f = basename((string) ($_GET['f'] ?? ''));
$path = PRIVATE_DIR . '/' . $f;
if ($f === '' || !is_file($path) || !val('SELECT COUNT(*) FROM job_apps WHERE cv_file = ?', [$f])) { http_response_code(404); exit('Not found'); }
$ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
header('Content-Type: ' . (DOC_TYPES[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . $f . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
readfile($path);
