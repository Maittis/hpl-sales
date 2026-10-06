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

$gallery = ['zambia-1.jpg','zambia-2.jpg','zambia-3.jpg','zambia-4.jpg','zambia-5.jpg','zambia-6.jpg','stat-1.jpg','stat-2.jpg'];
$gallery = array_values(array_filter($gallery, fn($f) => file_exists(__DIR__.'/img/'.$f)));
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
    :root{--bg:#050816;--gold:#d4af37;--gold-2:#f5d87a;--text:#f7f8fa;--muted:#b9c0d6;--border:rgba(212,175,55,0.35);--panel:rgba(255,255,255,0.04)}
    *{box-sizing:border-box}
    body{margin:0;font-family:Manrope,sans-serif;background:radial-gradient(1400px 900px at 50% -10%,rgba(212,175,55,0.18),transparent),var(--bg);color:var(--text);line-height:1.8}
    .wrap{max-width:1180px;margin:0 auto;padding:40px 20px 80px}
    .hero{text-align:center;padding:20px 0 30px}
    .badge{display:inline-flex;padding:8px 16px;border-radius:999px;border:1px solid var(--border);background:linear-gradient(90deg,rgba(212,175,55,0.25),transparent);color:var(--gold-2);font-weight:600;font-size:13px;letter-spacing:.12em;text-transform:uppercase}
    h1{font-size:clamp(32px,5.2vw,60px);margin:16px 0 12px;line-height:1.08;letter-spacing:-.02em}
    .sub{color:var(--muted);font-size:clamp(16px,2vw,19px);max-width:820px;margin:0 auto 24px}
    .cta-row{display:flex;gap:12px;flex-wrap:wrap;justify-content:center}
    .btn{display:inline-flex;padding:14px 26px;border-radius:10px;text-decoration:none;font-weight:800;font-size:15px;border:1px solid rgba(212,175,55,.6);background:linear-gradient(90deg,var(--gold-2),var(--gold));color:#050816;box-shadow:0 14px 44px rgba(212,175,55,.24)}
    .btn.ghost{background:transparent;color:var(--text);border:1px solid rgba(255,255,255,.28);box-shadow:none}
    .video-wrap{margin:40px auto 60px;max-width:980px}
    .video-frame{position:relative;aspect-ratio:16/9;border-radius:16px;overflow:hidden;border:1px solid rgba(212,175,55,.42);box-shadow:0 34px 90px rgba(0,0,0,.58);background:#000}
    .video-frame video,.video-frame iframe{width:100%;height:100%;display:block;border:0}
    section{margin:70px 0}
    .section-label{color:var(--gold-2);text-transform:uppercase;letter-spacing:.16em;font-size:12px;font-weight:800}
    h2{font-size:clamp(26px,4vw,44px);margin:10px 0 12px;letter-spacing:-.02em;line-height:1.12}
    .section-sub{color:var(--muted);max-width:780px;font-size:clamp(15px,1.6vw,17px)}
    .grid4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:28px}
    @media(max-width:1100px){.grid4{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:620px){.grid4{grid-template-columns:1fr}}
    .card{background:linear-gradient(180deg,rgba(255,255,255,.05),transparent);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:26px}
    .card h3{margin:10px 0 10px;font-size:18px}
    .card p{color:var(--muted);margin:0;font-size:15px}
    .steps{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:28px}
    @media(max-width:1100px){.steps{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:640px){.steps{grid-template-columns:1fr}}
    .step{background:linear-gradient(180deg,rgba(255,255,255,.05),transparent);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:26px}
    .step-num{color:var(--gold-2);font-weight:800;font-size:18px}
    .step h3{margin:8px 0 10px;font-size:18px}
    .step p{color:var(--muted);margin:0;font-size:15px}
    .zig{display:grid;gap:22px;margin-top:32px}
    .zig-row{display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:center}
    @media(max-width:920px){.zig-row{grid-template-columns:1fr}}
    .zig-media{position:relative;border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,.12);box-shadow:0 24px 70px rgba(0,0,0,.5);aspect-ratio:16/9}
    .zig-media img{width:100%;height:100%;object-fit:cover;display:block}
    .zig-text{padding:10px 6px}
    .zig-text .kicker{color:var(--gold-2);text-transform:uppercase;letter-spacing:.14em;font-size:11px;font-weight:800;margin-bottom:8px}
    .zig-text h3{margin:6px 0 10px;font-size:clamp(22px,3vw,34px);letter-spacing:-.01em}
    .zig-text p{color:var(--muted);margin:0 0 10px}
    .gallery{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:24px}
    @media(max-width:980px){.gallery{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:620px){.gallery{grid-template-columns:1fr}}
    .gal-item{position:relative;border-radius:12px;overflow:hidden;border:1px solid rgba(255,255,255,.1);aspect-ratio:4/3;background:#000;cursor:pointer}
    .gal-item img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .4s ease}
    .gal-item:hover img{transform:scale(1.04)}
    .faq{margin-top:24px;display:grid;gap:12px}
    .faq-item{border:1px solid rgba(255,255,255,.1);border-radius:12px;background:linear-gradient(180deg,rgba(255,255,255,.04),transparent);overflow:hidden}
    .faq-q{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;font-weight:700;cursor:pointer}
    .faq-a{display:none;padding:0 20px 18px;color:var(--muted)}
    .faq-ite
