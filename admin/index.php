<?php
require __DIR__ . '/config.php';
/* Shared helpers, which is where the selectable font catalog lives. Required
   rather than duplicated so the dropdowns offered here can never drift from the
   families the front page is able to request. site-bootstrap.php is a tracked
   file, so unlike config.php it ships in the deployment archive. */
require_once __DIR__ . '/../site-bootstrap.php';

/* WhatsApp number normalisation. Guarded and duplicated across the entry points
   because config.php carries the database credentials and is excluded from the
   deployment archive, so a helper living only there would be missing in
   production. Whichever file loads first defines these. */
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
        /* Shipped constant. config.php is excluded from the deployment archive
           for its credentials, so the copy on the server is not this one and
           may not carry the number. */
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

$fields = [
    ['key' => 'topline',           'label' => 'Top banner text',            'type' => 'text',     'group' => 'Header'],
    ['key' => 'brand_name',        'label' => 'Brand name',                 'type' => 'text',     'group' => 'Header'],
    ['key' => 'brand_mark',        'label' => 'Brand mark (logo letter)',   'type' => 'text',     'group' => 'Header'],
    ['key' => 'logo_alt',          'label' => 'Logo alt text',              'type' => 'text',     'group' => 'Header'],
    ['key' => 'nav_1',             'label' => 'Nav link 1',                 'type' => 'text',     'group' => 'Header'],
    ['key' => 'nav_2',             'label' => 'Nav link 2',                 'type' => 'text',     'group' => 'Header'],
    ['key' => 'nav_cta',           'label' => 'Nav button text',            'type' => 'text',     'group' => 'Header'],
    ['key' => 'hero_h1',           'label' => 'Headline (before highlight)', 'type' => 'text',    'group' => 'Hero'],
    ['key' => 'hero_h1_span',      'label' => 'Highlighted headline part',  'type' => 'text',     'group' => 'Hero'],
    ['key' => 'live_pill',         'label' => 'LIVE pill text',             'type' => 'text',     'group' => 'Hero'],
    ['key' => 'hero_sub',          'label' => 'Sub-headline',               'type' => 'text',     'group' => 'Hero'],
    ['key' => 'hero_intro',        'label' => 'Intro paragraph',            'type' => 'textarea', 'group' => 'Hero'],
    ['key' => 'panel_kicker',      'label' => 'Video badge text',           'type' => 'text',     'group' => 'Hero'],
      ['key' => 'video_drive_id',    'label' => 'Google Drive video file ID', 'type' => 'text',     'group' => 'Hero'],
      ['key' => 'promo_fallback_text', 'label' => 'Promo video fallback text', 'type' => 'text',     'group' => 'Hero'],
      ['key' => 'video_poster',      'label' => 'Hero poster image path',     'type' => 'text',     'group' => 'Hero'],
    ['key' => 'hero_caption',      'label' => 'Small copy under video',     'type' => 'textarea', 'group' => 'Hero'],
    ['key' => 'hero_cta_locked',   'label' => 'Hero CTA locked text',    'type' => 'text',     'group' => 'Hero'],
    ['key' => 'cta_text',          'label' => 'Button text (all CTAs)',     'type' => 'text',     'group' => 'Hero'],
    ['key' => 'pull_refresh_text',  'label' => 'Pull-to-refresh text',        'type' => 'text',     'group' => 'Hero'],
    ['key' => 'social_label',      'label' => 'Small label',                'type' => 'text',     'group' => 'Social proof'],
    ['key' => 'social_heading',    'label' => 'Heading',                    'type' => 'text',     'group' => 'Social proof'],
    ['key' => 'social_caption',    'label' => 'Image caption',              'type' => 'text',     'group' => 'Social proof'],
    // Ticker - Zambia
    ['key' => 'ticker_z1_icon', 'label' => 'Zambia ticker 1 icon', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z1_name', 'label' => 'Zambia ticker 1 name', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z1_text', 'label' => 'Zambia ticker 1 text', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z1_time', 'label' => 'Zambia ticker 1 time', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z2_icon', 'label' => 'Zambia ticker 2 icon', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z2_name', 'label' => 'Zambia ticker 2 name', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z2_text', 'label' => 'Zambia ticker 2 text', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z2_time', 'label' => 'Zambia ticker 2 time', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z3_icon', 'label' => 'Zambia ticker 3 icon', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z3_name', 'label' => 'Zambia ticker 3 name', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z3_text', 'label' => 'Zambia ticker 3 text', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z3_time', 'label' => 'Zambia ticker 3 time', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z4_icon', 'label' => 'Zambia ticker 4 icon', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z4_name', 'label' => 'Zambia ticker 4 name', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z4_text', 'label' => 'Zambia ticker 4 text', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z4_time', 'label' => 'Zambia ticker 4 time', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z5_icon', 'label' => 'Zambia ticker 5 icon', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z5_name', 'label' => 'Zambia ticker 5 name', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z5_text', 'label' => 'Zambia ticker 5 text', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z5_time', 'label' => 'Zambia ticker 5 time', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z6_icon', 'label' => 'Zambia ticker 6 icon', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z6_name', 'label' => 'Zambia ticker 6 name', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z6_text', 'label' => 'Zambia ticker 6 text', 'type' => 'text', 'group' => 'Social proof ticker'],
    ['key' => 'ticker_z6_time', 'label' => 'Zambia ticker 6 time', 'type' => 'text', 'group' => 'Social proof ticker'],
    // Ticker - Trust
    ['key' => 'ticker_t1_icon', 'label' => 'Trust ticker 1 icon', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t1_name', 'label' => 'Trust ticker 1 name', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t1_text', 'label' => 'Trust ticker 1 text', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t1_time', 'label' => 'Trust ticker 1 time', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t2_icon', 'label' => 'Trust ticker 2 icon', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t2_name', 'label' => 'Trust ticker 2 name', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t2_text', 'label' => 'Trust ticker 2 text', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t2_time', 'label' => 'Trust ticker 2 time', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t3_icon', 'label' => 'Trust ticker 3 icon', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t3_name', 'label' => 'Trust ticker 3 name', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t3_text', 'label' => 'Trust ticker 3 text', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t3_time', 'label' => 'Trust ticker 3 time', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t4_icon', 'label' => 'Trust ticker 4 icon', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t4_name', 'label' => 'Trust ticker 4 name', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t4_text', 'label' => 'Trust ticker 4 text', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t4_time', 'label' => 'Trust ticker 4 time', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t5_icon', 'label' => 'Trust ticker 5 icon', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t5_name', 'label' => 'Trust ticker 5 name', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t5_text', 'label' => 'Trust ticker 5 text', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t5_time', 'label' => 'Trust ticker 5 time', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t6_icon', 'label' => 'Trust ticker 6 icon', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t6_name', 'label' => 'Trust ticker 6 name', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t6_text', 'label' => 'Trust ticker 6 text', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'ticker_t6_time', 'label' => 'Trust ticker 6 time', 'type' => 'text', 'group' => 'Trust ticker'],
    ['key' => 'slides_label',   'label' => 'Photo section label',           'type' => 'text',     'group' => 'Field proof'],
    ['key' => 'slides_heading', 'label' => 'Photo section heading',         'type' => 'text',     'group' => 'Field proof'],
    ['key' => 'field_proof_enabled', 'label' => 'Show this section',         'type' => 'select',   'group' => 'Field proof', 'options' => ['1' => 'Visible', '0' => 'Hidden']],
    ['key' => 'field_proof_label', 'label' => 'Section label',                'type' => 'text',     'group' => 'Field proof'],
    ['key' => 'field_proof_heading', 'label' => 'Section heading',            'type' => 'text',     'group' => 'Field proof'],
    ['key' => 'field_proof_1_eyebrow', 'label' => 'Story 1 label',             'type' => 'text',     'group' => 'Field proof'],
    ['key' => 'field_proof_1_title', 'label' => 'Story 1 title',               'type' => 'text',     'group' => 'Field proof'],
    ['key' => 'field_proof_1_headline', 'label' => 'Story 1 headline',         'type' => 'text',     'group' => 'Field proof'],
    ['key' => 'field_proof_1_text', 'label' => 'Story 1 text',                 'type' => 'textarea', 'group' => 'Field proof'],
    ['key' => 'field_proof_1_cta', 'label' => 'Story 1 button text',           'type' => 'text',     'group' => 'Field proof'],
    ['key' => 'field_proof_2_eyebrow', 'label' => 'Story 2 label',             'type' => 'text',     'group' => 'Field proof'],
    ['key' => 'field_proof_2_title', 'label' => 'Story 2 title',               'type' => 'text',     'group' => 'Field proof'],
    ['key' => 'field_proof_2_headline', 'label' => 'Story 2 headline',         'type' => 'text',     'group' => 'Field proof'],
    ['key' => 'field_proof_2_text', 'label' => 'Story 2 text',                 'type' => 'textarea', 'group' => 'Field proof'],
    ['key' => 'field_proof_2_cta', 'label' => 'Story 2 button text',           'type' => 'text',     'group' => 'Field proof'],
    ['key' => 'benefits_label',    'label' => 'Section label',              'type' => 'text',     'group' => 'Benefits'],
    ['key' => 'b1_title',          'label' => 'Benefit #1 title',           'type' => 'text',     'group' => 'Benefits'],
    ['key' => 'b1_desc',           'label' => 'Benefit #1 text',            'type' => 'textarea', 'group' => 'Benefits'],
    ['key' => 'b2_title',          'label' => 'Benefit #2 title',           'type' => 'text',     'group' => 'Benefits'],
    ['key' => 'b2_desc',           'label' => 'Benefit #2 text',            'type' => 'textarea', 'group' => 'Benefits'],
    ['key' => 'b3_title',          'label' => 'Benefit #3 title',           'type' => 'text',     'group' => 'Benefits'],
    ['key' => 'b3_desc',           'label' => 'Benefit #3 text',            'type' => 'textarea', 'group' => 'Benefits'],
    /* The proof section renders one fixed player now, so the old layout picker
       and its carousel geometry keys are no longer read by the site. The keys stay
       in the schema and the rows stay put, but the controls are removed rather than
       left on screen as settings that silently do nothing. */
    ['key' => 'proof_single_note', 'label' => 'The Real Results section shows one fixed video: the first filled slot below. Clear slots 2-15 to keep only one testimonial in your library.', 'type' => 'note', 'group' => 'Proof videos'],
    ['key' => 'proof_embed_note', 'label' => 'Player embeds: paste the whole Bunny / MediaDelivery embed block into Video 1. Only the first slot is published now, and the player carries its own unmute control.', 'type' => 'note', 'group' => 'Proof videos'],
    ['key' => 'proof_video_1', 'label' => 'Video 1 - this is the one shown (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_1_caption', 'label' => 'Video 1 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_2', 'label' => 'Video 2 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_2_caption', 'label' => 'Video 2 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_3', 'label' => 'Video 3 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_3_caption', 'label' => 'Video 3 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_4', 'label' => 'Video 4 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_4_caption', 'label' => 'Video 4 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_5', 'label' => 'Video 5 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_5_caption', 'label' => 'Video 5 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_6', 'label' => 'Video 6 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_6_caption', 'label' => 'Video 6 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_7', 'label' => 'Video 7 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_7_caption', 'label' => 'Video 7 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_8', 'label' => 'Video 8 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_8_caption', 'label' => 'Video 8 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_9', 'label' => 'Video 9 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_9_caption', 'label' => 'Video 9 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_10', 'label' => 'Video 10 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_10_caption', 'label' => 'Video 10 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_11', 'label' => 'Video 11 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_11_caption', 'label' => 'Video 11 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_12', 'label' => 'Video 12 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_12_caption', 'label' => 'Video 12 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_13', 'label' => 'Video 13 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_13_caption', 'label' => 'Video 13 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_14', 'label' => 'Video 14 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_14_caption', 'label' => 'Video 14 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_15', 'label' => 'Video 15 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_video_15_caption', 'label' => 'Video 15 caption', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'ty_video_1', 'label' => 'Thank-you story video 1 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Thank you videos'],
    ['key' => 'ty_video_1_caption', 'label' => 'Thank-you story video 1 caption', 'type' => 'text', 'group' => 'Thank you videos'],
    ['key' => 'ty_video_2', 'label' => 'Thank-you story video 2 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Thank you videos'],
    ['key' => 'ty_video_2_caption', 'label' => 'Thank-you story video 2 caption', 'type' => 'text', 'group' => 'Thank you videos'],
    ['key' => 'ty_video_3', 'label' => 'Thank-you story video 3 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Thank you videos'],
    ['key' => 'ty_video_3_caption', 'label' => 'Thank-you story video 3 caption', 'type' => 'text', 'group' => 'Thank you videos'],
    ['key' => 'ty_video_4', 'label' => 'Thank-you story video 4 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Thank you videos'],
    ['key' => 'ty_video_4_caption', 'label' => 'Thank-you story video 4 caption', 'type' => 'text', 'group' => 'Thank you videos'],
    ['key' => 'ty_video_5', 'label' => 'Thank-you story video 5 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Thank you videos'],
    ['key' => 'ty_video_5_caption', 'label' => 'Thank-you story video 5 caption', 'type' => 'text', 'group' => 'Thank you videos'],
    ['key' => 'ty_video_6', 'label' => 'Thank-you story video 6 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Thank you videos'],
    ['key' => 'ty_video_6_caption', 'label' => 'Thank-you story video 6 caption', 'type' => 'text', 'group' => 'Thank you videos'],
    ['key' => 'faq1_q',            'label' => 'Question 1',                 'type' => 'text',     'group' => 'FAQ'],
    ['key' => 'faq1_a',            'label' => 'Answer 1 (one line per paragraph)', 'type' => 'textarea', 'group' => 'FAQ'],
    ['key' => 'faq2_q',            'label' => 'Question 2',                 'type' => 'text',     'group' => 'FAQ'],
    ['key' => 'faq2_a',            'label' => 'Answer 2',                   'type' => 'textarea', 'group' => 'FAQ'],
    ['key' => 'faq3_q',            'label' => 'Question 3',                 'type' => 'text',     'group' => 'FAQ'],
    ['key' => 'faq3_a',            'label' => 'Answer 3',                   'type' => 'textarea', 'group' => 'FAQ'],
    ['key' => 'faq4_q',            'label' => 'Question 4',                 'type' => 'text',     'group' => 'FAQ'],
    ['key' => 'faq4_a',            'label' => 'Answer 4',                   'type' => 'textarea', 'group' => 'FAQ'],
    ['key' => 'visit_label',        'label' => 'Small label',                'type' => 'text',     'group' => 'Visit us'],
    ['key' => 'visit_h',            'label' => 'Heading',                    'type' => 'text',     'group' => 'Visit us'],
    ['key' => 'visit_sub',          'label' => 'Intro line',                 'type' => 'textarea', 'group' => 'Visit us'],
    ['key' => 'visit_address',      'label' => 'Street address (one line per paragraph)', 'type' => 'textarea', 'group' => 'Visit us'],
    ['key' => 'visit_phone',        'label' => 'Phone / WhatsApp shown on the section', 'type' => 'text', 'group' => 'Visit us'],
    ['key' => 'visit_hours',        'label' => 'Opening hours (one line per paragraph)', 'type' => 'textarea', 'group' => 'Visit us'],
    ['key' => 'visit_cta_label',    'label' => 'Directions button text',     'type' => 'text',     'group' => 'Visit us'],
    ['key' => 'visit_map_lat',      'label' => 'Map latitude',               'type' => 'text',     'group' => 'Visit us map'],
    ['key' => 'visit_map_lon',      'label' => 'Map longitude',              'type' => 'text',     'group' => 'Visit us map'],
    ['key' => 'visit_map_zoom',     'label' => 'Map zoom (1 = whole world, 19 = front door)', 'type' => 'text', 'group' => 'Visit us map'],
    ['key' => 'visit_map_embed',    'label' => 'Or paste a map embed / share link, which wins over the coordinates above', 'type' => 'textarea', 'group' => 'Visit us map'],
    ['key' => 'visit_map_note',     'label' => 'Leave everything blank and no map is shown. Coordinates build an OpenStreetMap frame, which needs no API key and costs nothing. A pasted embed has its address stripped and is rebuilt from the URL, so nothing untrusted is published.', 'type' => 'note', 'group' => 'Visit us map'],
    ['key' => 'form_eyebrow',      'label' => 'Form small label',           'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_title',        'label' => 'Form heading',               'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_event',        'label' => 'Event / call name',          'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_desc',         'label' => 'Intro line',                 'type' => 'textarea', 'group' => 'Booking form'],
    ['key' => 'form_sub',          'label' => 'Sub-line',                   'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_submit',       'label' => 'Final button text',          'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_default_cc',    'label' => 'Fallback country code',      'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_q_terrain',     'label' => 'Search area question',           'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_q_target',      'label' => 'Target question',            'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_q_timing',      'label' => 'Timing question',           'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_q_looking_for', 'label' => 'Looking-for question label', 'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_q_finding',     'label' => 'Finding question label',     'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_q_experience',  'label' => 'Experience question label',  'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_q_customer_type', 'label' => 'Customer-type question label', 'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_q_message',      'label' => 'Message question label',      'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_message_hint',   'label' => 'Message hint text',         'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_step1_heading', 'label' => 'Step 1 heading',            'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_step1_sub',     'label' => 'Step 1 subtext',            'type' => 'textarea', 'group' => 'Booking form'],
    ['key' => 'form_label_name',    'label' => 'Full name field label',     'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_label_phone',   'label' => 'Phone field label',         'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_label_email',   'label' => 'Email field label',         'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_label_country', 'label' => 'Country field label',       'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_label_city',    'label' => 'City field label',          'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_continue',      'label' => 'Continue button text',      'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_step2_heading', 'label' => 'Step 2 heading',            'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_step2_sub',     'label' => 'Step 2 subtext',            'type' => 'textarea', 'group' => 'Booking form'],
['key' => 'form_advice_label',    'label' => 'Advice checkbox label',       'type' => 'textarea', 'group' => 'Booking form'],
    ['key' => 'form_step1_heading',   'label' => 'Step 1 heading',              'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_step1_sub',       'label' => 'Step 1 subtext',              'type' => 'textarea', 'group' => 'Booking form'],
    ['key' => 'form_step2_heading',   'label' => 'Step 2 heading',              'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_step2_sub',       'label' => 'Step 2 subtext',              'type' => 'textarea', 'group' => 'Booking form'],
    ['key' => 'form_step1_label',     'label' => 'Step 1 progress label',       'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_step2_label',     'label' => 'Step 2 progress label',       'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_error_name_required',  'label' => 'Name required error',    'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_error_phone_required', 'label' => 'Phone required error',   'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_error_email_invalid',  'label' => 'Email invalid error',    'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_error_country_required', 'label' => 'Country required error', 'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_error_city_required',  'label' => 'City required error',    'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_error_question_required', 'label' => 'Question required error', 'type' => 'text', 'group' => 'Booking form'],
    ['key' => 'form_back',          'label' => 'Back button text',          'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_submit_btn',    'label' => 'Submit button text',        'type' => 'text',     'group' => 'Booking form'],
    ['key' => 'form_success_heading', 'label' => 'Success heading',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'form_success_text',  'label' => 'Success message',           'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'form_success_note',  'label' => 'Success note',              'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'form_success_back',  'label' => 'Back link text',            'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_h',               'label' => 'Thank-you heading',         'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_badge',           'label' => 'Top badge text',             'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_sub',             'label' => 'Thank-you message',         'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_meta_title',      'label' => 'Browser tab title',         'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_meta_desc',       'label' => 'Meta description',          'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_watch_note',      'label' => 'Watch-the-video note',      'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_response_time',   'label' => 'Response-time pill text',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_whatsapp_button', 'label' => 'WhatsApp button text',      'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_back',            'label' => 'Back link text',            'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_video_drive_id',  'label' => 'Thank-you video Drive ID / URL (optional — used only if no embed is set)', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_video_embed',     'label' => 'Thank-you video embed HTML (Bunny.net / MediaDelivery — paste the full <iframe> block here; takes priority over Drive ID)', 'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_vplaceholder',    'label' => 'Video placeholder text',    'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_start_label',     'label' => 'Video section label',       'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_start_h',         'label' => 'Video section heading',     'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_start_sub',       'label' => 'Video section subtext',     'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_dd_label',        'label' => 'Due-diligence section label', 'type' => 'text',   'group' => 'Thank you page'],
    ['key' => 'ty_dd_h',            'label' => 'Due-diligence section heading', 'type' => 'text', 'group' => 'Thank you page'],
    ['key' => 'ty_dd_sub',          'label' => 'Due-diligence section subtext', 'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_stat1_value',     'label' => 'Trust stat 1 value',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_stat1_label',     'label' => 'Trust stat 1 label',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_stat2_value',     'label' => 'Trust stat 2 value',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_stat2_label',     'label' => 'Trust stat 2 label',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_stat3_value',     'label' => 'Trust stat 3 value',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_stat3_label',     'label' => 'Trust stat 3 label',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_social_intro',    'label' => 'Social links intro line',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_social_ig_url',   'label' => 'Facebook URL',            'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_social_ig_label', 'label' => 'Facebook card name',      'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_social_ig_desc',  'label' => 'Facebook card description', 'type' => 'text',    'group' => 'Thank you page'],
    ['key' => 'ty_social_yt_url',   'label' => 'YouTube URL',               'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_social_yt_label', 'label' => 'YouTube card name',         'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_social_yt_desc',  'label' => 'YouTube card description',  'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_social_tt_url',   'label' => 'TikTok URL',                'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_social_tt_label', 'label' => 'TikTok card name',          'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_social_tt_desc',  'label' => 'TikTok card description',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_social_x_url',    'label' => 'X / Twitter URL',           'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_social_x_label',  'label' => 'X / Twitter card name',     'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_social_x_desc',   'label' => 'X / Twitter card description', 'type' => 'text',   'group' => 'Thank you page'],
    ['key' => 'ty_q_label',         'label' => 'Q&A section label',         'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q_h',             'label' => 'Q&A section heading',       'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q_sub',           'label' => 'Q&A section subtext',       'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q1_q',            'label' => 'Question 1',                'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q1_a',            'label' => 'Answer 1',                  'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q2_q',            'label' => 'Question 2',                'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q2_a',            'label' => 'Answer 2',                  'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q3_q',            'label' => 'Question 3',                'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q3_a',            'label' => 'Answer 3',                  'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q4_q',            'label' => 'Question 4',                'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q4_a',            'label' => 'Answer 4',                  'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q5_q',            'label' => 'Question 5',                'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q5_a',            'label' => 'Answer 5',                  'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q6_q',            'label' => 'Question 6',                'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q6_a',            'label' => 'Answer 6',                  'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q7_q',            'label' => 'Question 7',                'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q7_a',            'label' => 'Answer 7',                  'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q8_q',            'label' => 'Question 8',                'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q8_a',            'label' => 'Answer 8',                  'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q9_q',  'label' => 'Question 9',  'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q9_a',  'label' => 'Answer 9',    'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q10_q', 'label' => 'Question 10', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q10_a', 'label' => 'Answer 10',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q11_q', 'label' => 'Question 11', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q11_a', 'label' => 'Answer 11',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q12_q', 'label' => 'Question 12', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q12_a', 'label' => 'Answer 12',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q13_q', 'label' => 'Question 13', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q13_a', 'label' => 'Answer 13',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q14_q', 'label' => 'Question 14', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q14_a', 'label' => 'Answer 14',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q15_q', 'label' => 'Question 15', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q15_a', 'label' => 'Answer 15',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q16_q', 'label' => 'Question 16', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q16_a', 'label' => 'Answer 16',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q17_q', 'label' => 'Question 17', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q17_a', 'label' => 'Answer 17',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q18_q', 'label' => 'Question 18', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q18_a', 'label' => 'Answer 18',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q19_q', 'label' => 'Question 19', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q19_a', 'label' => 'Answer 19',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q20_q', 'label' => 'Question 20', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q20_a', 'label' => 'Answer 20',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q21_q', 'label' => 'Question 21', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q21_a', 'label' => 'Answer 21',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q22_q', 'label' => 'Question 22', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q22_a', 'label' => 'Answer 22',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q23_q', 'label' => 'Question 23', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q23_a', 'label' => 'Answer 23',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q24_q', 'label' => 'Question 24', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q24_a', 'label' => 'Answer 24',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_q25_q', 'label' => 'Question 25', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_q25_a', 'label' => 'Answer 25',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_results_label',   'label' => 'Results section label',     'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_results_h',       'label' => 'Results section heading',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_results_sub',     'label' => 'Results section subtext',   'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_res1_amount',     'label' => 'Result 1 amount',           'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res1_time',       'label' => 'Result 1 timeframe',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res1_name',       'label' => 'Result 1 customer name',    'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res1_place',      'label' => 'Result 1 customer location', 'type' => 'text',    'group' => 'Thank you page'],
    ['key' => 'ty_res2_amount',     'label' => 'Result 2 amount',           'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res2_time',       'label' => 'Result 2 timeframe',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res2_name',       'label' => 'Result 2 customer name',    'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res2_place',      'label' => 'Result 2 customer location', 'type' => 'text',    'group' => 'Thank you page'],
    ['key' => 'ty_res3_amount',     'label' => 'Result 3 amount',           'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res3_time',       'label' => 'Result 3 timeframe',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res3_name',       'label' => 'Result 3 customer name',    'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res3_place',      'label' => 'Result 3 customer location', 'type' => 'text',    'group' => 'Thank you page'],
    ['key' => 'ty_res4_amount',     'label' => 'Result 4 amount',           'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res4_time',       'label' => 'Result 4 timeframe',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res4_name',       'label' => 'Result 4 customer name',    'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res4_place',      'label' => 'Result 4 customer location', 'type' => 'text',    'group' => 'Thank you page'],
    ['key' => 'ty_res5_amount',     'label' => 'Result 5 amount',           'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res5_time',       'label' => 'Result 5 timeframe',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res5_name',       'label' => 'Result 5 customer name',    'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res5_place',      'label' => 'Result 5 customer location', 'type' => 'text',    'group' => 'Thank you page'],
    ['key' => 'ty_res6_amount',     'label' => 'Result 6 amount',           'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res6_time',       'label' => 'Result 6 timeframe',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res6_name',       'label' => 'Result 6 customer name',    'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_res6_place',      'label' => 'Result 6 customer location', 'type' => 'text',    'group' => 'Thank you page'],
    ['key' => 'ty_timing_label',    'label' => 'Timing section label',      'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_timing_h',        'label' => 'Timing section heading',    'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_timing1_h',       'label' => 'Timing card 1 heading',     'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_timing1_text',    'label' => 'Timing card 1 text',        'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_timing2_h',       'label' => 'Timing card 2 heading',     'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_timing2_text',    'label' => 'Timing card 2 text',        'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_timing3_h',       'label' => 'Timing card 3 heading',     'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_timing3_text',    'label' => 'Timing card 3 text',        'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_next_label',      'label' => 'What-happens-next section label', 'type' => 'text', 'group' => 'Thank you page'],
    ['key' => 'ty_next_h',          'label' => 'What-happens-next section heading', 'type' => 'text', 'group' => 'Thank you page'],
    ['key' => 'ty_step1_h',         'label' => 'Next step 1 heading',       'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_step1_text',      'label' => 'Next step 1 text',          'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_step2_h',         'label' => 'Next step 2 heading',       'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_step2_text',      'label' => 'Next step 2 text',          'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_step3_h',         'label' => 'Next step 3 heading',       'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_step3_text',      'label' => 'Next step 3 text',          'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_contact_h',       'label' => 'Contact box heading',       'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_contact_dm',      'label' => 'Contact channel 1 (DM)',    'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_contact_email',   'label' => 'Contact channel 2 (email)', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_contact_note',    'label' => 'Contact box note',          'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_receipts_label',  'label' => 'Receipts section label',    'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_receipts_h',      'label' => 'Receipts section heading',  'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_receipts_sub',    'label' => 'Receipts section subtext',  'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_rcpt1_name',      'label' => 'Receipt 1 customer name',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt1_title',     'label' => 'Receipt 1 headline',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt1_text',      'label' => 'Receipt 1 story text',      'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_rcpt2_name',      'label' => 'Receipt 2 customer name',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt2_title',     'label' => 'Receipt 2 headline',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt2_text',      'label' => 'Receipt 2 story text',      'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_rcpt3_name',      'label' => 'Receipt 3 customer name',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt3_title',     'label' => 'Receipt 3 headline',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt3_text',      'label' => 'Receipt 3 story text',      'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_rcpt4_name',      'label' => 'Receipt 4 customer name',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt4_title',     'label' => 'Receipt 4 headline',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt4_text',      'label' => 'Receipt 4 story text',      'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_rcpt5_name',      'label' => 'Receipt 5 customer name',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt5_title',     'label' => 'Receipt 5 headline',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt5_text',      'label' => 'Receipt 5 story text',      'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_rcpt6_name',      'label' => 'Receipt 6 customer name',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt6_title',     'label' => 'Receipt 6 headline',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt6_text',      'label' => 'Receipt 6 story text',      'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_rcpt7_name',      'label' => 'Receipt 7 customer name',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt7_title',     'label' => 'Receipt 7 headline',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt7_text',      'label' => 'Receipt 7 story text',      'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_rcpt8_name',      'label' => 'Receipt 8 customer name',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt8_title',     'label' => 'Receipt 8 headline',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_rcpt8_text',      'label' => 'Receipt 8 story text',      'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_footer_link1',    'label' => 'Footer link 1 text',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_footer_link2',    'label' => 'Footer link 2 text',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_footer_link3',    'label' => 'Footer link 3 text',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_footer_link4',    'label' => 'Footer link 4 text',        'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_copyright',       'label' => 'Footer copyright line',     'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'final_h',           'label' => 'Heading',                    'type' => 'text',     'group' => 'Final CTA'],
    ['key' => 'final_sub',         'label' => 'Subtext',                    'type' => 'textarea', 'group' => 'Final CTA'],
    ['key' => 'cta_email',         'label' => 'Contact email (CTA mailto)', 'type' => 'text',     'group' => 'Final CTA'],
    ['key' => 'whatsapp_msg',      'label' => 'WhatsApp message template (use {name} for the lead name)', 'type' => 'textarea', 'group' => 'WhatsApp'],
    ['key' => 'wa_number',         'label' => 'Company WhatsApp number (with country code, e.g. +260966499575)', 'type' => 'text', 'group' => 'WhatsApp'],
    ['key' => 'wa_float_aria',     'label' => 'WhatsApp float button aria-label', 'type' => 'text', 'group' => 'WhatsApp'],
    ['key' => 'wa_number_note',    'label' => 'Leave this blank to fall back to the number built into the site config. Spaces, dashes and a leading 00 are fine. A number that cannot be resolved is ignored rather than published, because a WhatsApp link pointing at the wrong contact is worse than none.', 'type' => 'note', 'group' => 'WhatsApp'],
    ['key' => 'footer_brand',      'label' => 'Footer brand',               'type' => 'text',     'group' => 'Footer'],
    ['key' => 'footer_1',          'label' => 'Footer link 1 text',         'type' => 'text',     'group' => 'Footer'],
    ['key' => 'footer_1_url',      'label' => 'Footer link 1 URL',          'type' => 'text',     'group' => 'Footer'],
    ['key' => 'footer_2',          'label' => 'Footer link 2 text',         'type' => 'text',     'group' => 'Footer'],
    ['key' => 'footer_2_url',      'label' => 'Footer link 2 URL',          'type' => 'text',     'group' => 'Footer'],
    ['key' => 'footer_3',          'label' => 'Footer link 3 text',         'type' => 'text',     'group' => 'Footer'],
    ['key' => 'footer_3_url',      'label' => 'Footer link 3 URL',          'type' => 'text',     'group' => 'Footer'],
    ['key' => 'footer_4',          'label' => 'Footer link 4 text',         'type' => 'text',     'group' => 'Footer'],
    ['key' => 'footer_4_url',      'label' => 'Footer link 4 URL',          'type' => 'text',     'group' => 'Footer'],
    ['key' => 'disclaimer',        'label' => 'Legal disclaimer',           'type' => 'textarea', 'group' => 'Footer'],

    ['key' => 'pf_filter_all',        'label' => '"All" filter tab',            'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'proof_label_videos',   'label' => 'Videos tab label',            'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'proof_label_photos',   'label' => 'Field photos tab label',      'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'field_clip_fallback',  'label' => 'Uncaptioned clip title',      'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'field_photos_heading', 'label' => 'Field photos heading',        'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'field_photos_label',  'label' => 'Field photos section label',  'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'field_photo_1_title', 'label' => 'Field photo 1 title',         'type' => 'text', 'group' => 'Field photos'],
    ['key' => 'field_photo_1_text',  'label' => 'Field photo 1 body text',     'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_photo_2_title', 'label' => 'Field photo 2 title',         'type' => 'text', 'group' => 'Field photos'],
    ['key' => 'field_photo_2_text',  'label' => 'Field photo 2 body text',     'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_photo_3_title', 'label' => 'Field photo 3 title',         'type' => 'text', 'group' => 'Field photos'],
    ['key' => 'field_photo_3_text',  'label' => 'Field photo 3 body text',     'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_photo_4_title', 'label' => 'Field photo 4 title',         'type' => 'text', 'group' => 'Field photos'],
    ['key' => 'field_photo_4_text',  'label' => 'Field photo 4 body text',     'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_photo_body_1',  'label' => 'Field story body 1',          'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_photo_body_2',  'label' => 'Field story body 2',          'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_photo_body_3',  'label' => 'Field story body 3',          'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_photo_body_4',  'label' => 'Field story body 4',          'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_photo_body_5',  'label' => 'Field story body 5',          'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_photo_body_6',  'label' => 'Field story body 6',          'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_clip_body_1',   'label' => 'Field clip body 1',           'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_clip_body_2',   'label' => 'Field clip body 2',           'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_clip_body_3',   'label' => 'Field clip body 3',           'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_clip_body_4',   'label' => 'Field clip body 4',           'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_clip_body_5',   'label' => 'Field clip body 5',           'type' => 'textarea', 'group' => 'Field photos'],
    ['key' => 'field_photo_fallback', 'label' => 'Field photo fallback title', 'type' => 'text', 'group' => 'Field photos'],
    ['key' => 'field_clip_fallback',   'label' => 'Field clip fallback title',   'type' => 'text', 'group' => 'Field photos'],
    ['key' => 'voices_label',         'label' => 'Customer voices label',       'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'trust_1_name', 'label' => 'Trust story 1 name', 'type' => 'text', 'group' => 'Trust stories'],
    ['key' => 'trust_1_location', 'label' => 'Trust story 1 location', 'type' => 'text', 'group' => 'Trust stories'],
    ['key' => 'trust_1_quote', 'label' => 'Trust story 1 quote', 'type' => 'textarea', 'group' => 'Trust stories'],
    ['key' => 'trust_2_name', 'label' => 'Trust story 2 name', 'type' => 'text', 'group' => 'Trust stories'],
    ['key' => 'trust_2_location', 'label' => 'Trust story 2 location', 'type' => 'text', 'group' => 'Trust stories'],
    ['key' => 'trust_2_quote', 'label' => 'Trust story 2 quote', 'type' => 'textarea', 'group' => 'Trust stories'],
    ['key' => 'trust_3_name', 'label' => 'Trust story 3 name', 'type' => 'text', 'group' => 'Trust stories'],
    ['key' => 'trust_3_location', 'label' => 'Trust story 3 location', 'type' => 'text', 'group' => 'Trust stories'],
    ['key' => 'trust_3_quote', 'label' => 'Trust story 3 quote', 'type' => 'textarea', 'group' => 'Trust stories'],
    ['key' => 'trust_label', 'label' => 'Trust section label', 'type' => 'text', 'group' => 'Trust stories'],
    ['key' => 'trust_heading', 'label' => 'Trust section heading', 'type' => 'text', 'group' => 'Trust stories'],
    ['key' => 'trust_sub', 'label' => 'Trust section subtext', 'type' => 'textarea', 'group' => 'Trust stories'],

    ['key' => 'ratings_heading',      'label' => 'Ratings heading',             'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'ratings_count_text',   'label' => 'Ratings review count line',   'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'ratings_stories_heading', 'label' => 'Ratings stories heading',   'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'story_1_eyebrow', 'label' => 'Story 1 eyebrow', 'type' => 'text', 'group' => 'Ratings stories'],
    ['key' => 'story_1_title', 'label' => 'Story 1 title', 'type' => 'text', 'group' => 'Ratings stories'],
    ['key' => 'story_1_text', 'label' => 'Story 1 text', 'type' => 'textarea', 'group' => 'Ratings stories'],
    ['key' => 'story_2_eyebrow', 'label' => 'Story 2 eyebrow', 'type' => 'text', 'group' => 'Ratings stories'],
    ['key' => 'story_2_title', 'label' => 'Story 2 title', 'type' => 'text', 'group' => 'Ratings stories'],
    ['key' => 'story_2_text', 'label' => 'Story 2 text', 'type' => 'textarea', 'group' => 'Ratings stories'],
    ['key' => 'story_3_eyebrow', 'label' => 'Story 3 eyebrow', 'type' => 'text', 'group' => 'Ratings stories'],
    ['key' => 'story_3_title', 'label' => 'Story 3 title', 'type' => 'text', 'group' => 'Ratings stories'],
    ['key' => 'story_3_text', 'label' => 'Story 3 text', 'type' => 'textarea', 'group' => 'Ratings stories'],
    ['key' => 'story_4_eyebrow', 'label' => 'Story 4 eyebrow', 'type' => 'text', 'group' => 'Ratings stories'],
    ['key' => 'story_4_title', 'label' => 'Story 4 title', 'type' => 'text', 'group' => 'Ratings stories'],
    ['key' => 'story_4_text', 'label' => 'Story 4 text', 'type' => 'textarea', 'group' => 'Ratings stories'],
    ['key' => 'story_5_eyebrow', 'label' => 'Story 5 eyebrow', 'type' => 'text', 'group' => 'Ratings stories'],
    ['key' => 'story_5_title', 'label' => 'Story 5 title', 'type' => 'text', 'group' => 'Ratings stories'],
    ['key' => 'story_5_text', 'label' => 'Story 5 text', 'type' => 'textarea', 'group' => 'Ratings stories'],
    ['key' => 'story_6_eyebrow', 'label' => 'Story 6 eyebrow', 'type' => 'text', 'group' => 'Ratings stories'],
    ['key' => 'story_6_title', 'label' => 'Story 6 title', 'type' => 'text', 'group' => 'Ratings stories'],
    ['key' => 'story_6_text', 'label' => 'Story 6 text', 'type' => 'textarea', 'group' => 'Ratings stories'],
    ['key' => 'rating_label_5',       'label' => 'Rating bar label (5 star)',  'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'rating_label_4',       'label' => 'Rating bar label (4 star)',  'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'rating_label_3',       'label' => 'Rating bar label (3 star)',  'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'rating_label_2',       'label' => 'Rating bar label (2 star)',  'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'rating_label_1',       'label' => 'Rating bar label (1 star)',  'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'customer_advice_heading', 'label' => 'Customer advice heading',   'type' => 'text', 'group' => 'Section labels'],

    ['key' => 'testimonial_1_text',    'label' => 'Testimonial 1 text', 'type' => 'textarea', 'group' => 'Testimonial cards'],
    ['key' => 'testimonial_1_name',    'label' => 'Testimonial 1 name', 'type' => 'text', 'group' => 'Testimonial cards'],
    ['key' => 'testimonial_1_initial', 'label' => 'Testimonial 1 avatar initial', 'type' => 'text', 'group' => 'Testimonial cards'],
    ['key' => 'testimonial_1_location','label' => 'Testimonial 1 location', 'type' => 'text', 'group' => 'Testimonial cards'],
    ['key' => 'testimonial_2_text',    'label' => 'Testimonial 2 text', 'type' => 'textarea', 'group' => 'Testimonial cards'],
    ['key' => 'testimonial_2_name',    'label' => 'Testimonial 2 name', 'type' => 'text', 'group' => 'Testimonial cards'],
    ['key' => 'testimonial_2_initial', 'label' => 'Testimonial 2 avatar initial', 'type' => 'text', 'group' => 'Testimonial cards'],
    ['key' => 'testimonial_2_location','label' => 'Testimonial 2 location', 'type' => 'text', 'group' => 'Testimonial cards'],
    ['key' => 'testimonial_3_text',    'label' => 'Testimonial 3 text', 'type' => 'textarea', 'group' => 'Testimonial cards'],
    ['key' => 'testimonial_3_name',    'label' => 'Testimonial 3 name', 'type' => 'text', 'group' => 'Testimonial cards'],
    ['key' => 'testimonial_3_initial', 'label' => 'Testimonial 3 avatar initial', 'type' => 'text', 'group' => 'Testimonial cards'],
    ['key' => 'testimonial_3_location','label' => 'Testimonial 3 location', 'type' => 'text', 'group' => 'Testimonial cards'],

    ['key' => 'voices_heading',      'label' => 'Section heading',         'type' => 'text',    'group' => 'Customer voices'],
    ['key' => 'voice_whatsapp_title', 'label' => 'WhatsApp platform title', 'type' => 'text', 'group' => 'Customer voices'],
    ['key' => 'voice_whatsapp_meta',  'label' => 'WhatsApp meta line',       'type' => 'text', 'group' => 'Customer voices'],
    ['key' => 'voice_whatsapp_text',  'label' => 'WhatsApp message text (leave blank to hide the message)', 'type' => 'textarea', 'group' => 'Customer voices'],
    ['key' => 'voice_facebook_title', 'label' => 'Facebook platform title', 'type' => 'text', 'group' => 'Customer voices'],
    ['key' => 'voice_facebook_meta',  'label' => 'Facebook meta line',       'type' => 'text', 'group' => 'Customer voices'],
    ['key' => 'voice_facebook_text',  'label' => 'Facebook message text (leave blank to hide the message)', 'type' => 'textarea', 'group' => 'Customer voices'],
    ['key' => 'voice_tiktok_title',   'label' => 'TikTok platform title',    'type' => 'text', 'group' => 'Customer voices'],
    ['key' => 'voice_tiktok_meta',    'label' => 'TikTok meta line',         'type' => 'text', 'group' => 'Customer voices'],
    ['key' => 'voice_tiktok_text',    'label' => 'TikTok message text (leave blank to hide the message)', 'type' => 'textarea', 'group' => 'Customer voices'],

    ['key' => 'trust_customers_title', 'label' => 'Customer count heading',  'type' => 'text', 'group' => 'Trust indicators'],
    ['key' => 'trust_customers_text',  'label' => 'Customer count body text', 'type' => 'textarea', 'group' => 'Trust indicators'],
    ['key' => 'trust_ounces_text',     'label' => 'Ounces found body text',  'type' => 'textarea', 'group' => 'Trust indicators'],
    ['key' => 'trust_zig_title',      'label' => 'Trust panel title',           'type' => 'text', 'group' => 'Section labels'],
    ['key' => 'cookie_text',          'label' => 'Cookie notice text',          'type' => 'textarea', 'group' => 'Cookie notice'],
    ['key' => 'cookie_learn_more',    'label' => 'Cookie notice link text',     'type' => 'text', 'group' => 'Cookie notice'],
    ['key' => 'cookie_accept',        'label' => 'Cookie accept button',        'type' => 'text', 'group' => 'Cookie notice'],
    ['key' => 'cookie_deny',          'label' => 'Cookie decline button',       'type' => 'text', 'group' => 'Cookie notice'],
];

/* Font dropdowns are generated from the shared catalog rather than listed here,
   so the choices offered in the admin can never drift from the families the
   front page is able to request. */
$hplFontOptions = [];
foreach (hpl_font_catalog() as $hplFamily => $hplFontSpec) {
    $hplFontOptions[$hplFamily] = $hplFamily;
}
$fields[] = ['key' => 'font_heading', 'label' => 'Heading font',  'type' => 'select', 'group' => 'Typography', 'options' => $hplFontOptions];
$fields[] = ['key' => 'font_body',    'label' => 'Body font',     'type' => 'select', 'group' => 'Typography', 'options' => $hplFontOptions];
$fields[] = ['key' => 'size_hero',    'label' => 'Hero heading size (px)',   'type' => 'number', 'group' => 'Typography', 'min' => 24, 'max' => 120];
$fields[] = ['key' => 'size_h2',      'label' => 'Section heading size (px)', 'type' => 'number', 'group' => 'Typography', 'min' => 16, 'max' => 80];
$fields[] = ['key' => 'size_body',    'label' => 'Body text size (px)',      'type' => 'number', 'group' => 'Typography', 'min' => 11, 'max' => 30];
$fields[] = ['key' => 'size_small',   'label' => 'Small label size (px)',    'type' => 'number', 'group' => 'Typography', 'min' => 8,  'max' => 24];

/* Each slot carries the page section it belongs to, so the Images tab can list
   them grouped by section instead of one long alphabetical-ish run. The order
   below is the order the sections appear on the page, top to bottom. The key,
   file and label are what the upload handler matches on, so they are unchanged. */
$imageSlots = [
    ['key' => 'logo',       'file' => 'hpllogo.jpeg',   'label' => 'Header / footer logo', 'section' => 'Header and footer'],
    ['key' => 'benefit_bg', 'file' => 'benefit-bg.png', 'label' => 'Benefits section background band', 'section' => 'Benefits section'],
    ['key' => 'benefit_1',  'file' => 'benefit-1.jpg',  'label' => 'Benefit card 1 image', 'section' => 'Benefits section'],
    ['key' => 'benefit_2',  'file' => 'benefit-2.jpg',  'label' => 'Benefit card 2 image', 'section' => 'Benefits section'],
    ['key' => 'benefit_3',  'file' => 'benefit-3.jpg',  'label' => 'Benefit card 3 image', 'section' => 'Benefits section'],
    ['key' => 'proof_1',    'file' => 'proof-1.jpg',    'label' => 'Social proof photo 1 (landscape)', 'section' => 'Social proof photos'],
    ['key' => 'proof_2',    'file' => 'proof-2.jpg',    'label' => 'Social proof photo 2 (landscape)', 'section' => 'Social proof photos'],
    ['key' => 'proof_3',    'file' => 'proof-3.jpg',    'label' => 'Social proof photo 3 (landscape)', 'section' => 'Social proof photos'],
    ['key' => 'proof_4',    'file' => 'proof-4.jpg',    'label' => 'Social proof photo 4 (landscape)', 'section' => 'Social proof photos'],
    ['key' => 'field_proof_1_image', 'file' => 'field-proof-1.jpg', 'label' => 'Field proof story 1 photo', 'section' => 'Field proof stories'],
    ['key' => 'field_proof_2_image', 'file' => 'field-proof-2.jpg', 'label' => 'Field proof story 2 photo', 'section' => 'Field proof stories'],
    ['key' => 'stat_1',      'file' => 'stat-1.jpg',      'label' => 'Trust card 1 photo — customers (landscape)', 'section' => 'Trust stories'],
    ['key' => 'stat_2',      'file' => 'stat-2.jpg',      'label' => 'Trust card 2 photo — gold found (landscape)', 'section' => 'Trust stories'],
    ['key' => 'slide_1',    'file' => 'slide-1.jpg',    'label' => 'Auto-slide testimonial 1 (landscape)', 'section' => 'Auto-slide testimonials'],
    ['key' => 'slide_2',    'file' => 'slide-2.jpg',    'label' => 'Auto-slide testimonial 2 (landscape)', 'section' => 'Auto-slide testimonials'],
    ['key' => 'slide_3',    'file' => 'slide-3.jpg',    'label' => 'Auto-slide testimonial 3 (landscape)', 'section' => 'Auto-slide testimonials'],
    ['key' => 'slide_4',    'file' => 'slide-4.jpg',    'label' => 'Auto-slide testimonial 4 (landscape)', 'section' => 'Auto-slide testimonials'],
    ['key' => 'slide_5',    'file' => 'slide-5.jpg',    'label' => 'Auto-slide testimonial 5 (landscape)', 'section' => 'Auto-slide testimonials'],
    ['key' => 'slide_6',    'file' => 'slide-6.jpg',    'label' => 'Auto-slide testimonial 6 (landscape)', 'section' => 'Auto-slide testimonials'],
    ['key' => 'visit_1',    'file' => 'visit-1.jpg',    'label' => 'Showroom photo 1 (landscape)', 'section' => 'Visit us - showroom gallery'],
    ['key' => 'visit_2',    'file' => 'visit-2.jpg',    'label' => 'Showroom photo 2 (landscape)', 'section' => 'Visit us - showroom gallery'],
    ['key' => 'visit_3',    'file' => 'visit-3.jpg',    'label' => 'Showroom photo 3 (landscape)', 'section' => 'Visit us - showroom gallery'],
    ['key' => 'ty_1',       'file' => 'ty-1.jpg',       'label' => 'Thank-you photo 1 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_2',       'file' => 'ty-2.jpg',       'label' => 'Thank-you photo 2 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_3',       'file' => 'ty-3.jpg',       'label' => 'Thank-you photo 3 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_4',       'file' => 'ty-4.jpg',       'label' => 'Thank-you photo 4 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_5',       'file' => 'ty-5.jpg',       'label' => 'Thank-you photo 5 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_6',       'file' => 'ty-6.jpg',       'label' => 'Thank-you photo 6 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_rcpt_1',  'file' => 'ty-rcpt-1.jpg',  'label' => 'Receipt screenshot 1 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_rcpt_2',  'file' => 'ty-rcpt-2.jpg',  'label' => 'Receipt screenshot 2 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_rcpt_3',  'file' => 'ty-rcpt-3.jpg',  'label' => 'Receipt screenshot 3 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_rcpt_4',  'file' => 'ty-rcpt-4.jpg',  'label' => 'Receipt screenshot 4 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_rcpt_5',  'file' => 'ty-rcpt-5.jpg',  'label' => 'Receipt screenshot 5 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_rcpt_6',  'file' => 'ty-rcpt-6.jpg',  'label' => 'Receipt screenshot 6 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_rcpt_7',  'file' => 'ty-rcpt-7.jpg',  'label' => 'Receipt screenshot 7 (landscape)', 'section' => 'Thank-you page'],
    ['key' => 'ty_rcpt_8',  'file' => 'ty-rcpt-8.jpg',  'label' => 'Receipt screenshot 8 (landscape)', 'section' => 'Thank-you page'],
];

/* Bucket the slots by section, keeping the order the sections were declared in. */
$imageSlotsBySection = [];
foreach ($imageSlots as $slot) {
    $imageSlotsBySection[$slot['section']][] = $slot;
}

  $customerVoiceImageSlots = [
    ['key' => 'customer_voice_whatsapp', 'file' => 'customer-voice-whatsapp.jpg', 'label' => 'WhatsApp screenshot'],
    ['key' => 'customer_voice_facebook', 'file' => 'customer-voice-facebook.jpg', 'label' => 'Facebook screenshot'],
    ['key' => 'customer_voice_tiktok', 'file' => 'customer-voice-tiktok.jpg', 'label' => 'TikTok screenshot'],
  ];

$videoSlots = [
    ['key' => 'thankyou_video', 'file' => 'thankyou.mp4', 'dir' => 'img/', 'label' => 'Thank-you page video (MP4 / WebM)', 'setting' => ''],
    ['key' => 'proof_video_1', 'file' => 'story-1.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 1 (MP4 / WebM)', 'setting' => 'uploads/proof/story-1.mp4'],
    ['key' => 'proof_video_2', 'file' => 'story-2.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 2 (MP4 / WebM)', 'setting' => 'uploads/proof/story-2.mp4'],
    ['key' => 'proof_video_3', 'file' => 'story-3.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 3 (MP4 / WebM)', 'setting' => 'uploads/proof/story-3.mp4'],
    ['key' => 'proof_video_4', 'file' => 'story-4.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 4 (MP4 / WebM)', 'setting' => 'uploads/proof/story-4.mp4'],
    ['key' => 'proof_video_5', 'file' => 'story-5.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 5 (MP4 / WebM)', 'setting' => 'uploads/proof/story-5.mp4'],
    ['key' => 'proof_video_6', 'file' => 'story-6.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 6 (MP4 / WebM)', 'setting' => 'uploads/proof/story-6.mp4'],
    ['key' => 'proof_video_7', 'file' => 'story-7.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 7 (MP4 / WebM)', 'setting' => 'uploads/proof/story-7.mp4'],
    ['key' => 'proof_video_8', 'file' => 'story-8.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 8 (MP4 / WebM)', 'setting' => 'uploads/proof/story-8.mp4'],
    ['key' => 'proof_video_9', 'file' => 'story-9.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 9 (MP4 / WebM)', 'setting' => 'uploads/proof/story-9.mp4'],
    ['key' => 'proof_video_10', 'file' => 'story-10.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 10 (MP4 / WebM)', 'setting' => 'uploads/proof/story-10.mp4'],
    ['key' => 'proof_video_11', 'file' => 'story-11.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 11 (MP4 / WebM)', 'setting' => 'uploads/proof/story-11.mp4'],
    ['key' => 'proof_video_12', 'file' => 'story-12.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 12 (MP4 / WebM)', 'setting' => 'uploads/proof/story-12.mp4'],
    ['key' => 'proof_video_13', 'file' => 'story-13.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 13 (MP4 / WebM)', 'setting' => 'uploads/proof/story-13.mp4'],
    ['key' => 'proof_video_14', 'file' => 'story-14.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 14 (MP4 / WebM)', 'setting' => 'uploads/proof/story-14.mp4'],
    ['key' => 'proof_video_15', 'file' => 'story-15.mp4', 'dir' => 'uploads/proof/', 'label' => 'Customer story video 15 (MP4 / WebM)', 'setting' => 'uploads/proof/story-15.mp4'],
    ['key' => 'ty_video_1', 'file' => 'ty-story-1.mp4', 'dir' => 'uploads/proof/', 'label' => 'Thank-you story video 1 (MP4 / WebM)', 'setting' => 'uploads/proof/ty-story-1.mp4'],
    ['key' => 'ty_video_2', 'file' => 'ty-story-2.mp4', 'dir' => 'uploads/proof/', 'label' => 'Thank-you story video 2 (MP4 / WebM)', 'setting' => 'uploads/proof/ty-story-2.mp4'],
    ['key' => 'ty_video_3', 'file' => 'ty-story-3.mp4', 'dir' => 'uploads/proof/', 'label' => 'Thank-you story video 3 (MP4 / WebM)', 'setting' => 'uploads/proof/ty-story-3.mp4'],
    ['key' => 'ty_video_4', 'file' => 'ty-story-4.mp4', 'dir' => 'uploads/proof/', 'label' => 'Thank-you story video 4 (MP4 / WebM)', 'setting' => 'uploads/proof/ty-story-4.mp4'],
    ['key' => 'ty_video_5', 'file' => 'ty-story-5.mp4', 'dir' => 'uploads/proof/', 'label' => 'Thank-you story video 5 (MP4 / WebM)', 'setting' => 'uploads/proof/ty-story-5.mp4'],
    ['key' => 'ty_video_6', 'file' => 'ty-story-6.mp4', 'dir' => 'uploads/proof/', 'label' => 'Thank-you story video 6 (MP4 / WebM)', 'setting' => 'uploads/proof/ty-story-6.mp4'],
];

$settings = hpl_settings();

/**
 * Column order and headings for the lead list, the CSV export and the detail
 * block, declared once so they can never disagree with each other.
 */
$leadColumns = [
    'name'         => 'Name',
    'phone'        => 'WhatsApp',
    'email'        => 'Email',
    'country'      => 'Country',
    'city'         => 'City / Town',
    'looking_for'  => 'Looking for',
    'finding'      => 'Looking to find',
    'experience'   => 'Detector experience',
    'customer_type'=> 'Customer type',
    'timing'       => 'Buy timeframe',
    'knowledge'    => 'Message',
    'needs_advice' => 'Needs advice',
    'source'       => 'Source',
    'status'       => 'Status',
    'submitted_at' => 'Submitted',
];

$activeTab = (string)($_POST['tab'] ?? $_GET['tab'] ?? 'leads');
if (!in_array($activeTab, ['leads', 'settings', 'images', 'customer-voices', 'analytics'], true)) {
    $activeTab = 'leads';
}
$message = '';
$error = '';

/* Post/Redirect/Get.
   Every mutating action ends by redirecting instead of rendering the POST
   response. Without this, refreshing the page replays the POST - the browser
   warns about resubmitting, and if the user confirms, the upload runs a second
   time and silently overwrites what they just saved. */
function hpl_flash(string $type, string $text): void
{
    $_SESSION['hpl_flash'] = ['type' => $type, 'text' => $text];
}

/**
 * URL for an image preview in the admin, with a cache-buster.
 *
 * The thumbnails pointed straight at ../img/<file>, but .htaccess serves images
 * with "access plus 30 days", so the browser held the old picture long after an
 * upload overwrote the file and the admin looked like the upload had failed. The
 * public page never hit this because hpl_img_url() appends ?v=<filemtime>; doing
 * the same here makes a replacement show up straight away.
 */
function hpl_admin_img_url(string $file): string
{
    $path = __DIR__ . '/../img/' . basename($file);
    $stamp = is_file($path) ? (string)@filemtime($path) : '';
    return '../img/' . h(basename($file)) . ($stamp !== '' ? '?v=' . $stamp : '');
}

function hpl_redirect_after_post(string $tab = ''): void
{
    if (isset($_SESSION['hpl_flash'])) {
        $url = 'index.php?flash=1' . ($tab !== '' ? '&tab=' . rawurlencode($tab) : '');
        header('Location: ' . $url);
        exit;
    }
}

/* Pick up a flashed message left by the redirect above. */
if (isset($_GET['flash']) && isset($_SESSION['hpl_flash'])) {
    $flash = $_SESSION['hpl_flash'];
    unset($_SESSION['hpl_flash']);
    if (($flash['type'] ?? '') === 'error') { $error = (string)$flash['text']; }
    else { $message = (string)$flash['text']; }
}

$connection = db();

// The lead table gains the qualification columns on first use after a deploy,
// so the list never has to guard against a missing column per query.
if ($connection) {
    hpl_ensure_lead_schema($connection);
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!$connection) {
        header('Location: index.php');
        exit;
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=hpl-leads-' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, array_values($leadColumns));
    $select = 'SELECT `' . implode('`, `', array_keys($leadColumns)) . '` FROM leads ORDER BY id DESC';
    $result = $connection->query($select);
    while ($row = $result->fetch_assoc()) {
        // Leading tab keeps Excel from reading the number as a numeric value.
        $row['phone'] = "\t" . $row['phone'];
        $row['needs_advice'] = ((int)$row['needs_advice']) ? 'Yes' : 'No';
        $row['submitted_at'] = date('Y-m-d H:i:s', strtotime($row['submitted_at']));
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lead_action'])) {
    if (!csrf_ok()) {
        $error = 'Invalid form token. Please reload and try again.';
    } elseif (!$connection) {
        $error = 'Database unavailable. Lead was not updated.';
    } else {
        $id = (int)($_POST['lead_id'] ?? 0);
        $action = $_POST['lead_action'];
        if ($action === 'toggle') {
            $stmt = $connection->prepare('UPDATE leads SET status = IF(status = \'new\', \'contacted\', \'new\'), contacted_at = IF(status = \'new\', NOW(), NULL) WHERE id = ?');
        } elseif ($action === 'delete') {
            $stmt = $connection->prepare('DELETE FROM leads WHERE id = ?');
        } else {
            $stmt = null;
        }
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $message = 'Lead updated.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_content'])) {
    if (!csrf_ok()) {
        $error = 'Invalid form token. Please reload and try again.';
    } elseif (!$connection) {
        $error = 'Database unavailable. Content was not saved.';
    } else {
        $upsert = $connection->prepare('INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        $saved = 0;
        foreach ($fields as $field) {
            /* A note is guidance, not a setting. Saving it would write an empty
               row that the site then reads as a real value. */
            if (($field['type'] ?? '') === 'note') { continue; }
            $value = (string)($_POST[$field['key']] ?? '');

            /* Select and number inputs are constrained to what the form actually
               offered. A hand-crafted POST could otherwise store a font name or
               a size the control has no way to show again, which leaves the admin
               displaying nothing selected. Rejecting keeps the stored value in
               step with the choices on screen. */
            if (($field['type'] ?? '') === 'select') {
                if (!array_key_exists($value, $field['options'] ?? [])) { continue; }
            } elseif (($field['type'] ?? '') === 'number') {
                $value = trim($value);
                if ($value === '' || !is_numeric($value)) {
                    $value = '';
                } else {
                    $number = (int)$value;
                    $min = (int)($field['min'] ?? 0);
                    $max = (int)($field['max'] ?? 9999);
                    /* Out of range is stored blank, which the front end reads as
                       "use the built-in default" rather than rendering it. */
                    $value = ($number >= $min && $number <= $max) ? (string)$number : '';
                }
            }

            $upsert->bind_param('ss', $field['key'], $value);
            $upsert->execute();
            $saved++;
        }
        $message = 'Saved ' . $saved . ' fields. Visit the <a href="../index.php" style="color:#2c3e6e">site</a> to preview.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!csrf_ok()) {
        $error = 'Invalid form token. Please reload and try again.';
    } elseif (!$connection) {
        $error = 'Database unavailable. Password was not changed.';
    } else {
        $password = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (strlen($password) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $update = $connection->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
            $update->bind_param('si', $hash, $_SESSION['hpl_admin_id']);
            $update->execute();
            $message = 'Password updated.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_customer_reviews'])) {
  if (!csrf_ok()) {
    $error = 'Invalid form token. Please reload and try again.';
  } elseif (!isset($_FILES['customer_reviews']['name']) || !is_array($_FILES['customer_reviews']['name'])) {
    $error = 'Choose one or more review screenshots to upload.';
  } else {
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $uploaded = 0;
    $failed = 0;
    foreach ($_FILES['customer_reviews']['name'] as $i => $name) {
      $uploadError = (int)($_FILES['customer_reviews']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
      if ($uploadError === UPLOAD_ERR_NO_FILE) { continue; }
      if ($uploadError !== UPLOAD_ERR_OK || (int)($_FILES['customer_reviews']['size'][$i] ?? 0) > 8 * 1024 * 1024) {
        $failed++;
        continue;
      }

      $tmp = (string)($_FILES['customer_reviews']['tmp_name'][$i] ?? '');
      if (!is_uploaded_file($tmp)) {
        $failed++;
        continue;
      }
      $imageInfo = @getimagesize($tmp);
      $mime = (string)($imageInfo['mime'] ?? '');
      if (!isset($extensions[$mime])) {
        $failed++;
        continue;
      }

      $filename = 'customer-review-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $extensions[$mime];
      if (move_uploaded_file($tmp, __DIR__ . '/../img/' . $filename)) {
        $uploaded++;
      } else {
        $failed++;
      }
    }

    if ($uploaded > 0) {
      $message = 'Uploaded ' . $uploaded . ' review screenshot' . ($uploaded === 1 ? '' : 's') . '. They now appear on the landing page.';
    }
    if ($failed > 0) {
      $error = $failed . ' file' . ($failed === 1 ? '' : 's') . ' could not be uploaded. Use JPG, PNG, WebP, or GIF files under 8 MB each.';
    } elseif ($uploaded === 0) {
      $error = 'Choose one or more review screenshots to upload.';
    }
  }
}

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_customer_review'])) {
    if (!csrf_ok()) {
      $error = 'Invalid form token. Please reload and try again.';
    } else {
      $filename = basename((string)($_POST['review_file'] ?? ''));
      $path = __DIR__ . '/../img/' . $filename;
      if (!preg_match('/^customer-review-\d{8}-\d{6}-[a-f0-9]{12}\.(jpg|png|webp|gif)$/i', $filename)) {
        $error = 'Invalid review screenshot.';
      } elseif (!is_file($path) || !unlink($path)) {
        $error = 'Could not remove that review screenshot.';
      } else {
        $message = 'Review screenshot removed.';
      }
    }
    if ($error !== '') hpl_flash('error', $error);
    elseif ($message !== '') hpl_flash('success', $message);
    hpl_redirect_after_post('customer-voices');
  }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    if (!csrf_ok()) {
        $error = 'Invalid form token. Please reload and try again.';
    } elseif (!$connection) {
        $error = 'Database unavailable. Image was not uploaded.';
    } else {
        $key = (string)($_POST['upload_image'] ?? '');
        $slot = null;
        foreach (array_merge($imageSlots, $customerVoiceImageSlots) as $candidate) {
            if ($candidate['key'] === $key) { $slot = $candidate; break; }
        }
        if (!$slot) {
            $error = 'Unknown image slot.';
        } elseif (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Choose a file to upload first.';
        } elseif ($_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload failed (error code ' . (int)$_FILES[$key]['error'] . ').';
        } elseif ($_FILES[$key]['size'] > 8 * 1024 * 1024) {
            $error = 'File is larger than 8 MB. Resize or compress it first.';
        } else {
            $tmp = $_FILES[$key]['tmp_name'];
            if (!is_uploaded_file($tmp)) {
                $error = 'Invalid upload.';
            } else {
                $image = @imagecreatefromstring((string)file_get_contents($tmp));
                if (!$image) {
                    $error = 'File is not a supported image (JPG, PNG, WebP, GIF).';
                } else {
                    $ext = strtolower(pathinfo($slot['file'], PATHINFO_EXTENSION));
                    $targetPath = __DIR__ . '/../img/' . $slot['file'];
                    $ok = ($ext === 'png' || $ext === 'gif')
                        ? imagepng($image, $targetPath)
                        : imagejpeg($image, $targetPath, 88);
                    imagedestroy($image);
                    if (!$ok) {
                        $error = 'Could not save the image. Make sure the img/ folder is writable.';
                    } else {
                        $message = 'Uploaded "' . h($slot['file']) . '" successfully. The site picks it up automatically.';
                    }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_video'])) {
    if (!csrf_ok()) {
        $error = 'Invalid form token. Please reload and try again.';
    } elseif (!$connection) {
        $error = 'Database unavailable. Video was not uploaded.';
    } else {
        $key = (string)($_POST['upload_video'] ?? '');
        $slot = null;
        foreach ($videoSlots as $candidate) {
            if ($candidate['key'] === $key) { $slot = $candidate; break; }
        }
        if (!$slot) {
            $error = 'Unknown video slot.';
        } elseif (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Choose a video file to upload first.';
        } elseif ($_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload failed (error code ' . (int)$_FILES[$key]['error'] . ').';
        } elseif ($_FILES[$key]['size'] > 190 * 1024 * 1024) {
            $error = 'Video is larger than 190 MB. Compress it first.';
        } else {
            $tmp = $_FILES[$key]['tmp_name'];
            $ext = strtolower(pathinfo((string)($_FILES[$key]['name'] ?? ''), PATHINFO_EXTENSION));
            if (!is_uploaded_file($tmp)) {
                $error = 'Invalid upload.';
            } elseif (!in_array($ext, ['mp4', 'm4v', 'webm'], true)) {
                $error = 'Only MP4, M4V or WebM video files are supported.';
            } else {
                $dir = __DIR__ . '/../' . $slot['dir'];
                if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                    $error = 'Could not create the ' . h($slot['dir']) . ' folder. Create it and make it writable.';
                } else {
                    if (move_uploaded_file($tmp, $dir . '/' . $slot['file'])) {
                        $message = 'Video uploaded as "' . h($slot['file']) . '". It is now used automatically on the site.';
                        if ($slot['setting'] !== '') {
                            $upd = $connection->prepare('INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
                            $upd->bind_param('ss', $slot['key'], $slot['setting']);
                            $upd->execute();
                            $upd->close();
                        }
                    } else {
                        $error = 'Could not save the video. Make sure the ' . h($slot['dir']) . ' folder is writable.';
                    }
                }
            }
        }
    }
}

$leads = [];
$totalLeads = 0;
$todayLeads = 0;
$newLeads = 0;
$notifications = [];
if ($connection) {
    $totalLeads = (int)$connection->query('SELECT COUNT(*) AS c FROM leads')->fetch_assoc()['c'];
    $todayLeads = (int)$connection->query('SELECT COUNT(*) AS c FROM leads WHERE DATE(submitted_at) = CURDATE()')->fetch_assoc()['c'];
    $newLeads = (int)$connection->query('SELECT COUNT(*) AS c FROM leads WHERE status = \'new\'')->fetch_assoc()['c'];
    if ($newLeads > 0) {
        $notifResult = $connection->query('SELECT id, name, submitted_at FROM leads WHERE status = \'new\' ORDER BY submitted_at DESC LIMIT 10');
        if ($notifResult) {
            while ($row = $notifResult->fetch_assoc()) {
                $notifications[] = $row;
            }
        }
    }
    $leadSelect = 'SELECT id, `' . implode('`, `', array_keys($leadColumns)) . '` FROM leads ORDER BY id DESC LIMIT 100';
    $leadResult = $connection->query($leadSelect);
    if ($leadResult) {
        while ($row = $leadResult->fetch_assoc()) {
            $leads[] = $row;
        }
    }
}

$analytics = [];
$totalPageViews = 0;
$totalCtaClicks = 0;
$totalVideoPlays = 0;
$totalScrollEvents = 0;
$uniqueSessions = 0;
$analyticsTableExists = false;
if ($connection) {
    $checkTable = $connection->query("SHOW TABLES LIKE 'analytics'");
    $analyticsTableExists = $checkTable && $checkTable->num_rows > 0;

    if ($analyticsTableExists) {
        $totalPageViews = (int)$connection->query('SELECT COUNT(*) AS c FROM analytics WHERE event_type = \'page_view\'')->fetch_assoc()['c'];
        $totalCtaClicks = (int)$connection->query('SELECT COUNT(*) AS c FROM analytics WHERE event_type = \'cta_click\'')->fetch_assoc()['c'];
        $totalVideoPlays = (int)$connection->query('SELECT COUNT(*) AS c FROM analytics WHERE event_type = \'video_play\'')->fetch_assoc()['c'];
        $totalScrollEvents = (int)$connection->query('SELECT COUNT(*) AS c FROM analytics WHERE event_type = \'scroll\'')->fetch_assoc()['c'];
        $uniqueSessions = (int)$connection->query('SELECT COUNT(DISTINCT session_id) AS c FROM analytics')->fetch_assoc()['c'];

        $analyticsResult = $connection->query('SELECT event_type, COUNT(*) as count, DATE(created_at) as date FROM analytics WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY event_type, DATE(created_at) ORDER BY date DESC, event_type');
        if ($analyticsResult) {
            while ($row = $analyticsResult->fetch_assoc()) {
                $analytics[] = $row;
            }
        }
    }
}

/* All POST handling is done by this point. Hand the result to the redirect so
   a refresh cannot repeat the action. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($message !== '' || $error !== '')) {
    hpl_flash($error !== '' ? 'error' : 'ok', $error !== '' ? $error : $message);
    hpl_redirect_after_post($activeTab);
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin | HPL Landing Page</title>
  <link rel="icon" type="image/png" sizes="16x16" href="../img/favicon-16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon-32.png">
  <link rel="icon" type="image/png" sizes="192x192" href="../img/favicon-192.png">
  <link rel="apple-touch-icon" href="../img/favicon-180.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-primary: #eef0f4;
      --bg-secondary: #fff;
      --text-primary: #182038;
      --text-secondary: #8b93a5;
      --border-color: #cfd4dc;
      --accent: #d4a52c;
      --accent-hover: #f4ca5b;
      --nav-bg: #111a38;
      --nav-text: #d9deea;
    }
    .dark-mode {
      --bg-primary: #0f1419;
      --bg-secondary: #1a1f2e;
      --text-primary: #e8eaed;
      --text-secondary: #9aa0a6;
      --border-color: #3c4043;
      --accent: #d4a52c;
      --accent-hover: #f4ca5b;
      --nav-bg: #0a0e14;
      --nav-text: #e8eaed;
    }
    * { box-sizing:border-box; }
    body { margin:0; background:var(--bg-primary); color:var(--text-primary); 'Manrope','Plus Jakarta Sans',sans-serif; transition:background 0.3s,color 0.3s; }
    .topbar { background:var(--nav-bg); color:#fff; padding:16px 24px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .topbar .brand { font-family:'Manrope','Plus Jakarta Sans',sans-serif; font-size:18px; font-weight:700; }
    .topbar a { color:var(--nav-text); font-size:13px; text-decoration:none; margin-left:18px; }
    .topbar a:hover { color:var(--accent-hover); }
    .wrap { margin:0; max-width:none; padding:30px 24px 60px; width:100%; }
    .notice { background:#eaf5ee; border-left:4px solid #2e8b57; color:#23402f; font-size:14px; margin:0 0 18px; padding:12px 16px; }
    .error { background:#fbeeec; border-left:4px solid #c0392b; color:#7a2c25; font-size:14px; margin:0 0 18px; padding:12px 16px; }
    .stats { display:grid; gap:14px; grid-template-columns:repeat(3,1fr); margin:0 0 22px; }
    .stats.five { grid-template-columns:repeat(5,1fr); }
    .stat { background:var(--bg-secondary); border-radius:10px; box-shadow:0 1px 3px rgba(17,26,56,.08); padding:18px 20px; }
    .stat .num { color:var(--text-primary); font-family:'Manrope','Plus Jakarta Sans',sans-serif; font-size:30px; font-weight:700; line-height:1; }
    .stat .lbl { color:var(--text-secondary); font-size:12px; font-weight:700; letter-spacing:.05em; margin-top:7px; text-transform:uppercase; }
    .card { background:var(--bg-secondary); border-radius:10px; box-shadow:0 1px 3px rgba(17,26,56,.08); margin-bottom:22px; padding:24px; }
    .card h2 {font-weight:700;  font-family:'Manrope','Plus Jakarta Sans',sans-serif; font-size:18px; margin:0 0 16px; color:var(--text-primary); }
    .card-head { align-items:center; display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px; }
    .card-head h2 { margin:0; }
    .grid { display:grid; gap:14px; grid-template-columns:1fr 1fr; }
    .grid .full { grid-column:1/-1; }
    /* Section headings inside the Images card, so upload slots are grouped by
       the part of the page the photo belongs to. */
    .img-section { align-items:center; border-bottom:1px solid var(--border-color); color:var(--text-primary); display:flex; font-family:'Manrope','Plus Jakarta Sans',sans-serif; font-size:13px; font-weight:700; gap:8px; letter-spacing:.04em; margin:26px 0 14px; padding-bottom:8px; text-transform:uppercase; }
    .img-section:first-of-type { margin-top:0; }
    .img-section-count { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:999px; color:var(--text-secondary); font-size:10px; font-weight:700; letter-spacing:0; padding:2px 8px; text-transform:none; }
    label { display:block; font-size:12px; font-weight:700; margin-bottom:4px; color:var(--text-primary); }
    .field-note { background:#f4f6fb; border-left:3px solid var(--accent,#2c3e6e); color:var(--text-muted,#5a6478); font-size:12px; line-height:1.55; margin:0; padding:10px 12px; }
    .hint { display:block; font-size:11px; color:var(--text-secondary); margin-top:3px; }
    .link-btn.is-disabled { opacity:.55; cursor:not-allowed; box-shadow:none; }
    input[type=text], input[type=password], textarea { border:1px solid var(--border-color); border-radius:6px; font:15px/1.5 'Manrope','Plus Jakarta Sans',sans-serif; padding:10px 12px; width:100%; background:var(--bg-secondary); color:var(--text-primary); }
    textarea { min-height:90px; resize:vertical; }
    .btn { background:var(--accent); border:0; border-radius:6px; color:#111a38; cursor:pointer; font-size:13px; font-weight:700; padding:13px 30px; text-transform:uppercase; }
    .btn:hover { background:var(--accent-hover); }
    .btn-row { margin-top:18px; }
    .link-btn { background:var(--bg-primary); border:1px solid var(--border-color); border-radius:6px; color:#2c3e6e; cursor:pointer; display:inline-block; font-size:12px; font-weight:700; padding:9px 14px; text-decoration:none; }
    .link-btn:hover { background:var(--bg-secondary); }
    .link-btn.danger { color:#c0392b; }
    .link-btn.wa { background:#25d366; border-color:#1da851; color:#fff; }
    table.leads { border-collapse:collapse; font-size:14px; min-width:620px; width:100%; }
    .table-scroll { -webkit-overflow-scrolling:touch; overflow-x:auto; }
    table.leads th, table.leads td { border-bottom:1px solid var(--border-color); padding:10px; text-align:left; vertical-align:top; }
    table.leads th { color:var(--text-primary); font-size:12px; text-transform:uppercase; }
    .img-thumb { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:6px; display:block; height:70px; margin:6px 0 10px; object-fit:cover; width:120px; }
    .img-empty { border:1px dashed var(--border-color); border-radius:6px; color:var(--text-secondary); font-size:12px; margin:6px 0 10px; padding:24px 12px; text-align:center; }
    .card p.hint { color:var(--text-secondary); font-size:13px; line-height:1.5; margin:0 0 16px; }
    table.leads .muted { color:var(--text-secondary); }
    table.leads td .ops { align-items:center; display:flex; flex-wrap:nowrap; gap:6px; }
    table.leads td .ops form { margin:0; }
    table.leads td .ops .link-btn { white-space:nowrap; }
    .badge { border-radius:20px; display:inline-block; font-size:11px; font-weight:700; padding:3px 10px; text-transform:uppercase; }
    .badge.new { background:#fff3d6; color:#8a6200; }
    .badge.contacted { background:#eaf5ee; color:#2e8b57; }
    .lead-name { color:#2c3e6e; cursor:pointer; text-decoration:underline; }
    .lead-name:hover { color:var(--accent); }
    .modal-overlay { background:rgba(17,26,56,0.6); display:none; inset:0; position:fixed; z-index:1000; }
    .modal { background:var(--bg-secondary); border-radius:10px; box-shadow:0 4px 20px rgba(17,26,56,0.2); left:50%; max-height:90vh; max-width:600px; overflow-y:auto; padding:30px; position:fixed; top:50%; transform:translate(-50%,-50%); width:90%; }
    .modal h2 {font-weight:700;  font-family:'Manrope','Plus Jakarta Sans',sans-serif; font-size:20px; margin:0 0 20px; color:var(--text-primary); }
    .modal-row { margin-bottom:16px; }
    .modal-label { color:var(--text-primary); font-size:12px; font-weight:700; text-transform:uppercase; }
    .modal-value { color:var(--text-primary); font-size:14px; line-height:1.6; margin-top:4px; word-wrap:break-word; }
    .modal-close { background:var(--bg-primary); border:1px solid var(--border-color); border-radius:6px; color:#2c3e6e; cursor:pointer; font-size:13px; font-weight:700; padding:10px 20px; text-transform:uppercase; }
    .modal-close:hover { background:var(--bg-secondary); }
    .dark-mode-toggle { background:none; border:0; color:var(--nav-text); cursor:pointer; font-size:18px; margin-left:18px; }
    .dark-mode-toggle:hover { color:var(--accent-hover); }
    .notification-bell { background:none; border:0; color:var(--nav-text); cursor:pointer; font-size:18px; margin-left:18px; position:relative; }
    .notification-bell:hover { color:var(--accent-hover); }
    .notification-badge { background:#c0392b; border-radius:50%; color:#fff; font-size:10px; font-weight:700; height:18px; line-height:18px; position:absolute; right:-8px; text-align:center; top:-6px; width:18px; }
    .notification-dropdown { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:8px; box-shadow:0 4px 20px rgba(17,26,56,0.15); display:none; max-height:400px; overflow-y:auto; position:absolute; right:24px; top:60px; width:320px; z-index:100; }
    .notification-dropdown.show { display:block; }
    .notification-item { border-bottom:1px solid var(--border-color); padding:12px 16px; }
    .notification-item:last-child { border-bottom:none; }
    .notification-item .name { color:var(--text-primary); font-weight:600; font-size:14px; }
    .notification-item .time { color:var(--text-secondary); font-size:12px; margin-top:4px; }
    .notification-item .view-btn { background:var(--accent); border:0; border-radius:4px; color:#111a38; cursor:pointer; font-size:11px; font-weight:700; margin-top:8px; padding:6px 12px; text-transform:uppercase; }
    .notification-item .view-btn:hover { background:var(--accent-hover); }
    .notification-empty { color:var(--text-secondary); font-size:13px; padding:20px 16px; text-align:center; }
    .tabs { border-bottom:2px solid var(--border-color); display:flex; gap:6px; margin:0 0 22px; }
    .tab-btn { background:none; border:0; border-bottom:3px solid transparent; color:var(--text-secondary); cursor:pointer; font-size:13px; font-weight:700; letter-spacing:.06em; margin-bottom:-2px; padding:12px 18px; text-transform:uppercase; }
    .tab-btn:hover { color:var(--text-primary); }
    .tab-btn.active { border-bottom-color:var(--accent); color:var(--text-primary); }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }
    @media (max-width:640px) {
      .stats { grid-template-columns:1fr; }
      .grid { grid-template-columns:1fr; }
      .tabs { flex-wrap:wrap; }
      .tab-btn { padding:10px 12px; }
      .wrap { padding:22px 12px 50px; }
      .card { padding:18px; }
    }
  </style>
</head>
<body>
  <div class="topbar">
    <span class="brand">HPL GOLD — Admin</span>
    <span class="links">
      <button class="dark-mode-toggle" id="darkModeToggle" type="button">🌙</button>
      <button class="notification-bell" id="notificationBell" type="button">
        🔔
        <?php if ($newLeads > 0): ?>
          <span class="notification-badge"><?= $newLeads > 9 ? '9+' : $newLeads ?></span>
        <?php endif; ?>
      </button>
      <div class="notification-dropdown" id="notificationDropdown">
        <?php if (empty($notifications)): ?>
          <div class="notification-empty">No new notifications</div>
        <?php else: ?>
          <?php foreach ($notifications as $notif): ?>
            <div class="notification-item">
              <div class="name"><?= h($notif['name']) ?></div>
              <div class="time"><?= h(date('M j, Y H:i', strtotime($notif['submitted_at']))) ?></div>
              <button class="view-btn" onclick="switchTab('leads')">View Lead</button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <a href="../index.php">View site</a><a href="logout.php">Log out</a>
    </span>
  </div>
  <div class="wrap">
    <?php if ($message !== ''): ?><div class="notice"><?= $message ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error"><?= h($error) ?></div><?php endif; ?>

    <div class="tabs" role="tablist">
      <button class="tab-btn <?= $activeTab === 'leads' ? 'active' : '' ?>" type="button" role="tab" aria-selected="<?= $activeTab === 'leads' ? 'true' : 'false' ?>" data-tab="leads">Leads</button>
      <button class="tab-btn <?= $activeTab === 'analytics' ? 'active' : '' ?>" type="button" role="tab" aria-selected="<?= $activeTab === 'analytics' ? 'true' : 'false' ?>" data-tab="analytics">Analytics</button>
      <button class="tab-btn <?= $activeTab === 'settings' ? 'active' : '' ?>" type="button" role="tab" aria-selected="<?= $activeTab === 'settings' ? 'true' : 'false' ?>" data-tab="settings">Settings</button>
      <button class="tab-btn <?= $activeTab === 'images' ? 'active' : '' ?>" type="button" role="tab" aria-selected="<?= $activeTab === 'images' ? 'true' : 'false' ?>" data-tab="images">Images</button>
      <button class="tab-btn <?= $activeTab === 'customer-voices' ? 'active' : '' ?>" type="button" role="tab" aria-selected="<?= $activeTab === 'customer-voices' ? 'true' : 'false' ?>" data-tab="customer-voices">Customer voices</button>
    </div>

    <div class="tab-panel <?= $activeTab === 'leads' ? 'active' : '' ?>" id="panel-leads" role="tabpanel">
    <div class="stats">
      <div class="stat"><div class="num"><?= $totalLeads ?></div><div class="lbl">Total leads</div></div>
      <div class="stat"><div class="num"><?= $newLeads ?></div><div class="lbl">Uncontacted</div></div>
      <div class="stat"><div class="num"><?= $todayLeads ?></div><div class="lbl">Today</div></div>
    </div>

    <div class="card">
      <div class="card-head">
        <h2>Leads</h2>
        <a class="link-btn" href="?export=csv">Export CSV</a>
      </div>
      <?php if (!$connection): ?>
        <p style="color:#8b93a5;font-size:14px">Database unavailable.</p>
      <?php elseif (empty($leads)): ?>
        <p style="color:#8b93a5;font-size:14px">No submissions yet. Submit one through the site form at <b>index.php#book</b>.</p>
      <?php else: ?>
        <div class="table-scroll">
        <table class="leads">
          <thead><tr><th>Submitted</th><th>Name</th><th>WhatsApp</th><th>Location</th><th>Looking for</th><th>Looking to find</th><th>Experience</th><th>Customer type</th><th>Timing</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($leads as $lead): ?>
<?php
  // One attribute carries the whole record so the detail modal stays in step with
  // the column list above without a second set of data-* pairs to maintain.
  $leadDetail = $lead;
  $leadDetail['submitted_at'] = date('M j, Y H:i', strtotime((string)$lead['submitted_at']));
  $leadDetail['needs_advice'] = ((int)($lead['needs_advice'] ?? 0)) ? 'Yes' : 'No';
?>
            <tr>
              <td class="muted"><?= h($leadDetail['submitted_at']) ?></td>
              <td><span class="lead-name" data-lead="<?= htmlspecialchars(json_encode($leadDetail), ENT_QUOTES) ?>"><?= h($lead['name']) ?></span></td>
              <td><?= h($lead['phone']) ?></td>
              <td class="muted"><?= h(trim(($lead['city'] ?? '') . ($lead['city'] !== '' && $lead['country'] !== '' ? ', ' : '') . ($lead['country'] ?? ''))) ?></td>
              <td class="muted"><?= h($lead['looking_for'] ?? '') ?></td>
              <td class="muted"><?= h($lead['finding'] ?? '') ?></td>
              <td class="muted"><?= h($lead['experience'] ?? '') ?></td>
              <td class="muted"><?= h($lead['customer_type'] ?? '') ?></td>
              <td class="muted"><?= h($lead['timing'] ?? '') ?></td>
              <td><span class="badge <?= $lead['status'] === 'contacted' ? 'contacted' : 'new' ?>"><?= h(ucfirst((string)$lead['status'])) ?></span></td>
              <td>
                <div class="ops">
<?php
/* This one messages the lead, not the company, so it uses the lead's own phone
   and hpl_wa_url's company-number fallback would be wrong here. hpl_wa_digits
   is reused purely to strip separators and reject junk. Nigeria stays the
   assumed country code for these, matching the previous behaviour: leads are
   captured outside Zambia often enough that assuming 260 silently misdirects
   a returned call to the wrong country. */
$waDigits = hpl_wa_digits((string)($lead['phone'] ?? ''), '234');
$waText = str_replace('{name}', (string)($lead['name'] ?? ''), (string)($settings['whatsapp_msg'] ?? ''));
$waUrl = $waDigits !== '' ? 'https://wa.me/' . $waDigits . '?text=' . rawurlencode($waText) : '';
?>
<?php if ($waUrl !== '') { ?>
                  <a class="link-btn wa" href="<?= h($waUrl) ?>" target="_blank" rel="noopener">WhatsApp</a>
<?php } else { ?>
                  <span class="link-btn wa is-disabled" title="No usable phone number for this lead">No number</span>
<?php } ?>
                  <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="leads">
                    <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
                    <button class="link-btn" type="submit" name="lead_action" value="toggle"><?= $lead['status'] === 'contacted' ? 'Mark new' : 'Mark contacted' ?></button>
                  </form>
                  <form method="post" onsubmit="return confirm('Delete this lead?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="leads">
                    <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
                    <button class="link-btn danger" type="submit" name="lead_action" value="delete">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      <?php endif; ?>
    </div>
    </div>

    <div class="tab-panel <?= $activeTab === 'analytics' ? 'active' : '' ?>" id="panel-analytics" role="tabpanel">
    <?php if (!$analyticsTableExists): ?>
      <div class="card">
        <h2>Analytics Setup Required</h2>
        <p style="color:#8b93a5;font-size:14px">The analytics table has not been created yet. Run the following SQL in phpMyAdmin or MySQL to enable analytics:</p>
        <pre style="background:#f4f4f4;border:1px solid #ddd;border-radius:6px;font-size:12px;margin:16px 0;overflow-x:auto;padding:12px">CREATE TABLE IF NOT EXISTS analytics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_type VARCHAR(50) NOT NULL,
  event_data TEXT,
  session_id VARCHAR(100),
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_event_type (event_type),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</pre>
      </div>
    <?php else: ?>
      <div class="stats five">
        <div class="stat"><div class="num"><?= $totalPageViews ?></div><div class="lbl">Page Views</div></div>
        <div class="stat"><div class="num"><?= $uniqueSessions ?></div><div class="lbl">Unique Sessions</div></div>
        <div class="stat"><div class="num"><?= $totalCtaClicks ?></div><div class="lbl">CTA Clicks</div></div>
        <div class="stat"><div class="num"><?= $totalVideoPlays ?></div><div class="lbl">Video Plays</div></div>
        <div class="stat"><div class="num"><?= $totalScrollEvents ?></div><div class="lbl">Scroll Events</div></div>
      </div>

      <div class="card">
        <h2>Recent Activity (Last 7 Days)</h2>
        <?php if (empty($analytics)): ?>
          <p style="color:#8b93a5;font-size:14px">No analytics data yet. Visit the frontend site to start tracking.</p>
        <?php else: ?>
          <div class="table-scroll">
          <table class="leads">
            <thead><tr><th>Date</th><th>Event Type</th><th>Count</th></tr></thead>
            <tbody>
            <?php foreach ($analytics as $row): ?>
              <tr>
                <td class="muted"><?= h($row['date']) ?></td>
                <td><?= h($row['event_type']) ?></td>
                <td><?= (int)$row['count'] ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    </div>

    <div class="tab-panel <?= $activeTab === 'settings' ? 'active' : '' ?>" id="panel-settings" role="tabpanel">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="tab" value="settings">
<?php
$groups = [];
foreach ($fields as $field) {
    $groups[$field['group']][] = $field;
}
foreach ($groups as $title => $group):
?>
      <div class="card">
        <h2><?= h($title) ?></h2>
        <div class="grid">
<?php foreach ($group as $field): ?>
          <div class="<?= in_array($field['type'], ['textarea', 'select', 'note'], true) ? 'full' : '' ?>">
<?php if ($field['type'] === 'note'): ?>
            <p class="field-note"><?= h($field['label']) ?></p>
<?php else: ?>
            <label for="f_<?= h($field['key']) ?>"><?= h($field['label']) ?></label>
<?php endif; ?>
<?php if ($field['type'] === 'note'): ?>
            <!-- guidance only: rendered above, no control -->
<?php elseif ($field['type'] === 'textarea'): ?>
            <textarea id="f_<?= h($field['key']) ?>" name="<?= h($field['key']) ?>"><?= h($settings[$field['key']]) ?></textarea>
<?php elseif ($field['type'] === 'number'): ?>
            <input type="number" id="f_<?= h($field['key']) ?>" name="<?= h($field['key']) ?>"
                   value="<?= h($settings[$field['key']]) ?>"
                   min="<?= h((string)($field['min'] ?? 0)) ?>"
                   max="<?= h((string)($field['max'] ?? 9999)) ?>" step="1"
                   inputmode="numeric">
<?php elseif ($field['type'] === 'select'): ?>
            <select id="f_<?= h($field['key']) ?>" name="<?= h($field['key']) ?>">
<?php foreach (($field['options'] ?? []) as $ov => $ol): ?>
              <option value="<?= h($ov) ?>"<?= (string)($settings[$field['key']] ?? '') === (string)$ov ? ' selected' : '' ?>><?= h($ol) ?></option>
<?php endforeach; ?>
            </select>
<?php else: ?>
            <input type="text" id="f_<?= h($field['key']) ?>" name="<?= h($field['key']) ?>" value="<?= h($settings[$field['key']]) ?>">
<?php endif; ?>
          </div>
<?php endforeach; ?>
        </div>
      </div>
<?php endforeach; ?>
      <div class="card">
        <div class="btn-row">
          <button class="btn" type="submit" name="save_content" value="1">Save all content</button>
        </div>
      </div>
    </form>

    <div class="card">
      <h2>Change password</h2>
      <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="tab" value="settings">
        <div class="grid">
          <div>
            <label for="new_password">New password</label>
            <input type="password" id="new_password" name="new_password" required>
          </div>
          <div>
            <label for="confirm_password">Confirm new password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
          </div>
        </div>
        <div class="btn-row">
          <button class="btn" type="submit" name="change_password" value="1">Update password</button>
        </div>
      </form>
    </div>
    </div>

    <div class="tab-panel <?= $activeTab === 'customer-voices' ? 'active' : '' ?>" id="panel-customer-voices" role="tabpanel">
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="tab" value="customer-voices">
        <div class="card">
          <h2>Customer voices screenshots</h2>
          <p class="hint">Upload a JPG, PNG, WebP, or GIF for each social post. The matching card updates as soon as the upload completes.</p>
          <div class="grid">
<?php foreach ($customerVoiceImageSlots as $slot): ?>
            <div>
              <label for="up_<?= h($slot['key']) ?>"><?= h($slot['label']) ?></label>
<?php if (file_exists(__DIR__ . '/../img/' . $slot['file'])): ?>
              <img class="img-thumb" src="<?= hpl_admin_img_url($slot['file']) ?>" alt="<?= h($slot['label']) ?>">
<?php else: ?>
              <div class="img-empty">No screenshot uploaded yet</div>
<?php endif; ?>
              <input type="file" id="up_<?= h($slot['key']) ?>" name="<?= h($slot['key']) ?>" accept=".jpg,.jpeg,.png,.webp,.gif">
              <div class="btn-row">
                <button class="btn" type="submit" name="upload_image" value="<?= h($slot['key']) ?>">Upload <?= h($slot['label']) ?></button>
              </div>
            </div>
<?php endforeach; ?>
          </div>
        </div>
      </form>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="tab" value="customer-voices">
        <div class="card">
          <h2>Additional review screenshots</h2>
          <p class="hint">Upload multiple genuine customer review screenshots. JPG, PNG, WebP, or GIF; up to 8 MB each. Uploaded screenshots appear below “Real equipment. Real ground.”</p>
          <label for="customerReviewsUpload">Review screenshots</label>
          <input type="file" id="customerReviewsUpload" name="customer_reviews[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
          <div class="btn-row">
            <button class="btn" type="submit" name="upload_customer_reviews" value="1">Upload review screenshots</button>
          </div>
        </div>
      </form>
<?php
  $uploadedCustomerReviews = glob(__DIR__ . '/../img/customer-review-*') ?: [];
  sort($uploadedCustomerReviews, SORT_NATURAL | SORT_FLAG_CASE);
?>
<?php if ($uploadedCustomerReviews): ?>
      <div class="card">
        <h2>Uploaded review screenshots</h2>
        <div class="grid">
<?php foreach ($uploadedCustomerReviews as $reviewFile): $reviewFilename = basename($reviewFile); ?>
          <div>
            <img class="img-thumb" src="<?= hpl_admin_img_url($reviewFilename) ?>" alt="Uploaded customer review screenshot">
            <span><?= h($reviewFilename) ?></span>
            <form method="post" onsubmit="return confirm('Remove this review screenshot?');">
              <?= csrf_field() ?>
              <input type="hidden" name="tab" value="customer-voices">
              <input type="hidden" name="review_file" value="<?= h($reviewFilename) ?>">
              <button class="btn" type="submit" name="delete_customer_review" value="1">Remove</button>
            </form>
          </div>
<?php endforeach; ?>
        </div>
      </div>
<?php endif; ?>
    </div>

    <div class="tab-panel <?= $activeTab === 'images' ? 'active' : '' ?>" id="panel-images" role="tabpanel">
      <div class="card">
        <h2>Images</h2>
        <p class="hint">Upload a JPG, PNG, WebP, or GIF for each slot. Photos are stored directly in <b>img/</b> and the page updates automatically. Slots are grouped by the section of the page each photo appears in.</p>
<?php /* Each section is posted as its own form rather than all sharing one.
       PHP only accepts max_file_uploads file parts in a single request, which
       is 20 on a default install, and this tab holds 28 image inputs plus 16
       video inputs. Under one form the parts past the limit were dropped
       before any handler ran, so those uploads silently did nothing. The
       largest section is 6 inputs, well inside the limit. */ ?>
<?php foreach ($imageSlotsBySection as $sectionName => $sectionSlots): ?>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="tab" value="images">
          <h3 class="img-section"><?= h($sectionName) ?> <span class="img-section-count"><?= count($sectionSlots) ?> slot<?= count($sectionSlots) === 1 ? '' : 's' ?></span></h3>
          <div class="grid">
<?php foreach ($sectionSlots as $slot): ?>
            <div>
              <label for="up_<?= h($slot['key']) ?>"><?= h($slot['label']) ?></label>
<?php if (file_exists(__DIR__ . '/../img/' . $slot['file'])): ?>
              <img class="img-thumb" src="<?= hpl_admin_img_url($slot['file']) ?>" alt="<?= h($slot['file']) ?>">
<?php else: ?>
              <div class="img-empty">No image yet — page uses the CSS art fallback</div>
<?php endif; ?>
              <input type="file" id="up_<?= h($slot['key']) ?>" name="<?= h($slot['key']) ?>" accept=".jpg,.jpeg,.png,.webp,.gif">
              <div class="btn-row">
                <button class="btn" type="submit" name="upload_image" value="<?= h($slot['key']) ?>">Upload <?= h($slot['file']) ?></button>
              </div>
            </div>
<?php endforeach; ?>
          </div>
        </form>
<?php endforeach; ?>
      </div>
      <div class="card">
        <h2>Videos</h2>
        <p class="hint">Upload an <b>MP4</b>, <b>M4V</b> or <b>WebM</b> for each player (up to 190 MB). The story videos are stored in <b>uploads/proof/</b> and the page switches over automatically. Compressing 9:16 phone video to 720x1280 keeps it sharp and small enough for visitors on mobile data.</p>
<?php /* Chunked for the same reason as the images above. Eight per form keeps
       clear of the limit even on a host that lowers it below the 16 these
       slots would otherwise occupy in a single request. */ ?>
<?php foreach (array_chunk($videoSlots, 8) as $videoChunk): ?>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="tab" value="images">
<?php foreach ($videoChunk as $slot): ?>
          <label for="upv_<?= h($slot['key']) ?>"><?= h($slot['label']) ?></label>
<?php $vpath = __DIR__ . '/../' . $slot['dir'] . $slot['file']; if (file_exists($vpath)): ?>
          <p class="hint" style="margin:6px 0 10px">Video uploaded: <?= h($slot['file']) ?> (<?= number_format(filesize($vpath) / 1048576, 1) ?> MB)</p>
<?php else: ?>
          <p class="hint" style="margin:6px 0 10px">No video uploaded yet — this player will be skipped until you add one.</p>
<?php endif; ?>
          <input type="file" id="upv_<?= h($slot['key']) ?>" name="<?= h($slot['key']) ?>" accept=".mp4,.m4v,.webm,video/mp4,video/webm">
          <div class="btn-row">
            <button class="btn" type="submit" name="upload_video" value="<?= h($slot['key']) ?>">Upload video</button>
          </div>
<?php endforeach; ?>
        </form>
<?php endforeach; ?>
      </div>
  </div>

  <script>
    window.HPL_LEAD_LABELS = <?= json_encode($leadColumns) ?>;
  </script>
  <div class="modal-overlay" id="leadModalOverlay" onclick="hideLeadModal()">
    <div class="modal" onclick="event.stopPropagation()">
      <h2 id="modalLeadName">Lead Details</h2>
      <div id="modalLeadBody"></div>
      <div style="margin-top:24px;text-align:right">
        <button class="modal-close" onclick="hideLeadModal()">Close</button>
      </div>
    </div>
  </div>

  <script>
    // Renders straight from the shared column list, so a new field shows up in the
    // detail view as soon as it is added to $leadColumns.
    function showLeadModal(element) {
      var lead = {};
      try {
        lead = JSON.parse(element.getAttribute('data-lead')) || {};
      } catch (err) {
        lead = {};
      }

      var labels = window.HPL_LEAD_LABELS || {};
      var body = document.getElementById('modalLeadBody');
      body.textContent = '';

      Object.keys(labels).forEach(function (key) {
        if (key === 'name') return;
        var value = lead[key];
        if (value === null || value === undefined || value === '') return;

        var row = document.createElement('div');
        row.className = 'modal-row';
        var label = document.createElement('div');
        label.className = 'modal-label';
        label.textContent = labels[key];
        var text = document.createElement('div');
        text.className = 'modal-value';
        text.textContent = String(value);
        row.appendChild(label);
        row.appendChild(text);
        body.appendChild(row);
      });

      document.getElementById('modalLeadName').textContent = lead.name || 'Lead Details';
      document.getElementById('leadModalOverlay').style.display = 'block';
    }

    function hideLeadModal() {
      document.getElementById('leadModalOverlay').style.display = 'none';
    }

    function switchTab(tabName) {
      var tabButtons = document.querySelectorAll('.tab-btn');
      var panels = document.querySelectorAll('.tab-panel');
      for (var i = 0; i < panels.length; i++) {
        panels[i].classList.toggle('active', panels[i].id === 'panel-' + tabName);
      }
      for (var i = 0; i < tabButtons.length; i++) {
        var active = tabButtons[i].dataset.tab === tabName;
        tabButtons[i].classList.toggle('active', active);
        tabButtons[i].setAttribute('aria-selected', active ? 'true' : 'false');
      }
      document.getElementById('notificationDropdown').classList.remove('show');
    }

    (function () {
      var tabButtons = document.querySelectorAll('.tab-btn');
      var panels = document.querySelectorAll('.tab-panel');
      function show(tab) {
        var i;
        for (i = 0; i < panels.length; i++) {
          panels[i].classList.toggle('active', panels[i].id === 'panel-' + tab);
        }
        for (i = 0; i < tabButtons.length; i++) {
          var active = tabButtons[i].dataset.tab === tab;
          tabButtons[i].classList.toggle('active', active);
          tabButtons[i].setAttribute('aria-selected', active ? 'true' : 'false');
        }
      }
      for (i = 0; i < tabButtons.length; i++) {
        tabButtons[i].addEventListener('click', function () {
          show(this.dataset.tab);
        });
      }

      var leadNames = document.querySelectorAll('.lead-name');
      for (i = 0; i < leadNames.length; i++) {
        leadNames[i].addEventListener('click', function () {
          showLeadModal(this);
        });
      }

      var notificationBell = document.getElementById('notificationBell');
      var notificationDropdown = document.getElementById('notificationDropdown');
      if (notificationBell && notificationDropdown) {
        notificationBell.addEventListener('click', function(e) {
          e.stopPropagation();
          notificationDropdown.classList.toggle('show');
        });
        document.addEventListener('click', function() {
          notificationDropdown.classList.remove('show');
        });
        notificationDropdown.addEventListener('click', function(e) {
          e.stopPropagation();
        });
      }

      var darkModeToggle = document.getElementById('darkModeToggle');
      var darkMode = localStorage.getItem('hpl_admin_dark_mode') === 'true';
      if (darkMode) {
        document.body.classList.add('dark-mode');
        darkModeToggle.textContent = '☀️';
      }
      if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
          darkMode = !darkMode;
          document.body.classList.toggle('dark-mode', darkMode);
          localStorage.setItem('hpl_admin_dark_mode', darkMode);
          darkModeToggle.textContent = darkMode ? '☀️' : '🌙';
        });
      }
    })();
  </script>
</body>
</html>