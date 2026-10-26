<?php
/** Shared pieces for the Health Services and Counselling pages: topic lists, request form, submission handling. */

function welfare_lines(string $key): array
{
    $v = setting($key) !== '' ? setting($key) : (welfare_settings()[$key] ?? '');
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $v))));
}
function welfare_text(string $key): string { return setting($key) !== '' ? setting($key) : (welfare_settings()[$key] ?? ''); }

/** Handle a POSTed help request. Returns a list of error strings; on success stores it and redirects to ?sent=1. */
function welfare_handle_post(string $kind, string $pageUrl): array
{
    if (!is_post() || ($_POST['do'] ?? '') !== 'help') return [];
    csrf_check();
    $errors = [];
    if (!spam_guard('help_' . $kind, 6, 900)) $errors[] = 'Too many requests from this device in a short time. Please try again later, or call the school.';
    $topics = welfare_lines($kind . '_topics');
    $topic = post('topic'); $other = post('topic_other');
    if ($topic === '__other') { if ($other === '') $errors[] = 'Please type what you need help with in the “Other” box.'; $topic = 'Other'; }
    elseif (!in_array($topic, $topics, true)) $errors[] = 'Please choose what you need help with.';
    $msg = trim((string) ($_POST['message'] ?? ''));
    if ($msg === '') $errors[] = 'Please tell us a little about what is happening.';
    $anon = !empty($_POST['anonymous']);
    if (!$anon && post('name') === '' && post('contact') === '') $errors[] = 'Give your name or a way to reach you, or tick “I prefer to stay anonymous”.';
    if (!$errors) {
        $ref = 'HR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        q('INSERT INTO help_requests (ref,kind,topic,topic_other,urgent,anonymous,name,class_name,role_text,contact,preferred_time,message) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [$ref, $kind, $topic, $topic === 'Other' ? $other : '', !empty($_POST['urgent']) ? 1 : 0, $anon ? 1 : 0, $anon ? '' : post('name'), $anon ? '' : post('class_name'), post('role_text'), $anon ? '' : post('contact'), post('preferred_time'), $msg]);
        notify(($_POST['urgent'] ?? '') ? 'URGENT: ' : '' . 'New ' . ($kind === 'health' ? 'health' : 'counselling') . ' request ' . $ref,
            'A new ' . ($kind === 'health' ? 'health' : 'counselling') . " request was submitted" . ($anon ? ' anonymously' : '') . ".\nOpen the admin panel → Student Welfare → Help Requests.");
        $_SESSION['help_receipt'] = ['ref' => $ref, 'kind' => $kind, 'anon' => $anon];
        redirect($pageUrl . '?sent=1#help');
    }
    return $errors;
}

/** Print the request form for $kind ('health' or 'counselling'). */
function welfare_form(string $kind, array $errors): void
{
    $health = $kind === 'health';
    $topics = welfare_lines($kind . '_topics');
    $receipt = (!empty($_GET['sent']) && !empty($_SESSION['help_receipt']) && $_SESSION['help_receipt']['kind'] === $kind) ? $_SESSION['help_receipt'] : null;
    if ($receipt) unset($_SESSION['help_receipt']);
    $roles = ['Student', 'Parent or guardian', 'Teacher or staff member', 'Other'];
    ?>
    <section class="section alt" id="help"><div class="container" style="max-width:860px">
      <div class="section-head"><span class="eyebrow"><?= $health ? 'Seek help' : 'Seek counselling' ?></span><h2><?= $health ? 'Ask for health support' : 'Talk to someone' ?></h2>
        <p><?= $health ? 'Tell us what is wrong and we will get back to you.' : 'Reaching out is a strong thing to do. Tell us a little and a counsellor will respond.' ?></p></div>
      <div class="notice" style="margin-bottom:1.2rem">🚨 <strong>In danger or an emergency?</strong> Do not wait for this form.<br><?= nl2br(e(welfare_text('emergency_info'))) ?><?= setting('phone') ? '<br>School: <strong>' . e(setting('phone')) . '</strong>' : '' ?></div>
      <?php if ($receipt): ?>
        <div class="card"><div class="card-body" style="text-align:center;padding:2rem 1.5rem">
          <div style="font-size:2.6rem">💚</div><h3>Thank you for reaching out</h3>
          <p>Your request has been received<?= $receipt['anon'] ? ' anonymously' : '' ?>. <?= $receipt['anon'] ? 'Because you did not leave contact details we cannot reply, so please come to the ' . ($health ? 'health unit' : 'counselling centre') . ' when you are ready.' : 'A member of the ' . ($health ? 'health' : 'counselling') . ' team will contact you soon.' ?></p>
          <p>Reference: <strong style="font-size:1.2rem"><?= e($receipt['ref']) ?></strong> <span class="meta">(quote this if you visit or call)</span></p></div></div>
      <?php else: ?>
        <?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>
        <form class="form" method="post" action="#help"><?= csrf_field() ?><input type="hidden" name="do" value="help"><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
          <div class="field"><label class="req"><?= $health ? 'What do you need help with?' : 'What would you like to talk about?' ?></label>
            <select name="topic" id="hr-topic" required><option value="">Choose…</option>
              <?php foreach ($topics as $t): if (stripos($t, 'Other (type') === 0) continue; ?><option <?= ($_POST['topic'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
              <option value="__other" <?= ($_POST['topic'] ?? '') === '__other' ? 'selected' : '' ?>>Other (type your own)</option></select></div>
          <div class="field" id="hr-other" style="display:none"><label class="req">Tell us what it is about</label><input name="topic_other" value="<?= old('topic_other') ?>" maxlength="120"></div>
          <div class="field"><label class="req">Tell us more</label><textarea name="message" rows="5" required placeholder="<?= $health ? 'Describe the symptoms or the situation.' : 'Share as much or as little as you like.' ?>"><?= old('message') ?></textarea></div>
          <label style="display:flex;gap:.6rem;align-items:flex-start;margin-bottom:.7rem"><input type="checkbox" name="urgent" value="1" <?= isset($_POST['urgent']) ? 'checked' : '' ?>> <span><strong>This is urgent</strong> — please contact me as soon as possible.</span></label>
          <label style="display:flex;gap:.6rem;align-items:flex-start;margin-bottom:1rem"><input type="checkbox" name="anonymous" id="hr-anon" value="1" <?= isset($_POST['anonymous']) ? 'checked' : '' ?>> <span><strong>I prefer to stay anonymous</strong> — we will not know who you are, but we also cannot reply.</span></label>
          <div id="hr-who"><div class="row">
            <div class="field"><label>Your name</label><input name="name" value="<?= old('name') ?>"></div>
            <div class="field"><label>Class (if a student)</label><input name="class_name" value="<?= old('class_name') ?>" placeholder="e.g. Senior 3 A"></div>
            <div class="field"><label>Phone or email</label><input name="contact" value="<?= old('contact') ?>"></div>
            <div class="field"><label>Best time to reach you</label><input name="preferred_time" value="<?= old('preferred_time') ?>" placeholder="e.g. after lessons"></div></div></div>
          <div class="field"><label>I am a…</label><select name="role_text"><option value="">—</option><?php foreach ($roles as $r): ?><option <?= ($_POST['role_text'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select></div>
          <?php if (!$health): ?><p class="meta">🔒 <?= e(welfare_text('counselling_promise')) ?></p><?php endif; ?>
          <button class="btn btn-primary" type="submit"><?= $health ? 'Send my request' : 'Ask for counselling' ?></button>
        </form>
        <script>(function(){var t=document.getElementById('hr-topic'),o=document.getElementById('hr-other'),oi=o.querySelector('input'),a=document.getElementById('hr-anon'),w=document.getElementById('hr-who');
          function u(){var other=t.value==='__other';o.style.display=other?'block':'none';oi.required=other;w.style.display=a.checked?'none':'block';}
          t.addEventListener('change',u);a.addEventListener('change',u);u();})();</script>
      <?php endif; ?>
    </div></section>
    <?php
}

/** Shared layout for the health and counselling pages. */
function welfare_page(string $kind): void
{
    global $pageTitle, $pageDesc, $pageImage, $bannerTitle, $bannerSub, $crumbs;   // the header/banner templates read these
    $pageImage = null;
    $health = $kind === 'health';
    $self = $health ? 'health.php' : 'counselling.php';
    $errors = welfare_handle_post($kind, url($self));
    $slug = $health ? 'health-services' : 'counselling';
    $page = row('SELECT * FROM pages WHERE slug = ?', [$slug]);
    $services = rows('SELECT * FROM welfare_services WHERE type = ? AND active = 1 ORDER BY sort, id', [$kind]);
    $title = $page['title'] ?? ($health ? 'Health Services' : 'Counselling and Career Centre');
    $pageTitle = $title;
    $pageDesc = welfare_text($kind . '_intro');
    $bannerTitle = $title;
    $bannerSub = $health ? 'Caring for the health and wellbeing of every learner.' : 'A safe, confidential place to talk, learn and plan your future.';
    $crumbs = [['Student Life', url('page.php?slug=clubs')]];
    include __DIR__ . '/header.php';
    include __DIR__ . '/banner.php';
    $contact = welfare_text($kind . '_contact') ?: setting('phone');
    ?>
    <section class="section"><div class="container">
      <div class="split" style="margin-bottom:3rem;align-items:start">
        <div class="reveal"><span class="eyebrow"><?= $health ? 'Health unit' : 'Counselling & careers' ?></span><h2><?= $health ? 'Here when you need us' : 'You are not alone' ?></h2>
          <div class="prose wide"><?= $page ? $page['body'] : '<p>' . e(welfare_text($kind . '_intro')) . '</p>' ?></div>
          <p><a class="btn btn-accent" href="#help"><?= $health ? '🩺 Seek help' : '💬 Seek counselling' ?></a> <a class="btn btn-ghost" href="#services">Our services</a></p></div>
        <div class="card reveal"><div class="card-body">
          <h3 style="margin-top:0">Find us</h3>
          <dl class="give-dl">
            <dt>Where</dt><dd><?= e(welfare_text($kind . '_location')) ?></dd>
            <dt>When</dt><dd><?= e(welfare_text($kind . '_hours')) ?></dd>
            <?php if ($contact): ?><dt>Contact</dt><dd><strong><?= e($contact) ?></strong></dd><?php endif; ?>
          </dl>
          <?php if (!$health): ?><p class="meta" style="margin-top:1rem">🔒 <?= e(welfare_text('counselling_promise')) ?></p><?php endif; ?>
        </div></div>
      </div>
      <?php if ($services): ?>
      <div class="section-head" id="services"><span class="eyebrow">What we offer</span><h2><?= $health ? 'Health services' : 'Counselling and career services' ?></h2></div>
      <div class="grid g3">
        <?php foreach ($services as $s): ?><div class="card feature reveal"><div class="ico"><?= $health ? '🩺' : '💬' ?></div><h3><?= e($s['title']) ?></h3><p><?= e($s['description']) ?></p></div><?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if ($health && welfare_text('health_parents_note') !== ''): ?>
        <div class="notice" style="margin-top:2.5rem"><strong>For parents and guardians:</strong> <?= e(welfare_text('health_parents_note')) ?></div>
      <?php endif; ?>
    </div></section>
    <?php
    welfare_form($kind, $errors);
    include __DIR__ . '/footer.php';
}
