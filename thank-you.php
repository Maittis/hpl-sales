<?php
require_once __DIR__ . '/site-bootstrap.php';
require_once __DIR__ . '/config.php';
$s = hpl_settings();
$visitor = trim((string)($_GET['name'] ?? ''));
$visitor = preg_replace('/[\x00-\x1F\x7F]/u', '', $visitor);
$visitor = trim(preg_replace('/\s+/u', ' ', $visitor));
if (function_exists('mb_substr')) { $visitor = mb_substr($visitor, 0, 40, 'UTF-8'); } else { $visitor = substr($visitor, 0, 40); }
$firstName = $visitor !== '' ? (explode(' ', $visitor)[0] ?? '') : '';
$tyVideo = '';
if (file_exists(__DIR__ . '/img/thankyou.mp4')) { $tyVideo = 'img/thankyou.mp4'; }
elseif (trim((string)($s['ty_video_drive_id'] ?? '')) !== '' && trim((string)($s['ty_video_drive_id'] ?? '')) !== 'YOUR_DRIVE_FILE_ID') { $tyVideo = hpl_media_url($s['ty_video_drive_id'] ?? ''); }
if (!function_exists('hpl_wa_digits')) {
    function hpl_wa_digits(string $raw, string $fallbackCc = '260'): string {
        $digits = preg_replace('/\D+/', '', trim($raw));
        if ($digits === null || $digits === '') return '';
        if (strpos($digits, '00') === 0) $digits = substr($digits, 2);
        if ($digits !== '' && $digits[0] === '0') $digits = $fallbackCc . substr($digits, 1);
        $len = strlen($digits);
        if ($len < 7 || $len > 15) return '';
        return $digits;
    }
}
if (!function_exists('hpl_wa_number')) {
    function hpl_wa_number(array $settings, string $fallbackCc = '260'): array {
        $digits = hpl_wa_digits((string)($settings['wa_number'] ?? ''), $fallbackCc);
        if ($digits !== '') return ['digits' => $digits, 'configured' => true];
        $digits = hpl_wa_digits('+260966499575', $fallbackCc);
        if ($digits === '') $digits = hpl_wa_digits((string)(hpl_defaults()['wa_number'] ?? ''), $fallbackCc);
        return ['digits' => $digits, 'configured' => $digits !== ''];
    }
}
if (!function_exists('hpl_wa_url')) {
    function hpl_wa_url(array $settings, string $name = '', string $fallbackCc = '260'): string {
        $number = hpl_wa_number($settings, $fallbackCc);
        if ($number['digits'] === '') return '';
        $text = str_replace('{name}', $name !== '' ? $name : 'there', (string)($settings['whatsapp_msg'] ?? ''));
        return 'https://wa.me/' . $number['digits'] . '?text=' . rawurlencode($text);
    }
}
$waUrl = hpl_wa_url($s, $firstName !== '' ? $firstName : '');
$hplCopy = static function (string $key, string $fallback) use ($s): string {
    $value = $s[$key] ?? '';
    if (is_string($value) && trim($value) !== '') return $value;
    return $fallback;
};
$gallery = ['field-proof-1.jpg','field-proof-2.jpg','proof-1.jpg','proof-2.jpg','proof-3.jpg','slide-1.jpg','slide-2.jpg','slide-3.jpg','slide-4.jpg','slide-5.jpg','slide-6.jpg','ty-1.jpg','ty-2.jpg','ty-3.jpg','ty-4.jpg','ty-5.jpg','ty-6.jpg','visit-1.jpg','visit-2.jpg','visit-3.jpg','stat-1.jpg','stat-2.jpg','story-photo-1.jpg','story-photo-2.jpg','story-photo-3.jpg','benefit-1.jpg','benefit-2.jpg','benefit-3.jpg'];
$gallery = array_values(array_filter($gallery, fn($f) => file_exists(__DIR__ . '/img/' . $f)));
$galleryData = json_encode(array_map(fn($f) => hpl_img_url($f), $gallery), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
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
  <style>:root{--bg:#050816;--bg-elev:#0a0f1f;--gold:#d4af37;--gold-2:#f5d87a;--gold-3:#fff3d6;--text:#f7f8fa;--muted:#9aa3b8;--border:rgba(212,175,55,0.25);--border-strong:rgba(212,175,55,0.45);--card:rgba(255,255,255,0.03);--card-hover:rgba(255,255,255,0.06);}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font-family:Manrope,sans-serif;background:radial-gradient(1400px 900px at 50% -10%,rgba(212,175,55,0.15),transparent 60%),var(--bg);color:var(--text);line-height:1.7;-webkit-font-smoothing:antialiased}.wrap{max-width:1120px;margin:0 auto;padding:0 24px 80px}a{text-decoration:none;color:inherit}.hero{padding:60px 0 40px;text-align:center;border-bottom:1px solid var(--border);margin-bottom:40px}.badge{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:999px;border:1px solid var(--border-strong);background:linear-gradient(90deg,rgba(212,175,55,0.25),rgba(212,175,55,0.08));color:var(--gold-2);font-weight:700;font-size:13px;letter-spacing:.14em;text-transform:uppercase}.badge::before{content:'✓';width:20px;height:20px;border-radius:50%;background:var(--gold);color:#050816;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800}h1{font-size:clamp(36px,5.5vw,64px);margin:20px 0 16px;line-height:1.05;letter-spacing:-.025em;font-weight:800}.hero .sub{color:var(--muted);font-size:clamp(17px,2.2vw,20px);max-width:800px;margin:0 auto 32px;line-height:1.7}.cta-row{display:flex;gap:14px;flex-wrap:wrap;justify-content:center}.btn{display:inline-flex;align-items:center;padding:16px 32px;border-radius:12px;font-weight:800;font-size:15px;border:1px solid rgba(212,175,55,.7);background:linear-gradient(90deg,var(--gold-2),var(--gold));color:#050816;box-shadow:0 16px 48px rgba(212,175,55,.28)}.btn.ghost{background:transparent;color:var(--text);border:1px solid rgba(255,255,255,.25);box-shadow:none}section{margin:72px 0}.section-header{text-align:center;max-width:780px;margin:0 auto 48px}.section-label{display:inline-block;padding:6px 14px;border-radius:999px;background:rgba(212,175,55,0.12);border:1px solid var(--border);color:var(--gold-2);text-transform:uppercase;letter-spacing:.18em;font-size:11px;font-weight:800;margin-bottom:16px}h2{font-size:clamp(30px,4.5vw,48px);margin:0 0 14px;letter-spacing:-.02em;line-height:1.1;font-weight:800}.section-sub{color:var(--muted);font-size:clamp(16px,1.8vw,18px)}.video-frame{position:relative;aspect-ratio:16/9;border-radius:18px;overflow:hidden;border:1px solid var(--border-strong);box-shadow:0 40px 100px rgba(0,0,0,.6);background:#000}.video-frame video,.video-frame iframe{width:100%;height:100%;display:block;border:0}.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}@media(max-width:1000px){.steps{grid-template-columns:repeat(2,1fr)}@media(max-width:640px){.steps{grid-template-columns:1fr}}}.step{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px}.step-num{color:var(--gold-2);font-weight:800;font-size:42px;line-height:1;margin-bottom:12px}.step h3{margin:0 0 10px;font-size:18px;font-weight:700}.step p{color:var(--muted);margin:0;font-size:14px;line-height:1.65}.final-cta{text-align:center;padding:60px 20px;background:linear-gradient(180deg,transparent,rgba(212,175,55,0.04));border-radius:24px;border:1px solid var(--border);margin-top:40px}.foot{text-align:center;color:var(--muted);font-size:13px;padding:40px 0 20px;border-top:1px solid var(--border);margin-top:80px}.foot img{height:36px;margin:0 auto 16px;opacity:.8}</style>
</head>
<body>
  <div class="wrap">
    <header class="hero">
      <span class="badge">Inquiry Received — Specialist Assigned</span>
      <h1><?= h($hplCopy('ty_h','Thank you. Your request has been received.')) ?><?= $firstName!==''?', '.h($firstName):'' ?></h1>
      <p class="sub"><?= h($hplCopy('ty_sub','Your information has been received by the HPL team. We will review your requirements and contact you to discuss what you are looking for.')) ?></p>
      <div class="cta-row"><?php if($waUrl!==''): ?><a class="btn" href="<?= h($waUrl) ?>" target="_blank" rel="noopener"><?= h($hplCopy('ty_whatsapp_button','Message HPL on WhatsApp')) ?></a><?php endif; ?><a class="btn ghost" href="index.php"><?= h($hplCopy('ty_back','Explore HPL Equipment')) ?></a></div>
    </header>
    <section>
      <div class="
