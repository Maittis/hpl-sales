<?php
require_once __DIR__ . '/config.php';
$s = hpl_settings();
$story = $_GET['story'] ?? 'detectorist-story';
$proofImage1 = file_exists(__DIR__ . '/img/field-proof-1.jpg') ? 'img/field-proof-1.jpg' : 'img/slide-1.jpg';
$proofImage2 = file_exists(__DIR__ . '/img/field-proof-2.jpg') ? 'img/field-proof-2.jpg' : 'img/slide-2.jpg';

$stories = [
    'detectorist-story' => [
    'title' => $s['field_proof_1_title'] ?? 'Detectorist story',
    'eyebrow' => $s['field_proof_1_eyebrow'] ?? 'Field proof',
    'headline' => $s['field_proof_1_headline'] ?? 'Choosing the right detector for challenging ground.',
    'text' => $s['field_proof_1_text'] ?? '',
    'image' => $proofImage1,
    'cta' => $s['field_proof_1_cta'] ?? 'Book a call',
        'cta_link' => '#book'
    ],
    'on-ground-proof' => [
      'title' => $s['field_proof_2_title'] ?? 'On-ground proof',
      'eyebrow' => $s['field_proof_2_eyebrow'] ?? 'Field photo',
      'headline' => $s['field_proof_2_headline'] ?? 'Customer field shots showing real usage and working conditions.',
      'text' => $s['field_proof_2_text'] ?? '',
      'image' => $proofImage2,
      'cta' => $s['field_proof_2_cta'] ?? 'See the field kit',
        'cta_link' => '#what-you-get'
    ],
    'find-result' => [
        'title' => 'Find result',
        'eyebrow' => 'Gold / find',
        'headline' => 'Ground-truth imagery ready for future gold, nugget, or result uploads.',
        'text' => 'This is where the right machine becomes obvious. The signal, setup, and target conditions all matter — and the result tells the real story faster than any sales pitch.',
        'image' => 'img/slide-3.jpg',
        'cta' => 'Talk to the team',
        'cta_link' => '#book'
    ],
    'your-next-find' => [
        'title' => 'Ready for more',
        'eyebrow' => 'Your next find',
        'headline' => 'Built to keep moving from the first signal to the next successful outing.',
        'text' => 'Every outing is a chance to learn more about the ground, sharpen the setup, and find the right pattern. This is the next step for detectorists who want more than just a machine — they want a system that keeps working.',
        'image' => 'img/slide-4.jpg',
        'cta' => 'Get yours now',
        'cta_link' => '#book'
    ],
];

$storyData = $stories[$story] ?? $stories['detectorist-story'];
$storySlug = $story ?? 'detectorist-story';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($storyData['title'], ENT_QUOTES, 'UTF-8') ?> | HPL Gold Detectors</title>
  <style>
    :root { --navy:#111a38; --gold:#d4a52c; --gold-light:#f4ca5b; --muted:#c4cad7; --paper:#fff; --wash:#0b122a; }
    * { box-sizing:border-box; }
    body {
      margin:0; 'Manrope','Plus Jakarta Sans',sans-serif; background:var(--navy); color:#fff;
    }
    .page { min-height:100vh; padding:48px 22px; }
    .story-wrap {
      max-width:960px; margin:0 auto; background:var(--navy); border:1px solid rgba(255,255,255,.12);
      border-radius:24px; box-shadow:0 22px 60px rgba(0,0,0,.24); overflow:hidden;
    }
    .story-header {
      background:var(--navy); color:#fff; padding:22px 28px 18px;
    }
    .eyebrow {
      display:inline-block; font-size:11px; letter-spacing:.14em; font-weight:700; text-transform:uppercase; color:var(--gold-light); margin-bottom:10px;
    }
    .story-header h1 {font-weight:400; 
      margin:0; font-size:clamp(30px,5vw,52px); line-height:1.04; letter-spacing:0; font-family:'Anton',sans-serif;
    }
    .story-body {
      display:grid; grid-template-columns:1.1fr .9fr; gap:28px; padding:28px;
    }
    .story-copy h2 {font-weight:400; 
      color:#fff; margin:0 0 16px; font-size:clamp(22px,3vw,34px); line-height:1.12; letter-spacing:0; font-family:'Anton',sans-serif;
    }
    .story-copy p {
      margin:0 0 18px; color:var(--muted); font-size:16px; line-height:1.7;
    }
    .story-image {
      border-radius:18px; overflow:hidden; background:var(--wash); min-height:360px;
      border:1px solid rgba(255,255,255,.12);
    }
    .story-image img {
      display:block; width:100%; height:100%; object-fit:cover;
    }
    .cta-row {
      margin-top:18px;
    }
    .button {
      display:inline-block; background:var(--gold); color:var(--navy); text-decoration:none; font-weight:700; letter-spacing:.08em; text-transform:uppercase; font-size:13px; padding:15px 24px; border-radius:10px;
    }
    .button:hover { background:var(--gold-light); }
    .secondary {
      display:inline-block; margin-left:10px; color:#fff; text-decoration:none; font-weight:700; padding:15px 18px; border:1px solid rgba(255,255,255,.28); border-radius:10px;
    }
    @media (max-width:760px) {
      .story-body { grid-template-columns:1fr; padding:20px; }
      .story-header { padding:18px 20px; }
      .secondary { margin-left:0; margin-top:10px; }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="story-wrap">
      <div class="story-header">
        <span class="eyebrow"><?= htmlspecialchars($storyData['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
        <h1><?= htmlspecialchars($storyData['title'], ENT_QUOTES, 'UTF-8') ?></h1>
      </div>
      <div class="story-body">
        <div class="story-copy">
          <h2><?= htmlspecialchars($storyData['headline'], ENT_QUOTES, 'UTF-8') ?></h2>
          <p><?= htmlspecialchars($storyData['text'], ENT_QUOTES, 'UTF-8') ?></p>
          <div class="cta-row">
            <a class="button" href="index.php<?= htmlspecialchars($storyData['cta_link'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($storyData['cta'], ENT_QUOTES, 'UTF-8') ?></a>
            <a class="secondary" href="index.php">Back to site</a>
          </div>
        </div>
        <div class="story-image">
          <img src="<?= htmlspecialchars($storyData['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($storyData['title'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
    </div>
  </div>
</body>
</html>
