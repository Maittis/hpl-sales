<?php
require_once __DIR__ . '/site-bootstrap.php';
require_once __DIR__ . '/config.php';
$s = hpl_settings();

/* An uploaded image keeps its filename, so the browser serves the cached copy
   and a successful replacement looks like it did nothing. Stamping the URL with
   the file's mtime makes a changed image a new resource and leaves unchanged
   ones cached. Kept local to this page so it cannot clash with config.php. */
if (!function_exists('hpl_img_url')) {
function hpl_img_url(string $file): string
      {
          /* Collapse the duplicate photo filenames onto one URL, same as the
             homepage does, so this page does not re-download a picture the
             visitor already has. */
          $file = hpl_canonical_photo($file);
          $path = __DIR__ . '/img/' . $file;
          $stamp = is_file($path) ? (string)@filemtime($path) : '';
          return h('img/' . $file) . ($stamp !== '' ? '?v=' . $stamp : '');
      }
}

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
    $tyVideo = hpl_media_url($s['ty_video_drive_id'] ?? '');
}

$tyPhotos = [];
for ($i = 1; $i <= 6; $i++) {
    $file = 'ty-' . $i . '.jpg';
    if (file_exists(__DIR__ . '/img/' . $file)) {
        $tyPhotos[] = ['file' => $file, 'caption' => (string)($s['ty_' . $i . '_caption'] ?? '')];
    }
}

$tyVideos = [];
for ($i = 1; $i <= 15; $i++) {
    $src = hpl_media_url((string)($s['proof_video_' . $i] ?? ''));
    if ($src === '') {
        continue;
    }
    $tyVideos[] = ['src' => $src, 'caption' => (string)($s['proof_video_' . $i . '_caption'] ?? '')];
}

/* Same normalisation and fallback as index.php, guarded so whichever file
   loads first defines it. Defined locally because config.php ships without the
   shared helpers. */
if (!function_exists('hpl_wa_digits')) {
    function hpl_wa_digits(string $raw, string $fallbackCc = '260'): string
    {
        $digits = preg_replace('/\D+/', '', trim($raw));
        if ($digits === null || $digits === '') {
            return '';
        }
        if (strpos($digits, '00') === 0) {
            $digits = substr($digits, 2);
        }
        if ($digits !== '' && $digits[0] === '0') {
            $digits = $fallbackCc . substr($digits, 1);
        }
        $len = strlen($digits);
        if ($len < 7 || $len > 15) {
            return '';
        }
        return $digits;
    }
}
if (!function_exists('hpl_wa_number')) {
    function hpl_wa_number(array $settings, string $fallbackCc = '260'): array
    {
        $digits = hpl_wa_digits((string)($settings['wa_number'] ?? ''), $fallbackCc);
        if ($digits !== '') {
            return ['digits' => $digits, 'configured' => true];
        }
        /* Shipped constant, because neither the settings row nor config.php is
           dependable: the row is often blank and config.php is excluded from
           the deployment archive, so the live copy may not carry the number.
           See the same note in index.php. */
        $digits = hpl_wa_digits('+260966499575', $fallbackCc);
        if ($digits === '') {
            $digits = hpl_wa_digits((string)(hpl_defaults()['wa_number'] ?? ''), $fallbackCc);
        }
        return ['digits' => $digits, 'configured' => $digits !== ''];
    }
}
if (!function_exists('hpl_wa_url')) {
    function hpl_wa_url(array $settings, string $name = '', string $fallbackCc = '260'): string
    {
        $number = hpl_wa_number($settings, $fallbackCc);
        if ($number['digits'] === '') {
            return '';
        }
        $text = str_replace('{name}', $name !== '' ? $name : 'there', (string)($settings['whatsapp_msg'] ?? ''));
        return 'https://wa.me/' . $number['digits'] . '?text=' . rawurlencode($text);
    }
}
$waNumber = hpl_wa_number($s)['digits'];
// Name substituted here rather than inside the helper so this page can greet the
// lead by their own name instead of the anonymous 'there'.
$waUrl = hpl_wa_url($s, $firstName !== '' ? $firstName : '');
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
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root { --navy:#111a38; --navy-dark:#090f24; --gold:#d4a52c; --gold-light:#f4ca5b; --muted:#687083; --wash:#f4f2ed; }
    * { box-sizing:border-box; }
    body { background:var(--navy); color:#fff; 'Manrope','Plus Jakarta Sans',sans-serif; margin:0; }
    .wrap { margin:0 auto; max-width:920px; padding:0 24px 70px; }
    .ty-hero { padding:56px 0 34px; text-align:center; }
    .badge { background:var(--gold); border-radius:999px; color:var(--navy-dark); display:inline-block; font-size:12px; font-weight:700; letter-spacing:.13em; padding:9px 16px; text-transform:uppercase; }
    h1 {font-weight:400;  font-family:'Anton',sans-serif; font-size:clamp(30px,5vw,46px); letter-spacing:0; line-height:1.08; margin:20px 0 14px; }
    .ty-sub { color:rgba(255,255,255,.82); font-size:17px; line-height:1.6; margin:0 auto; max-width:640px; }
    .next { background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:14px; margin:34px 0 0; padding:26px; }
    .next h2 {font-weight:700;  font-family:'Manrope','Plus Jakarta Sans',sans-serif; font-size:22px; margin:0 0 18px; }
    .next ol { color:rgba(255,255,255,.85); display:grid; gap:14px; grid-template-columns:repeat(3,1fr); list-style:none; margin:0; padding:0; }
    .next li { font-size:15px; line-height:1.5; }
    .next li b { color:var(--gold-light); display:block; font-size:13px; letter-spacing:.1em; margin-bottom:5px; text-transform:uppercase; }
    .panel { margin-top:44px; }
    .panel-label { color:var(--gold); font-size:12px; font-weight:700; letter-spacing:.13em; margin-bottom:10px; text-transform:uppercase; }
    .panel h2 {font-weight:400;  font-family:'Anton',sans-serif; font-size:clamp(22px,3.4vw,30px); letter-spacing:0; margin:0 0 18px; }
    .ty-video-block { margin:34px auto 0; max-width:760px; text-align:center; }
    .ty-video-block h2 {font-weight:700;  font-family:'Manrope','Plus Jakarta Sans',sans-serif; font-size:clamp(20px,3vw,26px); letter-spacing:-.02em; margin:0 0 16px; }
    .ty-video { aspect-ratio:16/9; background:#000; border:1px solid rgba(255,255,255,.16); border-radius:14px; box-shadow:0 18px 50px rgba(0,0,0,.35); overflow:hidden; width:100%; }
    .ty-video video { display:block; height:100%; object-fit:contain; width:100%; }
    .ty-video-empty { align-items:center; background:rgba(255,255,255,.05); border-style:dashed; color:rgba(255,255,255,.65); display:flex; font-size:14.5px; justify-content:center; padding:20px; text-align:center; }
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
      <span class="badge"><?= h($s['ty_badge']) ?></span>
      <h1><?= h($s['ty_h']) ?><?= $firstName !== '' ? ', ' . h($firstName) : '' ?>!</h1>
      <p class="ty-sub"><?= h($s['ty_sub']) ?></p>

      <div class="ty-video-block">
        <div class="panel-label"><?= h($s['ty_vlabel']) ?></div>
        <h2><?= h($s['ty_vh']) ?></h2>
<?php if ($tyVideo !== ''): ?>
        <div class="ty-video">
          <video controls playsinline preload="metadata">
            <source src="<?= h($tyVideo) ?>" type="<?= h(hpl_media_type($tyVideo)) ?>">
            Your browser can't play this video.
          </video>
        </div>
<?php else: ?>
        <div class="ty-video ty-video-empty">
          <span><?= h($s['ty_vplaceholder']) ?></span>
        </div>
<?php endif; ?>
      </div>
    </div>

    <div class="next">
      <h2><?= h($s['ty_next_h']) ?></h2>
      <ol>
        <li><b>Step 1</b><?= h($s['ty_step1']) ?></li>
        <li><b>Step 2</b><?= h($s['ty_step2']) ?></li>
        <li><b>Step 3</b><?= h($s['ty_step3']) ?></li>
      </ol>
    </div>

<?php if (!empty($tyPhotos)): ?>
    <div class="panel">
      <div class="panel-label"><?= h($s['ty_finds_label']) ?></div>
      <h2><?= h($s['ty_finds_h']) ?></h2>
      <div class="grid">
<?php foreach ($tyPhotos as $p): ?>
        <figure>
          <img src="<?= hpl_img_url($p['file']) ?>" alt="<?= h($p['caption'] !== '' ? $p['caption'] : 'Customer find') ?>" loading="lazy">
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
      <div class="panel-label"><?= h($s['ty_stories_label']) ?></div>
      <h2><?= h($s['ty_stories_h']) ?></h2>
      <div class="grid vid-grid">
<?php foreach ($tyVideos as $v): ?>
        <figure>
          <video muted loop playsinline preload="none" data-ty-video>
            <source src="<?= h($v['src']) ?>" type="<?= h(hpl_media_type($v['src'])) ?>">
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
    <p class="empty"><?= h($s['ty_fallback']) ?></p>
<?php endif; ?>

    <div class="cta">
<?php if ($waUrl !== ''): ?>
      <a class="btn" href="<?= h($waUrl) ?>" target="_blank" rel="noopener">Message us on WhatsApp</a>
<?php endif; ?>
      <a class="btn ghost" href="index.php"><?= h($s['ty_back']) ?></a>
    </div>

    <div class="foot">
<?php if (file_exists(__DIR__ . '/img/hpllogo.jpeg')): ?>
      <img src="<?= hpl_img_url('hpllogo.jpeg') ?>" alt="HPL Gold Detectors">
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
