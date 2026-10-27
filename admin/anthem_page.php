<?php
require_once __DIR__ . '/_layout.php';
require_perm('anthem');

const ANTHEM_AUDIO = ['mp3' => ['audio/mpeg', 'audio/mp3'], 'm4a' => ['audio/mp4', 'audio/x-m4a', 'video/mp4', 'audio/m4a'], 'ogg' => ['audio/ogg', 'application/ogg'], 'wav' => ['audio/x-wav', 'audio/wav', 'audio/wave', 'audio/vnd.wave']];

/** Save an uploaded anthem recording (MP3, M4A, OGG or WAV) into the public uploads folder. */
function anthem_save_audio(string $field): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed (error ' . $f['error'] . '). The file may be too large for this server.');
    if ($f['size'] > 25 * 1048576) throw new RuntimeException('The recording is larger than 25MB. Please compress it (an MP3 of 3 to 5 MB is plenty).');
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!isset(ANTHEM_AUDIO[$ext])) throw new RuntimeException('Use an MP3, M4A, OGG or WAV file.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!in_array($mime, ANTHEM_AUDIO[$ext], true)) throw new RuntimeException('That file does not look like a real ' . strtoupper($ext) . ' recording.');
    $name = 'anthem_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $name)) throw new RuntimeException('Could not save the recording.');
    return 'uploads/' . $name;
}
function anthem_youtube_id(string $u): string
{
    return preg_match('#(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/))([A-Za-z0-9_-]{11})#', $u, $m) ? $m[1] : '';
}

if (is_post()) {
    csrf_check();
    try {
        foreach (['anthem_title', 'anthem_intro', 'anthem_lyrics', 'anthem_credits'] as $k) save_setting($k, trim((string) ($_POST[$k] ?? '')));
        save_setting('anthem_show', isset($_POST['anthem_show']) ? '1' : '0');
        $vid = trim((string) ($_POST['anthem_video'] ?? ''));
        if ($vid !== '' && !anthem_youtube_id($vid)) throw new RuntimeException('Please paste a normal YouTube link (youtube.com/watch?v=… or youtu.be/…), or leave it empty.');
        save_setting('anthem_video', $vid);
        $audio = anthem_save_audio('anthem_audio_file');
        if ($audio) { $old = setting('anthem_audio'); save_setting('anthem_audio', $audio); if ($old && str_starts_with($old, 'uploads/anthem_')) @unlink(ROOT . '/' . $old); }
        elseif (!empty($_POST['anthem_audio_remove'])) { $old = setting('anthem_audio'); save_setting('anthem_audio', ''); if ($old && str_starts_with($old, 'uploads/anthem_')) @unlink(ROOT . '/' . $old); }
        $img = upload_image('anthem_image_file');
        if ($img) save_setting('anthem_image', $img); elseif (!empty($_POST['anthem_image_remove'])) save_setting('anthem_image', '');
        flash('School anthem saved.');
    } catch (RuntimeException $ex) { flash($ex->getMessage(), 'err'); }
    redirect('admin/anthem_page.php');
}
admin_head('School Anthem');
$aud = setting('anthem_audio');
?>
<form class="card" method="post" enctype="multipart/form-data"><?= csrf_field() ?>
  <h2>School anthem</h2>
  <p class="muted">Feature the anthem on its own page and as a highlight on the home page. Add the recording, the words and, if you like, a video. Only add lyrics and recordings that the school owns or has permission to publish.</p>
  <div class="f"><label><input type="checkbox" name="anthem_show" value="1" <?= setting('anthem_show') !== '0' ? 'checked' : '' ?>> Show the anthem on the home page and in the menus</label></div>
  <div class="cols">
    <div class="f"><label>Title</label><input type="text" name="anthem_title" value="<?= e(setting('anthem_title')) ?>"></div>
    <div class="f"><label>Credits (optional)</label><input type="text" name="anthem_credits" value="<?= e(setting('anthem_credits')) ?>" placeholder="e.g. Words and music by …, sung by the school choir"></div>
  </div>
  <div class="f"><label>Short introduction</label><textarea name="anthem_intro"><?= e(setting('anthem_intro')) ?></textarea><small>One or two lines shown above the player and on the home page.</small></div>
  <div class="f"><label>Anthem words (lyrics)</label><textarea name="anthem_lyrics" style="min-height:260px"><?= e(setting('anthem_lyrics')) ?></textarea><small>Type or paste the words. Leave a blank line between verses and the chorus. Lines are kept exactly as you type them.</small></div>
  <div class="f"><label>Recording (MP3, M4A, OGG or WAV, up to 25MB)</label>
    <?php if ($aud): ?><audio controls preload="none" src="<?= e(media($aud)) ?>" style="display:block;margin:.4rem 0;max-width:100%"></audio><label style="font-weight:400"><input type="checkbox" name="anthem_audio_remove" value="1"> Remove the current recording</label><br><?php endif; ?>
    <input type="file" name="anthem_audio_file" accept=".mp3,.m4a,.ogg,.wav,audio/*"></div>
  <div class="f"><label>YouTube video (optional)</label><input type="text" name="anthem_video" value="<?= e(setting('anthem_video')) ?>" placeholder="https://www.youtube.com/watch?v=…"><small>If the anthem is on YouTube, paste the link and a video appears on the anthem page.</small></div>
  <div class="f"><label>Cover picture (optional)</label>
    <?php if (setting('anthem_image')): ?><img class="preview" src="<?= e(media(setting('anthem_image'))) ?>" alt=""><label style="font-weight:400"><input type="checkbox" name="anthem_image_remove" value="1"> Remove the picture</label><br><?php endif; ?>
    <input type="file" name="anthem_image_file" accept="image/*"><small>For example the school choir or the crest. Wide pictures work best.</small></div>
  <button class="btn accent">Save</button> <a class="btn ghost" href="<?= url('anthem.php') ?>" target="_blank">View the anthem page ↗</a>
</form>
<?php admin_foot(); ?>
