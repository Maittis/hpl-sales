CREATE DATABASE IF NOT EXISTS hpl_sales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hpl_sales;

CREATE TABLE IF NOT EXISTS settings (
  key_name VARCHAR(80) PRIMARY KEY,
  value MEDIUMTEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  knowledge TEXT NOT NULL,
  wants_to_learn VARCHAR(10) NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT 'new',
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  contacted_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_status (status),
  INDEX idx_submitted_at (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (key_name, value) VALUES
  ('topline', 'HPL Gold Detectors | Built for the serious prospector'),
  ('brand_name', 'HPL GOLD'),
  ('brand_mark', 'H'),
  ('nav_1', 'The Detector'),
  ('nav_2', 'FAQ'),
  ('nav_cta', 'Get yours now'),
  ('hero_h1', 'Are you leaving gold in the '),
  ('hero_h1_span', 'ground?'),
  ('live_pill', 'New Gold Detectors — Now Shipping'),
  ('hero_sub', 'Introducing our Gold Detectors — field-tested for the ground you actually search.'),
  ('hero_intro', 'Watch the video to see how to use it, where to search, and why this machine finds targets others miss.'),
  ('video_drive_id', 'YOUR_DRIVE_FILE_ID'),
  ('panel_kicker', 'Watch: How, where & why'),
  ('hero_caption', 'A practical guide to choosing, setting up, and using the right detector for your ground, goals, and experience.'),
  ('cta_text', 'I''M READY TO FIND GOLD'),
  ('social_label', 'What detectorists are saying:'),
  ('social_heading', 'Real Results'),
  ('social_caption', 'REAL FIELD RESULTS. REAL HPL SUPPORT.'),
  ('benefits_label', 'What you get with every Gold Detector:'),
  ('b1_title', '#1: The Gold Detector'),
  ('b1_desc', 'A precision-tuned gold detector with the depth and discrimination to lock onto gold and ignore hot ground.'),
  ('b2_title', '#2: Complete Field Kit'),
  ('b2_desc', 'Ships ready to swing — coil, headphones, batteries, carry bag, and a quick-start setup guide.'),
  ('b3_title', '#3: How-Where-Why Playbook'),
  ('b3_desc', 'Learn how to set up, where to search for gold, and why the settings matter — with lifetime support.'),
  ('offer_art_title', 'Your complete gold detection setup'),
  ('offer_video_drive_id', 'YOUR_DRIVE_FILE_ID'),
  ('offer_label', 'What You Get'),
  ('offer_desc', 'One field-tested machine, ready to search. We make setup simple and your first outing far more confident.'),
  ('faq1_q', 'How does a gold detector find gold?'),
  ('faq1_a', 'It sends a signal into the ground and reads what comes back.
Most machines chase every bump and tone — our gold detectors only flag targets worth digging, so they stay silent on junk and sing on gold, even in highly mineralized soil.
The video above walks you through exactly how it works, how to set it up, and what the signals mean.'),
  ('faq2_q', 'Where can I use it?'),
  ('faq2_a', 'Gold fields, rivers and washouts, beaches, old campsites, relic ground, and park sites.
Our gold detectors ship tuned for the ground you hunt most, with presets you can switch in seconds.
If your ground type is unusual, message us and we''ll confirm whether it''s a fit before you order.'),
  ('faq3_q', 'Why is it better than a cheap detector?'),
  ('faq3_a', 'Depth and discrimination.
Cheap machines find surface metal and dig trash. Our gold detectors push deeper, separate gold from hot rock, and pair with our how-where-why playbook so you stop wasting days on empty ground.
If your current detector hasn''t found a real target yet, that''s exactly who we built this for.'),
  ('faq4_q', 'How much does it cost and how fast does it ship?'),
  ('faq4_a', 'Choose your package at checkout — every package includes the complete field kit.
We ship with tracking, and delivery typically lands within 5-10 business days depending on your location.
If the free content has not already paid for itself, don''t buy it. If it has, this machine will pay for itself fast.'),
  ('final_h', 'Ready to stop guessing and start finding?'),
  ('final_sub', 'Tell us about your ground. We will help you choose the right detector.'),
  ('cta_email', 'hello@hplgold.com'),
  ('whatsapp_msg', 'Hello {name}, thanks for your interest in HPL Gold Detectors. How can we help you find the right machine?'),
  ('footer_brand', 'HPL GOLD DETECTORS'),
  ('footer_1', 'Privacy Statement'),
  ('footer_1_url', '#faq'),
  ('footer_2', 'Terms and Conditions'),
  ('footer_2_url', '#faq'),
  ('footer_3', 'DMCA Policy'),
  ('footer_3_url', 'mailto:hello@hplgold.com'),
  ('footer_4', 'Do Not Sell My Info'),
  ('footer_4_url', 'mailto:hello@hplgold.com'),
  ('disclaimer', 'Results vary depending on ground conditions, equipment, experience, effort, and application. HPL Gold does not guarantee that any detector will find gold or produce a specific result. Detector recommendations are for general information only and are not professional, financial, or investment advice. Copyright © 2026 HPL Gold Detectors. All rights reserved.')
;

INSERT INTO admins (username, password_hash) VALUES ('admin', '$2y$10$HuQojKubGHkyAcnIi/ssPuwamE9xut//lZ35uPyK57ZFJC3tejV0W');

CREATE TABLE IF NOT EXISTS analytics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_type VARCHAR(50) NOT NULL,
  event_data TEXT,
  session_id VARCHAR(100),
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_event_type (event_type),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;