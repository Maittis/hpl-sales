<?php
require_once __DIR__ . '/config.php';
$s = hpl_settings();

$leadError = '';
$leadName = $_POST['lead_name'] ?? '';
$leadFirst = $_POST['lead_first'] ?? '';
$leadLast = $_POST['lead_last'] ?? '';
$leadCc = $_POST['lead_cc'] ?? (string)($s['form_default_cc'] ?? '260');
$leadPhone = $_POST['lead_phone'] ?? '';
$leadKnowledge = $_POST['lead_knowledge'] ?? '';
$leadEmail = trim((string)($_POST['lead_email'] ?? ''));
$leadTerrain = trim((string)($_POST['lead_terrain'] ?? ''));
$leadTarget = trim((string)($_POST['lead_target'] ?? ''));
$leadTiming = trim((string)($_POST['lead_timing'] ?? ''));
if (isset($_POST['lead_submit'])) {
    $connection = db();
    $first = trim((string)$leadFirst);
    $last = trim((string)$leadLast);
    $name = trim($first . ' ' . $last);
    if ($name === '') {
        $name = trim((string)$leadName);
    }
    $cc = preg_replace('/\D/', '', (string)$leadCc);
    $digits = preg_replace('/\D/', '', (string)$leadPhone);
    $phone = $cc . $digits;
    $knowledge = trim((string)$leadKnowledge);
    $learn = ($_POST['lead_learn'] ?? '') === 'Yes' ? 'Yes' : 'No';
    if (!$connection) {
        $leadError = 'Could not reach the server database. Please try again later.';
    } elseif ($name === '' || $digits === '') {
        $leadError = 'Please fill in your name and phone number.';
    } elseif ($leadTerrain === '' || $leadTarget === '') {
        $leadError = 'Please answer the two questions before continuing.';
    } else {
        try {
            $stmt = $connection->prepare('INSERT INTO leads (name, phone, email, terrain, target, timing, knowledge, wants_to_learn) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $emailVal = $leadEmail !== '' ? $leadEmail : null;
            $stmt->bind_param('ssssssss', $name, $phone, $emailVal, $leadTerrain, $leadTarget, $leadTiming, $knowledge, $learn);
            $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            $leadError = 'Could not save your details right now. Please try again in a moment.';
        }
        if ($leadError === '') {
            $firstOut = trim((string)$first);
            if ($firstOut === '') {
                $parts = explode(' ', $name);
                $firstOut = $parts[0] ?? '';
            }
            header('Location: thank-you.php?name=' . rawurlencode($firstOut));
            exit;
        }
    }
}
$done = isset($_GET['done']) ? (string)$_GET['done'] : '';

/**
 * Poster frame for a local proof video: the card is painted immediately instead
 * of sitting blank while the real video is still being fetched. Returns an empty
 * string when the poster is missing, so no broken image is ever left behind.
 */
function hpl_poster_attr(string $src): string
{
    if ($src === '' || !str_starts_with($src, 'uploads/')) {
        return '';
    }
    $poster = (string)preg_replace('/\.(mp4|m4v|webm|mov|ogv)$/i', '.jpg', $src);
    if ($poster === '' || $poster === $src) {
        return '';
    }
    if (!is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $poster))) {
        return '';
    }

    return 'poster="' . htmlspecialchars($poster, ENT_QUOTES) . '" ';
}
function hpl_countries(): array
{
    return [
        '234' => ['label' => '+234', 'name' => 'Nigeria', 'flag' => 'ng'],
        '260' => ['label' => '+260', 'name' => 'Zambia', 'flag' => 'zm'],
        '263' => ['label' => '+263', 'name' => 'Zimbabwe', 'flag' => 'zw'],
        '27' => ['label' => '+27', 'name' => 'South Africa', 'flag' => 'za'],
        '255' => ['label' => '+255', 'name' => 'Tanzania', 'flag' => 'tz'],
        '256' => ['label' => '+256', 'name' => 'Uganda', 'flag' => 'ug'],
        '254' => ['label' => '+254', 'name' => 'Kenya', 'flag' => 'ke'],
        '233' => ['label' => '+233', 'name' => 'Ghana', 'flag' => 'gh'],
        '250' => ['label' => '+250', 'name' => 'Rwanda', 'flag' => 'rw'],
        '267' => ['label' => '+267', 'name' => 'Botswana', 'flag' => 'bw'],
        '264' => ['label' => '+264', 'name' => 'Namibia', 'flag' => 'na'],
        '258' => ['label' => '+258', 'name' => 'Mozambique', 'flag' => 'mz'],
        '265' => ['label' => '+265', 'name' => 'Malawi', 'flag' => 'mw'],
        '268' => ['label' => '+268', 'name' => 'Eswatini', 'flag' => 'sz'],
        '266' => ['label' => '+266', 'name' => 'Lesotho', 'flag' => 'ls'],
        '20' => ['label' => '+20', 'name' => 'Egypt', 'flag' => 'eg'],
        '212' => ['label' => '+212', 'name' => 'Morocco', 'flag' => 'ma'],
        '216' => ['label' => '+216', 'name' => 'Tunisia', 'flag' => 'tn'],
        '213' => ['label' => '+213', 'name' => 'Algeria', 'flag' => 'dz'],
        '244' => ['label' => '+244', 'name' => 'Angola', 'flag' => 'ao'],
        '243' => ['label' => '+243', 'name' => 'DR Congo', 'flag' => 'cd'],
        '225' => ['label' => '+225', 'name' => 'Cote d Ivoire', 'flag' => 'ci'],
        '221' => ['label' => '+221', 'name' => 'Senegal', 'flag' => 'sn'],
        '223' => ['label' => '+223', 'name' => 'Mali', 'flag' => 'ml'],
        '235' => ['label' => '+235', 'name' => 'Chad', 'flag' => 'td'],
        '237' => ['label' => '+237', 'name' => 'Cameroon', 'flag' => 'cm'],
        '241' => ['label' => '+241', 'name' => 'Gabon', 'flag' => 'ga'],
        '242' => ['label' => '+242', 'name' => 'Republic of the Congo', 'flag' => 'cg'],
        '251' => ['label' => '+251', 'name' => 'Ethiopia', 'flag' => 'et'],
        '211' => ['label' => '+211', 'name' => 'South Sudan', 'flag' => 'ss'],
        '252' => ['label' => '+252', 'name' => 'Somalia', 'flag' => 'so'],
        '220' => ['label' => '+220', 'name' => 'Gambia', 'flag' => 'gm'],
        '232' => ['label' => '+232', 'name' => 'Sierra Leone', 'flag' => 'sl'],
        '226' => ['label' => '+226', 'name' => 'Burkina Faso', 'flag' => 'bf'],
        '227' => ['label' => '+227', 'name' => 'Niger', 'flag' => 'ne'],
        '222' => ['label' => '+222', 'name' => 'Mauritania', 'flag' => 'mr'],
        '261' => ['label' => '+261', 'name' => 'Madagascar', 'flag' => 'mg'],
        '230' => ['label' => '+230', 'name' => 'Mauritius', 'flag' => 'mu'],
        '248' => ['label' => '+248', 'name' => 'Seychelles', 'flag' => 'sc'],
        '245' => ['label' => '+245', 'name' => 'Guinea-Bissau', 'flag' => 'gw'],
        '44' => ['label' => '+44', 'name' => 'United Kingdom', 'flag' => 'gb'],
        '353' => ['label' => '+353', 'name' => 'Ireland', 'flag' => 'ie'],
        '49' => ['label' => '+49', 'name' => 'Germany', 'flag' => 'de'],
        '33' => ['label' => '+33', 'name' => 'France', 'flag' => 'fr'],
        '34' => ['label' => '+34', 'name' => 'Spain', 'flag' => 'es'],
        '39' => ['label' => '+39', 'name' => 'Italy', 'flag' => 'it'],
        '351' => ['label' => '+351', 'name' => 'Portugal', 'flag' => 'pt'],
        '31' => ['label' => '+31', 'name' => 'Netherlands', 'flag' => 'nl'],
        '32' => ['label' => '+32', 'name' => 'Belgium', 'flag' => 'be'],
        '41' => ['label' => '+41', 'name' => 'Switzerland', 'flag' => 'ch'],
        '43' => ['label' => '+43', 'name' => 'Austria', 'flag' => 'at'],
        '46' => ['label' => '+46', 'name' => 'Sweden', 'flag' => 'se'],
        '47' => ['label' => '+47', 'name' => 'Norway', 'flag' => 'no'],
        '45' => ['label' => '+45', 'name' => 'Denmark', 'flag' => 'dk'],
        '358' => ['label' => '+358', 'name' => 'Finland', 'flag' => 'fi'],
        '48' => ['label' => '+48', 'name' => 'Poland', 'flag' => 'pl'],
        '7' => ['label' => '+7', 'name' => 'Russia', 'flag' => 'ru'],
        '380' => ['label' => '+380', 'name' => 'Ukraine', 'flag' => 'ua'],
        '90' => ['label' => '+90', 'name' => 'Turkey', 'flag' => 'tr'],
        '971' => ['label' => '+971', 'name' => 'UAE', 'flag' => 'ae'],
        '966' => ['label' => '+966', 'name' => 'Saudi Arabia', 'flag' => 'sa'],
        '974' => ['label' => '+974', 'name' => 'Qatar', 'flag' => 'qa'],
        '965' => ['label' => '+965', 'name' => 'Kuwait', 'flag' => 'kw'],
        '968' => ['label' => '+968', 'name' => 'Oman', 'flag' => 'om'],
        '973' => ['label' => '+973', 'name' => 'Bahrain', 'flag' => 'bh'],
        '962' => ['label' => '+962', 'name' => 'Jordan', 'flag' => 'jo'],
        '972' => ['label' => '+972', 'name' => 'Israel', 'flag' => 'il'],
        '961' => ['label' => '+961', 'name' => 'Lebanon', 'flag' => 'lb'],
        '91' => ['label' => '+91', 'name' => 'India', 'flag' => 'in'],
        '92' => ['label' => '+92', 'name' => 'Pakistan', 'flag' => 'pk'],
        '880' => ['label' => '+880', 'name' => 'Bangladesh', 'flag' => 'bd'],
        '977' => ['label' => '+977', 'name' => 'Nepal', 'flag' => 'np'],
        '94' => ['label' => '+94', 'name' => 'Sri Lanka', 'flag' => 'lk'],
        '86' => ['label' => '+86', 'name' => 'China', 'flag' => 'cn'],
        '852' => ['label' => '+852', 'name' => 'Hong Kong', 'flag' => 'hk'],
        '81' => ['label' => '+81', 'name' => 'Japan', 'flag' => 'jp'],
        '82' => ['label' => '+82', 'name' => 'South Korea', 'flag' => 'kr'],
        '60' => ['label' => '+60', 'name' => 'Malaysia', 'flag' => 'my'],
        '65' => ['label' => '+65', 'name' => 'Singapore', 'flag' => 'sg'],
        '66' => ['label' => '+66', 'name' => 'Thailand', 'flag' => 'th'],
        '84' => ['label' => '+84', 'name' => 'Vietnam', 'flag' => 'vn'],
        '62' => ['label' => '+62', 'name' => 'Indonesia', 'flag' => 'id'],
        '63' => ['label' => '+63', 'name' => 'Philippines', 'flag' => 'ph'],
        '61' => ['label' => '+61', 'name' => 'Australia', 'flag' => 'au'],
        '64' => ['label' => '+64', 'name' => 'New Zealand', 'flag' => 'nz'],
        '1' => ['label' => '+1', 'name' => 'United States / Canada', 'flag' => 'us'],
        '52' => ['label' => '+52', 'name' => 'Mexico', 'flag' => 'mx'],
        '55' => ['label' => '+55', 'name' => 'Brazil', 'flag' => 'br'],
        '54' => ['label' => '+54', 'name' => 'Argentina', 'flag' => 'ar'],
        '57' => ['label' => '+57', 'name' => 'Colombia', 'flag' => 'co'],
        '56' => ['label' => '+56', 'name' => 'Chile', 'flag' => 'cl'],
        '51' => ['label' => '+51', 'name' => 'Peru', 'flag' => 'pe'],
        '58' => ['label' => '+58', 'name' => 'Venezuela', 'flag' => 've'],
        '593' => ['label' => '+593', 'name' => 'Ecuador', 'flag' => 'ec'],
        '591' => ['label' => '+591', 'name' => 'Bolivia', 'flag' => 'bo'],
        '598' => ['label' => '+598', 'name' => 'Uruguay', 'flag' => 'uy'],
        '595' => ['label' => '+595', 'name' => 'Paraguay', 'flag' => 'py'],
    ];
}

function hpl_options(string $group): array
{
    $map = [
        'terrain' => ['Old mine dumps and historic workings', 'Rivers, streams and riverbanks', 'Beach and shallow surf', 'Rocky desert and dry lake beds', 'Forest, farmland and rocky slopes', 'I am still deciding'],
        'target' => ['Fine gold and small nuggets', 'Placer gold in streams and creeks', 'Coins and relics', 'Lost jewellery', 'Whatever the machine finds best', 'Not sure yet'],
        'timing' => ['As soon as I can', 'Within the next few months', 'Later this year', 'Just researching for now'],
    ];
    return isset($map[$group]) ? $map[$group] : [];
}

function cc_flag(string $cc): string
{
    $all = hpl_countries();
    return isset($all[$cc]) ? $all[$cc]['flag'] : 'zm';
}

function cc_label(string $cc): string
{
    $all = hpl_countries();
    return isset($all[$cc]) ? $all[$cc]['label'] : '+' . $cc;
}

const DETECTOR_ART = '<div class="detector"><div class="handle"></div><div class="control"><div class="screen"></div><i></i></div><div class="shaft"></div><div class="coil"></div></div>';

function art_block(string $file, string $fallbackClass = ''): string
{
    if (file_exists(__DIR__ . '/img/' . $file)) {
        return '<img class="art-img" src="' . h('img/' . $file) . '" alt="HPL Gold Detectors" loading="lazy">';
    }
    if ($fallbackClass !== '') {
        return '<span class="' . h($fallbackClass) . '">' . DETECTOR_ART . '</span>';
    }
    return DETECTOR_ART;
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($s['topline']) ?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="img/favicon-16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32.png">
  <link rel="icon" type="image/png" sizes="48x48" href="img/favicon-48.png">
  <link rel="icon" type="image/png" sizes="192x192" href="img/favicon-192.png">
  <link rel="apple-touch-icon" href="img/favicon-180.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
  <style>
    :root { --navy:#111a38; --navy-dark:#090f24; --gold:#d4a52c; --gold-light:#f4ca5b; --ink:#182038; --muted:#687083; --paper:#fff; --wash:#f4f2ed; }
    * { box-sizing:border-box; }
    html { scroll-behavior:smooth; }
    body { margin:0; color:var(--ink); background:var(--paper); font-family:'DM Sans',sans-serif; font-size:17px; line-height:1.55; overflow-x:hidden; }
    a { color:inherit; text-decoration:none; }
    .page { width:100%; margin:0 auto; background:#fff; }
    .topline { background:#070b18; color:#fff; font-size:11px; font-weight:700; letter-spacing:.08em; padding:9px 20px; text-align:center; text-transform:uppercase; }
    header { background:var(--navy); color:#fff; padding:20px 34px; }
    .nav { align-items:center; display:flex; justify-content:space-between; }
    .brand { align-items:center; display:flex; font-family:'Space Grotesk',sans-serif; font-size:20px; font-weight:700; gap:10px; }
    .brand-mark { align-items:center; border:2px solid var(--gold-light); border-radius:50%; color:var(--gold-light); display:flex; font-size:12px; height:32px; justify-content:center; width:32px; }
    .logo-chip { background:#fff; border-radius:8px; line-height:0; padding:6px 9px; }
    .header-logo { display:block; height:44px; width:auto; }
    .nav-links { color:#d9deea; display:flex; font-size:15px; gap:26px; }
    .nav-cta { background:var(--gold); color:var(--navy-dark); font-size:12px; font-weight:700; padding:12px 18px; text-transform:uppercase; }
    .hero { background:var(--navy); color:#fff; padding:24px 34px 44px; text-align:center; }
    .hero h1 { font-family:'Space Grotesk',sans-serif; font-size:clamp(38px,7vw,62px); letter-spacing:-.06em; line-height:.98; margin:0 auto 20px; max-width:640px; }
    .hero h1 span { color:var(--gold-light); }
    .hero-sub { color:var(--gold-light); font-size:18px; font-weight:700; letter-spacing:.01em; line-height:1.35; margin:-6px auto 18px; max-width:620px; }
    .hero-intro { color:#e3e6ed; font-size:19px; line-height:1.45; margin:0 auto 30px; max-width:640px; }
    .detector-panel { background:#000; border:1px solid rgba(244,202,91,.35); margin:0 auto 30px; max-width:800px; aspect-ratio:16/9; overflow:hidden; position:relative; width:100%; }
    .detector-panel video { display:block; height:100%; object-fit:cover; width:100%; }
    .mute-toggle { background:rgba(9,15,36,.82); border:1px solid rgba(244,202,91,.6); color:var(--gold-light); cursor:pointer; font-size:12px; font-weight:700; letter-spacing:.1em; padding:9px 13px; position:absolute; right:14px; text-transform:uppercase; top:14px; z-index:3; }
    .mute-toggle:hover { background:var(--navy); }
    .video-fallback { align-items:center; color:#bfc8d8; display:flex; font-size:15px; height:100%; justify-content:center; padding:30px; text-align:center; }
    .panel-kicker { background:var(--gold); color:var(--navy-dark); font-size:12px; font-weight:700; left:16px; letter-spacing:.13em; padding:9px 12px; position:absolute; text-transform:uppercase; top:16px; z-index:2; }
    .hero-copy { color:#bfc8d8; font-size:15px; line-height:1.5; margin:0 auto 18px; max-width:560px; }
    .button { background:var(--gold); border:0; color:var(--navy-dark); cursor:pointer; display:inline-block; font-size:14px; font-weight:700; letter-spacing:.06em; padding:16px 38px; text-transform:uppercase; }
    .button:hover { background:var(--gold-light); }
    .section { padding:46px 34px; }
    .section.center { text-align:center; }
    .section h2 { color:var(--navy); font-family:'Space Grotesk',sans-serif; font-size:34px; letter-spacing:-.05em; line-height:1.05; margin:0 0 12px; }
    .section p { color:var(--muted); font-size:16px; line-height:1.6; margin:0 auto; max-width:640px; }
    .wash { background:var(--wash); }
    .proof { text-align:center; }
    .proof h2 { margin-bottom:20px; }
    .proof-stage { aspect-ratio:4/5; background:#000; box-shadow:0 14px 28px rgba(9,15,36,.16); margin:0 auto; max-height:52vh; max-width:520px; overflow:hidden; position:relative; width:min(520px,92vw); }
    .proof-slide { inset:0; opacity:0; position:absolute; transform:scale(1.03); transition:opacity .45s ease, transform .55s ease; }
    .proof-slide.on { opacity:1; transform:none; z-index:2; }
    .proof-slide video { display:block; height:100%; object-fit:cover; width:100%; }
    .proof-slide figcaption { background:linear-gradient(to top,rgba(0,0,0,.82),rgba(0,0,0,0)); bottom:0; color:#fff; font-size:14px; font-weight:700; left:0; padding:38px 14px 14px; position:absolute; right:0; text-align:left; }
    .proof-cue { align-items:center; animation:nudge 1.8s ease-in-out infinite; background:rgba(17,26,56,.85); border:0; border-radius:50%; bottom:16px; color:#fff; cursor:pointer; display:flex; font-size:24px; height:44px; justify-content:center; position:absolute; right:16px; transition:opacity .3s ease; width:44px; z-index:4; }
    .proof-stage-wrap.moved .proof-cue { opacity:0; pointer-events:none; }
    .proof-stage-wrap { margin:0 auto; max-width:560px; position:relative; }
    @keyframes nudge { 0%,100% { transform:translateX(0); } 50% { transform:translateX(6px); } }
    .proof-empty { background:var(--navy); color:#fff; font-size:14px; margin:0 auto; max-width:420px; padding:60px 20px; }
    .proof-hint { color:var(--muted); font-size:13px; font-weight:700; letter-spacing:.08em; margin:16px 0 0; text-transform:uppercase; }
    .proof-dots { display:flex; gap:8px; justify-content:center; margin:12px 0 0; }
    .proof-dots button { background:#cfd4dc; border:0; border-radius:50%; cursor:pointer; height:9px; padding:0; width:9px; }
    .proof-dots button.on { background:var(--gold); transform:scale(1.35); }
    /* circle of videos */
    .ring-wrap { margin:26px auto 0; max-width:100%; position:relative; touch-action:pan-y; }
    .ring { --ring-w:min(var(--ring-size,300px), 80vw); height:calc(var(--ring-w) / var(--ring-shape,0.5625) * 1.12 + 30px); margin:0 auto; max-width:100%; perspective:calc(var(--ring-r,300px) * 10); position:relative; width:calc(var(--ring-r,300px) * 2 + var(--ring-w) + 40px); }
    .ring-item { background:#0b1226; border-radius:14px; box-shadow:0 12px 30px rgba(9,15,36,.28); cursor:pointer; left:50%; margin:0; overflow:hidden; position:absolute; top:50%; transform:translate(-50%,-50%); transition:transform .62s cubic-bezier(.45,.05,.25,1), opacity .4s ease, filter .5s ease, box-shadow .4s ease; width:var(--ring-w); will-change:transform,opacity; }
    .ring-item video { aspect-ratio:var(--ring-shape,0.5625); display:block; height:auto; object-fit:cover; width:100%; }
    .ring-item figcaption { background:linear-gradient(to top,rgba(0,0,0,.86),rgba(0,0,0,0)); bottom:0; color:#fff; font-size:12px; font-weight:700; left:0; line-height:1.25; padding:30px 10px 10px; position:absolute; right:0; text-align:left; }
    .ring-item::after { border:2px solid transparent; border-radius:14px; content:''; inset:0; pointer-events:none; position:absolute; transition:border-color .35s ease; }
    .ring-item.front { box-shadow:0 20px 44px rgba(9,15,36,.4); z-index:5; }
    .ring-item.front::after { border-color:var(--gold); }
    .ring-item:not(.front) { filter:saturate(.82) brightness(.82); }
    .ring-item:hover:not(.front) { filter:none; }
    .ring-play { align-items:center; background:rgba(17,26,56,.55); border:0; border-radius:50%; color:#fff; cursor:pointer; display:flex; font-size:26px; height:62px; justify-content:center; left:50%; padding:0 0 0 4px; position:absolute; top:50%; transform:translate(-50%,-50%); transition:opacity .3s ease, transform .3s ease; width:62px; z-index:6; }
    .ring-play:focus-visible { outline:2px solid var(--gold); outline-offset:3px; }
    .ring-wrap.moved .ring-play { opacity:0; pointer-events:none; transform:translate(-50%,-50%) scale(.8); }
    .ring-wrap.playing .ring-play { opacity:0; pointer-events:none; }
    .ring-item video { pointer-events:none; }
    .ring-nav { align-items:center; display:flex; gap:14px; justify-content:center; margin:18px 0 0; }
    .ring-nav button { align-items:center; background:rgba(17,26,56,.08); border:1px solid rgba(9,15,36,.16); border-radius:50%; color:var(--navy); cursor:pointer; display:flex; font-size:20px; height:40px; justify-content:center; line-height:1; padding:0; width:40px; }
    .ring-nav button:hover { background:var(--gold); border-color:var(--gold); color:#fff; }
    .ring-nav button:focus-visible { outline:2px solid var(--gold); outline-offset:2px; }

/* 3D coverflow, tuned to the supplied reference: a dominant centre card on a
   near-black stage, neighbours one step back and softened, never fully hidden. */
.proof-dark { background:radial-gradient(ellipse 55% 75% at 50% 45%, rgba(55,61,72,.85) 0%, rgba(30,33,39,.65) 30%, rgba(10,10,10,.95) 65%, #060606 100%); min-height:100vh; position:relative; }
.proof-dark h2, .proof-dark .section-label { color:#fff; }
.proof-dark .proof-hint, .proof-dark .proof-caption { color:rgba(255,255,255,.55); }
.proof-coverflow .cf-stage { --cf-gap:30px; --cf-w:260px; height:calc(var(--cf-w) / var(--ring-shape,.5625) * 1.12); overflow:hidden; perspective:1200px; perspective-origin:50% 46%; position:relative; }
.proof-coverflow .cf-stage::before { background:radial-gradient(circle at 50% 50%, rgba(255,255,255,.10), transparent 45%); content:''; inset:0; pointer-events:none; position:absolute; }
.proof-coverflow .ring-item { border:1px solid rgba(255,255,255,.12); border-radius:28px; box-shadow:0 14px 34px rgba(0,0,0,.4); transform-style:preserve-3d; transition:transform .8s cubic-bezier(.22,1,.36,1), opacity .8s cubic-bezier(.22,1,.36,1), filter .8s cubic-bezier(.22,1,.36,1); width:var(--cf-w);  }
.proof-coverflow .ring-item.front { will-change:transform,opacity,filter; }
.proof-coverflow .ring-item::after { border-radius:28px; }
.proof-coverflow .ring-item.front { box-shadow:0 20px 60px rgba(0,0,0,.45), 0 0 0 1px rgba(255,255,255,.2), 0 0 58px rgba(255,255,255,.09); }
.proof-coverflow .ring-item { filter:blur(var(--cf-blur,0px)) brightness(var(--cf-bright,1)); }
.proof-coverflow .ring-item:hover:not(.front) { filter:blur(var(--cf-blur,0px)) brightness(calc(var(--cf-bright,1) + .14)); }
.proof-coverflow .cf-arrow { align-items:center; backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); background:rgba(30,30,30,.42); border:1px solid rgba(255,255,255,.16); border-radius:50%; color:rgba(255,255,255,.86); cursor:pointer; display:flex; font-size:26px; height:46px; justify-content:center; line-height:1; padding:0; position:absolute; top:50%; transform:translateY(-50%); transition:background-color .3s ease, transform .3s ease; width:46px; z-index:6; }
.proof-coverflow .cf-arrow:hover { background:rgba(30,30,30,.72); transform:translateY(-50%) scale(1.05); }
.proof-coverflow .cf-arrow:focus-visible { outline:2px solid var(--gold); outline-offset:3px; }
.proof-coverflow .cf-prev { left:8px; }
.proof-coverflow .cf-next { right:8px; }
.cf-dots { display:flex; gap:9px; justify-content:center; margin:22px 0 0; }
.cf-dots button { background:rgba(255,255,255,.3); border:0; border-radius:999px; cursor:pointer; height:8px; padding:0; transition:background-color .4s ease, width .5s cubic-bezier(.22,1,.36,1); width:8px; }
.cf-dots button:hover { background:rgba(255,255,255,.6); }
.cf-dots button.on { background:var(--gold); width:24px; }
.cf-dots button:focus-visible { outline:2px solid var(--gold); outline-offset:3px; }
.proof-coverflow .ring-play { backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); background:rgba(30,30,30,.55); height:64px; width:64px; }
.proof-coverflow .ring-play:hover { transform:translate(-50%,-50%) scale(1.08); }
.proof-coverflow .ring-nav button { background:rgba(255,255,255,.08); border-color:rgba(255,255,255,.18); color:#fff; }
    @media (max-width:1024px) { .proof-coverflow .cf-stage { --cf-gap:20px; --cf-w:230px; } }
    @media (max-width:640px) { .proof-coverflow .cf-stage { --cf-gap:14px; --cf-w:min(65vw, 280px); } .proof-coverflow .ring-item { border-radius:22px; } .proof-coverflow .ring-item::after { border-radius:22px; } .proof-coverflow .cf-arrow { height:40px; width:40px; font-size:21px; } }
    @media (max-width:640px) { .ring { --ring-r:120px; --ring-size:250px; } .ring-item { border-radius:11px; } .ring-item figcaption { font-size:10px; padding:22px 7px 7px; } .ring-play { font-size:22px; height:50px; width:50px; } }
    @media (max-width:400px) { .ring { --ring-r:95px; --ring-size:210px; } .ring-item figcaption { font-size:9px; padding:18px 6px 6px; } }
    .slides-stage { margin:0 auto; max-width:880px; overflow:hidden; position:relative; }
    .slides-frame { aspect-ratio:16/9; background:var(--navy); margin:0 auto; max-height:56vh; overflow:hidden; position:relative; }
    .slides-frame img { height:100%; inset:0; object-fit:cover; opacity:0; position:absolute; transform:scale(1.04); transition:opacity .9s ease, transform 1.4s ease; width:100%; }
    .slides-frame img.on { opacity:1; transform:none; z-index:2; }
    .slides-cap { background:linear-gradient(to top,rgba(0,0,0,.78),rgba(0,0,0,0)); bottom:0; color:#fff; font-size:15px; font-weight:700; left:0; opacity:0; padding:44px 20px 18px; position:absolute; right:0; text-align:left; transition:opacity .6s ease; z-index:3; }
    .slides-cap.on { opacity:1; }
    .slides-cap span { color:#f4ca5b; display:block; font-size:12px; font-weight:700; letter-spacing:.12em; margin-bottom:4px; text-transform:uppercase; }
    .slides-dots { display:flex; gap:9px; justify-content:center; margin:18px 0 0; }
    .slides-dots button { background:rgba(255,255,255,.35); border:0; border-radius:50%; cursor:pointer; height:9px; padding:0; width:9px; }
    .slides-dots button:hover { background:rgba(255,255,255,.6); }
    .slides-dots button.on { background:var(--gold); transform:scale(1.35); }
    .slides-empty { color:rgba(255,255,255,.7); font-size:14px; padding:50px 20px; }
    .slides { background:var(--navy); }
    .slides h2 { color:#fff; }
    .slides .section-label { color:var(--gold); }
    .proof-gallery { display:grid; gap:30px; grid-template-columns:repeat(3,1fr); margin:0 auto; max-width:860px; padding:6px 0 10px; }
    .proof-item { align-self:start; background:#fff; box-shadow:0 14px 28px rgba(9,15,36,.16); padding:12px 12px 18px; position:relative; transition:transform .25s ease; }
    .proof-item:nth-child(odd) { transform:rotate(-2.6deg); }
    .proof-item:nth-child(even) { transform:rotate(2.4deg); }
    .proof-item:hover { transform:rotate(0deg) scale(1.04); z-index:2; }
    .proof-item::before { background:rgba(244,202,91,.5); box-shadow:0 1px 3px rgba(0,0,0,.12); content:''; height:22px; left:50%; position:absolute; top:-9px; transform:translateX(-50%) rotate(-3deg); width:96px; }
    .proof-item::after { background:var(--gold-light); border-radius:50%; box-shadow:0 0 0 1px rgba(212,165,44,.4); content:''; display:block; height:9px; margin:14px auto 0; width:9px; }
    .proof-item .art-img { aspect-ratio:4/3; display:block; height:auto; object-fit:cover; width:100%; }
    .proof-fallback { aspect-ratio:4/3; background:var(--navy); display:block; position:relative; width:100%; }
    .proof-fallback .detector { height:275px; left:50%; position:absolute; top:18px; transform:translateX(-45%) rotate(-12deg) scale(.42); width:205px; }
    .proof-caption { color:var(--muted); font-size:13px; font-weight:700; letter-spacing:.08em; margin:18px 0 0; text-transform:uppercase; }
    .art-img { display:block; height:100%; object-fit:cover; width:100%; }
    .live-pill { background:#c0392b; border-radius:40px; color:#fff; display:inline-flex; align-items:center; gap:8px; font-size:12px; font-weight:700; letter-spacing:.12em; margin-bottom:20px; padding:9px 18px; text-transform:uppercase; }
    .live-pill i { background:#ff6b6b; border-radius:50%; display:inline-block; height:8px; position:relative; width:8px; }
    .live-pill i::after { animation:pulse 1.6s infinite; background:#ff6b6b; border-radius:50%; content:''; height:8px; left:0; position:absolute; top:0; width:8px; }
    @keyframes pulse { 0% { opacity:.8; transform:scale(1); } 100% { opacity:0; transform:scale(3.2); } }
    .section-label { color:var(--gold); font-size:13px; font-weight:700; letter-spacing:.13em; margin-bottom:11px; text-transform:uppercase; }
    .torn { background:var(--navy-dark); color:#fff; margin:0 12px; padding:24px; text-align:center; }
    .torn h2 { color:#fff; font-size:26px; margin:0; }
    .benefits-band { align-items:center; background:url('img/benefit-bg.png') no-repeat center; background-size:100% 100%; color:#fff; display:flex; justify-content:center; min-height:220px; padding:70px 30px; text-align:center; }
    .benefits-band h2 { color:#fff; font-family:'Space Grotesk',sans-serif; font-size:clamp(28px,4.5vw,44px); letter-spacing:-.03em; line-height:1.15; margin:0; max-width:820px; }
    .benefits { display:grid; gap:30px; grid-template-columns:repeat(3,1fr); margin:0 auto; max-width:1100px; }
    .benefit { text-align:center; }
    .benefit-art { background:var(--navy); height:180px; margin-bottom:14px; overflow:hidden; position:relative; }
    .benefit-art .detector { transform:translateX(-45%) rotate(-12deg) scale(.47); top:-24px; }
    .benefit h3 { color:var(--navy); font-family:'Space Grotesk',sans-serif; font-size:18px; margin:0 0 8px; }
    .benefit p { color:var(--muted); font-size:14px; line-height:1.5; }
    .spaced-cta { padding:10px 0 46px; text-align:center; }
    .accordions { margin:0 auto; max-width:700px; text-align:left; }
    details { border-bottom:1px solid #d9dce2; }
    summary { align-items:center; background:#7e8292; color:#fff; cursor:pointer; display:flex; font-size:15px; font-weight:700; justify-content:space-between; list-style:none; margin-top:12px; padding:14px 16px; }
    summary::-webkit-details-marker { display:none; }
    summary::after { content:'+'; font-size:20px; font-weight:400; }
    details[open] summary::after { content:'×'; }
    details p { color:var(--muted); font-size:14px; line-height:1.6; padding:0 16px 10px; }
    .final { background:var(--navy); color:#fff; padding:46px 34px; text-align:center; }
    .final h2 { color:#fff; font-size:32px; }
    .final p { color:#d7dce6; font-size:16px; margin:10px auto 24px; max-width:560px; }
    .lead-form { background:#fff; border-radius:12px; margin:24px auto 0; max-width:460px; padding:20px 20px 22px; text-align:left; }
    .lead-form label { color:var(--navy); display:block; font-size:14px; font-weight:700; margin:16px 0 0; }
    .lead-form fieldset { border:0; margin:12px 0 0; padding:0; }
    .lead-form legend { color:var(--navy); font-size:13px; font-weight:700; margin-bottom:4px; }
    .lead-form .hint { color:var(--muted); display:block; font-size:12px; font-weight:400; margin-top:3px; }
    .lead-form input[type=text], .lead-form input[type=tel], .lead-form input[type=email], .lead-form textarea { border:1px solid #cfd4dc; border-radius:8px; font:16px 'DM Sans',sans-serif; margin-top:7px; padding:13px 14px; width:100%; }
    .lead-form input:focus, .lead-form textarea:focus { outline:2px solid var(--gold-light); }
    .lead-form textarea { min-height:96px; resize:vertical; }
    .lead-form .opts { display:flex; gap:22px; margin-top:4px; }
    .lead-form .opts label { display:flex; align-items:center; gap:7px; font-size:14px; font-weight:600; margin:0; }
    .lead-form input[type=radio] { height:16px; width:16px; accent-color:var(--gold); }
    .form-intro { border-bottom:1px solid #e6e9ee; margin-bottom:16px; padding-bottom:14px; text-align:left; }
    .form-eyebrow { color:var(--muted); font-size:11px; font-weight:700; letter-spacing:.08em; margin:0 0 4px; text-transform:uppercase; }
    .form-title { color:var(--navy); font-size:20px; margin:0 0 3px; }
    .form-event { color:var(--navy); font-size:14px; font-weight:700; margin:0 0 5px; }
    .form-desc { color:#3d4652; font-size:13px; line-height:1.45; margin:0 0 3px; }
    .form-sub { color:var(--muted); font-size:12.5px; margin:0; }
    .form-step[hidden] { display:none; }
    .form-step { animation:stepIn .32s ease both; }
    @keyframes stepIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }
    .field-row { display:grid; gap:10px; grid-template-columns:1fr 1fr; }
    .field-row label { margin-top:0; }
    .lead-form .field-row label { font-size:13px; }
    .lead-form label { margin-top:11px; }
    .lead-form input[type=text], .lead-form input[type=tel], .lead-form input[type=email] { font-size:15px; margin-top:5px; padding:9px 11px; }
    .lead-form textarea { font-size:15px; margin-top:5px; min-height:70px; padding:9px 11px; }
    .phone-row { display:flex; gap:8px; margin-top:5px; }
    .cc-wrap { display:inline-flex; position:relative; }
    .cc-label { align-items:center; background:#fff; border:1px solid #cfd4dc; border-radius:8px; color:var(--navy); display:inline-flex; font:600 15px 'DM Sans',sans-serif; gap:7px; height:41px; padding:0 9px; white-space:nowrap; }
    .cc-label::after { border-left:4px solid transparent; border-right:4px solid transparent; border-top:5px solid var(--muted); content:''; margin-left:1px; }
    .cc-wrap:focus-within .cc-label { outline:2px solid var(--gold-light); }
    .cc-flag { border-radius:2px; box-shadow:0 0 0 1px rgba(0,0,0,.14); flex:none; height:auto; width:19px; }
    .cc-text { line-height:1; }
    .cc-wrap select { -webkit-appearance:none; appearance:none; background:transparent; border:0; cursor:pointer; height:100%; inset:0; opacity:0; position:absolute; width:100%; }
    .cc-wrap option { background:#fff; color:var(--navy); }
    .phone-row input { margin-top:0; }
    .form-nav { align-items:center; display:flex; gap:10px; margin-top:18px; }
    .form-nav .button { flex:1; font-size:15px; padding:12px 18px; }
    .btn-ghost { background:none; border:1px solid #cfd4dc; border-radius:8px; color:var(--navy); cursor:pointer; font:14px 'DM Sans',sans-serif; font-weight:700; padding:11px 16px; }
    .btn-ghost:hover { border-color:var(--navy); }
    .form-msg { border-radius:8px; display:none; font-size:13.5px; font-weight:600; margin:12px 0 0; padding:10px 12px; }
    .form-msg.on { display:block; }
    .form-msg.err { background:#fdeceb; color:#a4262c; }
    .lead-form .opt { color:var(--muted); font-size:11px; font-weight:600; letter-spacing:.04em; margin-left:4px; text-transform:uppercase; }
    .lead-form select { background:#fff url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23687083' d='M1 1.5 6 6.5l5-5'/%3E%3C/svg%3E") no-repeat right 12px center; border:1px solid #cfd4dc; border-radius:8px; color:var(--navy); font:15px 'DM Sans',sans-serif; height:41px; margin-top:5px; padding:0 32px 0 11px; width:100%; -webkit-appearance:none; appearance:none; }
    .lead-form select:focus { outline:2px solid var(--gold-light); }
    .form-error { background:#fbeeec; border-radius:8px; color:#7a2c25; font-size:14px; margin:0 auto 6px; max-width:520px; padding:11px 14px; }
    .thankyou { background:#fff; border-radius:12px; margin:28px auto 0; max-width:560px; padding:34px; }
    .thankyou h3 { color:var(--navy); font-family:'Space Grotesk',sans-serif; font-size:22px; margin:0 0 8px; }
    .thankyou p { color:var(--muted); font-size:15px; margin:0; }
    footer { background:#1f3564; color:#ffffff; font-size:12px; font-weight:300; line-height:1.3em; padding:35px 10px 25px; }
    .footer-inner { margin:0 auto; max-width:1170px; padding:0 5px; }
    .footer-top { align-items:center; display:flex; justify-content:space-between; min-height:29px; padding:0 10px 10px; }
.footer-brand { color:#ffffff; font-family:'Space Grotesk',sans-serif; font-size:19px; font-weight:700; letter-spacing:.01em; }
    .footer-logo { display:block; height:30px; width:auto; }
    .footer-menu-btn { background:none; border:0; color:#ffffff; cursor:pointer; display:none; padding:0; }
    .footer-nav { display:none; }
    .footer-nav.open { display:block; }
    .footer-nav ul { list-style:none; margin:0; padding:0; }
    .footer-nav a { color:#ffffff; display:block; font-size:16px; padding:8px 0; }
    .footer-legal { align-items:center; display:flex; flex-wrap:nowrap; justify-content:center; list-style:none; margin:0; padding:24px 0; text-align:center; }
    .footer-legal li { padding-right:15px; }
    .footer-legal a { color:#ffffff; font-size:16px; font-weight:400; }
    .disclaimer { color:#ffffff; font-size:16px; font-weight:300; line-height:1.3em; margin:0; padding:9px 0 10px; text-align:center; }
    .cookie-banner { background:#111a38; border-top:1px solid #d4a52c; bottom:0; color:#fff; left:0; padding:20px 24px; position:fixed; right:0; z-index:1000; }
    .cookie-banner.hidden { display:none; }
    .cookie-content { align-items:center; display:flex; gap:16px; justify-content:space-between; max-width:1170px; margin:0 auto; }
    .cookie-text { color:#d9deea; font-size:14px; line-height:1.4; }
    .cookie-text a { color:#f4ca5b; text-decoration:underline; }
    .cookie-buttons { display:flex; gap:10px; }
    .cookie-btn { border:0; border-radius:6px; cursor:pointer; font-size:13px; font-weight:700; padding:10px 20px; text-transform:uppercase; }
    .cookie-btn.accept { background:#d4a52c; color:#111a38; }
    .cookie-btn.accept:hover { background:#f4ca5b; }
    .cookie-btn.deny { background:#eef0f4; color:#2c3e6e; }
    .cookie-btn.deny:hover { background:#fff; }
    @media (max-width:640px) {
  .cookie-banner { padding:16px 14px; }
  .cookie-content { flex-direction:column; align-items:flex-start; gap:12px; }
  .cookie-buttons { width:100%; justify-content:space-between; }
  .cookie-btn { flex:1; text-align:center; }
  .topline { font-size:9px; padding:8px 12px; }
  header { padding:16px 16px; }
  .nav { gap:8px; }
  .nav-links { display:none; }
  .logo-chip { padding:5px 8px; }
  .header-logo { height:34px; }
  .nav-cta { font-size:11px; padding:10px 13px; }
  .hero { padding:22px 18px 32px; }
  .hero h1 { font-size:clamp(32px,9vw,44px); }
  .hero-sub { font-size:16px; }
  .hero-intro { font-size:17px; line-height:1.4; }
  .panel-kicker { font-size:10px; padding:7px 9px; }
  .mute-toggle { font-size:11px; padding:8px 11px; }
  .hero-copy { font-size:13px; }
  .button { padding:15px 22px; width:100%; }
  .section { padding:34px 18px; }
  .section h2 { font-size:26px; }
  .benefits-band { min-height:150px; padding:38px 18px; }
  .benefits-band h2 { font-size:24px; }
  .slides-frame { aspect-ratio:4/3; max-height:none; }
  .slides-cap { font-size:13px; padding:36px 14px 14px; }
  .benefits { gap:24px; grid-template-columns:1fr; }
  .benefit-art { height:210px; }
  .benefit-art .detector { transform:translateX(-45%) rotate(-12deg) scale(.59); top:-7px; }
  .spaced-cta { padding:6px 0 34px; }
  .final { padding:38px 18px; }
    .lead-form { padding:20px 18px 24px; }
    .field-row { grid-template-columns:1fr; }
    .form-title { font-size:22px; }
  .torn { margin:0; padding:20px 16px; }
  footer { padding-left:10px; padding-right:10px; }
  .footer-menu-btn { display:block; }
  .footer-legal { flex-wrap:wrap; padding:18px 0 10px; }
  .footer-legal a { font-size:12px; font-weight:300; }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="topline"><?= h($s['topline']) ?></div>
    <header><div class="nav"><a class="brand logo-chip" href="#top"><img class="header-logo" src="img/hpllogo.jpeg" alt="HPL Gold Detectors"></a><nav class="nav-links"><a href="#what-you-get"><?= h($s['nav_1']) ?></a><a href="#faq"><?= h($s['nav_2']) ?></a></nav><a class="nav-cta" href="#book"><?= h($s['nav_cta']) ?></a></div></header>
    <main id="top">
      <section class="hero">
        <span class="live-pill"><i></i><?= h($s['live_pill']) ?></span>
        <h1><?= h($s['hero_h1']) ?><span><?= h($s['hero_h1_span']) ?></span></h1>
        <p class="hero-sub"><?= h($s['hero_sub']) ?></p>
        <p class="hero-intro"><?= h($s['hero_intro']) ?></p>
        <div class="detector-panel"><span class="panel-kicker"><?= h($s['panel_kicker']) ?></span><button class="mute-toggle" id="soundBtn" type="button">Unmute</button><video id="promoVideo" autoplay muted loop playsinline preload="metadata" poster=""><?php $promoSrc = hpl_media_url($s['video_drive_id'] ?? ''); ?><?php if ($promoSrc !== '') { ?><source src="<?= h($promoSrc) ?>" type="video/mp4"><?php } ?><div class="video-fallback">Your browser can't play this video. <a href="#" style="text-decoration:underline">Open the promo on Google Drive</a>.</div></video></div>
        <p class="hero-copy"><?= h($s['hero_caption']) ?></p>
        <a class="button" href="#book"><?= h($s['cta_text']) ?></a>
      </section>

      <?php $proofItems = []; for ($i = 1; $i <= 5; $i++) { $src = hpl_media_url($s['proof_video_' . $i] ?? ''); if ($src === '') { continue; } $proofItems[] = ['src' => $src, 'caption' => (string)($s['proof_video_' . $i . '_caption'] ?? '')]; } ?><?php $layout = (string)($s['proof_layout'] ?? 'ring'); if (!in_array($layout, ['ring', 'strip', 'coverflow'], true)) { $layout = 'ring'; } ?><section class="section center <?= $layout === 'coverflow' ? 'proof-dark' : 'wash' ?> proof"><div class="section-label"><?= h($s['social_label']) ?></div><h2><?= h($s['social_heading']) ?></h2><?php if (empty($proofItems)): ?><div class="proof-empty">Customer videos will appear here once added from the admin panel.</div><?php elseif ($layout === 'strip'): ?><div class="proof-stage-wrap" id="proofWrap"><div class="proof-stage" id="proofStage"><?php foreach ($proofItems as $item): ?><figure class="proof-slide"><video muted loop playsinline preload="none" <?= hpl_poster_attr($item['src']) ?>data-proof-video><source src="<?= h($item['src']) ?>" type="<?= h(hpl_media_type($item['src'])) ?>"></video><?php if ($item['caption'] !== ''): ?><figcaption><?= h($item['caption']) ?></figcaption><?php endif; ?></figure><?php endforeach; ?></div><button class="proof-cue" id="proofCue" type="button" aria-label="Next video">&rsaquo;</button></div><p class="proof-hint"><?= h($s['proof_hint_strip']) ?></p><div class="proof-dots" id="proofDots"></div>
<?php elseif ($layout === 'coverflow'): ?>
<div class="ring-wrap proof-coverflow" id="ringWrap" style="--ring-shape:<?= h((string)max(0.2, min(4, (float)($s['proof_ring_shape'] ?? 0.5625)))) ?>;"><div class="cf-stage" id="ring"><?php foreach ($proofItems as $i => $item): ?><figure class="ring-item" data-ring-item role="button" tabindex="0" aria-label="Show story <?= (int)$i + 1 ?><?= $item['caption'] !== '' ? ': ' . h($item['caption']) : '' ?>"><video muted playsinline preload="none" <?= hpl_poster_attr($item['src']) ?>data-ring-video><source src="<?= h($item['src']) ?>" type="<?= h(hpl_media_type($item['src'])) ?>"></video><?php if ($item['caption'] !== ''): ?><figcaption><?= h($item['caption']) ?></figcaption><?php endif; ?></figure><?php endforeach; ?></div><button class="cf-arrow cf-prev" id="ringPrev" type="button" aria-label="Previous story"><span aria-hidden="true">&lsaquo;</span></button><button class="cf-arrow cf-next" id="ringNext" type="button" aria-label="Next story"><span aria-hidden="true">&rsaquo;</span></button><button class="ring-play" id="ringPlay" type="button" aria-label="Play this story"><span aria-hidden="true">&#9654;</span></button></div><p class="proof-hint"><?= h($s['proof_hint']) ?></p><div class="cf-dots" id="cfDots"></div><div class="ring-nav"><button type="button" id="ringSound" aria-label="Turn sound on" aria-pressed="false"><span aria-hidden="true">&#128266;</span></button><button type="button" id="ringToggle" aria-label="Pause videos"><span aria-hidden="true">&#10073;&#10073;</span></button></div><?php else: ?><div class="ring-wrap" id="ringWrap" style="--ring-r:<?= h((string)(max(0, (float)($s['proof_ring_r'] ?? 300)))) ?>px;--ring-size:<?= h((string)(max(80, (float)($s['proof_ring_size'] ?? 300)))) ?>px;--ring-shape:<?= h((string)max(0.2, min(4, (float)($s['proof_ring_shape'] ?? 0.5625)))) ?>"><div class="ring" id="ring"><?php foreach ($proofItems as $i => $item): ?><figure class="ring-item" data-ring-item role="button" tabindex="0" aria-label="Show story <?= (int)$i + 1 ?><?= $item['caption'] !== '' ? ': ' . h($item['caption']) : '' ?>"><video muted loop playsinline preload="none" <?= hpl_poster_attr($item['src']) ?>data-ring-video><source src="<?= h($item['src']) ?>" type="<?= h(hpl_media_type($item['src'])) ?>"></video><?php if ($item['caption'] !== ''): ?><figcaption><?= h($item['caption']) ?></figcaption><?php endif; ?></figure><?php endforeach; ?></div><button class="ring-play" id="ringPlay" type="button" aria-label="Play this story"><span aria-hidden="true">&#9654;</span></button></div><p class="proof-hint"><?= h($s['proof_hint']) ?></p><div class="ring-nav"><button type="button" id="ringSound" aria-label="Turn sound on" aria-pressed="false"><span aria-hidden="true">&#128266;</span></button><button type="button" id="ringToggle" aria-label="Pause videos"><span aria-hidden="true">&#10073;&#10073;</span></button><button type="button" id="ringPrev" aria-label="Previous story">&lsaquo;</button><button type="button" id="ringNext" aria-label="Next story">&rsaquo;</button></div><?php endif; ?><p class="proof-caption"><?= h($s['social_caption']) ?></p></section>

      <?php $slideItems = []; for ($i = 1; $i <= 6; $i++) { $f = 'slide-' . $i . '.jpg'; if (file_exists(__DIR__ . '/img/' . $f)) { $slideItems[] = ['file' => $f, 'caption' => (string)($s['slide_' . $i . '_caption'] ?? '')]; } } ?><section class="section center slides"><div class="section-label"><?= h($s['slides_label']) ?></div><h2><?= h($s['slides_heading']) ?></h2><?php if (empty($slideItems)): ?><div class="slides-empty">Field photos will appear here once uploaded from the admin panel.</div><?php else: ?><div class="slides-stage"><div class="slides-frame" id="slidesFrame"><?php foreach ($slideItems as $si => $item): ?>                  <img class="<?= $si === 0 ? 'on' : '' ?>" src="<?= h('img/' . $item['file']) ?>" alt="<?= h($item['caption'] !== '' ? $item['caption'] : 'Customer field photo') ?>" data-caption="<?= h($item['caption']) ?>" loading="<?= $si === 0 ? 'eager' : 'lazy' ?>"><?php endforeach; ?><p class="slides-cap" id="slidesCap"></p></div><div class="slides-dots" id="slidesDots"></div></div><?php endif; ?></section>

      <section id="what-you-get"><div class="benefits-band"><h2><?= h($s['benefits_label']) ?></h2></div><div class="section"><div class="benefits"><article class="benefit"><div class="benefit-art"><?= art_block('benefit-1.jpg') ?></div><h3><?= h($s['b1_title']) ?></h3><p><?= h($s['b1_desc']) ?></p></article><article class="benefit"><div class="benefit-art"><?= art_block('benefit-2.jpg') ?></div><h3><?= h($s['b2_title']) ?></h3><p><?= h($s['b2_desc']) ?></p></article><article class="benefit"><div class="benefit-art"><?= art_block('benefit-3.jpg') ?></div><h3><?= h($s['b3_title']) ?></h3><p><?= h($s['b3_desc']) ?></p></article></div></div></section>

      <div class="spaced-cta"><a class="button" href="#book"><?= h($s['cta_text']) ?></a></div>

      <section class="section" id="faq"><div class="accordions">
<?php for ($i = 1; $i <= 4; $i++) { ?>
<details<?php if ($i === 1) { ?> open<?php } ?>><summary><?= h($s['faq' . $i . '_q']) ?></summary><?php foreach (preg_split('/\r\n|\r|\n/', $s['faq' . $i . '_a']) as $paragraph) { if (trim($paragraph) !== '') { ?><p><?= h($paragraph) ?></p><?php } } ?></details>
<?php } ?>
      </div></section>

      <div class="spaced-cta"><a class="button" href="#book"><?= h($s['cta_text']) ?></a></div>
      <section class="final" id="book">
        <h2><?= h($s['final_h']) ?></h2>
        <p><?= h($s['final_sub']) ?></p>
<?php if ($done === '1'): ?>
        <div class="thankyou"><h3>You're on the list!</h3><p>Thanks, <?= h($leadName) ?>. We'll reach out within one business day to schedule your gold-detection assessment.</p></div>
<?php else: ?>
<?php if ($leadError !== ''): ?><div class="form-error"><?= h($leadError) ?></div><?php endif; ?>
        <form class="lead-form" action="#book" method="post" novalidate id="leadForm">
          <input type="hidden" name="lead_submit" value="1">
          <div class="form-intro">
            <p class="form-eyebrow"><?= h($s['form_eyebrow']) ?></p>
            <p class="form-title"><?= h($s['form_title']) ?></p>
            <p class="form-event"><?= h($s['form_event']) ?></p>
            <p class="form-desc"><?= h($s['form_desc']) ?></p>
            <p class="form-sub"><?= h($s['form_sub']) ?></p>
          </div>
          <p class="form-msg err" id="formMsg"></p>
          <div class="form-step" data-step="1">
            <div class="field-row">
              <label>First name *
                <input type="text" name="lead_first" id="leadFirst" value="<?= h($leadFirst) ?>" autocomplete="given-name">
              </label>
              <label>Last name *
                <input type="text" name="lead_last" id="leadLast" value="<?= h($leadLast) ?>" autocomplete="family-name">
              </label>
            </div>
            <label>Phone number *
              <span class="phone-row">
                <span class="cc-wrap">
                  <span class="cc-label"><img class="cc-flag" id="ccFlag" src="img/flags/<?= h(cc_flag((string)$leadCc)) ?>.png" alt=""><span class="cc-text" id="ccText"><?= h(cc_label((string)$leadCc)) ?></span></span>
                  <select name="lead_cc" id="leadCc" aria-label="Country code">
<?php foreach (hpl_countries() as $ccode => $cinfo): ?>
                    <option value="<?= h((string)$ccode) ?>" data-flag="<?= h($cinfo['flag']) ?>" title="<?= h($cinfo['name']) ?>"<?= (string)$leadCc === (string)$ccode ? ' selected' : '' ?>><?= h($cinfo['label']) ?></option>
<?php endforeach; ?>
                  </select>
                </span>
                <input type="tel" name="lead_phone" id="leadPhone" value="<?= h($leadPhone) ?>" placeholder="976 652 858" autocomplete="tel">
              </span>
            </label>
            <label>Email address <span class="opt">optional</span>
              <input type="email" name="lead_email" id="leadEmail" value="<?= h($leadEmail) ?>" placeholder="you@example.com" autocomplete="email">
            </label>
            <label><?= h($s['form_q_terrain']) ?> *
              <select name="lead_terrain" id="leadTerrain">
                <option value="">Select</option>
<?php foreach (hpl_options('terrain') as $opt): ?>
                <option value="<?= h($opt) ?>"<?= $leadTerrain === $opt ? ' selected' : '' ?>><?= h($opt) ?></option>
<?php endforeach; ?>
              </select>
            </label>
            <label><?= h($s['form_q_target']) ?> *
              <select name="lead_target" id="leadTarget">
                <option value="">Select</option>
<?php foreach (hpl_options('target') as $opt): ?>
                <option value="<?= h($opt) ?>"<?= $leadTarget === $opt ? ' selected' : '' ?>><?= h($opt) ?></option>
<?php endforeach; ?>
              </select>
            </label>
            <label><?= h($s['form_q_timing']) ?>
              <select name="lead_timing" id="leadTiming">
                <option value="">Select</option>
<?php foreach (hpl_options('timing') as $opt): ?>
                <option value="<?= h($opt) ?>"<?= $leadTiming === $opt ? ' selected' : '' ?>><?= h($opt) ?></option>
<?php endforeach; ?>
              </select>
            </label>
            <div class="form-nav"><button class="button" type="button" id="leadNext">Continue</button></div>
          </div>
          <div class="form-step" data-step="2" hidden>
            <label>What do you know about gold detectors?<span class="hint">Tell us where you are right now</span>
              <textarea name="lead_knowledge" rows="3" placeholder="e.g. I've watched YouTube videos but never used one"><?= h($leadKnowledge) ?></textarea>
            </label>
            <fieldset>
              <legend>Would you want to learn?</legend>
              <div class="opts">
                <label><input type="radio" name="lead_learn" value="Yes" required> Yes</label>
                <label><input type="radio" name="lead_learn" value="No"> No</label>
              </div>
            </fieldset>
            <div class="form-nav">
              <button class="btn-ghost" type="button" id="leadBack">Back</button>
              <button class="button" type="submit"><?= h($s['form_submit']) ?></button>
            </div>
          </div>
        </form>
<?php endif; ?>
      </section>
    </main>
    <footer role="contentinfo" aria-label="Site Footer"><div class="footer-inner">
      <div class="footer-top">
        <a class="footer-brand logo-chip" href="#top"><img class="footer-logo" src="img/hpllogo.jpeg" alt="HPL Gold Detectors"></a>
        <button class="footer-menu-btn" type="button" id="footerMenuBtn" aria-label="Open Footer Menu" aria-expanded="false"><svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg></button>
      </div>
      <nav class="footer-nav" id="footerNav" aria-label="Footer Navigation"><ul>
        <li><a href="#what-you-get"><?= h($s['nav_1']) ?></a></li>
        <li><a href="#faq"><?= h($s['nav_2']) ?></a></li>
        <li><a href="#book"><?= h($s['nav_cta']) ?></a></li>
      </ul></nav>
      <ul class="footer-legal" role="navigation" aria-label="Legal Links">
<?php for ($i = 1; $i <= 4; $i++) { ?>
        <li><a href="<?= h($s['footer_' . $i . '_url']) ?>"><?= h($s['footer_' . $i]) ?></a></li>
<?php } ?>
      </ul>
      <p class="disclaimer"><?= h($s['disclaimer']) ?></p>
    </div></footer>

    <div class="cookie-banner hidden" id="cookieBanner">
      <div class="cookie-content">
        <div class="cookie-text">We use cookies to improve your experience and analyze site traffic. <a href="#faq">Learn more</a></div>
        <div class="cookie-buttons">
          <button class="cookie-btn accept" id="acceptCookies">Accept</button>
          <button class="cookie-btn deny" id="denyCookies">Deny</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    (function () {
      var sessionId = 'sess_' + Math.random().toString(36).substr(2, 16) + Date.now().toString(36);
      var cookiesAccepted = localStorage.getItem('hpl_cookies_accepted');

      function trackEvent(eventType, eventData) {
        if (cookiesAccepted !== 'true') return;
        var data = {
          event_type: eventType,
          event_data: eventData || {},
          session_id: sessionId
        };
        fetch('admin/analytics.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        }).catch(function() {});
      }

      if (cookiesAccepted === 'true') {
        trackEvent('page_view', { page: 'landing' });
      }

      var cookieBanner = document.getElementById('cookieBanner');
      var acceptBtn = document.getElementById('acceptCookies');
      var denyBtn = document.getElementById('denyCookies');

      if (!cookiesAccepted && cookieBanner) {
        cookieBanner.classList.remove('hidden');
      }

      if (acceptBtn) {
        acceptBtn.addEventListener('click', function() {
          localStorage.setItem('hpl_cookies_accepted', 'true');
          cookiesAccepted = 'true';
          if (cookieBanner) cookieBanner.classList.add('hidden');
          trackEvent('page_view', { page: 'landing' });
        });
      }

      if (denyBtn) {
        denyBtn.addEventListener('click', function() {
          localStorage.setItem('hpl_cookies_accepted', 'false');
          if (cookieBanner) cookieBanner.classList.add('hidden');
        });
      }

      var buttons = document.querySelectorAll('.button');
      for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function() {
          trackEvent('cta_click', { text: this.textContent.trim(), href: this.getAttribute('href') });
        });
      }

      var promoVideo = document.getElementById('promoVideo');
      if (promoVideo) {
        promoVideo.addEventListener('play', function() {
          trackEvent('video_play', { video: 'promo' });
        });
      }

      var scrollTracked = { 25: false, 50: false, 75: false, 100: false };
      window.addEventListener('scroll', function() {
        var scrollPercent = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
        if (scrollPercent >= 25 && !scrollTracked[25]) {
          scrollTracked[25] = true;
          trackEvent('scroll', { depth: 25 });
        }
        if (scrollPercent >= 50 && !scrollTracked[50]) {
          scrollTracked[50] = true;
          trackEvent('scroll', { depth: 50 });
        }
        if (scrollPercent >= 75 && !scrollTracked[75]) {
          scrollTracked[75] = true;
          trackEvent('scroll', { depth: 75 });
        }
        if (scrollPercent >= 100 && !scrollTracked[100]) {
          scrollTracked[100] = true;
          trackEvent('scroll', { depth: 100 });
        }
      });
    })();
    (function () {
      var v = document.getElementById('promoVideo');
      var b = document.getElementById('soundBtn');
      if (!v || !b) return;
      v.volume = 0.7;
      v.play().catch(function () {});
      b.addEventListener('click', function () {
        if (v.muted) { v.muted = false; b.textContent = 'Mute'; }
        else { v.muted = true; b.textContent = 'Unmute'; }
      });
    })();
    (function () {
      var form = document.getElementById('leadForm');
      if (!form) return;
      var steps = form.querySelectorAll('.form-step');
      var next = document.getElementById('leadNext');
      var back = document.getElementById('leadBack');
      var msg = document.getElementById('formMsg');
      var f = document.getElementById('leadFirst');
      var l = document.getElementById('leadLast');
      var p = document.getElementById('leadPhone');
      var cc = document.getElementById('leadCc');
      var flag = document.getElementById('ccFlag');
      var ccText = document.getElementById('ccText');

      function paintCc() {
        var o = cc && cc.options ? cc.options[cc.selectedIndex] : null;
        if (!o) return;
        var flagIso = o.getAttribute('data-flag');
        if (flag && flagIso) {
          flag.onerror = function () {
            flag.onerror = null;
            flag.src = 'https://flagcdn.com/w20/' + flagIso + '.png';
          };
          flag.src = 'img/flags/' + flagIso + '.png';
        }
        if (ccText) ccText.textContent = '+' + o.value;
        if (cc) cc.title = o.textContent.replace(/^\+\d+\s*/, '') || 'Country code';
      }

      if (cc) {
        cc.addEventListener('change', function () { userPicked = true; paintCc(); });
        paintCc();
      }

      var userPicked = false;
      cc.addEventListener('pointerdown', function () { userPicked = true; });

      (function detectCountry() {
        if (userPicked) return;
        var providers = ['https://ipwho.is/', 'https://ipapi.co/json/'];
        var attempt = function (i) {
          if (i >= providers.length || userPicked) return;
          var ctrl = new AbortController();
          var timer = setTimeout(function () { ctrl.abort(); }, 2600);
          fetch(providers[i], { mode: 'cors', signal: ctrl.signal })
            .then(function (r) { return r.json(); })
            .then(function (d) {
              clearTimeout(timer);
              if (userPicked) return;
              var code = d && (d.country_calling_code || d.calling_code) ? String(d.country_calling_code || d.calling_code).replace(/\D/g, '') : '';
              if (!code) return attempt(i + 1);
              var rawCountry = d && d.country ? String(d.country) : '';
              var iso = (d && d.country_code ? String(d.country_code) : (rawCountry.length === 2 ? rawCountry : '')).toLowerCase();
              var cname = d && d.country_name ? String(d.country_name) : (rawCountry.length > 2 ? rawCountry : '');
              var match = null;
              for (var k = 0; k < cc.options.length; k++) {
                if (cc.options[k].value === code) { match = cc.options[k]; break; }
              }
              if (!match) {
                match = document.createElement('option');
                match.value = code;
                match.textContent = '+' + code + (cname ? ' ' + cname : '');
                match.setAttribute('data-flag', iso || 'un');
                cc.insertBefore(match, cc.firstChild);
              } else if (cc.firstChild !== match) {
                cc.insertBefore(match, cc.firstChild);
              }
              cc.value = code;
              paintCc();
              trackEvent('country_detected', { cc: code });
            })
            .catch(function () { clearTimeout(timer); attempt(i + 1); });
        };
        if (window.fetch) attempt(0);
      })();

      function show(n) {
        for (var i = 0; i < steps.length; i++) {
          if (i === n) { steps[i].removeAttribute('hidden'); }
          else { steps[i].setAttribute('hidden', ''); }
        }
        if (msg) msg.classList.remove('on');
        var first = steps[n].querySelector('input, textarea, select');
        if (first) first.focus();
        if (n === 0 && window.scrollY > 0) form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      function fail(text) {
        if (!msg) return;
        msg.textContent = text;
        msg.classList.add('on');
        trackEvent('form_error', { field: text });
      }

      next.addEventListener('click', function () {
        if (!f.value.trim()) return fail('Please enter your first name.');
        if (!l.value.trim()) return fail('Please enter your last name.');
        if (p.value.replace(/\D/g, '').length < 6) return fail('Please enter a valid phone number.');
        if (!document.getElementById('leadTerrain').value) return fail('Please choose where you will be searching.');
        if (!document.getElementById('leadTarget').value) return fail('Please tell us what you are hoping to find.');
        show(1);
      });

      back.addEventListener('click', function () { show(0); });

      form.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && e.target.tagName !== 'TEXTAREA') {
          e.preventDefault();
          if (!e.target.closest('.form-step').hasAttribute('hidden')) next.click();
        }
      });

      form.addEventListener('submit', function () {
        trackEvent('form_submit', { form: 'lead' });
      });
    })();
    (function () {
      var t = document.getElementById('footerMenuBtn');
      var n = document.getElementById('footerNav');
      if (!t || !n) return;
      t.addEventListener('click', function () {
        var open = n.classList.toggle('open');
        t.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    })();
    (function () {
      var ring = document.getElementById('ring');
      if (!ring) return;
      var items = Array.prototype.slice.call(ring.querySelectorAll('[data-ring-item]'));
      if (!items.length) return;
      var wrap = document.getElementById('ringWrap');
      // which layout is live, needed before any of the setup below runs
      var cf = !!(wrap && wrap.classList.contains('proof-coverflow'));
      var playBtn = document.getElementById('ringPlay');
      var toggleBtn = document.getElementById('ringToggle');
      var soundBtn = document.getElementById('ringSound');
      var prevBtn = document.getElementById('ringPrev');
      var nextBtn = document.getElementById('ringNext');
      var n = items.length;
      var idx = 0;
      var dotsWrap = document.getElementById('cfDots');
      var dots = [];
      if (cf && dotsWrap) {
        items.forEach(function (el, i) {
          var d = document.createElement('button');
          d.type = 'button';
          d.setAttribute('aria-label', 'Go to story ' + (i + 1));
          d.addEventListener('click', function () { show(i); });
          dotsWrap.appendChild(d);
          dots.push(d);
        });
      }

      function syncDots() {
        for (var di = 0; di < dots.length; di++) dots[di].classList.toggle('on', di === idx);
      }

      var R = 300, size = 300;

      function readVars() {
        var cs = getComputedStyle(ring);
        R = parseFloat(cs.getPropertyValue('--ring-r')) || 300;
        size = parseFloat(cs.getPropertyValue('--ring-size')) || 300;
      }

      function frontVideo() { return items[idx].querySelector('video'); }

      function layout(animate) {
        readVars();
        if (cf) {
          // Reference values, stepped by distance from the centre. The centre card
          // sits at the origin; each step outward moves back in Z, turns inwards,
          // shrinks, dims and blurs a little more.
          var zs = [0, -80, -150];
          var scs = [1, 0.9, 0.8];
          var rys = [0, 8, 14];
          var ops = [1, 0.8, 0.55];
          var blurs = [0, 1, 2];
          var brights = [1, 0.65, 0.45];

          // The reference leaves clear space between the cards instead of letting them
          // overlap, so each step outward is worked out from the width of the card
          // beside it plus a gap. Perspective shortens a card the further back it sits,
          // so the offset is divided by the same factor to land the edge where it
          // belongs. Whatever runs past the stage is clipped by the stage.
          var cs = getComputedStyle(ring);
          var gap = parseFloat(cs.getPropertyValue('--cf-gap')) || 30;
          var per = parseFloat(cs.perspective) || 1200;
          // Taken from the laid out card rather than the custom property, which holds
          // an unresolved min() on small screens and would not parse to a number.
          var csw = items[idx].offsetWidth || 260;

          var xs = [0, 0, 0];
          var edge = csw / 2;
          for (var a = 1; a < 3; a++) {
            var f = per / (per - zs[a]);
            var hw = (csw * scs[a] / 2) * f;
            xs[a] = (edge + gap + hw) / f;
            edge = xs[a] * f + hw;
          }

          items.forEach(function (el, i) {
            var off = i - idx;
            if (off > n / 2) off -= n;
            if (off < -n / 2) off += n;
            var a = Math.min(Math.abs(off), 2);
            var sgn = off < 0 ? -1 : 1;
            if (!animate) el.style.transition = 'none';
            var x = a === 0 ? 0 : xs[a] * sgn;
            el.style.transform = 'translate(-50%,-50%) translate3d(' + x.toFixed(1) + 'px,0,' + zs[a].toFixed(1) + 'px) rotateY(' + (rys[a] * sgn).toFixed(1) + 'deg) scale(' + scs[a].toFixed(3) + ')';
            el.style.opacity = String(ops[a]);
            el.style.setProperty('--cf-blur', blurs[a] + 'px');
            el.style.setProperty('--cf-bright', String(brights[a]));
            el.style.zIndex = String(100 - a);
            el.classList.toggle('front', a === 0);
            if (!animate) { void el.offsetWidth; el.style.transition = ''; }
          });
          return;
        }
        items.forEach(function (el, i) {
          var off = i - idx;
          if (off > n / 2) off -= n;
          if (off < -n / 2) off += n;
          // one player faces the viewer, its neighbours sit edge-on around the cylinder
          var ang = off * 90;
          ang = ((ang + 180) % 360 + 360) % 360 - 180;
          var isFront = off === 0;
          if (!animate) el.style.transition = 'none';
          // rotateY spins the player around the ring so it hands over to the next one
          el.style.transform = 'rotateY(' + ang.toFixed(2) + 'deg) translateZ(' + R.toFixed(1) + 'px) translate(-50%,-50%)';
          el.style.opacity = Math.abs(ang) >= 88 ? '0' : '1';
          el.style.zIndex = String(100 - Math.abs(ang));
          el.classList.toggle('front', isFront);
          if (!animate) { void el.offsetWidth; el.style.transition = ''; }
        });
      }

      function syncBtn() {
        syncDots();
        var v = frontVideo();
        var playing = v && !v.paused;
        if (toggleBtn) {
          toggleBtn.innerHTML = playing ? '<span aria-hidden="true">&#10073;&#10073;</span>' : '<span aria-hidden="true">&#9654;</span>';
          toggleBtn.setAttribute('aria-label', playing ? 'Pause videos' : 'Play videos');
        }
        if (playBtn) playBtn.style.display = playing ? 'none' : '';
      }

      // Sound stays off until the visitor asks for it, but the choice then sticks
      // across every handover instead of being re-muted on each new story.
      var soundOn = false;
      try { soundOn = localStorage.getItem('hpl_story_sound') === '1'; } catch (e) {}

      function applySound() {
        items.forEach(function (el) {
          var v = el.querySelector('video');
          if (v) { v.muted = !soundOn; v.volume = 0.8; }
        });
        if (soundBtn) {
          soundBtn.innerHTML = soundOn
            ? '<span aria-hidden="true">&#128266;</span>'
            : '<span aria-hidden="true">&#128263;</span>';
          soundBtn.setAttribute('aria-label', soundOn ? 'Turn sound off' : 'Turn sound on');
          soundBtn.setAttribute('aria-pressed', soundOn ? 'true' : 'false');
        }
      }

      function toggleSound() {
        soundOn = !soundOn;
        try { localStorage.setItem('hpl_story_sound', soundOn ? '1' : '0'); } catch (e) {}
        applySound();
        var fv = frontVideo();
        if (soundOn && fv && fv.paused) playFront();
      }

      function playFront() {
        var v = frontVideo();
        if (!v) return;
        v.muted = !soundOn;
        var pr = v.play();
        if (pr && pr.catch) pr.catch(function () {});
        syncBtn();
      }

      function pauseAll() {
        items.forEach(function (el) {
          var v = el.querySelector('video');
          if (v && !v.paused) v.pause();
        });
        syncBtn();
      }

      function show(i, autoplay) {
        if (i === idx) return;
        var old = items[idx].querySelector('video');
        if (old) { old.pause(); old.currentTime = 0; }
        idx = ((i % n) + n) % n;
        layout(true);
        syncBtn();
        if (autoplay !== false) playFront();
        if (wrap) wrap.classList.add('moved');
      }

      function next() { show(idx + 1, true); }
      function prev() { show(idx - 1, true); }

      items.forEach(function (el, i) {
        el.addEventListener('click', function () {
          if (i === idx) { togglePlay(); } else { show(i, true); }
        });
        el.addEventListener('keydown', function (e) {
          if (e.key !== 'Enter' && e.key !== ' ') return;
          e.preventDefault();
          if (i === idx) { togglePlay(); } else { show(i, true); }
        });
        var v = el.querySelector('video');
        if (v) v.addEventListener('play', syncBtn);
        if (v) v.addEventListener('pause', syncBtn);
        // coverflow only: once a story has played out, move to the next one
        if (cf && v) v.addEventListener('ended', function () {
          if (i === idx) show(i + 1, true);
        });
        if (v) v.addEventListener('playing', function () {
          var nx = items[(i + 1) % n].querySelector('video');
          if (nx && nx.preload !== 'auto') { nx.preload = 'auto'; nx.load(); }
        });
      });

      function togglePlay() {
        var v = frontVideo();
        if (!v) return;
        if (v.paused) { playFront(); } else { v.pause(); syncBtn(); }
      }

      if (soundBtn) soundBtn.addEventListener('click', toggleSound);
      applySound();
      if (playBtn) playBtn.addEventListener('click', playFront);
      if (toggleBtn) toggleBtn.addEventListener('click', togglePlay);
      if (prevBtn) prevBtn.addEventListener('click', prev);
      if (nextBtn) nextBtn.addEventListener('click', next);

      var tx = 0, ty = 0;
      ring.addEventListener('touchstart', function (e) {
        tx = e.changedTouches[0].clientX; ty = e.changedTouches[0].clientY;
      }, { passive: true });
      ring.addEventListener('touchend', function (e) {
        var dx = e.changedTouches[0].clientX - tx;
        var dy = e.changedTouches[0].clientY - ty;
        if (Math.abs(dx) < 45 || Math.abs(dx) < Math.abs(dy)) return;
        if (dx < 0) next(); else prev();
      }, { passive: true });

      var wlock = false;
      window.addEventListener('wheel', function (e) {
        if (wlock || Math.abs(e.deltaX) < 10) return;
        var r = ring.getBoundingClientRect();
        if (r.bottom < 80 || r.top > window.innerHeight - 80) return;
        e.preventDefault();
        wlock = true;
        window.setTimeout(function () { wlock = false; }, 650);
        if (e.deltaX > 0) next(); else prev();
      }, { passive: false });

      document.addEventListener('keydown', function (e) {
        var r = ring.getBoundingClientRect();
        if (r.bottom < 0 || r.top > window.innerHeight) return;
        if (e.key === 'ArrowRight') next();
        if (e.key === 'ArrowLeft') prev();
      });

      window.addEventListener('resize', function () { layout(false); });

      if (cf) {
        // Hold the story still while it is being watched, and never advance
        // a video the visitor cannot see.
        var resting = false;
        wrap.addEventListener('mouseenter', function () { resting = true; pauseAll(); });
        wrap.addEventListener('mouseleave', function () { resting = false; if (!document.hidden) playFront(); });
        document.addEventListener('visibilitychange', function () {
          if (document.hidden) pauseAll();
          else if (!resting) playFront();
        });
      }

      layout(false);
      if (n === 1) {
        if (wrap) wrap.classList.add('moved');
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
      }
      syncBtn();
    })();
    (function () {
      window.__hplInView = true;
      var sec = document.querySelector('.proof');
      if (!sec || !('IntersectionObserver' in window)) { return; }
      var slow = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      new IntersectionObserver(function (entries) {
        var vis = entries[0].isIntersecting;
        window.__hplInView = vis;
        if (slow) { return; }
        if (!vis) {
          sec.querySelectorAll('video').forEach(function (v) { if (v && !v.paused) { v.pause(); } });
          return;
        }
        var front = sec.querySelector('.ring-item.front video') || sec.querySelector('.proof-slide.on video');
        if (front && front.paused) { front.play().catch(function () {}); }
      }, { rootMargin: '300px 0px' }).observe(sec);
    })();
    (function () {
      var stage = document.getElementById('proofStage');
      if (!stage) return;
      var slides = Array.prototype.slice.call(stage.querySelectorAll('.proof-slide'));
      if (!slides.length) return;
      var dotsWrap = document.getElementById('proofDots');
      var wrap = document.getElementById('proofWrap');
      var cue = document.getElementById('proofCue');
      var idx = 0;

      var dots = slides.map(function (s, i) {
        var d = document.createElement('button');
        d.type = 'button';
        d.setAttribute('aria-label', 'Go to video ' + (i + 1));
        d.addEventListener('click', function () { show(i); });
        if (dotsWrap) dotsWrap.appendChild(d);
        return d;
      });

      function show(i) {
        if (i === idx) return;
        var prev = slides[idx];
        prev.classList.remove('on');
        var pv = prev.querySelector('video');
        if (pv) { pv.pause(); pv.currentTime = 0; }
        idx = i;
        var cur = slides[idx];
        cur.classList.add('on');
        var cv = cur.querySelector('video');
        if (cv) { cv.play().catch(function () {}); }
        dots.forEach(function (d, n) { d.classList.toggle('on', n === idx); });
        if (wrap) wrap.classList.add('moved');
      }

      function next() { show((idx + 1) % slides.length); }
      function prev() { show((idx - 1 + slides.length) % slides.length); }

      slides[0].classList.add('on');
      dots[0].classList.add('on');
      var first = slides[0].querySelector('video');
      if (first && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        first.play().catch(function () {});
      }

      if (cue) cue.addEventListener('click', next);

      var lock = false;
      window.addEventListener('wheel', function (e) {
        if (lock || Math.abs(e.deltaY) < 8) return;
        var r = stage.getBoundingClientRect();
        var visible = r.bottom > 80 && r.top < window.innerHeight - 80;
        if (!visible) return;
        var down = e.deltaY > 0;
        if (down && idx === slides.length - 1) return;
        if (!down && idx === 0) return;
        e.preventDefault();
        lock = true;
        window.setTimeout(function () { lock = false; }, 600);
        if (down) next(); else prev();
      }, { passive: false });

      var tx = 0, ty = 0;
      stage.addEventListener('touchstart', function (e) {
        tx = e.changedTouches[0].clientX; ty = e.changedTouches[0].clientY;
      }, { passive: true });
      stage.addEventListener('touchend', function (e) {
        var dx = e.changedTouches[0].clientX - tx;
        var dy = e.changedTouches[0].clientY - ty;
        if (Math.abs(dx) < 45 || Math.abs(dx) < Math.abs(dy)) return;
        if (dx < 0) next(); else prev();
      }, { passive: true });

      document.addEventListener('keydown', function (e) {
        var r = stage.getBoundingClientRect();
        if (r.bottom < 0 || r.top > window.innerHeight) return;
        if (e.key === 'ArrowRight') next();
        if (e.key === 'ArrowLeft') prev();
      });
    })();
    (function () {
      var frame = document.getElementById('slidesFrame');
      if (!frame) return;
      var imgs = Array.prototype.slice.call(frame.querySelectorAll('img'));
      if (imgs.length < 2) { if (imgs.length === 1) imgs[0].classList.add('on'); return; }
      var cap = document.getElementById('slidesCap');
      var dotsWrap = document.getElementById('slidesDots');
      var i = 0;
      var timer = null;

      var dots = imgs.map(function (img, n) {
        var d = document.createElement('button');
        d.type = 'button';
        d.setAttribute('aria-label', 'Show photo ' + (n + 1));
        d.addEventListener('click', function () { show(n); restart(); });
        if (dotsWrap) dotsWrap.appendChild(d);
        return d;
      });

      function show(n) {
        imgs[i].classList.remove('on');
        i = n;
        imgs[i].classList.add('on');
        dots.forEach(function (d, k) { d.classList.toggle('on', k === i); });
        if (cap) {
          var text = imgs[i].getAttribute('data-caption') || '';
          cap.innerHTML = text ? '<span>Field result</span>' + text : '';
          cap.classList.toggle('on', !!text);
        }
      }

      function next() { show((i + 1) % imgs.length); }
      function prev() { show((i - 1 + imgs.length) % imgs.length); }

      function start() { timer = window.setInterval(next, 4200); }
      function stop() { if (timer) { window.clearInterval(timer); timer = null; } }
      function restart() { stop(); start(); }

      show(0);
      start();

      var stage = frame.parentNode;
      stage.addEventListener('mouseenter', stop);
      stage.addEventListener('mouseleave', start);
      stage.addEventListener('click', function (e) { next(); restart(); });
      stage.addEventListener('touchstart', function () { stop(); }, { passive: true });

      document.addEventListener('visibilitychange', function () {
        if (document.hidden) stop(); else start();
      });
    })();
  </script>
</body>
</html>