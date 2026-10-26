<?php
// Switch the public website off (maintenance page) or back on. Admin area stays reachable.
require_once __DIR__ . '/../includes/bootstrap.php';
$ME = require_admin();
require_perm('settings');

if (is_post()) {
    csrf_check();
    $msg = trim(post('message'));
    if ($msg !== '') save_setting('maintenance_message', mb_substr($msg, 0, 600));
    $act = post('action');
    if ($act === 'off') {
        save_setting('maintenance_mode', '1');
        flash('The website is now OFFLINE for visitors. They see the "back soon" page; you can still browse while signed in.');
    } elseif ($act === 'on') {
        save_setting('maintenance_mode', '0');
        flash('The website is LIVE again.');
    } else {
        flash('Maintenance message saved.');
    }
}
redirect('admin/index.php');
