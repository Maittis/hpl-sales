<?php
require_once __DIR__ . '/config.php';
$s = hpl_settings();

$leadError = '';
$leadName = $_POST['lead_name'] ?? '';
$leadPhone = $_POST['lead_phone'] ?? '';
$leadKnowledge = $_POST['lead_knowledge'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lead_submit'])) {
    $connection = db();
    $name = trim($_POST['lead_name'] ?? '');
    $phone = trim($_POST['lead_phone'] ?? '');
    $knowledge = trim($_POST['lead_knowledge'] ?? '');
    $learn = ($_POST['lead_learn'] ?? '') === 'Yes' ? 'Yes' : 'No';
    if (!$connection) {
        $leadError = 'Could not reach the server database. Please try again later.';
    } elseif ($name === '' || $phone === '') {
        $leadError = 'Please fill in your name and phone number.';
    } else {
        try {
            $stmt = $connection->prepare('INSERT INTO leads (name, phone, knowledge, wants_to_learn) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $name, $phone, $knowledge, $learn);
            $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            $leadError = 'Could not save your details right now. Please try again in a moment.';
        }
        if ($leadError === '') {
            header('Location: index.php?done=1#book');
            exit;
        }
    }
}
$done = isset($_GET['done']) ? (string)$_GET['done'] : '';

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
    .offer-panel { margin-bottom:34px; }
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
    .lead-form { background:#fff; border-radius:12px; margin:28px auto 0; max-width:560px; padding:26px 26px 30px; text-align:left; }
    .lead-form label { color:var(--navy); display:block; font-size:14px; font-weight:700; margin:16px 0 0; }
    .lead-form fieldset { border:0; margin:16px 0 0; padding:0; }
    .lead-form legend { color:var(--navy); font-size:14px; font-weight:700; margin-bottom:8px; }
    .lead-form .hint { color:var(--muted); display:block; font-size:12px; font-weight:400; margin-top:3px; }
    .lead-form input[type=text], .lead-form input[type=tel], .lead-form textarea { border:1px solid #cfd4dc; border-radius:8px; font:16px 'DM Sans',sans-serif; margin-top:7px; padding:13px 14px; width:100%; }
    .lead-form input:focus, .lead-form textarea:focus { outline:2px solid var(--gold-light); }
    .lead-form textarea { min-height:96px; resize:vertical; }
    .lead-form .opts { display:flex; gap:26px; margin-top:6px; }
    .lead-form .opts label { display:flex; align-items:center; gap:8px; font-size:15px; font-weight:600; margin:0; }
    .lead-form input[type=radio] { height:18px; width:18px; accent-color:var(--gold); }
    .lead-form .submit-row { margin:26px 0 0; text-align:center; }
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
  .proof-gallery { gap:22px; grid-template-columns:1fr; max-width:420px; }
  .benefits { gap:24px; grid-template-columns:1fr; }
  .benefit-art { height:210px; }
  .benefit-art .detector { transform:translateX(-45%) rotate(-12deg) scale(.59); top:-7px; }
  .spaced-cta { padding:6px 0 34px; }
  .offer-panel { margin-bottom:24px; }
  .final { padding:38px 18px; }
  .lead-form { padding:20px 18px 24px; }
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
        <div class="detector-panel"><span class="panel-kicker"><?= h($s['panel_kicker']) ?></span><button class="mute-toggle" id="soundBtn" type="button">Unmute</button><video id="promoVideo" autoplay muted loop playsinline preload="metadata" poster=""><source src="https://drive.usercontent.google.com/download?id=<?= h($s['video_drive_id']) ?>&amp;export=download&amp;confirm=t" type="video/mp4"><div class="video-fallback">Your browser can't play this video. <a href="#" style="text-decoration:underline">Open the promo on Google Drive</a>.</div></video></div>
        <p class="hero-copy"><?= h($s['hero_caption']) ?></p>
        <a class="button" href="#book"><?= h($s['cta_text']) ?></a>
      </section>

      <section class="section center wash proof"><div class="section-label"><?= h($s['social_label']) ?></div><h2><?= h($s['social_heading']) ?></h2><div class="proof-gallery"><div class="proof-item"><?= art_block('proof-1.jpg', 'proof-fallback') ?></div><div class="proof-item"><?= art_block('proof-2.jpg', 'proof-fallback') ?></div><div class="proof-item"><?= art_block('proof-3.jpg', 'proof-fallback') ?></div></div><p class="proof-caption"><?= h($s['social_caption']) ?></p></section>

      <section id="what-you-get"><div class="benefits-band"><h2><?= h($s['benefits_label']) ?></h2></div><div class="section"><div class="benefits"><article class="benefit"><div class="benefit-art"><?= art_block('benefit-1.jpg') ?></div><h3><?= h($s['b1_title']) ?></h3><p><?= h($s['b1_desc']) ?></p></article><article class="benefit"><div class="benefit-art"><?= art_block('benefit-2.jpg') ?></div><h3><?= h($s['b2_title']) ?></h3><p><?= h($s['b2_desc']) ?></p></article><article class="benefit"><div class="benefit-art"><?= art_block('benefit-3.jpg') ?></div><h3><?= h($s['b3_title']) ?></h3><p><?= h($s['b3_desc']) ?></p></article></div></div></section>

      <div class="spaced-cta"><a class="button" href="#book"><?= h($s['cta_text']) ?></a></div>
      <section class="section center wash"><div class="detector-panel offer-panel"><span class="panel-kicker"><?= h($s['offer_art_title']) ?></span><button class="mute-toggle" id="offerSoundBtn" type="button">Unmute</button><?php $offerVideo = file_exists(__DIR__ . '/img/offer.mp4') ? 'img/offer.mp4' : 'https://drive.usercontent.google.com/download?id=' . h($s['offer_video_drive_id']) . '&export=download&confirm=t'; ?><video id="offerVideo" autoplay muted loop playsinline preload="metadata"><source src="<?= h($offerVideo) ?>" type="video/mp4"><div class="video-fallback">Your browser can't play this video. <a href="#" style="text-decoration:underline">Open on Google Drive</a>.</div></video></div><div class="section-label"><?= h($s['offer_label']) ?></div><p><?= h($s['offer_desc']) ?></p></section>

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
        <form class="lead-form" action="#book" method="post">
          <input type="hidden" name="lead_submit" value="1">
          <label>Full name<span class="hint"></span>
            <input type="text" name="lead_name" value="<?= h($leadName) ?>" required>
          </label>
          <label>Phone number<span class="hint">+234 800 000 0000</span>
            <input type="tel" name="lead_phone" value="<?= h($leadPhone) ?>" placeholder="+234 800 000 0000" required>
          </label>
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
          <div class="submit-row"><button class="button" type="submit"><?= h($s['cta_text']) ?></button></div>
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

      var offerVideo = document.getElementById('offerVideo');
      if (offerVideo) {
        offerVideo.addEventListener('play', function() {
          trackEvent('video_play', { video: 'offer' });
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
      var v = document.getElementById('offerVideo');
      var b = document.getElementById('offerSoundBtn');
      if (!v || !b) return;
      v.volume = 0.7;
      v.play().catch(function () {});
      b.addEventListener('click', function () {
        if (v.muted) { v.muted = false; b.textContent = 'Mute'; }
        else { v.muted = true; b.textContent = 'Unmute'; }
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
  </script>
</body>
</html>