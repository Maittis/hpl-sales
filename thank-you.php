<?php
require_once __DIR__ . '/site-bootstrap.php';
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
    $tyVideo = hpl_media_url($s['ty_video_drive_id'] ?? '');
}

if (!function_exists('hpl_wa_digits')) {
    function hpl_wa_digits(string $raw, string $fallbackCc = '260'): string {
        $digits = preg_replace('/\D+/', '', trim($raw));
        if ($digits === null || $digits === '') { return ''; }
        if (strpos($digits, '00') === 0) { $digits = substr($digits, 2); }
        if ($digits !== '' && $digits[0] === '0') { $digits = $fallbackCc . substr($digits, 1); }
        $len = strlen($digits);
        if ($len < 7 || $len > 15) { return ''; }
        return $digits;
    }
}
if (!function_exists('hpl_wa_number')) {
    function hpl_wa_number(array $settings, string $fallbackCc = '260'): array {
        $digits = hpl_wa_digits((string)($settings['wa_number'] ?? ''), $fallbackCc);
        if ($digits !== '') { return ['digits' => $digits, 'configured' => true]; }
        $digits = hpl_wa_digits('+260966499575', $fallbackCc);
        if ($digits === '') { $digits = hpl_wa_digits((string)(hpl_defaults()['wa_number'] ?? ''), $fallbackCc); }
        return ['digits' => $digits, 'configured' => $digits !== ''];
    }
}
if (!function_exists('hpl_wa_url')) {
    function hpl_wa_url(array $settings, string $name = '', string $fallbackCc = '260'): string {
        $number = hpl_wa_number($settings, $fallbackCc);
        if ($number['digits'] === '') { return ''; }
        $text = str_replace('{name}', $name !== '' ? $name : 'there', (string)($settings['whatsapp_msg'] ?? ''));
        return 'https://wa.me/' . $number['digits'] . '?text=' . rawurlencode($text);
    }
}
$waUrl = hpl_wa_url($s, $firstName !== '' ? $firstName : '');

$hplCopy = static function (string $key, string $fallback) use ($s): string {
    $value = $s[$key] ?? '';
    if (is_string($value) && trim($value) !== '') { return $value; }
    return $fallback;
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Thank you | <?= h($s['brand_name'] ?? 'HPL GOLD') ?></title>
  <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--bg:#050816;--gold:#d4af37;--gold-2:#f5d87a;--text:#f7f8fa;--muted:#b9c0d6;--border:rgba(212,175,55,0.35);}
    *{box-sizing:border-box}
    body{margin:0;font-family:Manrope,sans-serif;background:radial-gradient(1200px 800px at 50% -10%,rgba(212,175,55,0.18),transparent),var(--bg);color:var(--text);line-height:1.7}
    .wrap{max-width:1100px;margin:0 auto;padding:40px 20px 80px}
    .hero{text-align:center;padding:20px 0 40px}
    .badge{display:inline-flex;padding:8px 16px;border-radius:999px;border:1px solid var(--border);background:linear-gradient(90deg,rgba(212,175,55,0.25),transparent);color:var(--gold-2);font-weight:600;font-size:13px;letter-spacing:.12em;text-transform:uppercase}
    h1{font-size:clamp(32px,5vw,56px);margin:16px 0 12px;line-height:1.1;letter-spacing:-.02em}
    .sub{color:var(--muted);font-size:clamp(16px,2vw,18px);max-width:780px;margin:0 auto 24px}
    .cta-row{display:flex;gap:12px;flex-wrap:wrap;justify-content:center}
    .btn{display:inline-flex;padding:14px 24px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;border:1px solid rgba(212,175,55,.6);background:linear-gradient(90deg,var(--gold-2),var(--gold));color:#050816;box-shadow:0 12px 40px rgba(212,175,55,.25)}
    .btn.ghost{background:transparent;color:var(--text);border:1px solid rgba(255,255,255,.25);box-shadow:none}
    .video-wrap{margin:40px auto 60px;max-width:900px}
    .video-frame{position:relative;aspect-ratio:16/9;border-radius:16px;overflow:hidden;border:1px solid rgba(212,175,55,.4);box-shadow:0 30px 80px rgba(0,0,0,.55);background:#000}
    .video-frame video,.video-frame iframe{width:100%;height:100%;display:block;border:0}
    section{margin:60px 0}
    .section-label{color:var(--gold-2);text-transform:uppercase;letter-spacing:.16em;font-size:12px;font-weight:700}
    h2{font-size:clamp(26px,4vw,42px);margin:10px 0 12px;letter-spacing:-.02em}
    .steps{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:30px}
    @media(max-width:1000px){.steps{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:640px){.steps{grid-template-columns:1fr}}
    .step{background:linear-gradient(180deg,rgba(255,255,255,.04),transparent);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:24px}
    .step-num{color:var(--gold-2);font-weight:800;font-size:18px}
    .step h3{margin:8px 0 10px;font-size:18px}
    .step p{color:var(--muted);margin:0;font-size:15px}
    .cta-final{text-align:center;margin:80px 0 40px}
    .foot{text-align:center;color:rgba(255,255,255,.6);font-size:13px}
    .foot img{height:36px;margin-bottom:12px}
  </style>
</head>
<body>
  <div class="wrap">
    <header class="hero">
      <span class="badge">Request received</span>
      <h1>Thank you. Your request has been received.<?= $firstName !== '' ? ', ' . h($firstName) : '' ?></h1>
      <p class="sub">Your information has been received by the HPL team. We will review your requirements and contact you to discuss what you are looking for.</p>
      <div class="cta-row">
<?php if ($waUrl !== ''): ?>
        <a class="btn" href="<?= h($waUrl) ?>" target="_blank" rel="noopener">Message HPL on WhatsApp</a>
<?php endif; ?>
        <a class="btn ghost" href="index.php">Explore HPL</a>
      </div>
    </header>

    <section class="video-wrap" id="ty-video">
      <div class="video-frame">
<?php if ($tyVideo !== ''): ?>
        <video controls playsinline preload="metadata">
          <source src="<?= h($tyVideo) ?>" type="<?= h(hpl_media_type($tyVideo)) ?>">
          Your browser can't play this video.
        </video>
<?php else: ?>
        <iframe src="https://player.mediadelivery.net/embed/767583/a00df9be-a8b6-4a29-8456-3a63a095035c?autoplay=false&amp;loop=false&amp;muted=false&amp;preload=true&amp;responsive=true" loading="lazy" title="HPL Gold Detectors" allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;fullscreen" allowfullscreen></iframe>
<?php endif; ?>
      </div>
    </section>

    <section>
      <div class="section-label">What happens next</div>
      <h2>Here's what you can expect from the HPL team</h2>
      <div class="steps">
        <div class="step"><div class="step-num">01</div><h3>We review your details</h3><p>We review the information you provided so we understand what type of equipment and solution may fit your needs.</p></div>
        <div class="step"><div class="step-num">02</div><h3>We contact you</h3><p>An HPL team member will contact you using the details you provided.</p></div>
        <div class="step"><div class="step-num">03</div><h3>We understand your requirements</h3><p>We’ll discuss your location, experience, target, ground conditions and what you’re hoping to achieve.</p></div>
        <div class="step"><div class="step-num">04</div><h3>We help you move forward</
