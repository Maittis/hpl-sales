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
    if (is_string($value) && trim($value) !== '') { return $value; }
    return $fallback;
};

if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('hpl_img_url')) {
    function hpl_img_url(string $file): string {
        $path = __DIR__ . '/img/' . $file;
        $stamp = is_file($path) ? (string)@filemtime($path) : '';
        return 'img/' . $file . ($stamp !== '' ? '?v=' . $stamp : '');
    }
}
if (!function_exists('hpl_media_type')) {
    function hpl_media_type(string $url): string {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: $url, PATHINFO_EXTENSION));
        if ($ext === 'mp4') return 'video/mp4';
        if ($ext === 'webm') return 'video/webm';
        if ($ext === 'mov') return 'video/quicktime';
        return 'video/mp4';
    }
}
$gallery = ['field-proof-1.jpg','field-proof-2.jpg','proof-1.jpg','proof-2.jpg','proof-3.jpg','slide-1.jpg','slide-2.jpg','slide-3.jpg','slide-4.jpg','slide-5.jpg','slide-6.jpg','ty-1.jpg','ty-2.jpg','ty-3.jpg','ty-4.jpg','ty-5.jpg','ty-6.jpg','visit-1.jpg','visit-2.jpg','visit-3.jpg','stat-1.jpg','stat-2.jpg','story-photo-1.jpg','story-photo-2.jpg','story-photo-3.jpg','benefit-1.jpg','benefit-2.jpg','benefit-3.jpg'];
$gallery = array_values(array_filter($gallery, fn($f) => file_exists(__DIR__ . '/img/' . $f)));
$galleryData = json_encode(array_map(fn($f) => hpl_img_url($f), $gallery), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);

/* Q&A accordion items — text only, no video players. */
$tyQuestions = [
    ['icon' => '🔍', 'q' => $hplCopy('ty_q1_q', 'What is this actually?'), 'a' => $hplCopy('ty_q1_a', 'A full breakdown of how HPL works — the detectors, the complete field kit, and the how-where-why playbook that comes with every machine.')],
    ['icon' => '📋', 'q' => $hplCopy('ty_q2_q', 'What exactly am I getting?'), 'a' => $hplCopy('ty_q2_a', 'A precision-tuned gold detector, the complete field kit (coil, headphones, batteries, carry bag), and lifetime support from people who prospect themselves.')],
    ['icon' => '🎯', 'q' => $hplCopy('ty_q3_q', 'Who is this for?'), 'a' => $hplCopy('ty_q3_a', 'Whether you have never held a detector or you have been digging for years — if you search for gold, this is built for you.')],
    ['icon' => '👤', 'q' => $hplCopy('ty_q4_q', 'Who are you to sell this?'), 'a' => $hplCopy('ty_q4_a', 'HPL has put detectors in the hands of prospectors across Africa. The photos and finds on this site are from real customers on real ground.')],
    ['icon' => '🤷', 'q' => $hplCopy('ty_q5_q', 'Why would anyone pay this much?'), 'a' => $hplCopy('ty_q5_a', 'Because a cheap machine digs trash and burns your weekends. A tuned detector pays for itself the first time it locks onto gold and ignores hot ground.')],
    ['icon' => '📈', 'q' => $hplCopy('ty_q6_q', 'What results should I expect?'), 'a' => $hplCopy('ty_q6_a', 'Real timelines from real customers — what they found in their first trip, their first month, and their first season. Results vary with ground, effort, and experience.')],
    ['icon' => '🚀', 'q' => $hplCopy('ty_q7_q', 'What is possible with the right detector?'), 'a' => $hplCopy('ty_q7_a', 'The ceiling is higher than most people think. Customers have found nuggets, coins, and relics on ground others walked over for years.')],
    ['icon' => '💰', 'q' => $hplCopy('ty_q8_q', 'Is it expensive?'), 'a' => $hplCopy('ty_q8_a', 'Compare it to what staying stuck costs you every trip. A machine that separates gold from iron turns weekends into finds.')],
    ['icon' => '🛒', 'q' => $hplCopy('ty_q9_q', 'How long does shipping take?'), 'a' => $hplCopy('ty_q9_a', 'We ship with tracking. Delivery is typically 5-10 business days depending on your location. Once dispatched, you get a tracking number to follow your order every step of the way.')],
    ['icon' => '🌍', 'q' => $hplCopy('ty_q10_q', 'Do you ship to my country?'), 'a' => $hplCopy('ty_q10_a', 'We ship across Africa and internationally. If your country is not listed at checkout, message us before ordering — we almost always find a way to get the machine to you.')],
    ['icon' => '💳', 'q' => $hplCopy('ty_q11_q', 'What payment methods do you accept?'), 'a' => $hplCopy('ty_q11_a', 'Bank transfer, mobile money, and major card schemes. Details are provided when you confirm your order. All payments are secured and confirmed before dispatch.')],
    ['icon' => '🔧', 'q' => $hplCopy('ty_q12_q', 'Is there a warranty?'), 'a' => $hplCopy('ty_q12_a', 'Yes. Every machine carries a manufacturer warranty against defects. If something goes wrong that is not caused by misuse or damage, we sort it out — no long-distance runaround.')],
    ['icon' => '🗺️', 'q' => $hplCopy('ty_q13_q', 'What if the machine does not work for my ground?'), 'a' => $hplCopy('ty_q13_a', 'Before you buy, we talk through your terrain, your targets, and your experience so you get the right setup. The how-where-why playbook also explains how to adjust the settings for your specific ground conditions.')],
    ['icon' => '🤝', 'q' => $hplCopy('ty_q14_q', 'Do you offer after-sales support?'), 'a' => $hplCopy('ty_q14_a', 'Yes. The team that sells you the machine is the team that answers your calls afterward. Setup help, ground advice, and spare parts are all available after purchase.')],
    ['icon' => '🔄', 'q' => $hplCopy('ty_q15_q', 'Can I upgrade or trade in my old detector?'), 'a' => $hplCopy('ty_q15_a', 'We do not run a formal trade-in programme, but if you are moving up from a basic machine, tell us what you have — we can part-exchange or give you an honest valuation to put toward the right setup.')],
    ['icon' => '⚔️', 'q' => $hplCopy('ty_q16_q', 'What is the difference between your machines and a cheap detector?'), 'a' => $hplCopy('ty_q16_a', 'Cheap machines find surface metal and dig trash. Our detectors tune out hot ground and junk, lock onto gold, and give you a signal you can trust. The difference shows on the first swing.')],
    ['icon' => '🌱', 'q' => $hplCopy('ty_q17_q', 'Is this suitable for beginners?'), 'a' => $hplCopy('ty_q17_a', 'Absolutely. Every machine ships with a quick-start guide and a full how-where-why playbook. If you have never held a detector before, the setup is straightforward and the team is available to walk you through it.')],
    ['icon' => '📚', 'q' => $hplCopy('ty_q18_q', 'Do I need any prior experience?'), 'a' => $hplCopy('ty_q18_a', 'No. The machine and the playbook are designed to take you from your first outing to confident swing in a single session. Experience helps, but it is not required.')],
    ['icon' => '📏', 'q' => $hplCopy('ty_q19_q', 'How deep can it detect?'), 'a' => $hplCopy('ty_q19_a', 'Depth depends on ground conditions, target size, and settings. On typical African ground, our machines detect small nuggets at depths beginners are surprised by. The guide explains how to push deeper on your terrain.')],
    ['icon' => '🎯', 'q' => $hplCopy('ty_q20_q', 'Can it discriminate between gold and other metals?'), 'a' => $hplCopy('ty_q20_a', 'Yes. Discrimination is the whole point. The machine separates gold-bearing targets from iron, slag, and trash so you spend your time digging finds, not bottle tops.')],
    ['icon' => '🛡️', 'q' => $hplCopy('ty_q21_q', 'What happens if I damage the machine?'), 'a' => $hplCopy('ty_q21_a', 'Accidents happen. If the damage is from normal field use, contact us first — we often repair rather than replace, and the cost is always less than a new machine. Wilful damage voids the warranty.')],
    ['icon' => '🎓', 'q' => $hplCopy('ty_q22_q', 'Do you provide training or a guide?'), 'a' => $hplCopy('ty_q22_a', 'Every purchase includes the how-where-why playbook — written for the ground you are hunting, not generic instructions. If you want a live walkthrough before you buy, book a call and we will cover the key settings together.')],
    ['icon' => '📦', 'q' => $hplCopy('ty_q23_q', 'Are spare parts available?'), 'a' => $hplCopy('ty_q23_a', 'Yes. Coils, batteries, headphones, and carry bags are all stocked. If you need something specific, just ask — most parts ship within a few days.')],
    ['icon' => '🧭', 'q' => $hplCopy('ty_q24_q', 'How do I know which machine is right for me?'), 'a' => $hplCopy('ty_q24_a', 'That is exactly what the pre-sale call is for. We ask about your ground, your budget, what you are looking to find, and your experience level. The right machine for a riverbank is not the right machine for a worked dump.')],
    ['icon' => '📞', 'q' => $hplCopy('ty_q25_q', 'What if I change my mind after ordering?'), 'a' => $hplCopy('ty_q25_a', 'Machines are built and dispatched fresh, so returns are accepted within 14 days of delivery if the machine is unopened and in original condition. Contact us before sending anything back so we can issue the correct return address and instructions.')],
];

/* Real results cards — text only, no video players. */
$tyResults = [
    ['amount' => $hplCopy('ty_res1_amount', '2.1oz'), 'time' => $hplCopy('ty_res1_time', 'first trip'), 'name' => $hplCopy('ty_res1_name', 'Chileshe M.'), 'place' => $hplCopy('ty_res1_place', 'Lusaka, Zambia')],
    ['amount' => $hplCopy('ty_res2_amount', '1.6oz'), 'time' => $hplCopy('ty_res2_time', 'in 3 weeks'), 'name' => $hplCopy('ty_res2_name', 'Kabaso N.'), 'place' => $hplCopy('ty_res2_place', 'Livingstone, Zambia')],
    ['amount' => $hplCopy('ty_res3_amount', 'First nugget'), 'time' => $hplCopy('ty_res3_time', 'in 2 weeks'), 'name' => $hplCopy('ty_res3_name', 'Mutinta K.'), 'place' => $hplCopy('ty_res3_place', 'Kitwe, Zambia')],
    ['amount' => $hplCopy('ty_res4_amount', '1.5oz'), 'time' => $hplCopy('ty_res4_time', 'first month'), 'name' => $hplCopy('ty_res4_name', 'James T.'), 'place' => $hplCopy('ty_res4_place', 'South Africa')],
    ['amount' => $hplCopy('ty_res5_amount', 'Cluster of coins'), 'time' => $hplCopy('ty_res5_time', 'in 20 minutes'), 'name' => $hplCopy('ty_res5_name', 'Mwamba B.'), 'place' => $hplCopy('ty_res5_place', 'Ndola, Zambia')],
    ['amount' => $hplCopy('ty_res6_amount', 'Relic find'), 'time' => $hplCopy('ty_res6_time', 'first outing'), 'name' => $hplCopy('ty_res6_name', 'Sarah K.'), 'place' => $hplCopy('ty_res6_place', 'Zimbabwe')],
];

/* Receipts — text proof cards, no video players. */
$tyReceipts = [
    ['name' => $hplCopy('ty_rcpt1_name', 'Chileshe M.'), 'title' => $hplCopy('ty_rcpt1_title', 'Found gold on his first trip out'), 'text' => $hplCopy('ty_rcpt1_text', 'Chileshe walked a worked dump for months with little to show. One weekend with the MAGNETAR 5000 and a slower, wider swing, he started lifting targets he had stepped over for years.')],
    ['name' => $hplCopy('ty_rcpt2_name', 'Mutinta K.'), 'title' => $hplCopy('ty_rcpt2_title', 'First find in under two weeks'), 'text' => $hplCopy('ty_rcpt2_text', 'Mutinta owned no equipment and spent weeks going out with his uncle before spending anything. By the time he bought a machine he already knew how to sweep, so the first months went on learning the ground rather than learning the settings.')],
    ['name' => $hplCopy('ty_rcpt3_name', 'Nachula'), 'title' => $hplCopy('ty_rcpt3_title', 'Steady targets, week after week'), 'text' => $hplCopy('ty_rcpt3_text', 'Nachula runs a small pit, and part of what comes out goes straight back into the household. He is after steady, repeatable targets that turn up often enough to be worth the week.')],
    ['name' => $hplCopy('ty_rcpt4_name', 'Bwalya'), 'title' => $hplCopy('ty_rcpt4_title', 'A machine that stopped shouting at iron'), 'text' => $hplCopy('ty_rcpt4_text', 'Bwalya had been working a cheap unit that chattered on every patch of iron and slag. On ground like that, a machine which separates the target from the scrap lets you keep working instead of switching off.')],
    ['name' => $hplCopy('ty_rcpt5_name', 'Mwamba'), 'title' => $hplCopy('ty_rcpt5_title', 'One complete setup, explained in one go'), 'text' => $hplCopy('ty_rcpt5_text', 'Mwamba needed more than a detector: pumps, hoses and a cradle to work deeper ground. Everything was set up and explained in one go, so he was not left guessing at parts that do not fit together.')],
    ['name' => $hplCopy('ty_rcpt6_name', 'Kabaso'), 'title' => $hplCopy('ty_rcpt6_title', 'First find on ground her family owned'), 'text' => $hplCopy('ty_rcpt6_text', 'Kabaso had land but no idea where to start, and did not want to buy the wrong machine. The session was spent on ground she had never walked with a detector. She still keeps the first piece she lifted.')],
    ['name' => $hplCopy('ty_rcpt7_name', 'David M.'), 'title' => $hplCopy('ty_rcpt7_title', 'Best detector he has ever used'), 'text' => $hplCopy('ty_rcpt7_text', 'Found gold on his first trip out. The team was incredibly helpful with setup, and the machine has not stopped performing since.')],
    ['name' => $hplCopy('ty_rcpt8_name', 'Sarah K.'), 'title' => $hplCopy('ty_rcpt8_title', 'Professional service, top-quality equipment'), 'text' => $hplCopy('ty_rcpt8_text', 'Highly recommend for serious detectorists. The support after the sale is what sets HPL apart from every other supplier.')],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= h($hplCopy('ty_meta_title', 'Your Request is Received')) ?> | <?= h($s['brand_name'] ?? 'HPL GOLD') ?></title>
  <meta name="description" content="<?= h($hplCopy('ty_meta_desc', 'Thanks for reaching out to HPL Gold Detectors. We review every request personally and will reach out soon.')) ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#111a38;--bg-elev:#0a0f1f;--gold:#d4af37;--gold-2:#f5d87a;--gold-3:#fff3d6;
      --text:#f7f8fa;--muted:#9aa3b8;--border:rgba(212,175,55,0.25);--border-strong:rgba(212,175,55,0.45);
      --card:rgba(255,255,255,0.03);--card-hover:rgba(255,255,255,0.06);
    }
    *{box-sizing:border-box}
    html{scroll-behavior:smooth}
    body{margin:0;font-family:Manrope,sans-serif;background:radial-gradient(1400px 900px at 50% -10%,rgba(212,175,55,0.15),transparent 60%),var(--bg);color:var(--text);line-height:1.7;-webkit-font-smoothing:antialiased}
    .wrap{max-width:1120px;margin:0 auto;padding:0 24px 80px}
    a{text-decoration:none;color:inherit}
    .hero{padding:60px 0 40px;text-align:center;border-bottom:1px solid var(--border);margin-bottom:40px}
    .badge{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:999px;border:1px solid var(--border-strong);background:linear-gradient(90deg,rgba(212,175,55,0.25),rgba(212,175,55,0.08));color:var(--gold-2);font-weight:700;font-size:13px;letter-spacing:.14em;text-transform:uppercase}
    .badge::before{content:'✓';width:20px;height:20px;border-radius:50%;background:var(--gold);color:#050816;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800}
    h1{font-family:Anton,sans-serif;font-size:clamp(36px,5.5vw,64px);margin:20px 0 16px;line-height:1.05;letter-spacing:.01em;font-weight:400}
    .hero .sub{color:var(--muted);font-size:clamp(17px,2.2vw,20px);max-width:800px;margin:0 auto 24px;line-height:1.7}
    .hero .watch-note{color:var(--text);font-size:15px;max-width:640px;margin:0 auto 28px;line-height:1.6}
    .response-pill{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;border-radius:999px;border:1px solid var(--border);background:rgba(212,175,55,0.08);color:var(--gold-2);font-weight:700;font-size:13px;margin-bottom:32px}
    .response-pill::before{content:'⏳';font-size:14px}
    .cta-row{display:flex;gap:14px;flex-wrap:wrap;justify-content:center}
    .btn{display:inline-flex;align-items:center;padding:16px 32px;border-radius:12px;font-weight:800;font-size:15px;border:1px solid rgba(212,175,55,.7);background:linear-gradient(90deg,var(--gold-2),var(--gold));color:#050816;box-shadow:0 16px 48px rgba(212,175,55,.28);transition:transform .2s ease,box-shadow .2s ease}
    .btn:hover{transform:translateY(-2px);box-shadow:0 20px 56px rgba(212,175,55,.36)}
    .btn.ghost{background:transparent;color:var(--text);border:1px solid rgba(255,255,255,.25);box-shadow:none}
    section{margin:72px 0}
    .section-header{text-align:center;max-width:780px;margin:0 auto 48px}
    .section-label{display:inline-block;color:var(--gold-2);font-weight:800;font-size:12px;letter-spacing:.22em;text-transform:uppercase;margin-bottom:14px}
    .section-header h2{font-family:Anton,sans-serif;font-size:clamp(28px,4vw,44px);margin:0 0 14px;line-height:1.1;letter-spacing:.01em;font-weight:400}
    .section-header p{color:var(--muted);font-size:17px;margin:0;line-height:1.7}
    /* Main video — the only player on the page */
    .main-video{max-width:896px;margin:0 auto;border:1px solid var(--border-strong);border-radius:20px;overflow:hidden;background:#000;box-shadow:0 30px 80px rgba(0,0,0,.5)}
    .main-video video{display:block;width:100%;height:auto;aspect-ratio:16/9;object-fit:cover}
    .main-video .video-fallback{padding:80px 24px;text-align:center;color:var(--muted);font-size:16px}
    /* Due diligence */
    .stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin:0 0 40px}
    .stat-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:32px 24px;text-align:center;transition:background .2s ease,border-color .2s ease}
    .stat-card:hover{background:var(--card-hover);border-color:var(--border-strong)}
    .stat-value{font-family:Anton,sans-serif;font-size:clamp(32px,4vw,48px);color:var(--gold-2);line-height:1;margin-bottom:10px;font-weight:400}
    .stat-label{color:var(--muted);font-size:14px;font-weight:600;letter-spacing:.04em}
    .social-intro{text-align:center;color:var(--text);font-size:17px;margin:0 0 24px;font-weight:600}
     .social-row{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;max-width:640px;margin:0 auto}
    .social-card{display:flex;flex-direction:column;align-items:center;gap:8px;background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px 16px;text-align:center;transition:background .2s ease,border-color .2s ease,transform .2s ease}
    .social-card:hover{background:var(--card-hover);border-color:var(--border-strong);transform:translateY(-3px)}
    .social-icon{font-size:28px;line-height:1}
    .social-name{font-weight:800;font-size:14px}
    .social-desc{color:var(--muted);font-size:12px;line-height:1.5}
    /* Q&A accordion */
    .qa-list{max-width:820px;margin:0 auto;display:flex;flex-direction:column;gap:12px}
    .qa-item{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:border-color .2s ease}
    .qa-item:hover{border-color:var(--border-strong)}
    .qa-item summary{display:flex;align-items:center;gap:14px;padding:20px 24px;cursor:pointer;list-style:none;font-weight:700;font-size:16px;transition:background .2s ease}
    .qa-item summary::-webkit-details-marker{display:none}
    .qa-item summary:hover{background:var(--card-hover)}
    .qa-item[open] summary{border-bottom:1px solid var(--border)}
    .qa-icon{font-size:22px;line-height:1;flex:0 0 auto}
    .qa-answer{padding:20px 24px;color:var(--muted);font-size:15px;line-height:1.7}
    /* Results grid */
    .results-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
    .result-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px 24px;text-align:center;transition:background .2s ease,border-color .2s ease,transform .2s ease}
    .result-card:hover{background:var(--card-hover);border-color:var(--border-strong);transform:translateY(-3px)}
    .result-amount{font-family:Anton,sans-serif;font-size:clamp(26px,3vw,36px);color:var(--gold-2);line-height:1.1;margin-bottom:6px;font-weight:400}
    .result-time{color:var(--gold-3);font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:16px}
    .result-name{font-weight:800;font-size:15px}
    .result-place{color:var(--muted);font-size:13px}
    /* Timing */
    .timing-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
    .timing-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px 24px;transition:background .2s ease,border-color .2s ease}
    .timing-card:hover{background:var(--card-hover);border-color:var(--border-strong)}
    .timing-icon{font-size:28px;margin-bottom:14px}
    .timing-card h3{font-family:Anton,sans-serif;font-size:20px;margin:0 0 10px;line-height:1.3;font-weight:400}
    .timing-card p{color:var(--muted);font-size:14px;margin:0;line-height:1.7}
    /* What happens next */
    .steps-row{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:40px}
    .step-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px 24px;text-align:center;transition:background .2s ease,border-color .2s ease}
    .step-card:hover{background:var(--card-hover);border-color:var(--border-strong)}
    .step-icon{font-size:32px;margin-bottom:14px}
    .step-card h3{font-family:Anton,sans-serif;font-size:20px;margin:0 0 10px;font-weight:400}
    .step-card p{color:var(--muted);font-size:14px;margin:0;line-height:1.7}
    .contact-box{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:32px;text-align:center;max-width:640px;margin:0 auto}
    .contact-box h3{font-family:Anton,sans-serif;font-size:22px;margin:0 0 18px;font-weight:400}
    .contact-channels{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:18px}
    .channel{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:12px;border:1px solid var(--border-strong);background:rgba(212,175,55,0.1);color:var(--gold-2);font-weight:800;font-size:14px}
    .contact-note{color:var(--muted);font-size:14px;margin:0;line-height:1.6}
    /* Receipts */
    .receipts-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
    .receipt-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px 24px;transition:background .2s ease,border-color .2s ease}
    .receipt-card:hover{background:var(--card-hover);border-color:var(--border-strong)}
    .receipt-name{font-weight:800;font-size:15px;color:var(--gold-2);margin-bottom:6px}
    .receipt-card h3{font-family:Anton,sans-serif;font-size:19px;margin:0 0 12px;line-height:1.3;font-weight:400}
     .receipt-card p{color:var(--muted);font-size:14px;margin:0;line-height:1.7}
     .receipt-img{display:block;width:100%;height:180px;object-fit:cover;border-radius:10px;margin-bottom:16px}
    footer{border-top:1px solid var(--border);margin-top:80px;padding:32px 0 0;text-align:center}
    .footer-links{display:flex;gap:24px;justify-content:center;flex-wrap:wrap;margin-bottom:16px}
    .footer-links a{color:var(--muted);font-size:14px;font-weight:600;transition:color .2s ease}
    .footer-links a:hover{color:var(--gold-2)}
    .copyright{color:var(--muted);font-size:13px;margin:0}
    @media (max-width:768px){
      .stats-row,.social-row,.results-grid,.timing-grid,.steps-row{grid-template-columns:1fr}
      .receipts-grid{grid-template-columns:1fr}
      .hero{padding:40px 0 32px}
      section{margin:56px 0}
    }
    @media (prefers-reduced-motion:reduce){
      *{transition:none !important}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <header class="hero">
      <span class="badge"><?= h($hplCopy('ty_badge', 'Inquiry Received — Specialist Assigned')) ?></span>
      <h1><?= h($hplCopy('ty_h', 'Thanks for reaching out')) ?><?= $firstName !== '' ? ', ' . h($firstName) : '' ?></h1>
      <p class="sub"><?= h($hplCopy('ty_sub', 'Your information has been received by the HPL team. We will review your requirements and contact you to discuss what you are looking for.')) ?></p>
      <p class="watch-note"><?= h($hplCopy('ty_watch_note', 'While you wait — watch the video below so you are fully prepared when we connect.')) ?></p>
      <div class="response-pill"><?= h($hplCopy('ty_response_time', 'We typically respond within one business day')) ?></div>
      <div class="cta-row">
        <?php if ($waUrl !== ''): ?>
        <a class="btn" href="<?= h($waUrl) ?>" target="_blank" rel="noopener"><?= h($hplCopy('ty_whatsapp_button', 'Message HPL on WhatsApp')) ?></a>
        <?php endif; ?>
        <a class="btn ghost" href="index.php"><?= h($hplCopy('ty_back', 'Explore HPL Equipment')) ?></a>
      </div>
    </header>

    <section id="start">
      <div class="section-header">
        <span class="section-label"><?= h($hplCopy('ty_start_label', 'Start here')) ?></span>
        <h2><?= h($hplCopy('ty_start_h', 'Watch This Before We Connect')) ?></h2>
        <p><?= h($hplCopy('ty_start_sub', 'Everything you need to know about HPL, how we work, and what to expect on the call.')) ?></p>
      </div>
      <div class="main-video">
        <?php if (trim((string)($s['ty_video_embed'] ?? '')) !== ''): ?>
          <?= $s['ty_video_embed'] ?>
        <?php elseif ($tyVideo !== ''): ?>
          <?php if (str_starts_with($tyVideo, 'http')): ?>
            <video controls playsinline preload="metadata" poster="">
              <source src="<?= h($tyVideo) ?>" type="video/mp4">
            </video>
          <?php else: ?>
            <video controls playsinline preload="metadata">
              <source src="<?= h($tyVideo) ?>" type="<?= h(hpl_media_type($tyVideo)) ?>">
            </video>
          <?php endif; ?>
        <?php else: ?>
          <div class="video-fallback"><?= h($hplCopy('ty_vplaceholder', 'Our thank-you video is being uploaded. Please check back shortly.')) ?></div>
        <?php endif; ?>
      </div>
    </section>

    <section id="diligence">
      <div class="section-header">
        <span class="section-label"><?= h($hplCopy('ty_dd_label', 'Do your due diligence')) ?></span>
        <h2><?= h($hplCopy('ty_dd_h', 'Research HPL Before The Call')) ?></h2>
        <p><?= h($hplCopy('ty_dd_sub', 'We want you to feel 100% confident before we even speak. Here is everything you need to know about who you are working with.')) ?></p>
      </div>
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-value"><?= h($hplCopy('ty_stat1_value', '500+')) ?></div>
          <div class="stat-label"><?= h($hplCopy('ty_stat1_label', 'Happy customers')) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= h($hplCopy('ty_stat2_value', '2000+')) ?></div>
          <div class="stat-label"><?= h($hplCopy('ty_stat2_label', 'Ounces found')) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= h($hplCopy('ty_stat3_value', '12+')) ?></div>
          <div class="stat-label"><?= h($hplCopy('ty_stat3_label', 'Countries served')) ?></div>
        </div>
      </div>
      <p class="social-intro"><?= h($hplCopy('ty_social_intro', 'Go check for yourself:')) ?></p>
      <div class="social-row">
        <a class="social-card" href="<?= h($hplCopy('ty_social_ig_url', 'https://facebook.com')) ?>" target="_blank" rel="noopener">
          <span class="social-icon">📘</span>
          <span class="social-name"><?= h($hplCopy('ty_social_ig_label', 'HPL Gold')) ?></span>
          <span class="social-desc"><?= h($hplCopy('ty_social_ig_desc', 'Facebook — see the finds, the field work, the results')) ?></span>
        </a>
        <a class="social-card" href="<?= h($hplCopy('ty_social_tt_url', 'https://tiktok.com')) ?>" target="_blank" rel="noopener">
          <span class="social-icon">🎵</span>
          <span class="social-name"><?= h($hplCopy('ty_social_tt_label', '@hplgold')) ?></span>
          <span class="social-desc"><?= h($hplCopy('ty_social_tt_desc', 'TikTok — short-form proof of what we sell')) ?></span>
        </a>
      </div>
    </section>

    <section id="questions">
      <div class="section-header">
        <span class="section-label"><?= h($hplCopy('ty_q_label', 'While you wait')) ?></span>
        <h2><?= h($hplCopy('ty_q_h', 'Got Questions? Here Are The Answers')) ?></h2>
        <p><?= h($hplCopy('ty_q_sub', 'Tap the one that is on your mind — we answer every question people ask before their call.')) ?></p>
      </div>
      <div class="qa-list">
        <?php foreach ($tyQuestions as $q): ?>
        <details class="qa-item">
          <summary><span class="qa-icon"><?= h($q['icon']) ?></span><?= h($q['q']) ?></summary>
          <div class="qa-answer"><?= h($q['a']) ?></div>
        </details>
        <?php endforeach; ?>
      </div>
    </section>

    <section id="results">
      <div class="section-header">
        <span class="section-label"><?= h($hplCopy('ty_results_label', 'Real results')) ?></span>
        <h2><?= h($hplCopy('ty_results_h', 'Don\'t Take Our Word For It')) ?></h2>
        <p><?= h($hplCopy('ty_results_sub', 'Real finds from real customers. These are the results people get with the right machine on the right ground.')) ?></p>
      </div>
      <div class="results-grid">
        <?php foreach ($tyResults as $r): ?>
        <div class="result-card">
          <div class="result-amount"><?= h($r['amount']) ?></div>
          <div class="result-time"><?= h($r['time']) ?></div>
          <div class="result-name"><?= h($r['name']) ?></div>
          <div class="result-place"><?= h($r['place']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section id="timing">
      <div class="section-header">
        <span class="section-label"><?= h($hplCopy('ty_timing_label', 'Timing matters')) ?></span>
        <h2><?= h($hplCopy('ty_timing_h', 'Why Prospectors Who Move First Win The Most')) ?></h2>
      </div>
      <div class="timing-grid">
        <div class="timing-card">
          <div class="timing-icon">📈</div>
          <h3><?= h($hplCopy('ty_timing1_h', 'The gold market is booming right now')) ?></h3>
          <p><?= h($hplCopy('ty_timing1_text', 'Gold prices are at record highs and more people are out searching than ever. The prospectors who get the right equipment now are the ones finding the targets others miss.')) ?></p>
        </div>
        <div class="timing-card">
          <div class="timing-icon">⛏️</div>
          <h3><?= h($hplCopy('ty_timing2_h', 'First movers take the biggest share')) ?></h3>
          <p><?= h($hplCopy('ty_timing2_text', 'The ground that has not been worked yet is the ground that still pays. Every season you wait, more people cover the same patches — and the easy finds get taken.')) ?></p>
        </div>
        <div class="timing-card">
          <div class="timing-icon">⏰</div>
          <h3><?= h($hplCopy('ty_timing3_h', 'Support is limited by design')) ?></h3>
          <p><?= h($hplCopy('ty_timing3_text', 'Every machine comes with personal setup help from people who prospect themselves. We only take on as many customers as we can properly support, so the guidance stays personal.')) ?></p>
        </div>
      </div>
    </section>

    <section id="next">
      <div class="section-header">
        <span class="section-label"><?= h($hplCopy('ty_next_label', 'What happens next')) ?></span>
        <h2><?= h($hplCopy('ty_next_h', 'What Happens Next')) ?></h2>
      </div>
      <div class="steps-row">
        <div class="step-card">
          <div class="step-icon">📱</div>
          <h3><?= h($hplCopy('ty_step1_h', '1. We Review')) ?></h3>
          <p><?= h($hplCopy('ty_step1_text', 'Every request is reviewed personally. We typically respond within one business day.')) ?></p>
        </div>
        <div class="step-card">
          <div class="step-icon">📞</div>
          <h3><?= h($hplCopy('ty_step2_h', '2. We Reach Out')) ?></h3>
          <p><?= h($hplCopy('ty_step2_text', 'You will get a WhatsApp message or a call to discuss your ground and the right setup. Keep an eye on your inbox.')) ?></p>
        </div>
        <div class="step-card">
          <div class="step-icon">🎯</div>
          <h3><?= h($hplCopy('ty_step3_h', '3. Show Up Ready')) ?></h3>
          <p><?= h($hplCopy('ty_step3_text', 'Watch the video above so the conversation is productive from minute one. The easier you make it, the faster we can help.')) ?></p>
        </div>
      </div>
      <div class="contact-box">
        <h3><?= h($hplCopy('ty_contact_h', 'You will hear from us via')) ?></h3>
        <div class="contact-channels">
          <span class="channel">📱 <?= h($hplCopy('ty_contact_dm', 'WhatsApp DM')) ?></span>
        </div>
        <p class="contact-note"><?= h($hplCopy('ty_contact_note', 'If you do not hear back within one business day, message us on WhatsApp — we will sort it out.')) ?></p>
      </div>
    </section>

    <section id="receipts">
      <div class="section-header">
        <span class="section-label"><?= h($hplCopy('ty_receipts_label', 'The receipts')) ?></span>
        <h2><?= h($hplCopy('ty_receipts_h', 'Wins From Real Prospectors')) ?></h2>
        <p><?= h($hplCopy('ty_receipts_sub', 'Real finds, real stories, real results — directly from our customers in the field.')) ?></p>
      </div>
      <div class="receipts-grid">
        <?php foreach ($tyReceipts as $i => $rcpt): 
          $rcptImg = 'ty-rcpt-' . ($i + 1) . '.jpg';
        ?>
        <div class="receipt-card">
          <?php if (file_exists(__DIR__ . '/img/' . $rcptImg)): ?>
            <img class="receipt-img" src="<?= hpl_img_url($rcptImg) ?>" alt="">
          <?php endif; ?>
          <div class="receipt-name"><?= h($rcpt['name']) ?></div>
          <h3><?= h($rcpt['title']) ?></h3>
          <p><?= h($rcpt['text']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <footer>
      <div class="footer-links">
        <a href="#start"><?= h($hplCopy('ty_footer_link1', 'Watch the video')) ?></a>
        <a href="#questions"><?= h($hplCopy('ty_footer_link2', 'Questions')) ?></a>
        <a href="#results"><?= h($hplCopy('ty_footer_link3', 'Real results')) ?></a>
        <a href="index.php"><?= h($hplCopy('ty_footer_link4', 'Back to HPL')) ?></a>
      </div>
      <p class="copyright"><?= h($hplCopy('ty_copyright', '© 2026 HPL Gold Detectors. All rights reserved.')) ?></p>
    </footer>
  </div>
</body>
</html>
