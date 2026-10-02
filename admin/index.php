<?php
require __DIR__ . '/config.php';

$fields = [
    ['key' => 'topline',           'label' => 'Top banner text',            'type' => 'text',     'group' => 'Header'],
    ['key' => 'brand_name',        'label' => 'Brand name',                 'type' => 'text',     'group' => 'Header'],
    ['key' => 'brand_mark',        'label' => 'Brand mark (logo letter)',   'type' => 'text',     'group' => 'Header'],
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
      ['key' => 'video_poster',      'label' => 'Hero poster image path',     'type' => 'text',     'group' => 'Hero'],
    ['key' => 'hero_caption',      'label' => 'Small copy under video',     'type' => 'textarea', 'group' => 'Hero'],
    ['key' => 'cta_text',          'label' => 'Button text (all CTAs)',     'type' => 'text',     'group' => 'Hero'],
    ['key' => 'social_label',      'label' => 'Small label',                'type' => 'text',     'group' => 'Social proof'],
    ['key' => 'social_heading',    'label' => 'Heading',                    'type' => 'text',     'group' => 'Social proof'],
    ['key' => 'social_caption',    'label' => 'Image caption',              'type' => 'text',     'group' => 'Social proof'],
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
    ['key' => 'proof_layout', 'label' => 'Story layout', 'type' => 'select', 'group' => 'Proof videos', 'options' => ['ring' => 'Circle of videos (current)', 'coverflow' => '3D coverflow (cinematic)', 'strip' => 'Single video + scroll (previous)']],
    ['key' => 'proof_ring_r', 'label' => 'Carousel radius (px) - how far the off-screen players swing back', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_ring_size', 'label' => 'Story player width (px, before perspective). 405 shows as about 450px on screen', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_ring_shape', 'label' => 'Story player shape (width / height). 0.5625 = 9:16 vertical, which matches the phone footage. Use 1.78 for 16:9 wide', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_hint', 'label' => 'Hint text under the videos', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_hint_strip', 'label' => 'Hint text (circle layout off)', 'type' => 'text', 'group' => 'Proof videos'],
    ['key' => 'proof_embed_note', 'label' => 'Player embeds: paste the whole Bunny / MediaDelivery embed block into any Video slot above. A 16:9 player is letterboxed inside the card, and the sound button hides on those cards because a cross-origin player cannot be unmuted from the page.', 'type' => 'note', 'group' => 'Proof videos'],
    ['key' => 'proof_video_1', 'label' => 'Video 1 (upload below, or paste a Bunny / MediaDelivery embed code, a direct link, or a Drive ID)', 'type' => 'text', 'group' => 'Proof videos'],
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
    ['key' => 'faq1_q',            'label' => 'Question 1',                 'type' => 'text',     'group' => 'FAQ'],
    ['key' => 'faq1_a',            'label' => 'Answer 1 (one line per paragraph)', 'type' => 'textarea', 'group' => 'FAQ'],
    ['key' => 'faq2_q',            'label' => 'Question 2',                 'type' => 'text',     'group' => 'FAQ'],
    ['key' => 'faq2_a',            'label' => 'Answer 2',                   'type' => 'textarea', 'group' => 'FAQ'],
    ['key' => 'faq3_q',            'label' => 'Question 3',                 'type' => 'text',     'group' => 'FAQ'],
    ['key' => 'faq3_a',            'label' => 'Answer 3',                   'type' => 'textarea', 'group' => 'FAQ'],
    ['key' => 'faq4_q',            'label' => 'Question 4',                 'type' => 'text',     'group' => 'FAQ'],
    ['key' => 'faq4_a',            'label' => 'Answer 4',                   'type' => 'textarea', 'group' => 'FAQ'],
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
    ['key' => 'ty_h',               'label' => 'Thank-you heading',         'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_badge',           'label' => 'Top badge text',             'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_sub',             'label' => 'Thank-you message',         'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_video_drive_id',  'label' => 'Thank-you video Drive ID',  'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_vlabel',          'label' => 'Video block small label',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_vh',              'label' => 'Video block heading',       'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_vplaceholder',    'label' => 'Video placeholder text',    'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_next_h',          'label' => '"What happens next" heading', 'type' => 'text',   'group' => 'Thank you page'],
    ['key' => 'ty_step1',           'label' => 'Next step 1 text',          'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_step2',           'label' => 'Next step 2 text',          'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_step3',           'label' => 'Next step 3 text',          'type' => 'textarea', 'group' => 'Thank you page'],
    ['key' => 'ty_finds_label',     'label' => 'Photos block small label',  'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_finds_h',         'label' => 'Photos block heading',      'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_stories_label',   'label' => 'Videos block small label',  'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_stories_h',       'label' => 'Videos block heading',      'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_fallback',        'label' => 'Text if no media at all',   'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_back',            'label' => 'Back link text',            'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_1_caption',       'label' => 'Thank-you photo 1 caption', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_2_caption',       'label' => 'Thank-you photo 2 caption', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_3_caption',       'label' => 'Thank-you photo 3 caption', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_4_caption',       'label' => 'Thank-you photo 4 caption', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_5_caption',       'label' => 'Thank-you photo 5 caption', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'ty_6_caption',       'label' => 'Thank-you photo 6 caption', 'type' => 'text',     'group' => 'Thank you page'],
    ['key' => 'final_h',           'label' => 'Heading',                    'type' => 'text',     'group' => 'Final CTA'],
    ['key' => 'final_sub',         'label' => 'Subtext',                    'type' => 'textarea', 'group' => 'Final CTA'],
    ['key' => 'cta_email',         'label' => 'Contact email (CTA mailto)', 'type' => 'text',     'group' => 'Final CTA'],
    ['key' => 'whatsapp_msg',      'label' => 'WhatsApp message template (use {name} for the lead name)', 'type' => 'textarea', 'group' => 'WhatsApp'],
    ['key' => 'wa_number',         'label' => 'Company WhatsApp number (with country code)', 'type' => 'text', 'group' => 'WhatsApp'],
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
];

$imageSlots = [
    ['key' => 'proof_1',    'file' => 'proof-1.jpg',    'label' => 'Social proof photo 1 (landscape)'],
    ['key' => 'proof_2',    'file' => 'proof-2.jpg',    'label' => 'Social proof photo 2 (landscape)'],
    ['key' => 'proof_3',    'file' => 'proof-3.jpg',    'label' => 'Social proof photo 3 (landscape)'],
    ['key' => 'benefit_1',  'file' => 'benefit-1.jpg',  'label' => 'Benefit card 1 image'],
    ['key' => 'benefit_2',  'file' => 'benefit-2.jpg',  'label' => 'Benefit card 2 image'],
    ['key' => 'benefit_3',  'file' => 'benefit-3.jpg',  'label' => 'Benefit card 3 image'],
    ['key' => 'logo',       'file' => 'hpllogo.jpeg',   'label' => 'Header / footer logo'],
    ['key' => 'benefit_bg', 'file' => 'benefit-bg.png', 'label' => 'Benefits section background band'],
    ['key' => 'field_proof_1_image', 'file' => 'field-proof-1.jpg', 'label' => 'Field proof story 1 photo'],
    ['key' => 'field_proof_2_image', 'file' => 'field-proof-2.jpg', 'label' => 'Field proof story 2 photo'],
    ['key' => 'slide_1',    'file' => 'slide-1.jpg',    'label' => 'Auto-slide testimonial 1 (landscape)'],
    ['key' => 'slide_2',    'file' => 'slide-2.jpg',    'label' => 'Auto-slide testimonial 2 (landscape)'],
    ['key' => 'slide_3',    'file' => 'slide-3.jpg',    'label' => 'Auto-slide testimonial 3 (landscape)'],
    ['key' => 'slide_4',    'file' => 'slide-4.jpg',    'label' => 'Auto-slide testimonial 4 (landscape)'],
    ['key' => 'slide_5',    'file' => 'slide-5.jpg',    'label' => 'Auto-slide testimonial 5 (landscape)'],
    ['key' => 'slide_6',    'file' => 'slide-6.jpg',    'label' => 'Auto-slide testimonial 6 (landscape)'],
    ['key' => 'ty_1',       'file' => 'ty-1.jpg',       'label' => 'Thank-you photo 1 (landscape)'],
    ['key' => 'ty_2',       'file' => 'ty-2.jpg',       'label' => 'Thank-you photo 2 (landscape)'],
    ['key' => 'ty_3',       'file' => 'ty-3.jpg',       'label' => 'Thank-you photo 3 (landscape)'],
    ['key' => 'ty_4',       'file' => 'ty-4.jpg',       'label' => 'Thank-you photo 4 (landscape)'],
    ['key' => 'ty_5',       'file' => 'ty-5.jpg',       'label' => 'Thank-you photo 5 (landscape)'],
    ['key' => 'ty_6',       'file' => 'ty-6.jpg',       'label' => 'Thank-you photo 6 (landscape)'],
];

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
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
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
    body { margin:0; background:var(--bg-primary); color:var(--text-primary); font-family:'DM Sans',sans-serif; transition:background 0.3s,color 0.3s; }
    .topbar { background:var(--nav-bg); color:#fff; padding:16px 24px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .topbar .brand { font-family:'Space Grotesk',sans-serif; font-size:18px; font-weight:700; }
    .topbar a { color:var(--nav-text); font-size:13px; text-decoration:none; margin-left:18px; }
    .topbar a:hover { color:var(--accent-hover); }
    .wrap { max-width:960px; margin:0 auto; padding:30px 18px 60px; }
    .notice { background:#eaf5ee; border-left:4px solid #2e8b57; color:#23402f; font-size:14px; margin:0 0 18px; padding:12px 16px; }
    .error { background:#fbeeec; border-left:4px solid #c0392b; color:#7a2c25; font-size:14px; margin:0 0 18px; padding:12px 16px; }
    .stats { display:grid; gap:14px; grid-template-columns:repeat(3,1fr); margin:0 0 22px; }
    .stats.five { grid-template-columns:repeat(5,1fr); }
    .stat { background:var(--bg-secondary); border-radius:10px; box-shadow:0 1px 3px rgba(17,26,56,.08); padding:18px 20px; }
    .stat .num { color:var(--text-primary); font-family:'Space Grotesk',sans-serif; font-size:30px; font-weight:700; line-height:1; }
    .stat .lbl { color:var(--text-secondary); font-size:12px; font-weight:700; letter-spacing:.05em; margin-top:7px; text-transform:uppercase; }
    .card { background:var(--bg-secondary); border-radius:10px; box-shadow:0 1px 3px rgba(17,26,56,.08); margin-bottom:22px; padding:24px; }
    .card h2 { font-family:'Space Grotesk',sans-serif; font-size:18px; margin:0 0 16px; color:var(--text-primary); }
    .card-head { align-items:center; display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px; }
    .card-head h2 { margin:0; }
    .grid { display:grid; gap:14px; grid-template-columns:1fr 1fr; }
    .grid .full { grid-column:1/-1; }
    label { display:block; font-size:12px; font-weight:700; margin-bottom:4px; color:var(--text-primary); }
    .field-note { background:#f4f6fb; border-left:3px solid var(--accent,#2c3e6e); color:var(--text-muted,#5a6478); font-size:12px; line-height:1.55; margin:0; padding:10px 12px; }
    .hint { display:block; font-size:11px; color:var(--text-secondary); margin-top:3px; }
    input[type=text], input[type=password], textarea { border:1px solid var(--border-color); border-radius:6px; font:15px/1.5 'DM Sans',sans-serif; padding:10px 12px; width:100%; background:var(--bg-secondary); color:var(--text-primary); }
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
    .modal h2 { font-family:'Space Grotesk',sans-serif; font-size:20px; margin:0 0 20px; color:var(--text-primary); }
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
$waDigits = preg_replace('/[^0-9]/', '', $lead['phone'] ?? '');
if (str_starts_with($waDigits, '00')) { $waDigits = substr($waDigits, 2); }
if ($waDigits !== '' && $waDigits[0] === '0') { $waDigits = '234' . substr($waDigits, 1); }
$waUrl = 'https://wa.me/' . $waDigits . '?text=' . rawurlencode(str_replace('{name}', $lead['name'] ?? '', $settings['whatsapp_msg'] ?? ''));
?>
                  <a class="link-btn wa" href="<?= h($waUrl) ?>" target="_blank" rel="noopener">WhatsApp</a>
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
              <img class="img-thumb" src="../img/<?= h($slot['file']) ?>" alt="<?= h($slot['label']) ?>">
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
    </div>

    <div class="tab-panel <?= $activeTab === 'images' ? 'active' : '' ?>" id="panel-images" role="tabpanel">
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="tab" value="images">
        <div class="card">
          <h2>Images</h2>
          <p class="hint">Upload a JPG, PNG, WebP, or GIF for each slot. Photos are stored directly in <b>img/</b> and the page updates automatically.</p>
          <div class="grid">
<?php foreach ($imageSlots as $slot): ?>
            <div>
              <label for="up_<?= h($slot['key']) ?>"><?= h($slot['label']) ?></label>
<?php if (file_exists(__DIR__ . '/../img/' . $slot['file'])): ?>
              <img class="img-thumb" src="../img/<?= h($slot['file']) ?>" alt="<?= h($slot['file']) ?>">
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
        </div>
        <div class="card">
          <h2>Videos</h2>
          <p class="hint">Upload an <b>MP4</b>, <b>M4V</b> or <b>WebM</b> for each player (up to 190 MB). The story videos are stored in <b>uploads/proof/</b> and the page switches over automatically. Compressing 9:16 phone video to 720x1280 keeps it sharp and small enough for visitors on mobile data.</p>
<?php foreach ($videoSlots as $slot): ?>
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