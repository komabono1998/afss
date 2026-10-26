<?php
require_once __DIR__ . '/_layout.php';

require_perm('settings');
// key => [label, type, help]
$groups = [
    'School identity' => [
        'school_name' => ['School name', 'text'], 'short_name' => ['Short name', 'text'], 'tagline' => ['Motto / tagline', 'text'],
        'intro' => ['Short description', 'textarea', 'Used in the footer and search-engine descriptions.'],
        'mission' => ['Mission statement', 'textarea'], 'vision' => ['Vision statement', 'textarea'],
        'logo' => ['Logo', 'image', 'Leave empty to use the AFSS crest that ships with the site.'],
        'moes_reg' => ['MoES registration no.', 'text'], 'licence' => ['Licence no.', 'text'], 'uneb_centre' => ['UNEB centre no.', 'text'],
    ],
    'Contact details' => [
        'address' => ['Address', 'text'], 'phone' => ['Phone', 'text'], 'phone2' => ['Second phone', 'text'],
        'email' => ['Public email', 'text'], 'notify_email' => ['Notification email', 'text', 'Receives alerts for applications, messages and job applications.'],
        'map_embed' => ['Map embed URL', 'text', 'The src of a Google Maps or OpenStreetMap embed.'],
    ],
    'Social media' => [
        'facebook' => ['Facebook URL', 'text'], 'twitter' => ['X / Twitter URL', 'text'], 'instagram' => ['Instagram URL', 'text'],
        'youtube' => ['YouTube URL', 'text'], 'whatsapp' => ['WhatsApp link', 'text'],
    ],
    'Home page' => [
        'announcement' => ['Announcement bar text', 'text', 'Leave empty to hide the top bar.'],
        'stat_students' => ['Stat 1 value', 'text'], 'stat_students_l' => ['Stat 1 label', 'text'],
        'stat_teachers' => ['Stat 2 value', 'text'], 'stat_teachers_l' => ['Stat 2 label', 'text'],
        'stat_years' => ['Stat 3 value', 'text'], 'stat_years_l' => ['Stat 3 label', 'text'],
        'stat_firstgrade' => ['Stat 4 value', 'text'], 'stat_firstgrade_l' => ['Stat 4 label', 'text'],
    ],
    'Whistle-blower page' => [
        'whistle_intro' => ['Page introduction', 'textarea', 'Shown at the top of the public Report a Concern page.'],
        'whistle_categories' => ['Concern types (one per line)', 'textarea', 'These fill the dropdown. An “Other (type your own)” option is always added automatically.'],
    ],
    'Photo gallery' => [
        'gallery_categories' => ['Event / function categories', 'textarea', 'One per line. These appear when creating albums and as filters on the gallery page.'],
    ],
    'Scholarships page' => [
        'scholarships_intro' => ['Page introduction', 'textarea'],
        'scholarships_apply' => ['General "How to apply" text (HTML allowed)', 'textarea', 'Shown at the bottom of the Scholarships & Bursaries page.'],
    ],
    'Admissions' => [
        'admissions_open' => ['Accept online applications', 'check'],
        'intake_text' => ['Intake message', 'textarea', 'Shown on the home page, admissions page and call-to-action.'],
    ],
    'Appearance' => [
        'primary_color' => ['Main colour (green)', 'color'], 'secondary_color' => ['Action colour (red)', 'color', 'Used for Apply buttons and highlights.'], 'accent_color' => ['Accent colour (gold)', 'color'],
    ],
];

if (is_post()) {
    csrf_check();
    $err = false;
    foreach ($groups as $fields) {
        foreach ($fields as $k => $f) {
            if ($f[1] === 'image') {
                try {
                    $new = upload_image($k . '_file');
                    if ($new) save_setting($k, $new);
                    if (!empty($_POST[$k . '_remove'])) save_setting($k, '');
                } catch (RuntimeException $ex) { flash($f[0] . ': ' . $ex->getMessage(), 'err'); $err = true; }
            } elseif ($f[1] === 'check') {
                save_setting($k, isset($_POST[$k]) ? '1' : '0');
            } elseif ($f[1] === 'color') {
                if (preg_match('/^#[0-9a-fA-F]{6}$/', post($k))) save_setting($k, post($k));
            } else {
                save_setting($k, post($k));
            }
        }
    }
    if (!$err) flash('Settings saved.');
    redirect('admin/settings.php');
}
admin_head('Site Settings');
?>
<form method="post" enctype="multipart/form-data">
<?= csrf_field() ?>
<?php foreach ($groups as $g => $fields): ?>
  <div class="card"><h2><?= e($g) ?></h2><div class="cols">
  <?php foreach ($fields as $k => $f): $v = setting($k); ?>
    <div class="f">
      <?php if ($f[1] === 'check'): ?><label><input type="checkbox" name="<?= e($k) ?>" value="1" <?= $v === '1' ? 'checked' : '' ?>> <?= e($f[0]) ?></label>
      <?php else: ?><label><?= e($f[0]) ?></label>
        <?php if ($f[1] === 'textarea'): ?><textarea name="<?= e($k) ?>"><?= e($v) ?></textarea>
        <?php elseif ($f[1] === 'color'): ?><input type="color" name="<?= e($k) ?>" value="<?= e($v ?: '#000000') ?>" style="height:42px;width:90px;padding:2px">
        <?php elseif ($f[1] === 'image'): ?><?php if ($v): ?><img class="preview" src="<?= e(media($v)) ?>" alt=""><label style="font-weight:400"><input type="checkbox" name="<?= e($k) ?>_remove" value="1"> Remove</label><?php endif; ?><input type="file" name="<?= e($k) ?>_file" accept="image/*">
        <?php else: ?><input type="text" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endif; ?>
        <?php if (!empty($f[2])): ?><small><?= e($f[2]) ?></small><?php endif; ?>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div></div>
<?php endforeach; ?>
<button class="btn accent">Save all settings</button>
</form>
<?php admin_foot(); ?>
