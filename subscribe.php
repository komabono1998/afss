<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!is_post()) redirect('');
csrf_check();
$email = strtolower(post('email'));
$back = $_SERVER['HTTP_REFERER'] ?? url();
if (!spam_guard('subscribe', 5)) { flash('Please try again later.', 'err'); redirect($back); }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('Please enter a valid email address.', 'err');
} else {
    $existing = row('SELECT id,active FROM subscribers WHERE email = ?', [$email]);
    if ($existing) q('UPDATE subscribers SET active = 1 WHERE id = ?', [$existing['id']]);
    else q('INSERT INTO subscribers (email, token) VALUES (?,?)', [$email, bin2hex(random_bytes(12))]);
    flash('Thank you! You are subscribed to the AFSS newsletter.');
}
redirect($back);
