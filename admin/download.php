<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$f = basename((string) ($_GET['f'] ?? ''));
$path = PRIVATE_DIR . '/' . $f;
if ($f === '' || !is_file($path) || !(can('job_apps') && val('SELECT COUNT(*) FROM job_apps WHERE cv_file = ?', [$f]) || can('admissions') && val('SELECT COUNT(*) FROM admissions_apps WHERE results_file = ? OR recommendation_file = ?', [$f, $f]) || can('scholarship_apps') && val('SELECT COUNT(*) FROM scholarship_apps WHERE supporting_file = ?', [$f]) || can('alumni_members') && val('SELECT COUNT(*) FROM alumni_members WHERE photo = ? OR proof_file = ?', [$f, $f]))) { http_response_code(404); exit('Not found'); }
$ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
$mime = DOC_TYPES[$ext] ?? IMG_TYPES[$ext] ?? 'application/octet-stream';
$inline = !empty($_GET['inline']) && isset(IMG_TYPES[$ext]);
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $f . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
readfile($path);
