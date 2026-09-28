<?php
require_once __DIR__ . '/config.php';
$s = hpl_settings();

$visitor = trim((string)($_GET['name'] ?? ''));
$visitor = preg_replace('/[\x00-\x1F\x7F]/u', '', $visitor);
$visitor = trim(preg_replace('/\s+/u', ' ', $visitor));
if (function_exists('mb_substr')) {
    $visitor = mb_substr($visitor, 0, 40, 'UTF-8');
} else {
    $visitor = substr($visitor, 0, 40);
}
$firstName = $visitor !== '' ? (explode(' ', $visitor)[0] ?? '') : '';

$tyVideo = '';
if (file_exists(__DIR__ . '/img/thankyou.mp4')) {
    $tyVideo = 'img/thankyou.mp4';
} elseif (trim((string)($s['ty_video_drive_id'] ?? '')) !== '' && trim((string)($s['ty_video_drive_id'] ?? '')) !== 'YOUR_DRIVE_FILE_ID') {
    $tyVideo = 'https://drive.usercontent.google.com/download?id=' . h($s['ty_video_drive_id']) . '&export=download&confirm=t';
}

$tyPhotos = [];
for ($i = 1; $i <= 6; $i++) {
    $file = 'ty-' . $i . '.jpg';
    if (file_exists(__DIR__ . '/img/' . $file)) {
        $tyPhotos[] = ['file' => $file, 'caption' => (string)($s['ty_' . $i . '_caption'] ?? '')];
    }
}

$tyVideos = [];
for ($i = 1; $i <= 3; $i++) {
    $pid = trim((string)($s['proof_video_' . $i] ?? ''));
    if ($pid === '' || $pid === 'YOUR_DRIVE_FILE_ID') {
        continue;
    }
    $src = (stripos($pid, 'http') === 0) ? $pid : 'https://drive.usercontent.google.com/download?id=' . $pid . '&export=download&confirm=t';
    $tyVideos[] = ['src' => $src, 'caption' => (string)($s['proof_video_' . $i . '_caption'] ?? '')];
}

$waNumber = preg_replace('/\D/', '', (string)($s['wa_number'] ?? ''));
if (str_starts_with($waNumber, '00')) {
    $waNumber = substr($waNumber, 2);
}
if ($waNumber !== '' && $waNumber[0] === '0') {
    $waNumber = '260' . substr($waNumber, 1);
}
$waUrl = $waNumber !== '' ? 'https://wa.me/' . $waNumber . '?text=' . rawurlencode(str_replace('{name}', $firstName !== '' ? $firstName : 'there', (string)($s['whatsapp_msg'] ?? ''))) : '';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Thank you | <?= h($s['brand_name']) ?></title>
  <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32.png">
  <link rel="icon" type="image/png" sizes="192x192" href="img/favicon-192.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
    :root { --navy:#111a38; --navy-dark:#090f24; --gold:#d4a52c; --gold-light:#f4ca5b; --muted:#687083; --wash:#f4f2ed; }
    * { box-sizing:border-box; }
    body { background:var(--navy); color:#fff; font-family:'DM Sans',sans-serif; margin:0; }
    .wrap { margin:0 auto; max-width:920px; padding:0 24px 70px; }
    .ty-hero { padding:56px 0 34px; text-align:center; }
    .badge { background:var(--gold); border-radius:999px; color:var(--navy-dark); display:inline-block; font-size:12px; font-weight:700; letter-spacing:.13em; padding:9px 16px; text-transform:uppercase; }
    h1 { font-family:'Space Grotesk',sans-serif; font-size:clamp(30px,5vw,46px); letter-spacing:-.03em; line-height:1.08; margin:20px 0 14px; }
    .ty-sub { color:rgba(255,255,255,.82); font-size:17px; line-height:1.6; margin:0 auto; max-width:640px; }
    .next { background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:14px; margin:34px 0 0; padding:26px; }
    .next h2 { font-family:'Space Grotesk',sans-serif; font-size:22px; margin:0 0 18px; }
    .next ol { color:rgba(255,255,255,.85); display:grid; gap:14px; grid-template-columns:repeat(3,1fr); list-style:none; margin:0; padding:0; }
    .next li { font-size:15px; line-height:1.5; }
    .next li b { color:var(--gold-light); display:block; font-size:13px; letter-spacing:.1em; margin-bottom:5px; text-transform:uppercase; }
    .panel { margin-top:44px; }
    .panel-label { color:var(--gold); font-size:12px; font-weight:700; letter-spacing:.13em; margin-bottom:10px; text-transform:uppercase; }
    .panel h2 { font-family:'Space Grotesk',sans-serif; font-size:clamp(22px,3.4vw,30px); letter-spacing:-.02em; margin:0 0 18px; }
    .ty-video { aspect-ratio:16/9; background:#000; border-radius:14px; overflow:hidden; width:100%; }
    .ty-video video { display:block; height:100%; object-fit:contain; width:100%; }
    .grid { display:grid; gap:14px; grid-template-columns:repeat(3,1fr); }
    .grid figure { background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1); border-radius:12px; margin:0; overflow:hidden; }
    .grid img { aspect-ratio:4/3; display:block; object-fit:cover; width:100%; }
    .grid figcaption { color:rgba(255,255,255,.85); font-size:13.5px; font-weight:600; padding:12px 14px 14px; }
    .vid-grid figure { background:#000; }
    .vid-grid video { aspect-ratio:16/9; display:block; width:100%; }
    .empty { color:rgba(255,255,255,.55); font-size:14px; padding:26px 0; }
    .cta { display:flex; flex-wrap:wrap; gap:12px; justify-content:center; margin-top:44px; }
    .btn { background:var(--gold); border-radius:8px; color:var(--navy-dark); display:inline-block; font-size:15px; font-weight:700; padding:14px 24px; text-decoration:none; }
    .btn.ghost { background:none; border:1px solid rgba(255,255,255,.3); color:#fff; }
    .foot { color:rgba(255,255,255,.5); font-size:13px; margin-top:40px; text-align:center; }
    .foot img { height:34px; margin-bottom:12px; width:auto; }
    @media (max-width:760px) {
      .wrap { padding:0 18px 50px; }
      .next ol { grid-template-columns:1fr; }
      .grid { grid-template-columns:1fr; }
      .ty-hero { padding:40px 0 26px; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="ty-hero">
      <span class="badge">Request received</span>
      <h1>Thank you<?= $firstName !== '' ? ', ' . h($firstName) : '' ?>!</h1>
      <p class="ty-sub"><?= h($s['ty_sub']) ?></p>

      <div class="next">
        <h2>What happens next</h2>
        <ol>
          <li><b>Step 1</b>We review your ground and target so we can match you to the right machine.</li>
          <li><b>Step 2</b>A member of the team calls or messages you within one business day.</li>
          <li><b>Step 3</b>We confirm the right setup, then you are ready for your first outing.</li>
        </ol>
      </div>
    </div>

<?php if ($tyVideo !== ''): ?>
    <div class="panel">
      <div class="panel-label">A message for you</div>
      <h2>Thank you from the HPL team</h2>
      <div class="ty-video">
        <video controls playsinline preload="metadata" poster="img/benefit-bg.png">
          <source src="<?= h($tyVideo) ?>" type="video/mp4">
          Your browser can't play this video.
        </video>
      </div>
    </div>
<?php endif; ?>

<?php if (!empty($tyPhotos)): ?>
    <div class="panel">
      <div class="panel-label">Real finds</div>
      <h2>What people are finding with it</h2>
      <div class="grid">
<?php foreach ($tyPhotos as $p): ?>
        <figure>
          <img src="<?= h('img/' . $p['file']) ?>" alt="<?= h($p['caption'] !== '' ? $p['caption'] : 'Customer find') ?>" loading="lazy">
<?php if ($p['caption'] !== ''): ?>
          <figcaption><?= h($p['caption']) ?></figcaption>
<?php endif; ?>
        </figure>
<?php endforeach; ?>
      </div>
    </div>
<?php endif; ?>

<?php if (!empty($tyVideos)): ?>
    <div class="panel">
      <div class="panel-label">Customer stories</div>
      <h2>Hear it from the field</h2>
      <div class="grid vid-grid">
<?php foreach ($tyVideos as $v): ?>
        <figure>
          <video muted loop playsinline preload="none" data-ty-video>
            <source src="<?= h($v['src']) ?>" type="video/mp4">
          </video>
<?php if ($v['caption'] !== ''): ?>
          <figcaption><?= h($v['caption']) ?></figcaption>
<?php endif; ?>
        </figure>
<?php endforeach; ?>
      </div>
    </div>
<?php endif; ?>

<?php if (empty($tyPhotos) && empty($tyVideos) && $tyVideo === ''): ?>
    <p class="empty">Your confirmation is in. We will be in touch shortly.</p>
<?php endif; ?>

    <div class="cta">
<?php if ($waUrl !== ''): ?>
      <a class="btn" href="<?= h($waUrl) ?>" target="_blank" rel="noopener">Message us on WhatsApp</a>
<?php endif; ?>
      <a class="btn ghost" href="index.php">Back to the site</a>
    </div>

    <div class="foot">
<?php if (file_exists(__DIR__ . '/img/hpllogo.jpeg')): ?>
      <img src="img/hpllogo.jpeg" alt="HPL Gold Detectors">
<?php endif; ?>
      <div>&copy; <?= h(date('Y')) ?> <?= h($s['brand_name']) ?></div>
    </div>
  </div>

  <script>
    (function () {
      var vids = document.querySelectorAll('[data-ty-video]');
      for (var i = 0; i < vids.length; i++) {
        vids[i].addEventListener('mouseenter', function () { this.play().catch(function () {}); });
        vids[i].addEventListener('mouseleave', function () { this.pause(); });
      }
    })();
  </script>
</body>
</html>
