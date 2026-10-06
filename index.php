<?php
require_once __DIR__ . '/site-bootstrap.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/video-player.php';
$s = hpl_settings();

/* ---------------------------------------------------------------------------
 * Typography: admin-selectable families and sizes.
 *
 * The family list is the closed catalog in site-bootstrap.php rather than free
 * text. The stylesheet link below is assembled from those entries, so the
 * request can never contain anything outside the whitelist and a mistyped name
 * cannot silently break it. Every value falls back to the current design when
 * unset or out of range, which keeps a live database that predates these
 * settings rendering exactly as it did before.
 * ------------------------------------------------------------------------ */
$hplFontCatalog = hpl_font_catalog();

/* An unset, unknown or deleted value falls back rather than producing an empty
   family stack, so the page keeps its original typography by default. */
$hplHeadingFont = (string)($s['font_heading'] ?? '');
if (!isset($hplFontCatalog[$hplHeadingFont])) { $hplHeadingFont = 'Anton'; }
$hplBodyFont = (string)($s['font_body'] ?? '');
if (!isset($hplFontCatalog[$hplBodyFont])) { $hplBodyFont = 'Manrope'; }

$hplHeadingStack = $hplFontCatalog[$hplHeadingFont]['stack'];
$hplBodyStack    = $hplFontCatalog[$hplBodyFont]['stack'];

/* The same family may be picked for both roles. Requesting it twice is rejected
   by the Google Fonts API, so the query is de-duplicated by value. */
$hplFontQuery = [];
foreach ([$hplHeadingFont, $hplBodyFont] as $hplFontKey) {
    $hplFontParam = 'family=' . rawurlencode($hplFontKey . ':wght@' . $hplFontCatalog[$hplFontKey]['weights']);
    if (!in_array($hplFontParam, $hplFontQuery, true)) { $hplFontQuery[] = $hplFontParam; }
}
$hplFontsHref = 'https://fonts.googleapis.com/css2?' . implode('&', $hplFontQuery) . '&display=swap';

/* Per-role sizes. Bounds are wide enough to be useful and tight enough that a
   stray zero or a runaway number cannot collapse the layout. */
$hplSizeRoles = ['hero' => [62, 24, 120], 'h2' => [34, 16, 80], 'body' => [17, 11, 30], 'small' => [13, 8, 24]];
$hplSizes = [];
foreach ($hplSizeRoles as $hplRole => $hplBounds) {
    $hplValue = (int)trim((string)($s['size_' . $hplRole] ?? ''));
    $hplSizes[$hplRole] = ($hplValue >= $hplBounds[1] && $hplValue <= $hplBounds[2]) ? $hplValue : $hplBounds[0];
}

/* Reads a copy setting, treating a blank row the same as a missing one. Settings
   that default to an empty string in config.php, and any field the owner clears
   in the admin, then fall back to the wording below instead of emptying the
   page. The default is supplied at each call site so the markup still shows the
   original text when the database has never held this key. */
$hplCopy = static function (string $key, string $fallback) use ($s): string {
    $value = trim((string)($s[$key] ?? ''));
    return $value !== '' ? $value : $fallback;
};

require_once __DIR__ . '/lead-handler.php';

/**
 * Lead qualification submission.
 *
 * Both steps post together, so the whole enquiry is validated and stored as one
 * row. On rejection the visitor is returned to the step that owns the first bad
 * field, with everything they typed still filled in.
 */
$leadFields = [
    'lead_name' => '',
    'lead_cc' => (string)($s['form_default_cc'] ?? '260'),
    'lead_phone' => '',
    'lead_email' => '',
    'lead_country' => '',
    'lead_city' => '',
    'lead_looking_for' => '',
    'lead_finding' => '',
    'lead_experience' => '',
    'lead_customer_type' => '',
    'lead_timing' => '',
    'lead_message' => '',
];
$leadNeedsAdvice = 0;
$leadSource = 'Landing page';
$leadErrors = [];
$leadSent = false;
$leadStep = 1;
// Read the token before any output so the session cookie leaves with the headers.
$leadCsrf = hpl_public_csrf_token();

/* WhatsApp contact link.
   An empty or malformed wa_number used to override the default and build
   "https://wa.me/?text=...", which opens WhatsApp with no recipient, so a
   visitor tapping it reached nobody. The helper below normalises whatever the
   admin pasted, falls back to the configured default when the stored value is
   unusable, and returns '' if even that fails - a dead link is hidden rather
   than rendered. Defined here rather than only in config.php because
   config.php is excluded from the deployment archive for its credentials. */
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

        /* Last resort. The settings row and config.php are both unreliable
           sources: the row is often left blank, and config.php is excluded
           from the deployment archive because it carries the database
           credentials, so the copy running on the server is not this one and
           cannot be relied on to hold the number. Relying on it meant the
           button vanished entirely in production while looking perfect in
           development. This constant ships, so the link always resolves. */
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
$waUrl = hpl_wa_url($s, 'there');
$waNumber = hpl_wa_number($s)['digits'];
$quickContactUrl = $waUrl;
$quickContactLabel = 'WhatsApp';

$leadStep1Fields = ['lead_name', 'lead_cc', 'lead_phone', 'lead_email', 'lead_country', 'lead_city'];

foreach (array_keys($leadFields) as $leadKey) {
    if (isset($_POST[$leadKey])) {
        $leadFields[$leadKey] = trim((string)$_POST[$leadKey]);
    }
}
if (!empty($_POST['lead_needs_advice'])) {
    $leadNeedsAdvice = 1;
}
if (isset($_POST['lead_source'])) {
    $leadSource = trim((string)$_POST['lead_source']);
}

if (isset($_POST['lead_submit'])) {
    $leadStep = 2;
    $connection = db();

    if (!hpl_public_csrf_ok()) {
        $leadErrors['lead_csrf'] = 'Your session expired. Please refresh the page and try again.';
    } elseif (!$connection) {
        $leadErrors['lead_csrf'] = 'Could not reach the server database. Please try again later.';
    } else {
        // Keeps a fresh deploy working before anyone runs a migration by hand.
        // config.php runs mysqli in strict mode, so a rejected ALTER throws;
        // log it and carry on rather than 500-ing the visitor's submission.
        try {
            hpl_ensure_lead_schema($connection);
        } catch (mysqli_sql_exception $e) {
            error_log('hpl: lead schema migration failed: ' . $e->getMessage());
        }

        $result = hpl_lead_message($_POST, hpl_lead_country_names());
        $leadErrors = $result['errors'];
        $leadData = $result['data'];
        $leadNeedsAdvice = (int)($leadData['needs_advice'] ?? 0);
        $leadSource = (string)($leadData['source'] ?? '');

        if ($leadErrors) {
            foreach (array_keys($leadErrors) as $bad) {
                if (in_array($bad, $leadStep1Fields, true)) {
                    $leadStep = 1;
                    break;
                }
            }
        } else {
            try {
                if (!hpl_lead_duplicate($connection, (string)$leadData['phone'])) {
                    // A replayed or double-clicked submit is treated as the same enquiry.
                    if (!hpl_lead_save($connection, $leadData)) {
                        $leadErrors['lead_csrf'] = 'Could not save your details right now. Please try again in a moment.';
                    }
                }
            } catch (mysqli_sql_exception $e) {
                // Duplicate column, missing table, lost connection - all of these
                // would otherwise surface as a bare 500 page.
                error_log('hpl: lead save failed: ' . $e->getMessage());
                $leadErrors['lead_csrf'] = 'Could not save your details right now. Please try again in a moment.';
            }
        }

        if (!$leadErrors) {
          header('Location: thank-you.php?name=' . rawurlencode($leadFields['lead_name']));
          exit;
        }
    }
}

/**
 * Per-field error text for the inline validation messages.
 *
 * @param array<string,string> $errors
 */
function lead_field_error(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<span class="field-error" id="' . h($field) . 'Error">' . h($errors[$field]) . '</span>' : '';
}

function lead_invalid(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' aria-invalid="true"' : '';
}

/**
 * Poster frame for a proof video: the card is painted immediately instead of
 * sitting blank while the real video is still being fetched.
 *
 * A local source is checked against disk so a missing poster never turns into a
 * broken image. A remote source is trusted and rewritten to the sibling .jpg on
 * the same host, which is how the Bunny pull zone serves them; a HEAD check
 * there would add a request per card on every page load, and the failure mode is
 * a blank card rather than a crash.
 */
function hpl_poster_attr(string $src): string
{
    $src = trim($src);
    if ($src === '') {
        return '';
    }

    $poster = (string)preg_replace('/\.(mp4|m4v|webm|mov|ogv)$/i', '.jpg', $src);
    if ($poster === '' || $poster === $src) {
        return '';
    }

    if (preg_match('~^https?://~i', $poster)) {
        return 'poster="' . htmlspecialchars($poster, ENT_QUOTES) . '" ';
    }

    if (str_starts_with($poster, 'uploads/')
        && is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $poster))) {
        return 'poster="' . htmlspecialchars($poster, ENT_QUOTES) . '" ';
    }

    return '';
}

/**
 * Proof video embeds.
 *
 * A proof story can be supplied either as a file the browser plays itself
 * (<video>) or as a third-party player embed, which is how Bunny Stream and
 * MediaDelivery hand out their copy-and-paste snippet. The two are told apart
 * here so the rest of the page can keep rendering <video> for the first and an
 * <iframe> for the second.
 *
 * Only known player hosts are accepted. An arbitrary pasted URL would otherwise
 * become an iframe pointing anywhere, which is a stored-XSS vector through the
 * admin panel, so an unrecognised host is treated as "not an embed" and falls
 * back to hpl_media_url() rather than being rendered.
 *
 * Defined in index.php rather than config.php on purpose: config.php is
 * deliberately left out of the deployment archive because it holds the database
 * credentials, so the page cannot depend on a helper that only exists there.
 */
if (!function_exists('hpl_embed_host_ok')) {
    function hpl_embed_host_ok(string $host): bool
    {
        $host = strtolower($host);
        $allowed = [
            'player.mediadelivery.net',
            'iframe.mediadelivery.net',
            'player.vimeocdn.com',
            'player.bunnycdn.net',
            'www.youtube.com',
            'www.youtube-nocookie.com',
            'player.twitch.tv',
            'fast.wistia.net',
            'fast.wistia.com',
        ];
        foreach ($allowed as $a) {
            if ($host === $a || substr($host, -strlen('.' . $a)) === '.' . $a) {
                return true;
            }
        }
        return false;
    }

    /**
     * Pull the player URL out of a pasted embed snippet, or accept a bare
     * player URL on its own. Returns '' when the value is not a recognised
     * embed, which is the signal to fall back to the normal media path.
     *
     * $autoplay picks how the player starts. The two proof surfaces want
     * different behaviour, so it is a parameter rather than a global: the
     * testimonial plays silently as the visitor reaches it, while the customer
     * advice clip waits to be started. Both keep Bunny's controls on, because
     * that is the only way to unmute - a cross-origin player cannot be reached
     * from the page.
     */
    function hpl_embed_url(string $value, bool $autoplay = false): string
    {
        $v = trim($value);
        if ($v === '') {
            return '';
        }

        // A pasted snippet carries the URL in the iframe's src. Anything else
        // in the snippet (the wrapper div, inline styles, allow attributes) is
        // discarded and rebuilt from the URL, so nothing untrusted is echoed.
        if (stripos($v, '<iframe') !== false || stripos($v, '<div') !== false) {
            if (!preg_match('~<iframe[^>]+src\s*=\s*["\']([^"\']+)["\']~i', $v, $m)) {
                return '';
            }
            $v = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (!preg_match('~^https?://~i', $v)) {
            return '';
        }

        $parts = parse_url($v);
        if ($parts === false || empty($parts['host']) || !hpl_embed_host_ok($parts['host'])) {
            return '';
        }

        $scheme = 'https';
        $path = (string)($parts['path'] ?? '/');
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

          if ($autoplay) {
            /* Muted autoplay is the only kind browsers permit without a gesture,
               and a paused poster frame reads as a still photograph. Sound stays
               off until the visitor unmutes through Bunny's own controls, because
               a cross-origin player cannot be unmuted from here. */
            $query['autoplay'] = '1';
            $query['muted'] = '1';
        } else {
            /* Click to play: the visitor starts it and chooses whether to unmute. */
            $query['autoplay'] = '0';
            $query['muted'] = '0';
        }
        if (!isset($query['loop'])) { $query['loop'] = '0'; }
        $query['playsinline'] = '1';
        $query['responsive'] = '1';
        /* Controls stay on: without them there is no way to unmute at all,
           because the parent page cannot reach into the player. */
        $query['controls'] = '1';

        return $scheme . '://' . $parts['host'] . $path . '?' . http_build_query($query);
    }

    /**
     * True when this proof slot is a player embed rather than a playable file.
     */
    function hpl_is_embed(string $value): bool
    {
        return hpl_embed_url($value) !== '';
    }

    /**
     * The media element for one proof card, in whichever form the slot supplies.
     *
     * All three layouts render their card through this so an embed is supported
     * everywhere a <video> is, and the layouts cannot drift apart again.
     *
     * $videoAttrs carries the layout-specific attributes (the hook the carousel
     * listens on, and loop where that layout wants it).
     */
    function hpl_proof_media(array $item, string $videoAttrs = ''): string
    {
        $src = (string)($item['src'] ?? '');
        $caption = (string)($item['caption'] ?? '');

        if (($item['kind'] ?? 'video') === 'embed') {
            /* An embed has no poster attribute the way <video> does, and the frame is
               kept hidden until the observer sets src. Until the player document
               actually arrives there is nothing on screen but an empty dark box, which
               does not read as a video at all. This stand-in sits under the frame so the
               section looks like a player from the first paint; the script retires it as
               soon as the player reports load. */
            return '<div class="proof-embed" style="aspect-ratio:16 / 9; height:100%; width:100%;">'
                . '<span class="proof-embed-poster" aria-hidden="true"><i class="proof-embed-play"></i></span>'
                . '<iframe data-embed-src="' . h($src) . '"'
                . ' title="' . h($caption !== '' ? $caption : 'Customer story video') . '"'
                . ' loading="lazy"'
                . ' style="border:0; display:block; height:100%; inset:0; position:absolute; width:100%;"'
                . ' allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture; fullscreen"'
                . ' allowfullscreen="true"'
                . ' referrerpolicy="strict-origin-when-cross-origin"></iframe>'
                . '</div>';
        }

        return '<video playsinline preload="none" ' . $videoAttrs . ' ' . hpl_poster_attr($src)
            . ' style="display:block; height:100%; object-fit:cover; width:100%;"><source src="' . h($src) . '" type="' . h(hpl_media_type($src)) . '"></video>';
    }
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

/* An uploaded image keeps the same filename, so the browser would keep serving
   its cached copy and a successful replacement would look like it did nothing.
   Stamping the URL with the file's mtime makes a changed image a new resource
   and an unchanged one still hit the cache. */
function hpl_img_url(string $file): string
{
    $path = __DIR__ . '/img/' . $file;
    $stamp = is_file($path) ? (string)@filemtime($path) : '';
    return h('img/' . $file) . ($stamp !== '' ? '?v=' . $stamp : '');
}

/** True for the map providers we are willing to frame. */
function hpl_map_host_ok(string $host): bool
{
    $host = strtolower($host);
    $allowed = [
        'www.openstreetmap.org',
        'openstreetmap.org',
        'www.google.com',
        'maps.google.com',
        'www.bing.com',
        'api.mapbox.com',
    ];
    foreach ($allowed as $a) {
        if ($host === $a || substr($host, -strlen('.' . $a)) === '.' . $a) {
            return true;
        }
    }
    return false;
}

/**
 * src for the "Visit us" map, or '' when nothing usable is configured.
 *
 * A pasted snippet is treated exactly like the video embeds above: only the
 * iframe src is kept, and the tag itself is rebuilt here, so nothing untrusted
 * is ever echoed. Failing that, plain coordinates build an OpenStreetMap frame,
 * which needs no API key and no billing account.
 */
function hpl_map_embed_url(string $embed, string $lat, string $lon, string $zoom): string
{
    $embed = trim($embed);
    if ($embed !== '') {
        if (stripos($embed, '<iframe') !== false) {
            if (!preg_match('~<iframe[^>]+src\s*=\s*["\']([^"\']+)["\']~i', $embed, $m)) {
                return '';
            }
            $embed = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if (!preg_match('~^https?://~i', $embed)) {
            return '';
        }
        $parts = parse_url($embed);
        if ($parts === false || empty($parts['host']) || !hpl_map_host_ok($parts['host'])) {
            return '';
        }
        /* Force https so a pasted http:// link cannot be downgraded in transit. */
        return 'https://' . $parts['host'] . (isset($parts['path']) ? $parts['path'] : '/')
            . (isset($parts['query']) ? '?' . $parts['query'] : '');
    }

    if (!is_numeric($lat) || !is_numeric($lon)) {
        return '';
    }
    $lat = (float)$lat;
    $lon = (float)$lon;
    if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
        return '';
    }

    $zoom = is_numeric($zoom) ? (int)$zoom : 16;
    $zoom = max(1, min(19, $zoom));

    /* Roughly the viewport a map at this zoom would show around the marker. */
    $span = 360 / (2 ** $zoom);
    $minLat = max(-85.0, $lat - $span * 0.6);
    $maxLat = min(85.0, $lat + $span * 0.6);
    $minLon = max(-180.0, $lon - $span);
    $maxLon = min(180.0, $lon + $span);

    return 'https://www.openstreetmap.org/export/embed.html?bbox='
        . $minLon . '%2C' . $minLat . '%2C' . $maxLon . '%2C' . $maxLat
        . '&layer=mapnik&marker=' . $lat . '%2C' . $lon;
}

/**
 * "Get directions" target. Uses the coordinates when we have them and falls back
 * to a plain search for the typed address, so the button works either way.
 */
function hpl_map_directions_url(array $s): string
{
    $lat = trim((string)($s['visit_map_lat'] ?? ''));
    $lon = trim((string)($s['visit_map_lon'] ?? ''));
    if (is_numeric($lat) && is_numeric($lon)) {
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lat . ',' . $lon);
    }
    $address = trim((string)($s['visit_address'] ?? ''));
    if ($address === '') {
        return '';
    }
    $firstLine = preg_split('/\r\n|\r|\n/', $address)[0];
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(trim($firstLine));
}

function art_block(string $file, string $fallbackClass = ''): string
{
    if (file_exists(__DIR__ . '/img/' . $file)) {
        return '<img class="art-img" src="' . hpl_img_url($file) . '" alt="HPL Gold Detectors" loading="lazy">';
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
  <title><?= h($s['topline'] ?? 'HPL Gold Detectors | Built for the serious prospector') ?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="img/favicon-16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32.png">
  <link rel="icon" type="image/png" sizes="48x48" href="img/favicon-48.png">
  <link rel="icon" type="image/png" sizes="192x192" href="img/favicon-192.png">
  <link rel="apple-touch-icon" href="img/favicon-180.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="<?= h($hplFontsHref) ?>" rel="stylesheet">
  <style>
    :root { --navy:#111a38; --navy-dark:#090f24; --gold:#d4a52c; --gold-light:#f4ca5b; --ink:#182038; --muted:#687083; --paper:#111a38; --wash:#111a38; }
    /* Typography, admin-controlled. These five custom properties are the single
       source of truth for family and the four size roles; every rule below
       references them instead of naming a font or a size directly.

       Values are printed without HTML escaping on purpose: the contents of a
       <style> element are raw text, so an entity like &#039; would reach the
       parser literally and corrupt the family name. They are safe regardless,
       because the stacks come from the whitelisted catalog above and the sizes
       are cast to int. */
    :root {
      --font-heading: <?= $hplHeadingStack ?>;
      --font-body: <?= $hplBodyStack ?>;
      --size-hero: <?= (int)$hplSizes['hero'] ?>px;
      --size-h2: <?= (int)$hplSizes['h2'] ?>px;
      --size-body: <?= (int)$hplSizes['body'] ?>px;
      --size-small: <?= (int)$hplSizes['small'] ?>px;
    }
    * { box-sizing:border-box; }
    html { scroll-behavior:smooth; }
    body { margin:0; color:#fff; background:var(--navy); font-family:var(--font-body); font-size:var(--size-body); line-height:1.55; overflow-wrap:break-word; overflow-x:hidden; }
    a { color:inherit; text-decoration:none; }
    .page { width:100%; margin:0 auto; background:var(--navy); }
    .topline { background:#070b18; color:#fff; font-size:11px; font-weight:700; letter-spacing:.08em; padding:9px 20px; text-align:center; text-transform:uppercase; }
    header { background:var(--navy); color:#fff; padding:20px 34px; }
    .nav { align-items:center; display:flex; justify-content:space-between; }
    .brand { align-items:center; display:flex; font-family:var(--font-body); font-size:20px; font-weight:700; gap:10px; }
    .brand-mark { align-items:center; border:2px solid var(--gold-light); border-radius:50%; color:var(--gold-light); display:flex; font-size:12px; height:32px; justify-content:center; width:32px; }
    .logo-chip { background:#fff; border-radius:8px; line-height:0; padding:6px 9px; }
    .header-logo { display:block; height:44px; width:auto; }
    .nav-links { color:#d9deea; display:flex; font-size:15px; gap:26px; }
    .nav-cta { background:var(--gold); color:var(--navy-dark); font-size:12px; font-weight:700; padding:12px 18px; text-transform:uppercase; }
    .hero { background:var(--navy); color:#fff; padding:24px 34px 44px; text-align:center; }
    .hero h1 {font-weight:400;  font-family:var(--font-heading); font-size:clamp(38px,7vw,var(--size-hero)); letter-spacing:0; line-height:.98; margin:0 auto 20px; max-width:640px; }
    .hero h1 span { color:var(--gold-light); }
    .hero-sub { color:var(--gold-light); font-size:18px; font-weight:700; letter-spacing:.01em; line-height:1.35; margin:-6px auto 18px; max-width:620px; }
    .hero-intro { color:#e3e6ed; font-size:19px; line-height:1.45; margin:0 auto 30px; max-width:640px; }
/* The promo panel now hosts the custom player in video-player.css, which
       draws its own 16:9 frame and bar. Only the width and spacing stay here so
       the hero layout around it is unchanged. */
    .detector-panel { margin:0 auto 30px; max-width:800px; width:100%; }

    /* Bunny Stream promo embed. The wrapper owns the 16:9 box so the frame
       keeps the existing panel dimensions while the player fills it. */
    .promo-embed { margin:0 auto 30px; max-width:1000px; width:100%; }
    .promo-frame {
      aspect-ratio:16/9;
      background:#05040c;
      border:1px solid rgba(128,128,128,0);
      border-radius:14px;
      overflow:hidden;
      position:relative;
      width:100%;
    }
    .promo-frame-el {
      border:0;
      display:block;
      height:100%;
      left:0;
      position:absolute;
      top:0;
      width:100%;
    }
    .customer-advice-section { background:var(--navy); color:#fff; text-align:center; }
    .customer-advice-player { margin:24px auto 0; max-width:900px; }
    .promo-frame-fallback { padding:60px 20px; text-align:center; }
    .promo-frame-fallback a { color:var(--gold); font-weight:700; }
    .mute-toggle { background:rgba(9,15,36,.82); border:1px solid rgba(244,202,91,.6); color:var(--gold-light); cursor:pointer; font-size:12px; font-weight:700; letter-spacing:.1em; padding:9px 13px; position:absolute; right:14px; text-transform:uppercase; top:14px; z-index:3; }
    .mute-toggle:hover { background:var(--navy); }
    .video-fallback { align-items:center; color:#bfc8d8; display:flex; font-size:15px; height:100%; justify-content:center; padding:30px; text-align:center; }
    .panel-kicker { display:none; }
    .hero-copy { color:#bfc8d8; font-size:15px; line-height:1.5; margin:0 auto 18px; max-width:560px; }
    .button { background:var(--gold); border:0; color:var(--navy-dark); cursor:pointer; display:inline-block; font-size:14px; font-weight:700; letter-spacing:.06em; padding:16px 38px; text-transform:uppercase; }
    .button, .nav-cta { box-shadow:0 0 24px rgba(244,202,91,.36); }
    .button:hover { background:var(--gold-light); }
    /* Most sections carry no action of their own, and the page is long, so the
       same booking prompt repeats as the visitor scrolls. Wrapper only - the
       button keeps its existing gold styling. */
    .cta-reminder { display:flex; justify-content:center; margin:40px auto 0; max-width:1120px; }
    .button.hero-find-cta { align-items:center; background:var(--gold); border:6px solid #713400; border-radius:999px; box-shadow:0 0 24px rgba(244,202,91,.36); color:var(--navy-dark); display:inline-flex; font-size:17px; font-weight:700; justify-content:center; line-height:1.3; min-height:94px; padding:20px 38px; text-align:center; width:min(460px,100%); transition:transform .2s ease, box-shadow .2s ease, background .2s ease; }
    .button.hero-find-cta:hover { background:var(--gold-light); box-shadow:0 0 40px rgba(244,202,91,.5); transform:scale(1.02); }
    a[href="#book"].book-gated { cursor:not-allowed; opacity:0.45; filter:grayscale(35%); pointer-events:none; }
    #book.book-hidden { max-height:0; opacity:0; overflow:hidden; padding-top:0; padding-bottom:0; margin-top:0; transition:max-height 0.7s ease-out, opacity 0.4s ease, margin 0.4s ease, padding 0.4s ease; }
    #book.book-revealed { max-height:6000px; opacity:1; transition:max-height 0.7s ease-in, opacity 0.5s ease; }
    .section { color:#fff; padding:46px 34px; }
    .section.center { text-align:center; }
    .section h2 {font-weight:400;  color:#fff; font-family:var(--font-heading); font-size:var(--size-h2); letter-spacing:0; line-height:1.05; margin:0 0 12px; text-transform:uppercase; }
    .section p { color:rgba(255,255,255,.78); font-size:16px; line-height:1.6; margin:0 auto; max-width:640px; }
    .wash { background:var(--navy); }
    .proof { background:var(--navy); text-align:center; padding-bottom:24px; }
    .proof h2 { color:#fff; margin-bottom:20px; }
    /* One fixed player.
       820px is deliberately a little narrower than the promo player above it
       (1000px), so the testimonial reads as supporting proof rather than a second
       hero, while still filling the column on desktop. */
    .proof-single { margin:26px auto 0; max-width:820px; position:relative; width:100%; }
    .proof-single .proof-embed { aspect-ratio:16/9; height:100%; width:100%; }
    .proof-single figcaption { background:linear-gradient(to top,rgba(0,0,0,.82),rgba(0,0,0,0)); bottom:0; color:#fff; font-size:13px; font-weight:700; left:0; padding:28px 14px 12px; position:absolute; right:0; text-align:left; }
    /* Keep Bunny embeds full-bleed inside the card so they feel like native video
       tiles. No scale() here on purpose: magnifying the frame cropped roughly 16%
       off every edge inside the overflow:hidden card, so a paused player read as a
       cropped photograph. Bunny letterboxes inside the box instead, which is what a
       normal embedded video does. */
    .proof-embed { align-items:center; aspect-ratio:16/9; background:#0b1226; border-radius:14px; box-shadow:0 16px 34px rgba(0,0,0,.32); display:flex; height:100%; justify-content:center; overflow:hidden; position:relative; width:100%; }
    .proof-embed iframe { border:0; display:block; height:100%; inset:0; max-height:100%; max-width:100%; object-fit:contain; position:absolute; width:100%; }
    /* Nothing is fetched until the observer sets src, so hide the empty frame
       instead of showing a black box that flashes before the player appears. */
    .proof-embed iframe:not([data-on]) { visibility:hidden; }
    .proof-embed video { display:block; height:100%; object-fit:cover; width:100%; }
    /* Stand-in shown while the player document is still on the wire. It sits over
       the empty frame so the box reads as a video player immediately rather than as a
       blank rectangle for however long the player takes to arrive. */
    .proof-embed-poster { align-items:center; display:flex; inset:0; justify-content:center; pointer-events:none; position:absolute; z-index:1; }
    .proof-embed-play { border:2px solid rgba(255,255,255,.9); border-radius:50%; display:block; height:62px; position:relative; width:62px; }
    .proof-embed-play::after { border-bottom:11px solid transparent; border-left:17px solid #fff; border-top:11px solid transparent; content:''; left:52%; position:absolute; top:50%; transform:translate(-50%,-50%); }
    .proof-embed[data-ready] .proof-embed-poster { display:none; }
    .proof-empty { background:var(--navy); color:#fff; font-size:14px; margin:0 auto; max-width:420px; padding:60px 20px; }
    @media (max-width:860px) { .proof-single { width:100%; } }
    .slides-stage { margin:0 auto; max-width:880px; overflow:hidden; position:relative; }
    .slides-frame { aspect-ratio:16/9; background:var(--navy); margin:0 auto; max-height:56vh; overflow:hidden; position:relative; }
    .slides-frame img { height:100%; inset:0; object-fit:cover; opacity:0; position:absolute; transform:scale(1.03); transition:opacity .28s ease, transform .7s ease; width:100%; }
    .slides-frame img.on { opacity:1; transform:none; z-index:2; }
    .slides-cap { background:linear-gradient(to top,rgba(0,0,0,.78),rgba(0,0,0,0)); bottom:0; color:#fff; font-size:15px; font-weight:700; left:0; opacity:0; padding:44px 20px 18px; position:absolute; right:0; text-align:left; transition:opacity .3s ease; z-index:3; }
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
    .proof-mix { background:var(--navy); border-top:1px solid rgba(244,202,91,.16); }
    .proof-field-section { background:var(--navy); border-top:1px solid rgba(244,202,91,.28); }
    .proof-field-section h2 { color:var(--gold-light); }
    .proof-field-section > .section-label { color:var(--gold); }
    .proof-visuals { background:var(--navy); border-top:1px solid rgba(244,202,91,.2); text-align:center; }
    .proof-visuals h2 { color:#fff; }
    .proof-visuals > .section-label { color:var(--gold); }
    .zig { display:flex; flex-direction:column; gap:44px; margin:32px auto 0; max-width:1120px; }
    .zig-row { align-items:center; display:flex; gap:44px; text-align:left; }
    /* Odd rows lead with the text, even rows lead with the photo, so the eye
       zigzags down the section instead of scanning a flat grid. */
    .zig-row:nth-child(even) { flex-direction:row-reverse; }
    .zig-text, .zig-media { flex:1 1 0; min-width:0; }
    .zig-title { color:#fff; font-family:var(--font-heading); font-size:clamp(21px,2.6vw,31px); font-weight:400; letter-spacing:0; line-height:1.1; margin:0 0 12px; }
    .zig-body { color:rgba(255,255,255,.74); font-size:16px; line-height:1.65; margin:0; max-width:44ch; }
    .zig-media { aspect-ratio:16/9; background:#0b122a; border:1px solid rgba(244,202,91,.28); border-radius:14px; overflow:hidden; }
    .zig-media img { display:block; height:100%; object-fit:cover; width:100%; }
    /* A clip in a row needs to be the containing block for its own player: the
       embed positions its iframe absolutely, and .zig-media is not positioned. */
    .zig-media.story-media { position:relative; }
    /* Benefit art keeps its own fixed 180px height rather than taking the
       16/9 crop used by the photo rows. Width stays fluid, as it was when
       these were three-up grid cards. */
    .benefit-media { aspect-ratio:auto; height:180px; transition:border-color .3s ease; }
    .benefit-media:hover { border-color:rgba(244,202,91,.45); }
    .benefit-media .detector { transform:translateX(-45%) rotate(-12deg) scale(.47); top:-24px; }
    /* Customer voice rows keep the platform dot and the post type as a meta
       line under the heading, and the screenshot fills the standard 16/9 box. */
    .voice-meta { align-items:center; color:rgba(255,255,255,.62); display:flex; font-size:11px; font-weight:700; gap:8px; letter-spacing:.14em; margin:0 0 14px; text-transform:uppercase; }
    .voice-meta .proof-social-dot { flex:0 0 auto; }
    .voice-todo { border-left:2px dashed rgba(244,202,91,.55); font-style:italic; padding-left:14px; }
    .voice-media .proof-social-screenshot { aspect-ratio:auto; border:0; border-radius:0; height:100%; min-height:0; width:100%; }
    .voice-media .proof-social-screenshot.has-image { aspect-ratio:auto; max-width:none; }
    .voice-media .proof-social-screenshot.has-image > img { height:100%; object-fit:cover; width:100%; }
    .review-screenshot-grid { align-items:start; display:grid; gap:18px; grid-template-columns:repeat(3,minmax(0,1fr)); margin:36px auto 0; max-width:1220px; }
    .review-screenshot-grid figure { background:var(--navy); border:1px solid rgba(244,202,91,.42); border-radius:10px; box-shadow:0 14px 32px rgba(0,0,0,.3); margin:0; overflow:hidden; padding:10px; }
    .review-screenshot-grid img { display:block; height:auto; max-height:520px; object-fit:contain; width:100%; }
    .review-screenshot-heading {font-weight:700;  color:var(--gold-light); font-family:var(--font-body); font-size:22px; margin:36px 0 0; }
    .proof-mix-grid { display:grid; gap:26px; grid-template-columns:1fr; margin:28px auto 0; max-width:1220px; }
    .proof-mix-story-link { color:inherit; display:block; text-decoration:none; }
    .proof-mix-card-link { color:inherit; display:block; height:100%; text-decoration:none; }
    .proof-mix-card { background:rgba(255,255,255,.05); border:1px solid rgba(244,202,91,.2); border-radius:18px; box-shadow:0 18px 45px rgba(17,26,56,.08); display:flex; flex-direction:column; height:100%; overflow:hidden; transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
    .proof-mix-card-link:hover .proof-mix-card,
    .proof-mix-card-link:focus-visible .proof-mix-card { box-shadow:0 28px 60px rgba(17,26,56,.12); transform:translateY(-3px); border-color:rgba(244,202,91,.4); }
    .proof-mix-media { aspect-ratio:4/3; background:linear-gradient(135deg,#111a38,#2b385c); position:relative; overflow:hidden; }
    .proof-mix-story {
      background:var(--navy); border:1px solid rgba(244,202,91,.42); border-radius:18px; box-shadow:0 18px 45px rgba(0,0,0,.22); display:grid; gap:0; grid-template-columns:minmax(0,1fr); overflow:hidden;
      text-decoration:none; transition:transform .25s ease, box-shadow .25s ease; width:100%; animation:proof-story-enter .65s cubic-bezier(.2,.7,.2,1) both;
    }
    .proof-mix-story:nth-child(2) { animation-delay:.1s; }
    .proof-mix-story:focus-within { box-shadow:0 24px 50px rgba(0,0,0,.32),0 0 0 1px rgba(244,202,91,.28); }
    @keyframes proof-story-enter { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
    .proof-mix-story-header {
      background:var(--navy); color:#fff; padding:18px 24px 14px;
    }
    .proof-mix-story-header .eyebrow {
      color:var(--gold-light); display:inline-block; font-size:11px; font-weight:700; letter-spacing:.14em; margin:0 0 8px; text-transform:uppercase;
    }
    .proof-mix-story-header h3 {font-weight:400; 
      color:#fff; font-family:var(--font-heading); font-size:clamp(28px,4vw,42px); letter-spacing:0; line-height:1.08; margin:0;
    }
    .proof-mix-story-body {
      align-items:stretch; display:grid; gap:28px; grid-template-columns:minmax(260px,.9fr) minmax(0,1.1fr); padding:26px;
    }
    .proof-mix-story-copy {
      align-self:center; grid-column:2; grid-row:1;
    }
    .proof-mix-story:nth-child(2) .proof-mix-story-copy { grid-column:1; }
    .proof-mix-story-copy h4 {font-weight:400; 
      color:var(--gold-light); font-family:var(--font-heading); font-size:clamp(22px,3vw,32px); letter-spacing:0; line-height:1.15; margin:0 0 14px;
    }
    .proof-mix-story-copy p {
      color:rgba(255,255,255,.78); font-size:15px; line-height:1.65; margin:0;
    }
    .proof-mix-story-actions {
      display:flex; flex-wrap:wrap; gap:10px; margin-top:20px;
    }
    .proof-mix-story-actions span {
      color:var(--navy); font-size:12px; font-weight:700; white-space:nowrap;
    }
    .proof-mix-story-actions a {
      border-radius:8px; display:inline-block; font-size:12px; font-weight:700; letter-spacing:.06em; padding:12px 16px; text-transform:uppercase;
    }
    .proof-mix-story-actions .primary {
      background:var(--gold); color:var(--navy-dark); text-decoration:none;
    }
    .proof-mix-story-actions .secondary {
      border:1px solid rgba(244,202,91,.42); color:var(--gold-light); text-decoration:none;
    }
    .proof-mix-story-media {
      aspect-ratio:4/3; background:#0b122a; border:1px solid rgba(244,202,91,.24); border-radius:14px; grid-column:1; grid-row:1; overflow:hidden; width:100%;
    }
    .proof-mix-story:nth-child(2) .proof-mix-story-media { grid-column:2; }
    .proof-mix-story-media img {
      display:block; height:100%; object-fit:cover; width:100%;
    }
    .proof-mix-media img,
    .proof-mix-media video { display:block; height:100%; object-fit:cover; width:100%; }
    .proof-mix-media::after { background:linear-gradient(to top, rgba(9,15,36,.72), rgba(9,15,36,0) 46%); content:''; inset:0; position:absolute; }
    .proof-mix-type { background:rgba(17,26,56,.82); border:1px solid rgba(244,202,91,.5); border-radius:999px; color:#f7d56b; font-size:10px; font-weight:700; inset:14px auto auto 14px; letter-spacing:.12em; padding:7px 10px; position:absolute; text-transform:uppercase; z-index:1; }
    .proof-mix-body { display:flex; flex:1; flex-direction:column; gap:8px; padding:18px 18px 20px; }
    .proof-mix-body h3 {font-weight:700;  color:var(--navy); font-family:var(--font-body); font-size:22px; letter-spacing:-.04em; line-height:1.1; margin:0; }
    .proof-mix-body p { color:var(--muted); font-size:14px; line-height:1.55; margin:0; max-width:none; }
    .proof-mix-ghost { align-items:center; background:linear-gradient(130deg,#f3d67a,#d9b654 40%,#8b6d26); color:#111a38; display:flex; font-size:18px; font-weight:700; height:100%; justify-content:center; letter-spacing:.08em; text-align:center; text-transform:uppercase; }
    .proof-mix-story-media.proof-mix-ghost { min-height:100px; }
    @media (max-width:760px) {
      .proof-mix-story-body { gap:18px; grid-template-columns:1fr; padding:18px; }
      .proof-mix-story-copy,
      .proof-mix-story:nth-child(2) .proof-mix-story-copy { grid-column:1; grid-row:2; }
      .proof-mix-story-media,
      .proof-mix-story:nth-child(2) .proof-mix-story-media { aspect-ratio:16/10; grid-column:1; grid-row:1; }
    }
    @media (max-width:480px) {
      .proof-mix-story-header { padding:16px 18px 12px; }
      .proof-mix-story-body { gap:16px; padding:16px; }
    }
    @media (prefers-reduced-motion:reduce) {
      .proof-mix-story { animation:none; transition:none; }
    }
    .proof-social-dot { background:linear-gradient(135deg,#2dd4bf,#14b8a6); border-radius:50%; display:block; height:10px; width:10px; }
    .proof-social-screenshot { background:#f3f6fb; border:1px solid rgba(17,26,56,.08); border-radius:12px; min-height:190px; overflow:hidden; position:relative; }
    .proof-social-screenshot.has-image { aspect-ratio:4/5; margin:0 auto; max-width:420px; min-height:0; }
    .proof-social-screenshot.has-image::before { display:none; }
    .proof-social-screenshot.has-image > img { display:block; height:100%; object-fit:cover; object-position:center center; width:100%; }
    .proof-social-screenshot::before { background:linear-gradient(135deg, rgba(255,255,255,.8), rgba(231,236,243,.3)); content:''; inset:0; position:absolute; }
    .proof-social-screenshot.whatsapp { background:linear-gradient(180deg,#d7f7d7,#f5f9f5); }
    .proof-social-screenshot.facebook { background:linear-gradient(180deg,#edf3ff,#ecf2ff); }
    .proof-social-screenshot.tiktok { background:linear-gradient(180deg,#f8eef8,#f5ebfb); }
    .proof-social-screenshot .mock { background:#fff; border:1px solid rgba(17,26,56,.08); border-radius:12px; box-shadow:0 10px 25px rgba(17,26,56,.06); left:50%; max-width:82%; padding:12px 12px 16px; position:absolute; top:50%; transform:translate(-50%,-50%); width:82%; }
    .proof-social-screenshot .mock-head { display:flex; gap:8px; margin-bottom:10px; }
    .proof-social-screenshot .mock-avatar { background:linear-gradient(135deg,#f3d67a,#d4a52c); border-radius:50%; height:24px; width:24px; }
    .proof-social-screenshot .mock-name { background:#dfe7f4; border-radius:6px; height:10px; margin-top:6px; width:70px; }
    .proof-social-screenshot .mock-line { background:#eceff5; border-radius:6px; display:block; height:8px; margin:7px 0; }
    .proof-social-screenshot .mock-line.short { width:42%; }
    .proof-social-screenshot .mock-line.long { width:94%; }
    .proof-social-screenshot .mock-line.mid { width:76%; }
    .proof-social-screenshot .mock-chip { background:#e7f7ed; border-radius:999px; display:inline-block; height:18px; margin-top:8px; width:84px; }
    @media (max-width:1024px) {
      .proof-mix-grid { grid-template-columns:1fr; }
    }
    @media (max-width:640px) {
      .proof-mix-grid { grid-template-columns:1fr; }
      .proof-mix-body h3 { font-size:19px; }
      .review-screenshot-grid { gap:14px; grid-template-columns:1fr; max-width:460px; }
      .zig { gap:26px; max-width:520px; }
  .zig-row, .zig-row:nth-child(even) { flex-direction:column; gap:16px; }
  /* The row turns into a column here, and .zig-media is flex:1 1 0. In a column
     that flex-basis:0 lands on the height, so the box collapsed to a couple of
     pixels and took every photo and player inside it to zero - the text beside it
     kept reading normally, which is why phones showed words and no media at all.
     flex:0 0 auto hands the main size back to aspect-ratio:16/9. */
  .zig-media, .zig-media.story-media { flex:0 0 auto; width:100%; }
    }
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
    .proof .proof-caption { display:none !important; }
    .art-img { display:block; height:100%; object-fit:cover; width:100%; }
    .live-pill { background:#c0392b; border-radius:40px; color:#fff; display:inline-flex; align-items:center; gap:8px; font-size:12px; font-weight:700; letter-spacing:.12em; margin-bottom:20px; padding:9px 18px; text-transform:uppercase; }
    .live-pill i { background:#ff6b6b; border-radius:50%; display:inline-block; height:8px; position:relative; width:8px; }
    .live-pill i::after { animation:pulse 1.6s infinite; background:#ff6b6b; border-radius:50%; content:''; height:8px; left:0; position:absolute; top:0; width:8px; }
    @keyframes pulse { 0% { opacity:.8; transform:scale(1); } 100% { opacity:0; transform:scale(3.2); } }
    .section-label { color:var(--gold); font-size:var(--size-small); font-weight:700; letter-spacing:.13em; margin-bottom:11px; text-transform:uppercase; }
    .torn { background:var(--navy-dark); color:#fff; margin:0 12px; padding:24px; text-align:center; }
    .torn h2 { color:#fff; font-size:26px; margin:0; }
    .benefits-band { align-items:center; background:url('img/benefit-bg.png') no-repeat center; background-size:100% 100%; color:#fff; display:flex; justify-content:center; min-height:220px; padding:70px 30px; text-align:center; }
    .benefits-band h2 {font-weight:400;  color:#fff; font-family:var(--font-heading); font-size:clamp(28px,4.5vw,44px); letter-spacing:0; line-height:1.15; margin:0; max-width:820px; }
    .benefit-media .detector { transform:translateX(-45%) rotate(-12deg) scale(.47); top:-24px; }
    .spaced-cta { padding:10px 0 46px; text-align:center; }
    /* Trust Indicators Section */
    .trust-section { background:var(--navy); padding:46px 34px; text-align:center; }
    .trust-section h2 {font-weight:400;  color:#fff; font-family:var(--font-heading); font-size:32px; letter-spacing:0; line-height:1.05; margin:0 0 16px; }
    .trust-section p { color:rgba(255,255,255,.78); font-size:16px; line-height:1.6; margin:0 auto 36px; max-width:640px; }
    /* Social Proof Ticker */
    .proof-ticker { background:rgba(244,202,91,.1); border-top:1px solid rgba(244,202,91,.3); border-bottom:1px solid rgba(244,202,91,.3); margin:0 auto 40px; max-width:1000px; overflow:hidden; padding:16px 0; position:relative; }
    .proof-ticker-track { display:flex; animation:scrollTicker 30s linear infinite; will-change:transform; }
    .proof-ticker:hover .proof-ticker-track { animation-play-state:paused; }
    /* Sits directly under the filter bar, so it is pulled up to read as part
       of the same control block rather than a floating band. */
    .proof-ticker.after-filter { margin:24px auto 34px; }
    /* An infinite animation still costs the compositor on every frame while it
       runs, even when the element is off screen. These decorations used to
       animate for the whole visit whether or not anyone could see them, so the
       script below pauses them whenever the section leaves the viewport. */
    .hpl-offscreen, .hpl-offscreen * { animation-play-state:paused !important; }
    @keyframes scrollTicker { 0% { transform:translateX(0); } 100% { transform:translateX(-50%); } }
    .ticker-item { align-items:center; display:flex; gap:10px; padding:0 30px; white-space:nowrap; }
    .ticker-item-icon { color:var(--gold); font-size:20px; }
    .ticker-item-text { color:rgba(255,255,255,.9); font-size:14px; font-weight:600; }
    .ticker-item-text strong { color:var(--gold-light); }
    .ticker-item-time { color:rgba(255,255,255,.5); font-size:12px; margin-left:8px; }
    /* Customer Ratings */
    .ratings-section { background:rgba(255,255,255,.03); border-radius:16px; margin:0 auto; max-width:800px; padding:32px 28px; }
    .ratings-header { align-items:center; display:flex; justify-content:center; gap:16px; margin-bottom:24px; }
    .ratings-overall { color:var(--gold); font-family:var(--font-body); font-size:48px; font-weight:700; letter-spacing:-.03em; line-height:1; }
    .ratings-stars { display:flex; gap:4px; }
    .star { color:var(--gold); font-size:28px; position:relative; }
    .star.filled { animation:starPop 0.5s ease-out forwards; }
    .star.half { background:linear-gradient(90deg,var(--gold) 50%,rgba(244,202,91,.3) 50%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
    @keyframes starPop { 0% { transform:scale(0) rotate(-180deg); } 50% { transform:scale(1.3) rotate(10deg); } 100% { transform:scale(1) rotate(0deg); } }
    .ratings-count { color:rgba(255,255,255,.6); font-size:14px; font-weight:600; }
    .ratings-breakdown { display:grid; gap:12px; max-width:500px; margin:0 auto; }
    .rating-bar { align-items:center; display:flex; gap:12px; }
    .rating-bar-label { color:rgba(255,255,255,.8); font-size:13px; font-weight:600; min-width:70px; }
    .rating-bar-track { background:rgba(255,255,255,.1); border-radius:6px; flex:1; height:8px; overflow:hidden; }
    .rating-bar-fill { background:linear-gradient(90deg,var(--gold),var(--gold-light)); border-radius:6px; height:100%; transition:width 1s ease-out; }
    .rating-bar-value { color:rgba(255,255,255,.6); font-size:13px; font-weight:600; min-width:40px; text-align:right; }
    /* Customer stories live inside the ratings container, so they keep to its
       800px measure: eyebrow, title, paragraph, then the photo underneath. */
    .ratings-stories { margin-top:20px; }
    .ratings-stories-heading { color:#fff; font-family:var(--font-heading); font-size:22px; font-weight:400; letter-spacing:0; margin:0 0 20px; text-align:center; }
    .ratings-story-grid { align-items:start; display:grid; gap:20px; grid-template-columns:repeat(3,1fr); }
    .ratings-story { background:rgba(255,255,255,.04); border:1px solid rgba(244,202,91,.22); border-radius:12px; display:flex; flex-direction:column; overflow:hidden; }
    .ratings-story-body { display:flex; flex:1 1 auto; flex-direction:column; padding:16px 16px 14px; }
    .ratings-story-eyebrow { color:var(--gold); font-size:10px; font-weight:700; letter-spacing:.14em; margin:0 0 8px; text-transform:uppercase; }
    .ratings-story-title { color:#fff; font-family:var(--font-heading); font-size:17px; font-weight:400; letter-spacing:0; line-height:1.18; margin:0 0 9px; }
    .ratings-story-text { color:rgba(255,255,255,.72); font-size:13px; line-height:1.6; margin:0; }
    .ratings-story img { aspect-ratio:4/3; border-top:1px solid rgba(255,255,255,.09); display:block; flex:0 0 auto; height:auto; object-fit:cover; object-position:center center; width:100%; }
    .testimonial-cards { display:grid; gap:18px; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); margin-top:32px; }
    .testimonial-card { background:rgba(255,255,255,.05); border:1px solid rgba(244,202,91,.2); border-radius:12px; padding:20px; transition:transform .3s ease, box-shadow .3s ease; }
    .testimonial-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.2); }
    .testimonial-stars { display:flex; gap:3px; margin-bottom:12px; }
    .testimonial-stars .star { font-size:18px; }
    .testimonial-text { color:rgba(255,255,255,.85); font-size:14px; line-height:1.6; margin-bottom:14px; }
    .testimonial-author { align-items:center; display:flex; gap:10px; }
    .testimonial-avatar { background:linear-gradient(135deg,var(--gold),var(--gold-light)); border-radius:50%; color:var(--navy-dark); display:flex; font-size:14px; font-weight:700; height:36px; justify-content:center; width:36px; }
    .testimonial-name { color:#fff; font-size:14px; font-weight:700; }
    .testimonial-location { color:rgba(255,255,255,.5); font-size:12px; }
    /* Trust stories sit in one column: one person per row, what they say first
       then their photo, stacked down the page. Even rows flip so the eye keeps
       travelling instead of scanning a flat list. */
    .trust-story-list { display:flex; flex-direction:column; gap:34px; margin:32px auto 0; max-width:1120px; }
    .trust-story { align-items:center; background:rgba(255,255,255,.04); border:1px solid rgba(244,202,91,.2); border-radius:16px; display:flex; gap:34px; padding:26px; text-align:left; }
    .trust-story:nth-child(even) { flex-direction:row-reverse; }
    .trust-story-body, .trust-story-media { flex:1 1 0; min-width:0; }
    .trust-story-quote { color:rgba(255,255,255,.82); font-size:17px; line-height:1.65; margin:0 0 18px; }
    .trust-story-quote::before { color:var(--gold); content:"\201C"; }
    .trust-story-quote::after { color:var(--gold); content:"\201D"; }
    .trust-story-author { align-items:center; display:flex; gap:11px; }
    .trust-story-avatar { align-items:center; background:linear-gradient(135deg,var(--gold),var(--gold-light)); border-radius:50%; color:var(--navy-dark); display:flex; font-size:14px; font-weight:800; height:36px; justify-content:center; width:36px; }
    .trust-story-meta { display:flex; flex-direction:column; }
    .trust-story-name { color:var(--gold-light); font-size:15px; font-weight:700; }
    .trust-story-media { aspect-ratio:4/3; background:#0b122a; border:1px solid rgba(244,202,91,.28); border-radius:14px; overflow:hidden; }
    .trust-story-media img { display:block; height:100%; object-fit:cover; width:100%; }
    .accordions { margin:0 auto; max-width:700px; text-align:left; }
      .visit-head { margin:0 auto 34px; max-width:720px; }
      .visit-label { color:var(--gold-light); display:block; font-size:13px; font-weight:700; letter-spacing:.18em; margin:0 0 10px; text-transform:uppercase; }
      .visit-grid { align-items:start; display:grid; gap:34px; grid-template-columns:minmax(0,1fr) minmax(0,1.05fr); margin:0 auto; max-width:1180px; text-align:left; }
      .visit-card { background:rgba(255,255,255,.04); border:1px solid rgba(244,202,91,.28); border-radius:18px; padding:26px; }
      .visit-rows { display:grid; gap:20px; margin:0; }
      .visit-row { display:grid; gap:5px; }
      .visit-row-label { color:var(--gold-light); font-size:12px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; }
      .visit-row p { color:rgba(255,255,255,.82); font-size:15px; line-height:1.6; margin:0; max-width:none; }
      .visit-row a { color:#fff; font-weight:600; text-decoration:underline; text-underline-offset:3px; }
      .visit-cta { display:inline-flex; margin-top:24px; }
      .visit-media { display:grid; gap:18px; }
      .visit-gallery { display:grid; gap:14px; grid-template-columns:repeat(3,minmax(0,1fr)); }
      .visit-gallery img { aspect-ratio:4/3; border:1px solid rgba(244,202,91,.22); border-radius:12px; display:block; height:100%; object-fit:cover; object-position:center center; overflow:hidden; width:100%; }
      .visit-gallery .visit-gallery-lead { grid-column:span 3; aspect-ratio:16/9; }
      .visit-map { border:1px solid rgba(244,202,91,.28); border-radius:16px; overflow:hidden; }
      .visit-map iframe { border:0; display:block; height:100%; width:100%; }
      .visit-map-frame { aspect-ratio:16/10; }
      @media (max-width:900px) {
        .visit-grid { gap:26px; grid-template-columns:1fr; }
        .visit-gallery { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .visit-gallery .visit-gallery-lead { grid-column:span 2; }
      }
      @media (max-width:520px) {
        .visit-gallery { grid-template-columns:1fr; }
        .visit-gallery .visit-gallery-lead { grid-column:span 1; }
        .visit-card { padding:20px; }
      }
    details { border-bottom:1px solid #d9dce2; }
    summary { align-items:center; background:#7e8292; color:#fff; cursor:pointer; display:flex; font-size:15px; font-weight:700; justify-content:space-between; list-style:none; margin-top:12px; padding:14px 16px; }
    summary::-webkit-details-marker { display:none; }
    summary::after { content:'+'; font-size:20px; font-weight:400; }
    details[open] summary::after { content:'×'; }
    details p { color:rgba(255,255,255,.78); font-size:14px; line-height:1.6; padding:0 16px 10px; }
    .final { background:var(--navy); color:#fff; padding:46px 34px; text-align:center; }
    .final h2 { color:#fff; font-size:32px; }
    .final p { color:#d7dce6; font-size:16px; margin:10px auto 24px; max-width:560px; }
    .lead-form { background:var(--navy); border-radius:12px; margin:24px auto 0; max-width:460px; padding:20px 20px 22px; text-align:left; }
    .lead-form label { color:#fff; display:block; font-size:14px; font-weight:700; margin:16px 0 0; }
    .lead-form fieldset { border:0; margin:12px 0 0; padding:0; }
    .lead-form legend { color:#fff; font-size:13px; font-weight:700; margin-bottom:4px; }
    .lead-form .hint { color:var(--muted); display:block; font-size:12px; font-weight:400; margin-top:3px; }
    .lead-form input[type=text], .lead-form input[type=tel], .lead-form input[type=email], .lead-form textarea { background:rgba(255,255,255,.08); border:1px solid rgba(244,202,91,.28); border-radius:8px; color:#fff; font:16px 'Manrope','Plus Jakarta Sans',sans-serif; margin-top:7px; padding:13px 14px; width:100%; }
    .lead-form input[type=text]::placeholder, .lead-form input[type=tel]::placeholder, .lead-form input[type=email]::placeholder, .lead-form textarea::placeholder { color:rgba(255,255,255,.5); }
    .lead-form input:focus, .lead-form textarea:focus { outline:2px solid var(--gold-light); }
    .lead-form textarea { min-height:96px; resize:vertical; }
    .lead-form .opts { display:flex; gap:22px; margin-top:4px; }
    .lead-form .opts label { color:#fff; display:flex; align-items:center; gap:7px; font-size:14px; font-weight:600; margin:0; }
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
    .cc-label { align-items:center; background:#fff; border:1px solid #cfd4dc; border-radius:8px; color:var(--navy); display:inline-flex; font:600 15px 'Manrope','Plus Jakarta Sans',sans-serif; gap:7px; height:41px; padding:0 9px; white-space:nowrap; }
    .cc-label::after { border-left:4px solid transparent; border-right:4px solid transparent; border-top:5px solid var(--muted); content:''; margin-left:1px; }
    .cc-wrap:focus-within .cc-label { outline:2px solid var(--gold-light); }
    .cc-flag { border-radius:2px; box-shadow:0 0 0 1px rgba(0,0,0,.14); flex:none; height:auto; width:19px; }
    .cc-text { line-height:1; }
    .cc-wrap select { -webkit-appearance:none; appearance:none; background:transparent; border:0; cursor:pointer; height:100%; inset:0; opacity:0; position:absolute; width:100%; }
    .cc-wrap option { background:var(--navy); color:#fff; }
    .phone-row input { margin-top:0; }
    .form-nav { align-items:center; display:flex; gap:10px; margin-top:18px; }
    .form-nav .button { flex:1; font-size:15px; padding:12px 18px; }
    .btn-ghost { background:none; border:1px solid #cfd4dc; border-radius:8px; color:var(--navy); cursor:pointer; font:14px 'Manrope','Plus Jakarta Sans',sans-serif; font-weight:700; padding:11px 16px; }
    .btn-ghost:hover { border-color:var(--navy); }
    .form-msg { border-radius:8px; display:none; font-size:13.5px; font-weight:600; margin:12px 0 0; padding:10px 12px; }
    .form-msg.on { display:block; }
    .form-msg.err { background:#fdeceb; color:#a4262c; }
    .lead-form .opt { color:var(--muted); font-size:11px; font-weight:600; letter-spacing:.04em; margin-left:4px; text-transform:uppercase; }
    .lead-form select { background:rgba(255,255,255,.08) url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23f4ca5b' d='M1 1.5 6 6.5l5-5'/%3E%3C/svg%3E") no-repeat right 12px center; border:1px solid rgba(244,202,91,.28); border-radius:8px; color:#fff; font:15px 'Manrope','Plus Jakarta Sans',sans-serif; height:41px; margin-top:5px; padding:0 32px 0 11px; width:100%; -webkit-appearance:none; appearance:none; }
    .lead-form select option { color:#fff; }
.lead-form select:focus { outline:2px solid var(--gold-light); }
    /* Two-step qualification funnel */
    .lead-form { max-width:560px; padding:26px 26px 28px; }
    .form-progress { margin:0 0 20px; }
    .form-progress-track { background:#e6e9ee; border-radius:40px; display:block; height:5px; overflow:hidden; }
    .form-progress-fill { background:linear-gradient(90deg,var(--gold),var(--gold-light)); border-radius:40px; display:block; height:100%; transition:width .35s ease; width:50%; }
    .form-progress-text { color:var(--muted); display:block; font-size:11.5px; font-weight:700; letter-spacing:.11em; margin-top:9px; text-transform:uppercase; }
    .form-step-head { border-bottom:1px solid rgba(244,202,91,.28); margin:0 0 4px; padding-bottom:15px; }
    .form-step-head h3 {font-weight:700;  color:#fff; font-family:var(--font-body); font-size:21px; letter-spacing:-.01em; line-height:1.2; margin:0 0 5px; }
    .form-step-head p { color:var(--muted); font-size:13.5px; line-height:1.5; margin:0; }
    .lead-form .req { color:#c0392b; }
    .lead-form .field { margin-top:16px; }
    .lead-form .field > label { font-size:13px; letter-spacing:.02em; margin:0 0 6px; }
    .lead-form .field-error { color:#a4262c; display:block; font-size:12.5px; font-weight:600; line-height:1.4; margin-top:6px; }
    .lead-form input[aria-invalid=true], .lead-form select[aria-invalid=true], .lead-form textarea[aria-invalid=true] { border-color:#d98b8b; }
    .lead-form .q-block[data-invalid] legend { color:#a4262c; }
    .lead-form .q-block { margin:0; padding:19px 0 0; }
    .lead-form .q-block legend { font-size:14px; letter-spacing:.01em; margin:0 0 10px; padding:0; }
    .cards { display:grid; gap:9px; }
    .lead-form .card { align-items:flex-start; background:rgba(255,255,255,.08); border:1px solid rgba(244,202,91,.28); border-radius:10px; cursor:pointer; display:flex; gap:12px; margin:0; padding:13px 15px; transition:border-color .18s ease, background .18s ease, box-shadow .18s ease; }
    .lead-form .card:hover { border-color:var(--gold); }
    .lead-form .card input { height:1px; margin:0; opacity:0; position:absolute; width:1px; }
    .card-mark { border:2px solid rgba(244,202,91,.5); border-radius:50%; flex:none; height:19px; margin-top:2px; position:relative; transition:border-color .18s ease; width:19px; }
    .card-mark::after { background:var(--navy-dark); border-radius:50%; content:''; height:9px; left:50%; opacity:0; position:absolute; top:50%; transform:translate(-50%,-50%) scale(.5); transition:opacity .18s ease, transform .18s ease; width:9px; }
    .lead-form .card input:checked ~ .card-mark { border-color:var(--gold); }
    .lead-form .card input:checked ~ .card-mark::after { opacity:1; transform:translate(-50%,-50%) scale(1); }
    .lead-form .card input:focus-visible ~ .card-mark { outline:2px solid var(--gold-light); outline-offset:2px; }
    .lead-form .card.is-on { background:rgba(244,202,91,.15); border-color:var(--gold); box-shadow:0 2px 10px rgba(212,165,44,.18); }
    .card-text { display:block; min-width:0; }
    .card-title { color:#fff; display:block; font-size:14.5px; font-weight:700; line-height:1.35; }
    .card-note { color:var(--muted); display:block; font-size:12.5px; font-weight:400; line-height:1.4; margin-top:2px; }
    .lead-form .advice-check { align-items:flex-start; background:#fffdf5; border:1px solid #f0e2bd; border-radius:10px; cursor:pointer; display:flex; gap:11px; margin-top:18px; padding:14px 15px; }
    .lead-form .advice-check input { accent-color:var(--gold); flex:none; height:17px; margin:1px 0 0; width:17px; }
    .lead-form .advice-check span { color:#fff; font-size:13.5px; font-weight:600; line-height:1.45; }
    .lead-form .button[disabled] { cursor:default; opacity:.65; }
    .lead-form .button.is-busy { pointer-events:none; }
    .final .lead-success { background:#fff; border-radius:14px; box-shadow:0 20px 48px rgba(4,10,28,.34); margin:26px auto 0; max-width:560px; padding:44px 34px; }
    .lead-success:focus { outline:none; }
    .lead-success-mark { align-items:center; background:linear-gradient(140deg,var(--gold),var(--gold-light)); border-radius:50%; color:var(--navy-dark); display:inline-flex; height:66px; justify-content:center; margin-bottom:18px; width:66px; }
    .lead-success-mark svg { height:34px; width:34px; }
    .final .lead-success h3 {font-weight:400;  color:var(--navy); font-family:var(--font-heading); font-size:30px; letter-spacing:0; margin:0 0 10px; }
    .final .lead-success p { color:var(--muted); font-size:15px; line-height:1.6; margin:0 auto; max-width:400px; }
    .final .lead-success p.lead-success-note { border-top:1px solid #e6e9ee; color:var(--navy); font-size:13px; font-weight:700; margin-top:24px; padding-top:16px; }
    .lead-success-actions { display:flex; gap:12px; justify-content:center; margin-top:24px; flex-wrap:wrap; }
    .lead-success-btn { border-radius:8px; display:inline-block; font-size:14px; font-weight:700; padding:12px 24px; text-decoration:none; }
    .lead-success-btn-primary { background:var(--gold); color:var(--navy-dark); }
    .lead-success-btn-primary:hover { background:var(--gold-light); }
    .lead-success-btn-secondary { background:transparent; border:1px solid var(--navy); color:var(--navy); }
    .lead-success-btn-secondary:hover { background:var(--navy); color:#fff; }
    .form-error { background:#fbeeec; border-radius:8px; color:#7a2c25; font-size:14px; margin:0 auto 6px; max-width:520px; padding:11px 14px; }
    .thankyou { background:#fff; border-radius:12px; margin:28px auto 0; max-width:560px; padding:34px; }
    .thankyou h3 {font-weight:700;  color:var(--navy); font-family:var(--font-body); font-size:22px; margin:0 0 8px; }
    .thankyou p { color:var(--muted); font-size:15px; margin:0; }
    footer { background:var(--navy); color:#ffffff; font-size:12px; font-weight:300; line-height:1.3em; padding:35px 10px 25px; }
    .footer-inner { margin:0 auto; max-width:1170px; padding:0 5px; }
    .footer-top { align-items:center; display:flex; justify-content:space-between; min-height:29px; padding:0 10px 10px; }
.footer-brand { color:#ffffff; font-family:var(--font-body); font-size:19px; font-weight:700; letter-spacing:.01em; }
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
    .wa-float {
      align-items:center;
      background:linear-gradient(135deg,#1ecb5a,#16a34a);
      border-radius:999px;
      bottom:24px;
      box-shadow:0 18px 36px rgba(16,185,129,.38);
      color:#fff;
      display:inline-flex;
      gap:10px;
      justify-content:center;
      padding:12px 16px 12px 14px;
      position:fixed;
      right:20px;
      text-decoration:none;
      transition:transform .2s ease, box-shadow .2s ease;
      z-index:1100;
    }
    .wa-float:hover,
    .wa-float:focus-visible {
      box-shadow:0 20px 40px rgba(16,185,129,.44),0 0 30px rgba(16,185,129,.3);
      transform:translateY(-2px);
    }
    .wa-float-icon {
      align-items:center;
      background:rgba(255,255,255,.18);
      border-radius:50%;
      display:inline-flex;
      font-size:19px;
      font-weight:700;
      height:30px;
      justify-content:center;
      width:30px;
    }
    .wa-float-label {
      font-size:12px;
      font-weight:800;
      letter-spacing:.08em;
      text-transform:uppercase;
    }
    /* Pull-to-refresh */
    .pull-refresh {
      align-items:center;
      background:linear-gradient(180deg,rgba(244,202,91,.1),transparent);
      display:flex;
      gap:12px;
      height:0;
      justify-content:center;
      overflow:hidden;
      position:fixed;
      top:0;
      left:0;
      right:0;
      transition:height .3s ease;
      z-index:999;
    }
    .pull-refresh.active { height:60px; }
    .pull-refresh-icon {
      font-size:24px;
      transition:transform .3s ease;
    }
    .pull-refresh.active .pull-refresh-icon { transform:rotate(180deg); }
    .pull-refresh-text {
      color:var(--gold-light);
      font-size:13px;
      font-weight:700;
      opacity:0;
      transition:opacity .3s ease;
    }
    .pull-refresh.active .pull-refresh-text { opacity:1; }
    /* Touch-friendly interactive elements */
    .touch-target {
      min-height:44px;
      min-width:44px;
    }
    .slides-stage {
      touch-action:pan-x;
      -webkit-overflow-scrolling:touch;
    }
/* Swipe indicators */
    .swipe-hint {
      align-items:center;
      animation:swipeHint 2s ease-in-out infinite;
      background:rgba(244,202,91,.2);
      border-radius:999px;
      color:var(--gold-light);
      display:flex;
      font-size:12px;
      font-weight:700;
      gap:6px;
      justify-content:center;
      margin:12px auto 0;
      padding:8px 14px;
      opacity:0;
      pointer-events:none;
      transition:opacity .3s ease;
    }
    .swipe-hint.visible { opacity:1; }
    @keyframes swipeHint { 0%,100% { transform:translateX(0); } 50% { transform:translateX(10px); } }
    /* Swipeable gallery indicators */
    .gallery-swipe {
      position:relative;
    }
    .gallery-swipe::after {
      content:'';
      position:absolute;
      top:0;
      left:0;
      right:0;
      bottom:0;
      background:linear-gradient(90deg,transparent 0%,rgba(0,0,0,.3) 0%,rgba(0,0,0,.3) 100%,transparent 100%);
      pointer-events:none;
      opacity:0;
      transition:opacity .3s ease;
    }
    .gallery-swipe.swiping::after { opacity:1; }
    /* Modern UI Effects */
    .testimonial-card {
      background:rgba(255,255,255,.05);
      border:1px solid rgba(244,202,91,.2);
      border-radius:12px;
      padding:20px;
      transition:transform .3s ease, box-shadow .3s ease, border-color .3s ease;
    }
    .testimonial-card:hover {
      transform:translateY(-3px);
      box-shadow:0 12px 32px rgba(0,0,0,.3);
      border-color:rgba(244,202,91,.4);
    }
    /* Gradient Borders (simplified for performance) */
    .gradient-border {
      position:relative;
      background:var(--navy);
      border-radius:12px;
    }
    .gradient-border::before {
      content:'';
      position:absolute;
      inset:-2px;
      background:linear-gradient(135deg,var(--gold),var(--gold-light),var(--gold));
      border-radius:14px;
      z-index:-1;
      opacity:0;
      transition:opacity .3s ease;
    }
    .gradient-border:hover::before {
      opacity:1;
    }
    /* Micro-interactions on buttons and links (simplified for performance) */
    .button {
      position:relative;
      overflow:hidden;
      transition:transform .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .button:active {
      transform:scale(0.97);
    }
    .nav-cta {
      position:relative;
      transition:transform .2s ease, box-shadow .2s ease;
    }
    .nav-cta:active {
      transform:scale(0.97);
    }
    a {
      transition:color .2s ease;
    }
    /* Smooth page transitions (simplified for performance) */
    .section {
      opacity:0;
      transform:translateY(20px);
      transition:opacity .4s ease, transform .4s ease;
      will-change:opacity, transform;
    }
    .section.visible {
      opacity:1;
      transform:translateY(0);
    }
    .hero {
      opacity:1;
      transform:translateY(0);
    }
    /* Ripple effect on buttons */
    .ripple {
      position:relative;
      overflow:hidden;
    }
    .ripple-effect {
      position:absolute;
      border-radius:50%;
      background:rgba(255,255,255,.4);
      transform:scale(0);
      animation:ripple 0.6s linear;
      pointer-events:none;
    }
    @keyframes ripple {
      to {
        transform:scale(4);
        opacity:0;
      }
    }
    /* Hover lift effect */
    .hover-lift {
      transition:transform .3s ease, box-shadow .3s ease;
    }
    .hover-lift:hover {
      transform:translateY(-4px);
      box-shadow:0 12px 28px rgba(0,0,0,.25);
    }
    /* Glow effect on hover */
    .glow-on-hover {
      transition:box-shadow .3s ease;
    }
    .glow-on-hover:hover {
      box-shadow:0 0 20px rgba(244,202,91,.4);
    }
    /* Card shine effect (disabled for performance) */
    .card-shine {
      position:relative;
      overflow:hidden;
    }
    .back-to-top {
      align-items:center; background:var(--gold); border:1px solid rgba(9,15,36,.28); border-radius:50%; bottom:94px; box-shadow:0 8px 20px rgba(9,15,36,.28); color:var(--navy-dark); cursor:pointer; display:flex; font-size:25px; font-weight:700; height:46px; justify-content:center; line-height:1; padding:0; position:fixed; right:24px; width:46px; z-index:990;
    }
    .back-to-top:hover { background:var(--gold-light); }
    @media (max-width:640px) {
  /* Phones: trim the reading size so paragraphs stop dominating. Main body copy
     sits at 15px, supporting lines at 14px. Form fields deliberately stay at
     16px - anything smaller makes iOS zoom the page on focus. The player is
     width:100% rather than a vw value: vw counts the scrollbar and ignores the
     18px section padding, which pushed this box past the right edge and left a
     gap there. */
  body { font-size:16px; }
  img, iframe, video, embed, object { max-width:100%; }
  .section p, .zig-body, .trust-section p, .final p, .hero-intro { font-size:15px; }
  details p, .visit-row p { font-size:14px; }
  .cookie-banner { padding:16px 14px; }
  .cookie-content { flex-direction:column; align-items:flex-start; gap:12px; }
  .cookie-buttons { width:100%; justify-content:space-between; }
  .cookie-btn { flex:1; text-align:center; }
  .trust-section { padding:32px 18px; }
  .trust-section h2 { font-size:26px; }
  .proof-ticker { margin:0 auto 28px; }
  .ticker-item { padding:0 20px; }
  .ticker-item-text { font-size:13px; }
  .ratings-section { padding:24px 18px; }
  .ratings-overall { font-size:36px; }
  .ratings-stars .star { font-size:22px; }
  .ratings-count { font-size:13px; }
  .ratings-breakdown { gap:10px; }
  .rating-bar-label { font-size:12px; min-width:60px; }
  .rating-bar-value { font-size:12px; min-width:35px; }
  .ratings-stories { margin-top:16px; }
  .ratings-stories-heading { font-size:19px; }
  .ratings-story-grid { grid-template-columns:1fr; }
  .trust-story-list { gap:22px; margin-top:24px; }
  .trust-story, .trust-story:nth-child(even) { flex-direction:column; gap:18px; padding:20px; }
  .trust-story-quote { font-size:15px; margin-bottom:14px; }
  /* Column direction makes align-items:center apply to the horizontal axis, so the
     media box shrink-wraps and its height:100% image collapses to zero. Stating a
     width gives the aspect-ratio box a definite size to resolve against. */
  .trust-story-media { aspect-ratio:16/9; width:100%; }
  /* minmax(0,1fr), not 1fr: a bare 1fr is minmax(auto,1fr), so the track refuses to
     shrink below the card's minimum content width and pushes past the right edge
     of a narrow phone. Letting the track reach zero is what keeps it flush. */
  .testimonial-cards { grid-template-columns:minmax(0, 1fr); margin-top:24px; }
  .testimonial-stars .star { font-size:16px; }
  .testimonial-text { font-size:13px; }
  .wa-float { bottom:70px; padding:10px 14px 10px 12px; right:16px; }
  .wa-float-label { display:none; }
  .back-to-top { bottom:130px; right:16px; }
  .topline { font-size:9px; padding:8px 12px; }
  .swipe-hint { display:flex; }
  .swipe-hint.visible { opacity:1; }
  .button,
  .nav-cta,
  .lead-form input,
  .lead-form select,
  .lead-form textarea {
    min-height:44px;
  }
  .card {
    min-height:48px;
  }
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
  .button.hero-find-cta { border-width:5px; font-size:15px; min-height:78px; padding:14px 22px; width:min(400px,90%); }
  .section { padding:34px 18px; }
  .section h2 { font-size:26px; }
.benefits-band { min-height:150px; padding:38px 18px; }
    .benefits-band h2 { font-size:24px; }
  .benefit-media { height:210px; }
  .benefit-media .detector { transform:translateX(-45%) rotate(-12deg) scale(.59); top:-7px; }
  .slides-frame { aspect-ratio:4/3; max-height:none; }
  .slides-cap { font-size:13px; padding:36px 14px 14px; }
  .spaced-cta { padding:6px 0 34px; }
  .final { padding:38px 18px; }
  .lead-form { padding:20px 18px 24px; }
  .lead-form .card { gap:11px; padding:12px 13px; }
  .card-title { font-size:14px; }
  .card-note { font-size:12px; }
  .form-step-head h3 { font-size:19px; }
  .form-nav .button { padding:12px 12px; }
  .final .lead-success { padding:34px 20px; }
  .final .lead-success h3 { font-size:26px; }
    .field-row { grid-template-columns:1fr; }
  /* iOS Safari zooms the whole viewport when a field under 16px is focused,
     which leaves the visitor zoomed in and stranded on the booking form. */
  .lead-form input[type=text], .lead-form input[type=tel], .lead-form input[type=email], .lead-form textarea, .lead-form select { font-size:16px; }
    .form-title { font-size:22px; }
  .torn { margin:0; padding:20px 16px; }
  footer { padding-left:10px; padding-right:10px; }
  .footer-menu-btn { display:block; }
  .footer-legal { flex-wrap:wrap; padding:18px 0 10px; }
  .footer-legal a { font-size:12px; font-weight:300; }
    }
  /* Visitors who ask for less motion get instant photo changes and no
     drifting scale, rather than a faster version of the same movement. */
  @media (prefers-reduced-motion:reduce) {
    .slides-frame img, .slides-cap { transition-duration:.01ms !important; }
    .slides-frame img.on { transform:none !important; }
  }

  /* ===================================================================
     PROOF ENHANCEMENT - additive layer.
     Namespaced .pf-* (filter) and .lb-* (lightbox) so nothing here can
     collide with the existing .proof-* / .slides-* / .ring-* rules, and so
     the whole layer can be removed without touching the original code.
     =================================================================== */

  /* ---- filter bar ----
     The bar sits between the hero and the proof sections, so it has to carry
     the same dark band as both of them - otherwise a light pill row is left
     stranded on the white page background between two dark sections. */
  .pf-bar {
    align-items:center; background:var(--navy);
    display:flex; flex-wrap:wrap; gap:8px; justify-content:center;
    margin:0; padding:26px 16px 30px;
  }
  .pf-bar[hidden] { display:none; }
  .pf-tab {
    -webkit-appearance:none; appearance:none;
    background:transparent; border:1px solid rgba(212,175,55,.45);
    border-radius:999px; color:rgba(255,255,255,.9); cursor:pointer;
    font-family:inherit; font-size:12px; font-weight:700;
    letter-spacing:.12em; padding:9px 16px; text-transform:uppercase;
    transition:background-color .25s ease, border-color .25s ease, color .25s ease, transform .25s ease;
  }
  .pf-tab:hover { border-color:rgba(212,175,55,.85); color:#fff; transform:translateY(-1px); }
  .pf-tab.is-on { background:var(--gold); border-color:var(--gold); color:#111; }
  .pf-n { font-size:10px; font-weight:700; margin-left:7px; opacity:.62; }
  .pf-tab:focus-visible, .lb-btn:focus-visible, .slides-frame img:focus-visible {
    outline:2px solid var(--gold); outline-offset:3px;
  }
  /* A filter hides content by presentation only: the nodes stay in the DOM,
     so switching back restores everything exactly as it was. */
  [data-pf-group].pf-hidden { display:none; }

  /* ---- field photos become interactive ---- */
  .slides-frame img { cursor:zoom-in; }
  .slides-frame img::after { content:none; }
  /* Subtle affordance: the photos are clickable but nothing on the page
     says so yet. */
p.lb-hint,
   .lb-hint {
    color:rgba(255,255,255,.72); font-size:12px; font-weight:600;
    letter-spacing:.1em; margin:14px 0 0; max-width:none; text-align:center;
    text-transform:uppercase;
  }
  .lb-hint[hidden] { display:none; }
  /* ---- lightbox ---- */
  .lb {
    align-items:center; background:rgba(6,8,16,.94);
    display:none; inset:0; justify-content:center;
    padding:20px; position:fixed; z-index:120;
  }
  .lb.is-open { display:flex; }
  .lb-dialog {
    background:var(--navy,#0d1226); border:1px solid rgba(212,175,55,.24);
    border-radius:16px; box-shadow:0 30px 80px rgba(0,0,0,.6);
    display:grid; gap:0; grid-template-columns:1fr; margin:auto;
    max-height:92vh; max-width:1040px; overflow:hidden; position:relative; width:100%;
  }
  .lb-stage {
    align-items:center; background:#05040c; display:flex;
    justify-content:center; min-height:220px; overflow:hidden; position:relative;
  }
  .lb-img {
    display:block; height:auto; max-height:66vh; max-width:100%;
    object-fit:contain; width:100%;
  }
  .lb-info { border-top:1px solid rgba(255,255,255,.09); padding:20px 24px 22px; }
  .lb-eyebrow {
    color:var(--gold,#d4af37); font-size:11px; font-weight:700;
    letter-spacing:.16em; margin:0 0 10px; text-transform:uppercase;
  }
  .lb-caption { font-size:16px; line-height:1.55; margin:0; }
  /* Story rows only render when a real value exists, so this block is empty
     on the current content instead of showing placeholder text. */
  .lb-story { border-top:1px solid rgba(255,255,255,.08); margin-top:16px; padding-top:16px; }
  .lb-story[hidden] { display:none; }
  .lb-row { display:grid; gap:3px 14px; grid-template-columns:104px 1fr; margin:0 0 11px; }
  .lb-row:last-child { margin-bottom:0; }
  .lb-row dt {
    color:rgba(255,255,255,.62); font-size:11px; font-weight:700;
    letter-spacing:.11em; padding-top:2px; text-transform:uppercase;
  }
  .lb-row dd { font-size:14px; line-height:1.5; margin:0; }
  .lb-storytext { color:rgba(255,255,255,.86); font-size:15px; line-height:1.65; margin:0 0 16px; }
  .lb-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:18px; }
  .lb-link {
    border:1px solid rgba(212,175,55,.4); border-radius:999px; color:var(--gold,#d4af37);
    display:inline-block; font-size:12px; font-weight:700; letter-spacing:.1em;
    padding:9px 16px; text-decoration:none; text-transform:uppercase;
    transition:background-color .2s ease, color .2s ease;
  }
  .lb-link:hover { background:var(--gold,#d4af37); color:#111; }
  .lb-btn {
    -webkit-appearance:none; appearance:none; background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.18); border-radius:50%; color:#fff;
    cursor:pointer; display:flex; font-size:20px; height:44px; line-height:1;
    align-items:center; justify-content:center; padding:0; width:44px;
    transition:background-color .2s ease, border-color .2s ease, transform .2s ease;
  }
  .lb-btn:hover { background:rgba(212,175,55,.9); border-color:var(--gold,#d4af37); color:#111; transform:scale(1.06); }
  .lb-close {
    align-items:center; background:rgba(6,8,16,.72); border:1px solid rgba(255,255,255,.2);
    display:flex; height:40px; justify-content:center; line-height:1;
    position:absolute; right:12px; top:12px; width:40px; z-index:2;
  }
  .lb-nav {
    align-items:center; background:rgba(6,8,16,.72); border:1px solid rgba(255,255,255,.2);
    display:flex; height:52px; justify-content:center; position:absolute;
    top:50%; transform:translateY(-50%); width:52px; z-index:2;
  }
  .lb-prev { left:12px; }
  .lb-next { right:12px; }
  .lb-count {
    color:rgba(255,255,255,.7); font-size:12px; font-weight:700; letter-spacing:.14em;
    text-align:right;
  }
  .lb.is-swiping .lb-img { transition:none; }

   @media (max-width:760px) {
     .pf-bar { gap:7px; padding:20px 12px 24px; }
     /* Keep the tap target at 44px even though the label itself is smaller. */
     .pf-tab { font-size:11px; min-height:44px; padding:8px 13px; }
    .lb { padding:0; }
    .lb-dialog { border-radius:0; height:100%; max-height:100%; max-width:none; }
    .lb-stage { flex:1 1 auto; }
    .lb-img { max-height:none; }
    .lb-info { max-height:46vh; overflow-y:auto; padding:16px 18px 20px; }
    .lb-nav { height:46px; width:46px; }
    .lb-prev { left:8px; }
    .lb-next { right:8px; }
    .lb-close { height:44px; right:8px; top:8px; width:44px; }
    .lb-row { grid-template-columns:1fr; gap:1px; }
  }
  @media (prefers-reduced-motion:reduce) {
    .pf-tab, .lb-btn, .lb-link { transition:none; }
.section { opacity:1; transform:none; }
      .star { animation:none; }
    .proof-ticker-track { animation:none; }
    .gradient-border-animated::before { animation:none; }
    .ripple-effect { animation:none; }
    .card-shine::before { animation:none; }
  }
  </style>
</head>
<body>
  <div class="pull-refresh" id="pullRefresh">
    <span class="pull-refresh-icon">↻</span>
      <span class="pull-refresh-text"><?= h($hplCopy('pull_refresh_text', 'Release to refresh')) ?></span>
  </div>
  <div class="page">
    <div class="topline"><?= h($s['topline'] ?? 'HPL Gold Detectors | Built for the serious prospector') ?></div>
    <header><div class="nav"><a class="brand logo-chip" href="#top"><img class="header-logo" src="<?= hpl_img_url('hpllogo.jpeg') ?>" alt="<?= h($hplCopy('logo_alt', 'HPL Gold Detectors')) ?>"></a><nav class="nav-links"><a href="#what-you-get"><?= h($s['nav_1'] ?? 'The Detector') ?></a><a href="#faq"><?= h($s['nav_2'] ?? 'FAQ') ?></a></nav><a class="nav-cta" href="#book"><?= h($s['nav_cta'] ?? 'Get yours now') ?></a></div></header>
    <main id="top">
      <section class="hero" id="hero">
        <span class="live-pill"><i></i><?= h($s['live_pill'] ?? 'New Gold Detectors — Now Shipping') ?></span>
        <h1><?= h($s['hero_h1'] ?? 'Are you leaving gold in the ') ?><span><?= h($s['hero_h1_span'] ?? 'ground?') ?></span></h1>
        <p class="hero-sub"><?= h($s['hero_sub'] ?? 'Introducing our Gold Detectors — field-tested for the ground you actually search.') ?></p>
        <p class="hero-intro"><?= h($hplCopy('hero_intro', $s['hero_intro'] ?? 'Watch the video to see how to use it, where to search, and why this machine finds targets others miss.')) ?></p>
        <div class="detector-panel promo-embed">
          <?php
            /* Promotional video is served from Bunny Stream. The library and
               video IDs are the only things that change if the video is ever
               replaced; everything else is fixed by the embed contract. */
            $bunnyLibrary = '767583';
            $bunnyVideo   = '62e9fec8-7052-4949-8a03-8104493b3795';
            $bunnySrc     = 'https://player.mediadelivery.net/embed/' . $bunnyLibrary . '/' . $bunnyVideo;
            $bunnyParams = 'autoplay=false&muted=false&loop=false&preload=true&responsive=true&playsinline=true';
            $bunnyTitle  = (string)($s['hero_h1'] ?? 'HPL Sales promo');
          ?>
          <div class="promo-frame">
            <iframe
              class="promo-frame-el"
              id="promoPlayer"
              src="<?= h($bunnySrc . '?' . $bunnyParams) ?>"
              title="<?= h($bunnyTitle) ?>"
              loading="eager"
              allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture; fullscreen"
              referrerpolicy="strict-origin-when-cross-origin"
              allowfullscreen="true"></iframe>
          </div>
          <noscript>
            <div class="promo-frame-fallback">
              <a href="<?= h($bunnySrc) ?>" target="_blank" rel="noopener"><?= h($hplCopy('promo_fallback_text', 'Watch the HPL promo video')) ?></a>
            </div>
          </noscript>
        </div>
        <p class="hero-copy"><?= h($s['hero_caption'] ?? 'A practical guide to choosing, setting up, and using the right detector for your ground, goals, and experience.') ?></p>
        <a class="button hero-find-cta" href="#book" aria-disabled="true" data-ready-label="<?= h($s['cta_text'] ?? 'I\'M READY TO FIND GOLD') ?>"><?= h($hplCopy('hero_cta_locked', 'WATCH THE VIDEO TO UNLOCK')) ?></a>
      </section>

      <?php
      /* Collected first because the filter bar below reports on it. Same
         loop and same values as before - only hoisted, nothing altered. */
      $proofItems = [];
      for ($i = 1; $i <= 15; $i++) {
          $raw = (string)($s['proof_video_' . $i] ?? '');
          /* An embed is checked first: a pasted snippet is not a media URL, so
             handing it to hpl_media_url() would produce nothing usable. */
          $embed = hpl_embed_url($raw);
          if ($embed !== '') {
              /* Both playback modes are kept on the item. The Real Results player
                 autoplays muted, the customer advice clip waits to be started, and
                 the caller picks which one it needs without rebuilding the URL. */
              $proofItems[] = [
                  'kind'     => 'embed',
                  'src'      => $embed,
                  'src_auto' => hpl_embed_url($raw, true),
                  'caption'  => (string)($s['proof_video_' . $i . '_caption'] ?? ''),
              ];
              continue;
          }
          $src = hpl_media_url($raw);
          if ($src === '') { continue; }
          $proofItems[] = ['kind' => 'video', 'src' => $src, 'caption' => (string)($s['proof_video_' . $i . '_caption'] ?? '')];
      }
          $customerAdviceItem = $proofItems[1] ?? null;
      /* The proof_layout setting is intentionally not read any more. The section
         renders one fixed player now, so a stored ring/strip/coverflow value must
         not be able to bring a carousel back. The admin still writes the key, so
         an existing database row stays valid either way. */

      /* Proof content model.
         Categories are declared once here and rendered from data, so a new
         proof type (customer message, find photo, detector screenshot, result)
         can be added later by appending one group plus its section markup -
         with no change to the filter, the nav or the lightbox.

         Only groups that actually hold content are emitted, so a category can
         never appear as an empty or broken tab. Counts are read from the same
         sources the sections below already use, which keeps this additive
         layer from becoming a second source of truth. */
      $proofGroups = [];
      if (!empty($proofItems)) {
          /* Only one proof video is rendered, so the tab advertises one item even
         when several are configured. Reporting the configured count would promise
         videos the section no longer shows. */
      $proofGroups[] = ['key' => 'videos', 'label' => $hplCopy('proof_label_videos', 'Videos'), 'count' => 1];
      }
      $photoCount = 0;
      for ($i = 1; $i <= 6; $i++) {
          if (file_exists(__DIR__ . '/img/slide-' . $i . '.jpg')) { $photoCount++; }
      }
      if ($photoCount > 0) {
          $proofGroups[] = ['key' => 'photos', 'label' => $hplCopy('proof_label_photos', 'Field Photos'), 'count' => $photoCount];
      }
      ?>
      <?php if (count($proofGroups) > 0): ?>
      <div class="pf-bar" id="pfBar" role="group" aria-label="Filter customer proof">
        <button type="button" class="pf-tab is-on" data-pf="all" aria-pressed="true"><?= h($hplCopy('pf_filter_all', 'All')) ?></button>
        <?php foreach ($proofGroups as $g): ?>
        <button type="button" class="pf-tab" data-pf="<?= h($g['key']) ?>" aria-pressed="false"
                aria-label="<?= h($g['label'] . ', ' . (int)$g['count'] . ' items') ?>">
          <?= h($g['label']) ?><span class="pf-n" aria-hidden="true"><?= (int)$g['count'] ?></span>
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Social Proof Ticker: Zambia -->
<?php
      $tickerZambiaItems = [
          ['icon' => 'ticker_z1_icon', 'iconDefault' => '✨', 'name' => 'ticker_z1_name', 'nameDefault' => 'Chileshe M.', 'text' => 'ticker_z1_text', 'textDefault' => 'found 2.1oz gold in Lusaka', 'time' => 'ticker_z1_time', 'timeDefault' => '3h ago'],
          ['icon' => 'ticker_z2_icon', 'iconDefault' => '🎯', 'name' => 'ticker_z2_name', 'nameDefault' => 'Mutinta K.', 'text' => 'ticker_z2_text', 'textDefault' => 'purchased MAGNETAR 5000', 'time' => 'ticker_z2_time', 'timeDefault' => '5h ago'],
          ['icon' => 'ticker_z3_icon', 'iconDefault' => '💎', 'name' => 'ticker_z3_name', 'nameDefault' => 'Mwamba B.', 'text' => 'ticker_z3_text', 'textDefault' => 'shipped to Kitwe', 'time' => 'ticker_z3_time', 'timeDefault' => '7h ago'],
          ['icon' => 'ticker_z4_icon', 'iconDefault' => '🚀', 'name' => 'ticker_z4_name', 'nameDefault' => 'Kabaso N.', 'text' => 'ticker_z4_text', 'textDefault' => 'found 1.6oz in Livingstone', 'time' => 'ticker_z4_time', 'timeDefault' => '11h ago'],
          ['icon' => 'ticker_z5_icon', 'iconDefault' => '⭐', 'name' => 'ticker_z5_name', 'nameDefault' => 'Chimwemwe S.', 'text' => 'ticker_z5_text', 'textDefault' => 'left a 5-star review', 'time' => 'ticker_z5_time', 'timeDefault' => '14h ago'],
          ['icon' => 'ticker_z6_icon', 'iconDefault' => '🏅', 'name' => 'ticker_z6_name', 'nameDefault' => 'Sakala T.', 'text' => 'ticker_z6_text', 'textDefault' => 'discovered a nugget near Chiluba', 'time' => 'ticker_z6_time', 'timeDefault' => '19h ago'],
      ];
      $tickerZambia = [];
      foreach ($tickerZambiaItems as $item) {
          $tickerZambia[] = [
              'icon' => $hplCopy($item['icon'], $item['iconDefault']),
              'name' => $hplCopy($item['name'], $item['nameDefault']),
              'text' => $hplCopy($item['text'], $item['textDefault']),
              'time' => $hplCopy($item['time'], $item['timeDefault']),
          ];
      }
      $tickerZambia = array_merge($tickerZambia, $tickerZambia);
      ?>
      <div class="proof-ticker after-filter">
        <div class="proof-ticker-track">
<?php foreach ($tickerZambia as $entry): ?>
            <div class="ticker-item">
              <span class="ticker-item-icon"><?= h($entry['icon']) ?></span>
              <span class="ticker-item-text"><strong><?= h($entry['name']) ?></strong> <?= h($entry['text']) ?></span>
              <span class="ticker-item-time"><?= h($entry['time']) ?></span>
            </div>
<?php endforeach; ?>
        </div>
      </div>

      <section data-pf-group="videos" class="section center wash proof"><div class="section-label"><?= h($s['social_label']) ?></div><h2><?= h($s['social_heading']) ?></h2><?php if (empty($proofItems)): ?><div class="proof-empty">Customer videos will appear here once added from the admin panel.</div><?php else: $featuredProof = $proofItems[0]; /* This testimonial plays as the visitor reaches it, so use the muted autoplay variant rather than the stored click-to-play URL. */ if (isset($featuredProof['src_auto'])) { $featuredProof['src'] = $featuredProof['src_auto']; } ?><figure class="proof-single"><?= hpl_proof_media($featuredProof, 'preload="none"') ?><?php if ($featuredProof['caption'] !== ''): ?><figcaption><?= h($featuredProof['caption']) ?></figcaption><?php endif; ?></figure><?php endif; ?><p class="proof-caption"><?= h($s['social_caption']) ?></p>
      </section>

      <?php
$proofVisuals = [];
          foreach ([
            'proof-1.jpg' => [
              'alt' => 'Detectorist holding a detector and recovered gold',
              'title' => 'The moment it pays off',
              'text' => 'A clean signal, a measured target, and gold in the hand. This is the whole reason the machine is tuned the way it is: depth you can feel, and discrimination that keeps quiet on everything else.',
            ],
            'proof-2.jpg' => [
              'alt' => 'Gold detector standing in worked ground',
              'title' => 'Steady on worked ground',
              'text' => 'Old ground is full of iron, scrap and hot rock that sends cheap machines into a permanent chatter. Ours is set to separate the recoverable signal from the noise, so you spend the afternoon digging targets instead of bottle tops.',
            ],
            'proof-3.jpg' => [
              'alt' => 'Gold detector shown across the field terrain',
              'title' => 'Ready for real terrain',
              'text' => 'River banks and washouts, old campsites, relic ground and park sites. Choose the ground you hunt most, switch the preset in seconds, and keep working instead of re-tuning.',
            ],
            'proof-4.jpg' => [
              'alt' => 'Detectorist working a fresh seam',
              'title' => 'Working a fresh seam',
              'text' => 'Sweep slowly and read the tone. A fresh seam gives up its reward quickly when the settings are right, which is exactly what the how-where-why playbook walks you through before you ever dig.',
            ],
          ] as $file => $visual) {
            if (file_exists(__DIR__ . '/img/' . $file)) { $proofVisuals[] = $visual + ['file' => $file]; }
          }
      ?>
      <?php if ($proofVisuals): ?>
      <section class="section proof-visuals">
        <div class="section-label"><?= h($hplCopy('field_photos_label', 'From the field')) ?></div>
        <h2><?= h($hplCopy('field_photos_heading', 'Real equipment. Real ground.')) ?></h2>
        <div class="zig">
          <?php foreach ($proofVisuals as $visual): ?>
          <div class="zig-row">
<div class="zig-text">
                <h3 class="zig-title"><?= h($visual['title']) ?></h3>
                <p class="zig-body"><?= h($visual['text']) ?></p>
              </div>
            <div class="zig-media">
              <img src="<?= hpl_img_url($visual['file']) ?>" alt="<?= h($visual['alt']) ?>" loading="lazy">
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="cta-reminder">
          <a class="button" href="#book"><?= h($s['cta_text']) ?></a>
        </div>
      </section>
      <?php endif; ?>

      <?php
      /* More field stories: the field photos and the customer clips interleaved
         into one zig-zag run. Both are read from the sources the rest of the page
         already uses - the slide-N.jpg files and the configured proof videos - so
         adding a photo or a clip in the admin panel is all it takes to extend it.
         id="slidesFrame" stays on the row wrapper because the field photo
         lightbox below reads it to find the photos it can open. */
      $slideItems = [];
      for ($i = 1; $i <= 6; $i++) {
          $f = 'slide-' . $i . '.jpg';
          if (file_exists(__DIR__ . '/img/' . $f)) {
              $slideItems[] = ['file' => $f, 'caption' => (string)($s['slide_' . $i . '_caption'] ?? '')];
          }
      }
/* Body copy for the rows below. Keyed by number because the admin has no
          field for it yet: photo N takes key N of the first list, clip N takes key
          N of the second. */
       $fieldPhotoBodies = [
           1 => $hplCopy('field_photo_body_1', 'A worked mine dump is the most honest test there is. Iron litter, slag and old cuttings on every swing, and the target still came through clean and repeatable instead of dissolving into the iron.'),
           2 => $hplCopy('field_photo_body_2', 'Two weeks from a first outing to a first piece, on ground that had already been worked by others. The learning is mostly in the swing - slow, wide, close to the soil - and once that settles the machine does its half.'),
           3 => $hplCopy('field_photo_body_3', 'Three days of wet sand before the win, on the one ground that punishes a machine for talking over iron. The target held its shape at the edge of coverage, which is exactly where cheap detectors give up.'),
           4 => $hplCopy('field_photo_body_4', 'Relics sit in the shallows beside old camps, in iron ground that hides everything else. What you need here is patience and a target you can still trust at the limit of your sweep.'),
           5 => $hplCopy('field_photo_body_5', 'A fresh washout is good odds while it lasts. Work the seam as it opens and it pays; wait a day and the water has filled it back in. The whole game is getting there while the ground is open.'),
           6 => $hplCopy('field_photo_body_6', 'Twenty minutes on a worked patch and a cluster of coins. Finds this quick are the ones that keep people out on weekends - fast, and they prove the setup before you commit to a full dig.'),
       ];
       $fieldClipBodies = [
           1 => $hplCopy('field_clip_body_1', 'The find on video, from the first signal to the piece in the hand. Worth watching if you would rather see how the machine behaves on a live target than read about it.'),
           2 => $hplCopy('field_clip_body_2', 'The full dig, filmed where it happened. It is the quickest way to judge a detector honestly, because the swing and the call are both still visible instead of edited out.'),
           3 => $hplCopy('field_clip_body_3', 'Another ground, another target, same discipline. These clips are here because detector performance is easy to claim and easy to film, so we filmed it instead.'),
           4 => $hplCopy('field_clip_body_4', 'A short one from a relic hunt, kept short because the interesting part is the sweep and the decision to dig, not the walk in.'),
           5 => $hplCopy('field_clip_body_5', 'The last clip for now. Same ground, same settings - and the point still stands that the machine only helps if you work it properly.'),
       ];
      $fieldStories = [];
      $photoNo = 0;
      $videoNo = 0;
      $videoIndex = 0;
      $lastPhoto = count($slideItems) - 1;
      foreach ($slideItems as $idx => $slide) {
          $photoNo++;
          $fieldStories[] = [
              'kind'  => 'photo',
              'file'  => $slide['file'],
              'cap'   => $slide['caption'],
              'title' => $slide['caption'] !== '' ? $slide['caption'] : $hplCopy('field_photo_fallback', 'Field photo') . ' ' . $photoNo,
              'body'  => $fieldPhotoBodies[$photoNo] ?? '',
          ];
          /* A clip goes in the gap after each photo while clips remain, so the
             section alternates photo / clip instead of sitting as two blocks of
             one medium. The last photo closes the run. */
          if ($idx < $lastPhoto && isset($proofItems[$videoIndex])) {
              $videoNo++;
              $clip = $proofItems[$videoIndex];
              $fieldStories[] = [
                  'kind'  => 'video',
                  'item'  => $clip,
                  'title' => $clip['caption'] !== '' ? $clip['caption'] : $hplCopy('field_clip_fallback', 'Field clip') . ' ' . $videoNo,
                  'body'  => $fieldClipBodies[$videoNo] ?? '',
              ];
              $videoIndex++;
          }
      }
      ?>
      <section data-pf-group="photos" class="section center slides">
        <div class="section-label"><?= h($s['slides_label']) ?></div>
        <h2><?= h($s['slides_heading']) ?></h2>
        <?php if (empty($fieldStories)): ?>
        <div class="slides-empty">Field photos will appear here once added from the admin panel.</div>
        <?php else: ?>
        <div class="zig" id="slidesFrame">
          <?php foreach ($fieldStories as $story): ?>
          <div class="zig-row">
            <div class="zig-text">
              <h3 class="zig-title"><?= h($story['title']) ?></h3>
              <?php if ($story['body'] !== ''): ?>
              <p class="zig-body"><?= h($story['body']) ?></p>
              <?php endif; ?>
            </div>
            <?php if ($story['kind'] === 'video'): ?>
            <div class="zig-media story-media">
              <?= hpl_proof_media($story['item'], 'preload="none"') ?>
            </div>
            <?php else: ?>
            <div class="zig-media">
              <img src="<?= hpl_img_url($story['file']) ?>" alt="<?= h($story['cap'] !== '' ? $story['cap'] : 'Customer field photo') ?>" data-caption="<?= h($story['cap']) ?>" loading="lazy">
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      
        <div class="cta-reminder">
          <a class="button" href="#book"><?= h($s['cta_text']) ?></a>
        </div>
      </section>

      <?php
        $fieldProofImage1 = file_exists(__DIR__ . '/img/field-proof-1.jpg') ? hpl_img_url('field-proof-1.jpg') : hpl_img_url('story-photo-1.jpg');
        $fieldProofImage2 = file_exists(__DIR__ . '/img/field-proof-2.jpg') ? hpl_img_url('field-proof-2.jpg') : hpl_img_url('story-photo-2.jpg');
      ?>
      <?php if (($s['field_proof_enabled'] ?? '1') === '1'): ?>
      <section class="section proof-mix proof-field-section">
        <div class="section-label"><?= h($s['field_proof_label'] ?? 'Field proof') ?></div>
        <h2><?= h($s['field_proof_heading'] ?? 'What the ground is proving') ?></h2>
        <div class="proof-mix-grid">
          <article class="proof-mix-story">
            <div class="proof-mix-story-header">
              <div class="eyebrow"><?= h($s['field_proof_1_eyebrow'] ?? 'Field proof') ?></div>
              <h3><?= h($s['field_proof_1_title'] ?? 'Detectorist story') ?></h3>
            </div>
            <div class="proof-mix-story-body">
              <div class="proof-mix-story-copy">
                <h4><?= h($s['field_proof_1_headline'] ?? 'Choosing the right detector for challenging ground.') ?></h4>
                <p><?= h($s['field_proof_1_text'] ?? '') ?></p>
                <div class="proof-mix-story-actions">
                  <a class="primary" href="#book"><?= h($s['field_proof_1_cta'] ?? 'Book a call') ?></a>
                </div>
              </div>
              <div class="proof-mix-story-media">
                <img src="<?= $fieldProofImage1 ?>" alt="<?= h($s['field_proof_1_title'] ?? 'Detectorist story') ?>" loading="lazy">
              </div>
            </div>
          </article>

          <article class="proof-mix-story">
            <div class="proof-mix-story-header">
              <div class="eyebrow"><?= h($s['field_proof_2_eyebrow'] ?? 'Field photo') ?></div>
              <h3><?= h($s['field_proof_2_title'] ?? 'On-ground proof') ?></h3>
            </div>
            <div class="proof-mix-story-body">
              <div class="proof-mix-story-copy">
                <h4><?= h($s['field_proof_2_headline'] ?? 'Customer field shots showing real usage and working conditions.') ?></h4>
                <p><?= h($s['field_proof_2_text'] ?? '') ?></p>
                <div class="proof-mix-story-actions">
                  <a class="primary" href="#what-you-get"><?= h($s['field_proof_2_cta'] ?? 'See the field kit') ?></a>
                </div>
              </div>
              <div class="proof-mix-story-media">
                <img src="<?= $fieldProofImage2 ?>" alt="<?= h($s['field_proof_2_title'] ?? 'On-ground proof') ?>" loading="lazy">
              </div>
            </div>
          </article>

        </div>
      </section>
      <?php endif; ?>

      <section class="section proof-mix">
        <div class="section-label"><?= h($hplCopy('voices_label', 'Customer voices')) ?></div>
        <h2><?= h($hplCopy('voices_heading', 'See what our customers are saying')) ?></h2>
        <div class="zig">
          <div class="zig-row">
            <div class="zig-text">
              <h3 class="zig-title"><?= h($hplCopy('voice_whatsapp_title', 'WhatsApp')) ?></h3>
              <p class="voice-meta"><span class="proof-social-dot" style="background:linear-gradient(135deg,#2dd4bf,#14b8a6);"></span><?= h($hplCopy('voice_whatsapp_meta', 'Today')) ?></p>
              <p class="zig-body <?= h($hplCopy('voice_whatsapp_text', '') === '' ? 'voice-todo' : '') ?>"><?= h($hplCopy('voice_whatsapp_text', 'TODO &mdash; paste the customer\'s real WhatsApp Today message here. No real message text exists for this row yet, so nothing is shown rather than inventing one.')) ?></p>
            </div>
            <div class="zig-media voice-media">
              <div class="proof-social-screenshot whatsapp<?= file_exists(__DIR__ . '/img/customer-voice-whatsapp.jpg') ? ' has-image' : '' ?>">
                <?php if (file_exists(__DIR__ . '/img/customer-voice-whatsapp.jpg')): ?>
                <img src="<?= hpl_img_url("customer-voice-whatsapp.jpg") ?>" alt="WhatsApp message from an HPL customer" loading="lazy">
                <?php else: ?>
                <div class="mock">
                  <div class="mock-head"><span class="mock-avatar"></span><span class="mock-name"></span></div>
                  <span class="mock-line long"></span>
                  <span class="mock-line mid"></span>
                  <span class="mock-line short"></span>
                  <span class="mock-chip"></span>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="zig-row">
            <div class="zig-text">
              <h3 class="zig-title"><?= h($hplCopy('voice_facebook_title', 'Facebook')) ?></h3>
              <p class="voice-meta"><span class="proof-social-dot" style="background:linear-gradient(135deg,#60a5fa,#3b82f6);"></span><?= h($hplCopy('voice_facebook_meta', 'Post')) ?></p>
              <p class="zig-body <?= h($hplCopy('voice_facebook_text', '') === '' ? 'voice-todo' : '') ?>"><?= h($hplCopy('voice_facebook_text', 'TODO &mdash; paste the customer\'s real Facebook Post message here. No real message text exists for this row yet, so nothing is shown rather than inventing one.')) ?></p>
            </div>
            <div class="zig-media voice-media">
              <div class="proof-social-screenshot facebook<?= file_exists(__DIR__ . '/img/customer-voice-facebook.jpg') ? ' has-image' : '' ?>">
                <?php if (file_exists(__DIR__ . '/img/customer-voice-facebook.jpg')): ?>
                <img src="<?= hpl_img_url("customer-voice-facebook.jpg") ?>" alt="Facebook post from an HPL customer" loading="lazy">
                <?php else: ?>
                <div class="mock">
                  <div class="mock-head"><span class="mock-avatar" style="background:linear-gradient(135deg,#f9a8d4,#ec4899);"></span><span class="mock-name"></span></div>
                  <span class="mock-line long"></span>
                  <span class="mock-line mid"></span>
                  <span class="mock-line short"></span>
                  <span class="mock-chip" style="background:#dbeafe; width:110px;"></span>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="zig-row">
            <div class="zig-text">
              <h3 class="zig-title"><?= h($hplCopy('voice_tiktok_title', 'TikTok')) ?></h3>
              <p class="voice-meta"><span class="proof-social-dot" style="background:linear-gradient(135deg,#f472b6,#a855f7);"></span><?= h($hplCopy('voice_tiktok_meta', 'Video')) ?></p>
              <p class="zig-body <?= h($hplCopy('voice_tiktok_text', '') === '' ? 'voice-todo' : '') ?>"><?= h($hplCopy('voice_tiktok_text', 'TODO &mdash; paste the customer\'s real TikTok Video message here. No real message text exists for this row yet, so nothing is shown rather than inventing one.')) ?></p>
            </div>
            <div class="zig-media voice-media">
              <div class="proof-social-screenshot tiktok<?= file_exists(__DIR__ . '/img/customer-voice-tiktok.jpg') ? ' has-image' : '' ?>">
                <?php if (file_exists(__DIR__ . '/img/customer-voice-tiktok.jpg')): ?>
                <img src="<?= hpl_img_url("customer-voice-tiktok.jpg") ?>" alt="TikTok post featuring an HPL detector" loading="lazy">
                <?php else: ?>
                <div class="mock">
                  <div class="mock-head"><span class="mock-avatar" style="background:linear-gradient(135deg,#fcd34d,#f59e0b);"></span><span class="mock-name"></span></div>
                  <span class="mock-line long"></span>
                  <span class="mock-line mid"></span>
                  <span class="mock-line short"></span>
                  <span class="mock-chip" style="background:#fce7f3; width:94px;"></span>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      
        <div class="cta-reminder">
          <a class="button" href="#book"><?= h($s['cta_text']) ?></a>
        </div>
      </section>

      <section id="what-you-get"><div class="benefits-band"><h2><?= h($s['benefits_label']) ?></h2></div><div class="section"><div class="zig"><div class="zig-row">
          <div class="zig-text"><h3 class="zig-title"><?= h($s['b1_title']) ?></h3><p class="zig-body"><?= h($s['b1_desc']) ?></p></div>
          <div class="zig-media benefit-media"><?= art_block('benefit-1.jpg') ?></div>
        </div><div class="zig-row">
          <div class="zig-text"><h3 class="zig-title"><?= h($s['b2_title']) ?></h3><p class="zig-body"><?= h($s['b2_desc']) ?></p></div>
          <div class="zig-media benefit-media"><?= art_block('benefit-2.jpg') ?></div>
        </div><div class="zig-row">
          <div class="zig-text"><h3 class="zig-title"><?= h($s['b3_title']) ?></h3><p class="zig-body"><?= h($s['b3_desc']) ?></p></div>
          <div class="zig-media benefit-media"><?= art_block('benefit-3.jpg') ?></div>
        </div></div></div>
      </section>

      <div class="spaced-cta"><a class="button ripple" href="#book"><?= h($s['cta_text']) ?></a></div>

      <?php
      /* One-column trust stories: one person per row - what they say about
         trusting us, then their photo. Held in a plain array rather than admin
         keys so this section adds nothing to the settings tables. Photos read
         img/trust-story-N.jpg and fall back to an existing field photo until
         those are uploaded, so nothing renders broken. */
$trustStories = [
          [
              'name'     => $hplCopy('trust_1_name', 'Chileshe M.'),
              'location' => $hplCopy('trust_1_location', 'Lusaka, Zambia'),
              'quote'    => $hplCopy('trust_1_quote', 'I had been quoted twice before I walked in here, and both times I was told the cheapest machine would do the same job. HPL was the only shop that asked what ground I was on, how deep I was digging, and what I had already dug up before recommending anything. That conversation saved me a year of guessing.'),
              'image'    => 'trust-story-1.jpg',
              'fallback' => 'story-photo-1.jpg',
          ],
          [
              'name'     => $hplCopy('trust_2_name', 'Mutinta K.'),
              'location' => $hplCopy('trust_2_location', 'Kitwe, Zambia'),
              'quote'    => $hplCopy('trust_2_quote', 'I called on a Sunday expecting to leave a voicemail. A person picked up, knew my machine by name, and told me exactly which part to look at. Two weeks later I hit a seam I had walked over a dozen times. Worth every kwacha.'),
              'image'    => 'trust-story-2.jpg',
              'fallback' => 'story-photo-2.jpg',
          ],
          [
              'name'     => $hplCopy('trust_3_name', 'Mwamba B.'),
              'location' => $hplCopy('trust_3_location', 'Ndola, Zambia'),
              'quote'    => $hplCopy('trust_3_quote', 'What sold me was not the pitch, it was the follow-up three weeks later. They asked how the machine was performing on my ground and actually listened to the answer. I have bought from three other suppliers. None of them called back.'),
              'image'    => 'trust-story-3.jpg',
              'fallback' => 'story-photo-3.jpg',
          ],
      ];
      ?>
      <section class="section trust-stories">
        <div class="section-label"><?= h($hplCopy('trust_label', 'In their words')) ?></div>
        <h2><?= h($hplCopy('trust_heading', 'Why detectorists trust us')) ?></h2>
        <div class="trust-story-list">
<?php foreach ($trustStories as $trustStory): ?>
<?php
  $trustFile = is_file(__DIR__ . '/img/' . $trustStory['image']) ? $trustStory['image'] : $trustStory['fallback'];
  $trustInitial = function_exists('mb_substr') ? mb_substr($trustStory['name'], 0, 1) : substr($trustStory['name'], 0, 1);
?>
          <article class="trust-story">
            <div class="trust-story-body">
              <blockquote class="trust-story-quote"><?= h($trustStory['quote']) ?></blockquote>
              <div class="trust-story-author">
                <span class="trust-story-avatar" aria-hidden="true"><?= h(strtoupper($trustInitial)) ?></span>
                <span class="trust-story-meta">
                  <span class="trust-story-name"><?= h($trustStory['name']) ?></span>
                  <span class="trust-story-location"><?= h($trustStory['location']) ?></span>
                </span>
              </div>
            </div>
            <div class="trust-story-media">
              <img src="<?= hpl_img_url($trustFile) ?>" alt="<?= h($trustStory['name']) ?> from <?= h($trustStory['location']) ?>" loading="lazy">
            </div>
          </article>
<?php endforeach; ?>
        </div>
      </section>

      <!-- Trust Indicators Section -->
      <section class="trust-section">
        <h2><?= h($hplCopy('ratings_heading', 'Trusted by Detectorists Across Africa')) ?></h2>
        <p><?= h($hplCopy('trust_sub', 'Join hundreds of successful gold prospectors who trust HPL equipment for their discoveries.')) ?></p>

        <!-- Trust picture cards -->
        <div class="zig">
          <div class="zig-row">
            <div class="zig-text">
              <h3 class="zig-title"><?= h($hplCopy('trust_customers_title', '500+ Happy Customers')) ?></h3>
              <p class="zig-body"><?= h($hplCopy('trust_customers_text', 'Detectorists in 12+ countries have put an HPL machine to work on their own ground. The photos on this page are the proof: real equipment, real recoveries, and owners who come back to tell us where they found it.')) ?></p>
            </div>
            <div class="zig-media">
              <img src="<?= hpl_img_url('stat-1.jpg') ?>" alt="HPL customers and detectorists in the field" loading="lazy">
            </div>
          </div>
          <div class="zig-row">
            <div class="zig-text">
              <h3 class="zig-title"><?= h($hplCopy('trust_ounces_title', $hplCopy('trust_zig_title', '2000+ Ounces Found'))) ?></h3>
              <p class="zig-body"><?= h($hplCopy('trust_ounces_text', 'More than two thousand ounces have come out of the ground with our detectors, and the people who bought them rate them 4.9 out of 5. We would rather earn that number in the field than advertise it.')) ?></p>
            </div>
            <div class="zig-media">
              <img src="<?= hpl_img_url('stat-2.jpg') ?>" alt="Gold recovered with HPL gold detectors" loading="lazy">
            </div>
          </div>
        </div>

        <!-- Social Proof Ticker -->
<?php
      $tickerTrustItems = [
          ['icon' => 'ticker_t1_icon', 'iconDefault' => '✨', 'name' => 'ticker_t1_name', 'nameDefault' => 'David M.', 'text' => 'ticker_t1_text', 'textDefault' => 'found 2.3oz gold in Zambia', 'time' => 'ticker_t1_time', 'timeDefault' => '2h ago'],
          ['icon' => 'ticker_t2_icon', 'iconDefault' => '🎯', 'name' => 'ticker_t2_name', 'nameDefault' => 'John K.', 'text' => 'ticker_t2_text', 'textDefault' => 'purchased MAGNETAR 5000', 'time' => 'ticker_t2_time', 'timeDefault' => '4h ago'],
          ['icon' => 'ticker_t3_icon', 'iconDefault' => '💎', 'name' => 'ticker_t3_name', 'nameDefault' => 'Sarah T.', 'text' => 'ticker_t3_text', 'textDefault' => 'discovered nugget in Zimbabwe', 'time' => 'ticker_t3_time', 'timeDefault' => '6h ago'],
          ['icon' => 'ticker_t4_icon', 'iconDefault' => '🚀', 'name' => 'ticker_t4_name', 'nameDefault' => 'Michael R.', 'text' => 'ticker_t4_text', 'textDefault' => 'shipped to South Africa', 'time' => 'ticker_t4_time', 'timeDefault' => '8h ago'],
          ['icon' => 'ticker_t5_icon', 'iconDefault' => '⭐', 'name' => 'ticker_t5_name', 'nameDefault' => 'Peter N.', 'text' => 'ticker_t5_text', 'textDefault' => 'left 5-star review', 'time' => 'ticker_t5_time', 'timeDefault' => '10h ago'],
          ['icon' => 'ticker_t6_icon', 'iconDefault' => '🏅', 'name' => 'ticker_t6_name', 'nameDefault' => 'James L.', 'text' => 'ticker_t6_text', 'textDefault' => 'found 1.8oz in Nigeria', 'time' => 'ticker_t6_time', 'timeDefault' => '12h ago'],
      ];
      $tickerTrust = [];
      foreach ($tickerTrustItems as $item) {
          $tickerTrust[] = [
              'icon' => $hplCopy($item['icon'], $item['iconDefault']),
              'name' => $hplCopy($item['name'], $item['nameDefault']),
              'text' => $hplCopy($item['text'], $item['textDefault']),
              'time' => $hplCopy($item['time'], $item['timeDefault']),
          ];
      }
      $tickerTrust = array_merge($tickerTrust, $tickerTrust);
      ?>
        <div class="proof-ticker">
          <div class="proof-ticker-track">
<?php foreach ($tickerTrust as $entry): ?>
            <div class="ticker-item">
              <span class="ticker-item-icon"><?= h($entry['icon']) ?></span>
              <span class="ticker-item-text"><strong><?= h($entry['name']) ?></strong> <?= h($entry['text']) ?></span>
              <span class="ticker-item-time"><?= h($entry['time']) ?></span>
            </div>
<?php endforeach; ?>
          </div>
        </div>

        <!-- Customer Ratings -->
        <div class="ratings-section">
          <div class="ratings-header">
            <span class="ratings-overall">4.9</span>
            <div class="ratings-stars">
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star half">★</span>
            </div>
            <span class="ratings-count"><?= h($hplCopy('ratings_count_text', 'Based on 127 reviews')) ?></span>
          </div>

          <div class="ratings-breakdown">
            <div class="rating-bar">
              <span class="rating-bar-label"><?= h($hplCopy('rating_label_5', '5 star')) ?></span>
              <div class="rating-bar-track">
                <div class="rating-bar-fill" style="width: 85%"></div>
              </div>
              <span class="rating-bar-value">85%</span>
            </div>
            <div class="rating-bar">
              <span class="rating-bar-label"><?= h($hplCopy('rating_label_4', '4 star')) ?></span>
              <div class="rating-bar-track">
                <div class="rating-bar-fill" style="width: 10%"></div>
              </div>
              <span class="rating-bar-value">10%</span>
            </div>
            <div class="rating-bar">
              <span class="rating-bar-label"><?= h($hplCopy('rating_label_3', '3 star')) ?></span>
              <div class="rating-bar-track">
                <div class="rating-bar-fill" style="width: 3%"></div>
              </div>
              <span class="rating-bar-value">3%</span>
            </div>
            <div class="rating-bar">
              <span class="rating-bar-label"><?= h($hplCopy('rating_label_2', '2 star')) ?></span>
              <div class="rating-bar-track">
                <div class="rating-bar-fill" style="width: 1%"></div>
              </div>
              <span class="rating-bar-value">1%</span>
            </div>
            <div class="rating-bar">
              <span class="rating-bar-label"><?= h($hplCopy('rating_label_1', '1 star')) ?></span>
              <div class="rating-bar-track">
                <div class="rating-bar-fill" style="width: 1%"></div>
              </div>
              <span class="rating-bar-value">1%</span>
            </div>
          </div>

          <div class="testimonial-cards">
            <div class="testimonial-card">
              <div class="testimonial-stars">
                <span class="star filled">★</span>
                <span class="star filled">★</span>
                <span class="star filled">★</span>
                <span class="star filled">★</span>
                <span class="star filled">★</span>
              </div>
              <p class="testimonial-text"><?= h($hplCopy('testimonial_1_text', '"Best detector I\'ve ever used. Found gold on my first trip out. The team was incredibly helpful with setup."')) ?></p>
              <div class="testimonial-author">
                <span class="testimonial-avatar"><?= h($hplCopy('testimonial_1_initial', 'DM')) ?></span>
                <div>
                  <span class="testimonial-name"><?= h($hplCopy('testimonial_1_name', 'David M.')) ?></span>
                  <span class="testimonial-location"><?= h($hplCopy('testimonial_1_location', 'Zambia')) ?></span>
                </div>
              </div>
            </div>
            <div class="testimonial-card">
              <div class="testimonial-stars">
                <span class="star filled">★</span>
                <span class="star filled">★</span>
                <span class="star filled">★</span>
                <span class="star filled">★</span>
                <span class="star filled">★</span>
              </div>
              <p class="testimonial-text"><?= h($hplCopy('testimonial_2_text', '"Professional service and top-quality equipment. Highly recommend for serious detectorists."')) ?></p>
              <div class="testimonial-author">
                <span class="testimonial-avatar"><?= h($hplCopy('testimonial_2_initial', 'SK')) ?></span>
                <div>
                  <span class="testimonial-name"><?= h($hplCopy('testimonial_2_name', 'Sarah K.')) ?></span>
                  <span class="testimonial-location"><?= h($hplCopy('testimonial_2_location', 'Zimbabwe')) ?></span>
                </div>
              </div>
            </div>
            <div class="testimonial-card">
              <div class="testimonial-stars">
                <span class="star filled">★</span>
                <span class="star filled">★</span>
                <span class="star filled">★</span>
                <span class="star filled">★</span>
                <span class="star half">★</span>
              </div>
              <p class="testimonial-text"><?= h($hplCopy('testimonial_3_text', '"Great machine, excellent support. Found 1.5oz in my first month. Will definitely buy again."')) ?></p>
              <div class="testimonial-author">
                <span class="testimonial-avatar"><?= h($hplCopy('testimonial_3_initial', 'JT')) ?></span>
                <div>
                  <span class="testimonial-name"><?= h($hplCopy('testimonial_3_name', 'James T.')) ?></span>
                  <span class="testimonial-location"><?= h($hplCopy('testimonial_3_location', 'South Africa')) ?></span>
                </div>
              </div>
            </div>
          </div>
          <!-- Customer stories: eyebrow, title, paragraph, image. Photos read
               img/zambia-1.jpg .. zambia-6.jpg and fall back to an existing field
               photo until those are uploaded, so nothing renders broken. -->
          <?php
          $ratingsStories = [
              [
                  'eyebrow' => $hplCopy('story_1_eyebrow', 'Chingola, Zambia'),
                  'title'   => $hplCopy('story_1_title', 'Came from a machine that could not live with the iron'),
                  'text'    => $hplCopy('story_1_text', 'Bwalya had been working a cheap unit that chattered on every patch of iron and slag, so she stopped trusting it and nearly stopped digging altogether. The change was not luck. On ground like that, a machine which separates the target from the scrap lets you keep working instead of switching off.'),
                  'image'   => 'zambia-4.jpg',
                  'fallback'=> 'story-photo-1.jpg',
              ],
              [
                  'eyebrow' => $hplCopy('story_2_eyebrow', 'Kabwe, Zambia'),
                  'title'   => $hplCopy('story_2_title', 'Learning the craft before buying the machine'),
                  'text'    => $hplCopy('story_2_text', 'Mulungu owned no equipment and spent weeks going out with his uncle before spending anything. That part is the one people skip. By the time he bought a machine he already knew how to sweep, so the first months went on learning the ground rather than learning the settings.'),
                  'image'   => 'zambia-5.jpg',
                  'fallback'=> 'story-photo-2.jpg',
              ],
              [
                  'eyebrow' => $hplCopy('story_3_eyebrow', 'Livingstone, Zambia'),
                  'title'   => $hplCopy('story_3_title', 'Chasing ordinary targets, not one big find'),
                  'text'    => $hplCopy('story_3_text', 'Nachula runs a small pit, and part of what comes out goes straight back into the household. He is not waiting on a once-in-a-lifetime piece. He is after steady, repeatable targets that turn up often enough to be worth the week, which is a different discipline altogether.'),
                  'image'   => 'zambia-6.jpg',
                  'fallback'=> 'story-photo-3.jpg',
              ],
              [
                  'eyebrow' => $hplCopy('story_4_eyebrow', 'Lusaka, Zambia'),
                  'title'   => $hplCopy('story_4_title', 'A worked dump that finally gave something back'),
                  'text'    => $hplCopy('story_4_text', 'Chileshe had walked the same old mine dump for months with little to show. One weekend with the MAGNETAR 5000 and a slower, wider swing, he started lifting targets he had stepped over before. Nothing exotic, just a machine that stopped shouting at every piece of iron in the spoil.'),
                  'image'   => 'zambia-1.jpg',
                  'fallback'=> 'story-photo-1.jpg',
              ],
              [
                  'eyebrow' => $hplCopy('story_5_eyebrow', 'Kitwe, Zambia'),
                  'title'   => $hplCopy('story_5_title', 'One complete setup instead of piecing it together'),
                  'text'    => $hplCopy('story_5_text', 'Mwamba needed more than a detector: pumps, hoses and a cradle to work deeper ground. Everything was set up and explained in one go, so he was not left guessing at parts that do not fit together. He now runs the same ground with a second team.'),
                  'image'   => 'zambia-2.jpg',
                  'fallback'=> 'story-photo-2.jpg',
              ],
              [
                  'eyebrow' => $hplCopy('story_6_eyebrow', 'Ndola, Zambia'),
                  'title'   => $hplCopy('story_6_title', 'First find on ground her family already owned'),
                  'text'    => $hplCopy('story_6_text', 'Kabaso had land but no idea where to start, and did not want to buy the wrong machine. The session was spent on ground she had never walked with a detector. She still keeps the first piece she lifted, as the reason she kept going.'),
                  'image'   => 'zambia-3.jpg',
                  'fallback'=> 'story-photo-3.jpg',
              ],
          ];
          ?>
          <div class="ratings-stories">
            <h3 class="ratings-stories-heading"><?= h($hplCopy('ratings_stories_heading', 'Stories from Zambia')) ?></h3>
            <div class="ratings-story-grid">
              <?php foreach ($ratingsStories as $story): ?>
              <article class="ratings-story">
                <div class="ratings-story-body">
                  <p class="ratings-story-eyebrow"><?= h($story['eyebrow']) ?></p>
                  <h4 class="ratings-story-title"><?= h($story['title']) ?></h4>
                  <p class="ratings-story-text"><?= h($story['text']) ?></p>
                </div>
                <?php $file = file_exists(__DIR__ . '/img/' . $story['image']) ? $story['image'] : $story['fallback']; ?>
                <img src="<?= hpl_img_url($file) ?>" alt="<?= h($story['title']) ?>" loading="lazy">
              </article>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      
        <div class="cta-reminder">
          <a class="button" href="#book"><?= h($s['cta_text']) ?></a>
        </div>
      </section>

<?php if ($customerAdviceItem): ?>
      <section class="section customer-advice-section">
        <h2><?= h($hplCopy('customer_advice_heading', 'Customer advice to you')) ?></h2>
        <figure class="promo-frame customer-advice-player">
<?php if (($customerAdviceItem['kind'] ?? '') === 'embed'): ?>
          <iframe class="promo-frame-el" src="<?= h($customerAdviceItem['src']) ?>" title="Customer advice to you" loading="lazy" allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture; fullscreen" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen="true"></iframe>
<?php else: ?>
          <video class="promo-frame-el" controls playsinline preload="metadata">
            <source src="<?= h($customerAdviceItem['src']) ?>" type="<?= h(hpl_media_type($customerAdviceItem['src'])) ?>">
          </video>
<?php endif; ?>
        </figure>
      
      </section>
<?php endif; ?>

      <section class="section" id="faq"><div class="accordions">
<?php for ($i = 1; $i <= 4; $i++) { ?>
<details<?php if ($i === 1) { ?> open<?php } ?>><summary><?= h($s['faq' . $i . '_q']) ?></summary><?php foreach (preg_split('/\r\n|\r|\n/', $s['faq' . $i . '_a']) as $paragraph) { if (trim($paragraph) !== '') { ?><p><?= h($paragraph) ?></p><?php } } ?></details>
<?php } ?>
      </div>
      </section>

<?php
  /* Physical address, showroom photos and a map, sitting between the FAQ and
     the closing button. Nothing is hardcoded: every line comes from the admin,
     and each block only appears once it has something to show. The section as a
     whole stays hidden until the shop details are filled in, so a half-finished
     page never shows an empty shell. */
  $visitMapSrc = hpl_map_embed_url(
      (string)($s['visit_map_embed'] ?? ''),
      (string)($s['visit_map_lat'] ?? ''),
      (string)($s['visit_map_lon'] ?? ''),
      (string)($s['visit_map_zoom'] ?? '')
  );
  $visitDirections = hpl_map_directions_url($s);
  $visitAddress = trim((string)($s['visit_address'] ?? ''));
  $visitPhone = trim((string)($s['visit_phone'] ?? ''));
  $visitHours = trim((string)($s['visit_hours'] ?? ''));
  /* config.php is deliberately left out of the deploy archive, so a live site
     can be running a config.php that predates these keys and has no defaults
     for them. Fall back to the same wording the defaults use, otherwise a
     half-filled admin would render an empty heading and lose the directions
     button. Anything set in the admin still wins. */
  $visitSub = trim((string)($s['visit_sub'] ?? ''))
      ?: 'Want to hold the machine before you buy? Walk into our showroom, see the detectors running, and talk to the people who tune them.';
  $visitLabel = trim((string)($s['visit_label'] ?? '')) ?: 'Visit us';
  $visitHeading = trim((string)($s['visit_h'] ?? '')) ?: 'Come and see us in person';
  $visitCtaLabel = trim((string)($s['visit_cta_label'] ?? '')) ?: 'Get directions';
  $visitPhotos = [];
  foreach (['visit-1.jpg', 'visit-2.jpg', 'visit-3.jpg'] as $visitFile) {
      if (is_file(__DIR__ . '/img/' . $visitFile)) { $visitPhotos[] = $visitFile; }
  }
  $visitHasDetails = $visitAddress !== '' || $visitPhone !== '' || $visitHours !== '';
  if ($visitHasDetails || $visitPhotos || $visitMapSrc !== ''):
?>
      <section class="section visit" id="visit">
        <div class="visit-head">
<?php if ($visitLabel !== ''): ?>
          <span class="visit-label"><?= h($visitLabel) ?></span>
<?php endif; ?>
<?php if ($visitHeading !== ''): ?>
          <h2><?= h($visitHeading) ?></h2>
<?php endif; ?>
<?php if ($visitSub !== ''): ?>
          <p><?= h($visitSub) ?></p>
<?php endif; ?>
        </div>
        <div class="visit-grid">
<?php if ($visitHasDetails): ?>
          <div class="visit-card">
            <div class="visit-rows">
<?php if ($visitAddress !== ''): ?>
              <div class="visit-row">
                <span class="visit-row-label">Address</span>
<?php foreach (preg_split('/\r\n|\r|\n/', $visitAddress) as $visitLine) { if (trim($visitLine) !== '') { ?>
                <p><?= h(trim($visitLine)) ?></p>
<?php } } ?>
              </div>
<?php endif; ?>
<?php if ($visitPhone !== ''): ?>
              <div class="visit-row">
                <span class="visit-row-label">Phone</span>
                <p><a href="tel:<?= h(preg_replace('/[^\d+]/', '', $visitPhone)) ?>"><?= h($visitPhone) ?></a></p>
              </div>
<?php endif; ?>
<?php if ($visitHours !== ''): ?>
              <div class="visit-row">
                <span class="visit-row-label">Opening hours</span>
<?php foreach (preg_split('/\r\n|\r|\n/', $visitHours) as $visitLine) { if (trim($visitLine) !== '') { ?>
                <p><?= h(trim($visitLine)) ?></p>
<?php } } ?>
              </div>
<?php endif; ?>
            </div>
<?php if ($visitDirections !== '' && $visitCtaLabel !== ''): ?>
            <a class="button ripple visit-cta" href="<?= h($visitDirections) ?>" target="_blank" rel="noopener noreferrer"><?= h($visitCtaLabel) ?></a>
<?php endif; ?>
          </div>
<?php endif; ?>
<?php if ($visitPhotos || $visitMapSrc !== ''): ?>
          <div class="visit-media">
<?php if ($visitPhotos): ?>
            <div class="visit-gallery">
<?php foreach ($visitPhotos as $visitIndex => $visitFile): ?>
              <img class="<?= $visitIndex === 0 ? 'visit-gallery-lead' : '' ?>" src="<?= hpl_img_url($visitFile) ?>" alt="<?= h($visitHeading !== '' ? $visitHeading : 'Showroom') . ' — photo ' . ($visitIndex + 1) ?>" loading="lazy">
<?php endforeach; ?>
            </div>
<?php endif; ?>
<?php if ($visitMapSrc !== ''): ?>
            <div class="visit-map">
              <div class="visit-map-frame">
                <iframe src="<?= h($visitMapSrc) ?>" title="Map showing <?= h($visitHeading !== '' ? $visitHeading : 'our location') ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen="true"></iframe>
              </div>
            </div>
<?php endif; ?>
          </div>
<?php endif; ?>
        </div>
      </section>
<?php endif; ?>

      <div class="spaced-cta"><a class="button ripple" href="#book"><?= h($s['cta_text']) ?></a></div>
      <section class="final" id="book">
        <h2><?= h($s['final_h']) ?></h2>
        <p><?= h($s['final_sub']) ?></p>
<?php if ($leadSent): ?>
        <div class="lead-success" id="leadSuccess" role="status" tabindex="-1">
          <span class="lead-success-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
          <h3><?= h($hplCopy('form_success_heading', 'Thank You!')) ?></h3>
          <p><?= h($hplCopy('form_success_text', 'Your enquiry has been received. A member of our HPL team will contact you shortly to discuss your requirements and recommend the right equipment.')) ?></p>
          <p class="lead-success-note"><?= h($hplCopy('form_success_note', 'We aim to respond within one business day.')) ?></p>
          <div class="lead-success-actions">
            <a href="thank-you.php" class="lead-success-btn lead-success-btn-secondary"><?= h($hplCopy('form_success_back', 'Go to Thank You Page')) ?></a>
          </div>
        </div>
<?php else: ?>
        <form class="lead-form" action="#book" method="post" novalidate id="leadForm"<?= $leadStep === 2 ? ' data-start-step="2"' : '' ?>>
          <input type="hidden" name="lead_submit" value="1">
          <input type="hidden" name="lead_source" value="Landing page">
          <input type="hidden" name="lead_csrf" value="<?= h($leadCsrf) ?>">

          <div class="form-progress">
            <span class="form-progress-track"><span class="form-progress-fill" id="leadProgressFill"></span></span>
            <span class="form-progress-text" id="leadProgressText">Step 1 of 2 &middot; Your details</span>
          </div>

          <p class="form-msg err<?= isset($leadErrors['lead_csrf']) ? ' on' : '' ?>" id="formMsg"><?= h($leadErrors['lead_csrf'] ?? '') ?></p>

          <div class="form-step" data-step="1">
            <header class="form-step-head">
              <h3><?= h($hplCopy('form_step1_heading', "Let's start with your details")) ?></h3>
              <p><?= h($hplCopy('form_step1_sub', 'Please enter your information so our HPL team can contact you.')) ?></p>
            </header>

            <div class="field" data-field="lead_name">
              <label for="leadName"><?= h($hplCopy('form_label_name', 'Full Name')) ?> <span class="req">*</span></label>
              <input type="text" id="leadName" name="lead_name" value="<?= h($leadFields['lead_name']) ?>" autocomplete="name" required<?= lead_invalid($leadErrors, 'lead_name') ?>>
              <?= lead_field_error($leadErrors, 'lead_name') ?>
            </div>

            <div class="field" data-field="lead_phone">
              <label for="leadPhone"><?= h($hplCopy('form_label_phone', 'WhatsApp Number')) ?> <span class="req">*</span></label>
              <span class="phone-row">
                <span class="cc-wrap">
                  <span class="cc-label"><img class="cc-flag" id="ccFlag" src="img/flags/<?= h(cc_flag((string)$leadFields['lead_cc'])) ?>.png" alt=""><span class="cc-text" id="ccText"><?= h(cc_label((string)$leadFields['lead_cc'])) ?></span></span>
                  <select name="lead_cc" id="leadCc" aria-label="Country code">
<?php foreach (hpl_countries() as $ccode => $cinfo): ?>
                    <option value="<?= h((string)$ccode) ?>" data-flag="<?= h($cinfo['flag']) ?>" data-country="<?= h($cinfo['name']) ?>" title="<?= h($cinfo['name']) ?>"<?= (string)$leadFields['lead_cc'] === (string)$ccode ? ' selected' : '' ?>><?= h($cinfo['label']) ?></option>
<?php endforeach; ?>
                  </select>
                </span>
                <input type="tel" id="leadPhone" name="lead_phone" value="<?= h($leadFields['lead_phone']) ?>" placeholder="123 456 789" autocomplete="tel-national" inputmode="tel" required<?= lead_invalid($leadErrors, 'lead_phone') ?>>
              </span>
              <?= lead_field_error($leadErrors, 'lead_phone') ?>
            </div>

            <div class="field" data-field="lead_email">
              <label for="leadEmail"><?= h($hplCopy('form_label_email', 'Email Address')) ?> <span class="opt">optional</span></label>
              <input type="email" id="leadEmail" name="lead_email" value="<?= h($leadFields['lead_email']) ?>" placeholder="you@example.com" autocomplete="email"<?= lead_invalid($leadErrors, 'lead_email') ?>>
              <?= lead_field_error($leadErrors, 'lead_email') ?>
            </div>

            <div class="field-row">
              <div class="field" data-field="lead_country">
                <label for="leadCountry"><?= h($hplCopy('form_label_country', 'Country')) ?> <span class="req">*</span></label>
                <select id="leadCountry" name="lead_country" required<?= lead_invalid($leadErrors, 'lead_country') ?>>
                  <option value="">Select country</option>
<?php foreach (hpl_lead_country_names() as $countryName): ?>
                  <option value="<?= h($countryName) ?>"<?= $leadFields['lead_country'] === $countryName ? ' selected' : '' ?>><?= h($countryName) ?></option>
<?php endforeach; ?>
                </select>
                <?= lead_field_error($leadErrors, 'lead_country') ?>
              </div>

              <div class="field" data-field="lead_city">
                <label for="leadCity"><?= h($hplCopy('form_label_city', 'City / Town')) ?> <span class="req">*</span></label>
                <input type="text" id="leadCity" name="lead_city" value="<?= h($leadFields['lead_city']) ?>" autocomplete="address-level2" required<?= lead_invalid($leadErrors, 'lead_city') ?>>
                <?= lead_field_error($leadErrors, 'lead_city') ?>
              </div>
            </div>

            <div class="form-nav"><button class="button" type="button" id="leadNext"><?= h($hplCopy('form_continue', 'Continue')) ?></button></div>
          </div>

          <div class="form-step" data-step="2" hidden>
            <header class="form-step-head">
              <h3><?= h($hplCopy('form_step2_heading', 'Tell us about what you need')) ?></h3>
              <p><?= h($hplCopy('form_step2_sub', 'These answers help us send you the right equipment, not a generic price list.')) ?></p>
            </header>

<?php $leadQuestions = hpl_lead_questions(); ?>
<?php foreach (['lead_looking_for', 'lead_finding', 'lead_experience', 'lead_customer_type', 'lead_timing'] as $qField): ?>
<?php $question = $leadQuestions[$qField]; ?>
            <fieldset class="q-block" data-field="<?= h($qField) ?>"<?= isset($leadErrors[$qField]) ? ' data-invalid="1"' : '' ?>>
              <legend><?= h($question['label']) ?> <span class="req">*</span></legend>
              <div class="cards">
<?php foreach ($question['options'] as $qValue => $qNote): ?>
                <label class="card<?= $leadFields[$qField] === $qValue ? ' is-on' : '' ?>">
                  <input type="radio" name="<?= h($qField) ?>" value="<?= h($qValue) ?>"<?= $leadFields[$qField] === $qValue ? ' checked' : '' ?>>
                  <span class="card-mark" aria-hidden="true"></span>
                  <span class="card-text"><span class="card-title"><?= h($qValue) ?></span><span class="card-note"><?= h($qNote) ?></span></span>
                </label>
<?php endforeach; ?>
              </div>
              <?= lead_field_error($leadErrors, $qField) ?>
            </fieldset>
<?php endforeach; ?>

            <div class="field" data-field="lead_message">
              <label for="leadMessage"><?= h($leadQuestions['lead_message']['label']) ?> <span class="opt">optional</span></label>
              <textarea id="leadMessage" name="lead_message" rows="4" placeholder="<?= h($hplCopy('form_message_hint', 'Example: I have a mining area in Kitwe and I am looking for a detector for deep gold.')) ?>"><?= h($leadFields['lead_message']) ?></textarea>
              <?= lead_field_error($leadErrors, 'lead_message') ?>
            </div>

            <label class="advice-check" for="leadAdvice">
              <input type="checkbox" id="leadAdvice" name="lead_needs_advice" value="1"<?= $leadNeedsAdvice ? ' checked' : '' ?>>
              <span><?= h($hplCopy('form_advice_label', "I'm not sure what equipment I need &mdash; please advise me.")) ?></span>
            </label>

<div class="form-nav">
              <button class="btn-ghost" type="button" id="leadBack"><?= h($hplCopy('form_back', 'Back')) ?></button>
              <button class="button" type="submit" id="leadSubmit"><?= h($hplCopy('form_submit_btn', 'Submit & Talk to HPL')) ?></button>
            </div>
          </div>
        </form>
<?php endif; ?>
      </section>
    </main>
    <footer role="contentinfo" aria-label="Site Footer"><div class="footer-inner">
      <div class="footer-top">
        <a class="footer-brand logo-chip" href="#top"><img class="footer-logo" src="<?= hpl_img_url('hpllogo.jpeg') ?>" alt="<?= h($hplCopy('logo_alt', 'HPL Gold Detectors')) ?>"></a>
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
        <div class="cookie-text"><?= h($hplCopy('cookie_text', 'We use cookies to improve your experience and analyze site traffic.')) ?> <a href="#faq"><?= h($hplCopy('cookie_learn_more', 'Learn more')) ?></a></div>
        <div class="cookie-buttons">
          <button class="cookie-btn accept" id="acceptCookies"><?= h($hplCopy('cookie_accept', 'Accept')) ?></button>
          <button class="cookie-btn deny" id="denyCookies"><?= h($hplCopy('cookie_deny', 'Deny')) ?></button>
        </div>
      </div>
    </div>
    <?php /* Hidden outright when there is no usable number: an anchor with an empty
       href is focusable, looks clickable, and goes nowhere. */ if ($quickContactUrl !== '') { ?>
    <a class="wa-float" href="<?= h($quickContactUrl) ?>" target="_blank" rel="noopener" aria-label="<?= h($hplCopy('wa_float_aria', 'Message the HPL team on WhatsApp')) ?>">
      <span class="wa-float-icon" aria-hidden="true">✆</span>
      <span class="wa-float-label"><?= h($quickContactLabel) ?></span>
    </a>
<?php } ?>
    <button class="back-to-top" id="backToTop" type="button" aria-label="Back to top" title="Back to top">&uarr;</button>
  </div>
  <script>
    /* Settings for client-side validation messages */
    window.hplFormSettings = {
      step1Heading: <?= json_encode($hplCopy('form_step1_heading', "Let's start with your details")) ?>,
      step1Sub: <?= json_encode($hplCopy('form_step1_sub', 'Please enter your information so our HPL team can contact you.')) ?>,
      step2Heading: <?= json_encode($hplCopy('form_step2_heading', 'Tell us about what you need')) ?>,
      step2Sub: <?= json_encode($hplCopy('form_step2_sub', 'These answers help us send you the right equipment, not a generic price list.')) ?>,
      stepLabels: [
        <?= json_encode($hplCopy('form_step1_label', 'Step 1 of 2 · Your details')) ?>,
        <?= json_encode($hplCopy('form_step2_label', 'Step 2 of 2 · Your requirements')) ?>
      ],
      validation: {
        nameRequired: <?= json_encode($hplCopy('form_error_name_required', 'Please enter your full name.')) ?>,
        phoneRequired: <?= json_encode($hplCopy('form_error_phone_required', 'Please enter your WhatsApp number.')) ?>,
        emailInvalid: <?= json_encode($hplCopy('form_error_email_invalid', 'Please check that email address, or leave it blank.')) ?>,
        countryRequired: <?= json_encode($hplCopy('form_error_country_required', 'Please select your country.')) ?>,
        cityRequired: <?= json_encode($hplCopy('form_error_city_required', 'Please enter your city or town.')) ?>,
        questionRequired: <?= json_encode($hplCopy('form_error_question_required', 'Please choose an option to continue.')) ?>
      },
      promoReadyLabel: <?= json_encode($hplCopy('cta_text', "I'm Ready to Find Gold")) ?>
    };
    /* Run as soon as the markup exists, not on window.load.
       window.load waits for every image and every third-party player iframe on
       the page - 35 images and 9 iframes here. On a phone that wait can drag on
       for many seconds, and a single slow or stalled request stops it entirely.
       Anything gated behind it leaves whole sections sitting at opacity 0, which
       is exactly how a visitor ends up seeing text with no photos and no players.
       These effects only need the DOM, and the observers inside them do their own
       "is it on screen yet" work, so there is nothing to gain by waiting.
       Defined out here at the top level on purpose: the rest of this file is a
       run of separate IIFEs, so anything declared inside one of them is invisible
       to the next. */
    function onDomReady(fn) {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn, { once: true });
      } else {
        fn();
      }
    }

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

      var backToTop = document.getElementById('backToTop');
      if (backToTop) backToTop.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });

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

/* ---------- Bunny Stream promo: 9-second watch gate ---------- */
      (function () {
        var frame = document.getElementById('promoPlayer');
        if (!frame) return;

        var UNLOCK_SECONDS = 9;
        var unlocked = false;
        var player = null;
        var ticker = null;

        function track(type, data) { if (window.hplTrack) window.hplTrack(type, data); }

        function lockAllCtas() {
          var ctas = document.querySelectorAll('a[href="#book"]');
          ctas.forEach(function(btn) {
            if (btn.getAttribute('aria-disabled') === 'true') return;
            btn.setAttribute('aria-disabled', 'true');
            btn.classList.add('book-gated');
          });
        }

        function unlockAll() {
          if (unlocked) return;
          unlocked = true;
          localStorage.setItem('hpl_cta_unlocked', '1');

          var ctas = document.querySelectorAll('a[href="#book"]');
          ctas.forEach(function(btn) {
            btn.setAttribute('aria-disabled', 'false');
            btn.classList.remove('book-gated');
            if (btn.classList.contains('hero-find-cta')) {
              btn.textContent = btn.getAttribute('data-ready-label') || window.hplFormSettings.promoReadyLabel;
            }
          });

          var form = document.getElementById('book');
          if (form) {
            form.classList.add('book-revealed');
            form.classList.remove('book-hidden');
          }
        }

        function startTicker() {
          stopTicker();
          var wallStart = Date.now();
          ticker = setInterval(function() {
            if (!player || unlocked) { stopTicker(); return; }
            try {
              player.getCurrentTime(function(t) {
                if (typeof t === 'number' && t >= UNLOCK_SECONDS) {
                  unlockAll();
                  stopTicker();
                }
              });
            } catch(e) {
              if (Date.now() - wallStart >= UNLOCK_SECONDS * 1000) {
                unlockAll();
                stopTicker();
              }
            }
          }, 250);
        }

        function stopTicker() {
          if (ticker) { clearInterval(ticker); ticker = null; }
        }

        function bind() {
          player = new playerjs.Player(frame);

          player.on('ready', function () {
            player.getPaused(function (paused) { if (!paused && !unlocked) startTicker(); });
          });

          player.on('play', function () {
            track('video_play', { video: 'promo' });
            if (!unlocked) startTicker();
          });

          player.on('pause', function () {
            stopTicker();
          });

          player.on('ended', function () {
            stopTicker();
            unlockAll();
          });
        }

        /* player.js must be loaded before the iframe can be bound. */
        var lib = document.createElement('script');
        lib.src = 'https://assets.mediadelivery.net/playerjs/playerjs-latest.min.js';
        lib.async = true;
        lib.onload = bind;
        /* If player.js cannot load, Bunny's own controls still play the video. */
        lib.onerror = function () { /* embed is self-sufficient */ };
        document.head.appendChild(lib);

        onDomReady(function() {
          if (localStorage.getItem('hpl_cta_unlocked') === '1') {
            unlockAll();
          } else {
            lockAllCtas();
            var form = document.getElementById('book');
            if (form) form.classList.add('book-hidden');
          }
        });
      })();

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
      // Shared with the form script below, which runs in its own closure.
      window.hplTrack = trackEvent;
    })();
(function () {
      var form = document.getElementById('leadForm');
      // After a successful POST the confirmation replaces the form, so there is
      // nothing below to wire up. Move focus to the panel instead: role="status"
      // alone is not announced reliably on a full page load.
      var sentPanel = document.getElementById('leadSuccess');
      if (sentPanel) sentPanel.focus();
      if (!form) return;
// Analytics lives in its own closure above; reach it without assuming it.
      function trackEvent(type, data) {
        if (window.hplTrack) window.hplTrack(type, data);
      }

      var steps = form.querySelectorAll('.form-step');
      var next = document.getElementById('leadNext');
      var back = document.getElementById('leadBack');
      var submitBtn = document.getElementById('leadSubmit');
      var msg = document.getElementById('formMsg');
      var cc = document.getElementById('leadCc');
      var country = document.getElementById('leadCountry');
      var flag = document.getElementById('ccFlag');
      var ccText = document.getElementById('ccText');
      var progressFill = document.getElementById('leadProgressFill');
      var progressText = document.getElementById('leadProgressText');
      var current = 0;
      var QUESTIONS = ['lead_looking_for', 'lead_finding', 'lead_experience', 'lead_customer_type', 'lead_timing'];
      var STEP1 = ['lead_name', 'lead_phone', 'lead_email', 'lead_country', 'lead_city'];
      var STEP_LABELS = window.hplFormSettings.stepLabels;

      function paintProgress() {
        if (progressFill) progressFill.style.width = (current === 0 ? 50 : 100) + '%';
        if (progressText) progressText.textContent = STEP_LABELS[current];
      }

      function clearMsg() {
        if (msg) { msg.textContent = ''; msg.classList.remove('on'); }
      }

      function showMsg(text) {
        if (!msg) return;
        msg.textContent = text;
        msg.classList.add('on');
        msg.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      function clearError(field) {
        var wrap = form.querySelector('[data-field="' + field + '"]');
        if (!wrap) return;
        var note = wrap.querySelector('.field-error');
        if (note) note.parentNode.removeChild(note);
        wrap.removeAttribute('data-invalid');
        var bad = wrap.querySelector('[aria-invalid]');
        if (bad) bad.removeAttribute('aria-invalid');
      }

      function setError(field, text) {
        var wrap = form.querySelector('[data-field="' + field + '"]');
        if (!wrap) return showMsg(text);
        var note = wrap.querySelector('.field-error');
        if (!note) {
          note = document.createElement('span');
          note.className = 'field-error';
          wrap.appendChild(note);
        }
        note.textContent = text;
        wrap.setAttribute('data-invalid', '1');
        var input = wrap.querySelector('input, select, textarea');
        // A card group is flagged on the group, not the radio hidden inside it.
        if (input && input.type !== 'radio' && input.type !== 'checkbox') {
          input.setAttribute('aria-invalid', 'true');
        }
        wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      function validateStep1() {
        clearMsg();
        for (var i = 0; i < STEP1.length; i++) clearError(STEP1[i]);

        var name = form.querySelector('#leadName');
        var phone = form.querySelector('#leadPhone');
        var city = form.querySelector('#leadCity');
        var email = form.querySelector('#leadEmail');

        if (!name || !name.value.trim()) { setError('lead_name', window.hplFormSettings.validation.nameRequired); return false; }
        if (!phone || phone.value.replace(/\D/g, '').length < 6) { setError('lead_phone', window.hplFormSettings.validation.phoneRequired); return false; }
        if (email && email.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.value.trim())) {
          setError('lead_email', window.hplFormSettings.validation.emailInvalid);
          return false;
        }
        if (!country || !country.value) { setError('lead_country', window.hplFormSettings.validation.countryRequired); return false; }
        if (!city || !city.value.trim()) { setError('lead_city', window.hplFormSettings.validation.cityRequired); return false; }
        return true;
      }

      function validateStep2() {
        clearMsg();
        for (var i = 0; i < QUESTIONS.length; i++) {
          if (!form.querySelector('input[name="' + QUESTIONS[i] + '"]:checked')) {
            for (var j = 0; j < QUESTIONS.length; j++) clearError(QUESTIONS[j]);
            setError(QUESTIONS[i], window.hplFormSettings.validation.questionRequired);
            return false;
          }
        }
        return true;
      }

      function show(n) {
        current = n;
        for (var i = 0; i < steps.length; i++) {
          if (i === n) steps[i].removeAttribute('hidden');
          else steps[i].setAttribute('hidden', '');
        }
        paintProgress();
        clearMsg();
        var first = steps[n].querySelector('input, textarea, select');
        if (first) first.focus();
        if (n === 0 && window.scrollY > 0) form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      // Card selection state and per-group error clearing.
      var cards = form.querySelectorAll('.card');
      for (var c = 0; c < cards.length; c++) {
        cards[c].addEventListener('change', function () {
          var name = this.querySelector('input').name;
          var group = form.querySelectorAll('.card input[name="' + name + '"]');
          for (var g = 0; g < group.length; g++) {
            group[g].parentNode.classList.toggle('is-on', group[g].checked);
          }
          clearError(name);
        });
      }

// Correcting or clearing a field should take its message away; Continue
      // re-checks everything anyway.
      form.addEventListener('input', function (e) {
        var wrap = e.target.closest ? e.target.closest('[data-field]') : null;
        if (!wrap || !wrap.getAttribute('data-invalid')) return;
        clearError(wrap.getAttribute('data-field'));
      });

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

      var userPicked = false;
      if (cc) {
        cc.addEventListener('change', function () {
          userPicked = true;
          paintCc();
          var o = cc.options[cc.selectedIndex];
          // The dialling code usually implies the country, so suggest it.
          if (country && !country.value && o) {
            var name = o.getAttribute('data-country');
            if (name) country.value = name;
          }
        });
        cc.addEventListener('pointerdown', function () { userPicked = true; });
        paintCc();
      }

      (function detectCountry() {
        if (userPicked) return;
        var providers = ['https://ipwho.is/', 'https://ipapi.co/json/'];
        var attempt = function (i) {
          if (i >= providers.length || userPicked || !cc) return;
          var ctrl = new AbortController();
          var timer = setTimeout(function () { ctrl.abort(); }, 2600);
          fetch(providers[i], { mode: 'cors', signal: ctrl.signal })
            .then(function (r) { return r.json(); })
            .then(function (d) {
              clearTimeout(timer);
              if (userPicked || !d) return;
              var code = (d.country_calling_code || d.calling_code) ? String(d.country_calling_code || d.calling_code).replace(/\D/g, '') : '';
              var iso = d.country_code ? String(d.country_code).toLowerCase() : '';
              if (!code && !iso) return attempt(i + 1);

              if (country && !country.value && iso) {
                for (var m = 0; m < cc.options.length; m++) {
                  if (cc.options[m].getAttribute('data-flag') === iso) {
                    var cname = cc.options[m].getAttribute('data-country');
                    if (cname) { country.value = cname; break; }
                  }
                }
              }

              if (code) {
                var match = null;
                for (var k = 0; k < cc.options.length; k++) {
                  if (cc.options[k].value === code) { match = cc.options[k]; break; }
                }
                if (!match) {
                  match = document.createElement('option');
                  match.value = code;
                  match.textContent = '+' + code + (d.country_name ? ' ' + String(d.country_name) : '');
                  match.setAttribute('data-flag', iso || 'un');
                  cc.insertBefore(match, cc.firstChild);
                } else if (cc.firstChild !== match) {
                  cc.insertBefore(match, cc.firstChild);
                }
                cc.value = code;
                paintCc();
              }
              trackEvent('country_detected', { cc: code });
            })
            .catch(function () { clearTimeout(timer); attempt(i + 1); });
        };
        if (window.fetch) attempt(0);
      })();

      if (next) {
        next.addEventListener('click', function () { if (validateStep1()) show(1); });
      }
      if (back) back.addEventListener('click', function () { show(0); });

form.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' || e.shiftKey || e.target.tagName === 'TEXTAREA') return;
        // Only the details step is advanced by Enter. On step 2 the key must reach
        // the button, otherwise preventDefault() below would swallow the submit.
        if (current !== 0) return;
        var step = e.target.closest ? e.target.closest('.form-step') : null;
        if (!step || step.hasAttribute('hidden')) return;
        // Hold the page either way: show inline errors, or move on without posting.
        e.preventDefault();
        if (validateStep1()) show(1);
      });

      // The server rejected the submission and marked which step owns the error.
      if (form.getAttribute('data-start-step') === '2') {
        show(1);
      } else {
        paintProgress();
      }

      var submitting = false;
      form.addEventListener('submit', function (e) {
        trackEvent('form_submit', { form: 'lead' });
        if (!validateStep2()) { e.preventDefault(); return; }
        // A second click while the first POST is in flight must not add a row.
        if (submitting) { e.preventDefault(); return; }
        submitting = true;
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.classList.add('is-busy');
          submitBtn.textContent = 'Sending\u2026';
        }
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
      /* Every player on the page, each one handled on its own.
         An iframe is emitted with data-embed-src and no src, so nothing streams
         until the player is reached, and it is unloaded again on the way out so
         a playing video never talks over the page behind it. A section can hold
         more than one player now, so each is observed individually rather than
         taking the first match inside a single section. */
      var frames = Array.prototype.slice.call(document.querySelectorAll('.proof-embed iframe'));
      if (!frames.length) return;

      frames.forEach(function (frame) {
        var box = frame.closest('.proof-embed');
        function markReady() {
          if (box) box.setAttribute('data-ready', '1');
        }
        /* The stand-in only makes sense while there is nothing to show, so retire it
           on the player's own load event rather than on a guess. */
        frame.addEventListener('load', markReady);
        function loadPlayer() {
          if (frame.hasAttribute('data-on')) return;
          frame.setAttribute('src', frame.getAttribute('data-embed-src') || '');
          frame.setAttribute('data-on', '1');
          /* Never let the stand-in sit on top of a player that is already working:
             if the load event is missed, drop it shortly after the frame goes live. */
          setTimeout(markReady, 8000);
        }
        function unloadPlayer() {
          if (!frame.hasAttribute('data-on')) return;
          frame.removeAttribute('src');
          frame.removeAttribute('data-on');
          if (box) box.removeAttribute('data-ready');
        }
        var host = frame.closest('section') || frame.parentElement;
        if (!host || !('IntersectionObserver' in window)) {
          loadPlayer();
          return;
        }
        new IntersectionObserver(function (entries) {
          if (entries[0].isIntersecting) {
            loadPlayer();
            return;
          }
          host.querySelectorAll('video').forEach(function (v) { if (v && !v.paused) { v.pause(); } });
          unloadPlayer();
        }, { rootMargin: '300px 0px' }).observe(host);
      });
    })();
    (function () {
      /* Pause decorative animation while it is off screen. Infinite animations
         keep costing the compositor every frame even when the element cannot be
         seen, so a section only animates while it is near the viewport and the
         cost of walking past it becomes a single class toggle. */
      var watched = [];
      ['.trust-section', '.proof-ticker', '.proof-visuals', '.slides'].forEach(function (sel) {
        document.querySelectorAll(sel).forEach(function (el) { watched.push(el); });
      });
      if (!watched.length) return;

      function setState(entries) {
        entries.forEach(function (entry) {
          entry.target.classList.toggle('hpl-offscreen', !entry.isIntersecting);
        });
      }
      if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(setState, { rootMargin: '120px 0px' });
        watched.forEach(function (el) { io.observe(el); });
      } else {
        var ticking = false;
        window.addEventListener('scroll', function () {
          if (ticking) return;
          ticking = true;
          requestAnimationFrame(function () {
            ticking = false;
            watched.forEach(function (el) {
              var r = el.getBoundingClientRect();
              el.classList.toggle('hpl-offscreen', r.bottom < -120 || r.top > window.innerHeight + 120);
            });
          });
        }, { passive: true });
      }
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

      function start() { timer = window.setInterval(next, 2600); }
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

    /* ===================================================================
       PROOF ENHANCEMENT: filter navigation + field-photo lightbox.
       Purely additive. It reads the two existing sections, adds a filter and
       a viewer, and never edits the markup, the video sources or the slider
       behaviour above.
       =================================================================== */
    (function () {
      'use strict';

      /* ---------- filter ---------- */
      var bar = document.getElementById('pfBar');
      var groups = Array.prototype.slice.call(document.querySelectorAll('[data-pf-group]'));
      var tabs = bar ? Array.prototype.slice.call(bar.querySelectorAll('.pf-tab')) : [];

      /* Hiding a section is presentation only - the nodes stay put, so going
         back to "All" restores the original page exactly. */
      function apply(key) {
        groups.forEach(function (g) {
          var k = g.getAttribute('data-pf-group');
          var hide = key !== 'all' && k !== key;
          g.classList.toggle('pf-hidden', hide);
          /* A hidden section keeps playing its videos in some browsers, which
             would leave audio running for something nobody can see. */
          if (hide) {
            Array.prototype.forEach.call(g.querySelectorAll('video'), function (v) {
              try { v.pause(); } catch (e) {}
            });
            // Same reasoning for a player embed: filtering to another tab
            // must not leave a muted video running behind the hidden section.
            Array.prototype.forEach.call(g.querySelectorAll('.proof-embed iframe[data-on]'), function (f) {
              f.removeAttribute('src');
              f.removeAttribute('data-on');
            });
          }
        });
        tabs.forEach(function (t) {
          var on = t.getAttribute('data-pf') === key;
          t.classList.toggle('is-on', on);
          t.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
      }

      tabs.forEach(function (t) {
        t.addEventListener('click', function () { apply(t.getAttribute('data-pf')); });
      });
      if (bar && tabs.length) { apply('all'); }

      /* ---------- field photo lightbox ----------
         Photos stay exactly where they are. The existing <img> elements get
         keyboard semantics and open the viewer, so the auto-advancing slider,
         its dots and its caption all keep working untouched. */
      var frame = document.getElementById('slidesFrame');
      if (!frame) return;
      var imgs = Array.prototype.slice.call(frame.querySelectorAll('img'));
      if (!imgs.length) return;

      /* Reads only what is really in the markup. Story fields are optional
         data-* attributes: when a field has no value it is simply not shown,
         so the viewer never has to display invented information. To attach a
         real story later, add the attributes to that <img>:
           data-story-customer, data-story-location, data-story-detector,
           data-story-result, data-story-text, data-story-video */
      function field(img, name) {
        return (img.getAttribute('data-story-' + name) || '').trim();
      }

      var photos = imgs.map(function (img, i) {
        img.setAttribute('role', 'button');
        img.setAttribute('tabindex', '0');
        img.setAttribute('aria-label', 'Open field story ' + (i + 1) + (img.getAttribute('data-caption') ? ': ' + img.getAttribute('data-caption') : ''));
        return {
          src: img.getAttribute('src'),
          alt: img.getAttribute('alt') || '',
          caption: img.getAttribute('data-caption') || '',
          customer: field(img, 'customer'),
          location: field(img, 'location'),
          detector: field(img, 'detector'),
          result: field(img, 'result'),
          story: field(img, 'text'),
          video: field(img, 'video'),
          el: img
        };
      });

      var lb = null, idx = 0, lastFocus = null;

      function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
          return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
      }

      function build() {
        var d = document.createElement('div');
        d.className = 'lb';
        d.id = 'lbRoot';
        d.setAttribute('role', 'dialog');
        d.setAttribute('aria-modal', 'true');
        d.setAttribute('aria-label', 'Field story');
        d.innerHTML =
          '<div class="lb-dialog" id="lbDialog">' +
            '<button type="button" class="lb-btn lb-close" id="lbClose" aria-label="Close field story"><span aria-hidden="true">&times;</span></button>' +
            '<button type="button" class="lb-nav lb-prev" id="lbPrev" aria-label="Previous story"><span aria-hidden="true">&lsaquo;</span></button>' +
            '<button type="button" class="lb-nav lb-next" id="lbNext" aria-label="Next story"><span aria-hidden="true">&rsaquo;</span></button>' +
            '<div class="lb-stage" id="lbStage"><img class="lb-img" id="lbImg" alt=""></div>' +
            '<div class="lb-info">' +
              '<p class="lb-eyebrow" id="lbEyebrow"></p>' +
              '<p class="lb-count" id="lbCount"></p>' +
              '<p class="lb-caption" id="lbCaption"></p>' +
              '<div class="lb-story" id="lbStory" hidden>' +
                '<p class="lb-storytext" id="lbStoryText"></p>' +
                '<dl id="lbRows"></dl>' +
                '<div class="lb-actions" id="lbActions"></div>' +
              '</div>' +
            '</div>' +
          '</div>';
        document.body.appendChild(d);
        lb = d;
      }

      var IMG = null, EYEBROW = null, COUNT = null, CAPTION = null,
          STORY = null, STORYTEXT = null, ROWS = null, ACTIONS = null;

      function cache() {
        IMG = lb.querySelector('#lbImg');
        EYEBROW = lb.querySelector('#lbEyebrow');
        COUNT = lb.querySelector('#lbCount');
        CAPTION = lb.querySelector('#lbCaption');
        STORY = lb.querySelector('#lbStory');
        STORYTEXT = lb.querySelector('#lbStoryText');
        ROWS = lb.querySelector('#lbRows');
        ACTIONS = lb.querySelector('#lbActions');
      }

      function row(label, value) {
        return '<div class="lb-row"><dt>' + esc(label) + '</dt><dd>' + esc(value) + '</dd></div>';
      }

      function render() {
        var p = photos[idx];
        IMG.setAttribute('src', p.src);
        IMG.setAttribute('alt', p.alt);
        EYEBROW.textContent = 'Field story #' + String(idx + 1).padStart(2, '0');
        COUNT.textContent = String(idx + 1).padStart(2, '0') + ' / ' + String(photos.length).padStart(2, '0');
        CAPTION.textContent = p.caption;

        /* Story area only exists when there is real information to show. */
        var rows = '';
        if (p.customer) { rows += row('Customer', p.customer); }
        if (p.location) { rows += row('Location', p.location); }
        if (p.detector) { rows += row('Detector', p.detector); }
        if (p.result)   { rows += row('Result', p.result); }
        STORYTEXT.hidden = !p.story;
        if (p.story) { STORYTEXT.textContent = p.story; }

        ACTIONS.innerHTML = '';
        if (p.video) {
          var a = document.createElement('a');
          a.className = 'lb-link';
          a.href = p.video;
          a.target = '_blank';
          a.rel = 'noopener';
          a.textContent = 'Watch customer story';
          ACTIONS.appendChild(a);
        }

        var hasStory = rows || p.story || p.video;
        STORY.hidden = !hasStory;
        ROWS.innerHTML = rows;

        /* Multi-photo galleries get arrows; a single photo does not. */
        lb.querySelector('#lbPrev').hidden = photos.length < 2;
        lb.querySelector('#lbNext').hidden = photos.length < 2;
      }

      function go(step) {
        idx = (idx + step + photos.length) % photos.length;
        render();
      }

      function open(i) {
        if (!lb) { build(); cache(); }
        idx = i;
        lastFocus = document.activeElement;
        render();
        lb.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        lb.querySelector('#lbClose').focus();
      }

      function close() {
        if (!lb || !lb.classList.contains('is-open')) return;
        lb.classList.remove('is-open');
        document.body.style.overflow = '';
        if (lastFocus && lastFocus.focus) lastFocus.focus();
      }

      if (!lb) { build(); cache(); }

      lb.querySelector('#lbClose').addEventListener('click', close);
      lb.querySelector('#lbPrev').addEventListener('click', function () { go(-1); });
      lb.querySelector('#lbNext').addEventListener('click', function () { go(1); });

      /* Clicking the dimmed backdrop closes; clicking the dialog does not. */
      lb.addEventListener('click', function (e) {
        if (e.target === lb) close();
      });

      document.addEventListener('keydown', function (e) {
        if (!lb || !lb.classList.contains('is-open')) return;
        if (e.key === 'Escape') { close(); return; }
        if (e.key === 'ArrowLeft') { go(-1); return; }
        if (e.key === 'ArrowRight') { go(1); return; }
        /* Keep Tab inside the dialog while it is open. */
        if (e.key === 'Tab') {
          var f = lb.querySelectorAll('button:not([hidden])');
          if (!f.length) return;
          var first = f[0], last = f[f.length - 1];
          if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
          else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
      });

      /* Opening a photo wins over the slider's click-to-advance, which is why
         propagation is stopped here rather than the existing handler removed. */
      frame.addEventListener('click', function (e) {
        var img = e.target;
        if (!img || img.tagName !== 'IMG') return;
        e.stopPropagation();
        open(imgs.indexOf(img));
      });

      frame.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ' && e.key !== 'Spacebar') return;
        var img = e.target;
        if (!img || img.tagName !== 'IMG') return;
        e.preventDefault();
        e.stopPropagation();
        open(imgs.indexOf(img));
      });

      /* Swipe. Decided on touchend so vertical scrolling is never hijacked. */
      var sx = 0, sy = 0, tracking = false;
      lb.addEventListener('touchstart', function (e) {
        if (e.touches.length !== 1) { tracking = false; return; }
        sx = e.touches[0].clientX; sy = e.touches[0].clientY; tracking = true;
      }, { passive: true });
      lb.addEventListener('touchend', function (e) {
        if (!tracking) return;
        tracking = false;
        var t = e.changedTouches[0];
        var dx = t.clientX - sx, dy = t.clientY - sy;
        if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) { go(dx < 0 ? 1 : -1); }
      }, { passive: true });

      /* Keep the gallery clean: no extra instruction text is needed. */
    })();

    // Trust Indicators - Animate stars on scroll (optimized with requestAnimationFrame)
    (function () {
      var stars = document.querySelectorAll('.star');
      var ratingBars = document.querySelectorAll('.rating-bar-fill');
      var animated = false;
      var ticking = false;

      function animateTrustIndicators() {
        if (animated) return;
        var trustSection = document.querySelector('.trust-section');
        if (!trustSection) return;

        var rect = trustSection.getBoundingClientRect();
        if (rect.top < window.innerHeight * 0.8) {
          animated = true;
          // Animate stars with staggered delay
          stars.forEach(function (star, index) {
            setTimeout(function () {
              star.classList.add('filled');
            }, index * 100);
          });
          // Animate rating bars
          ratingBars.forEach(function (bar, index) {
            var width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(function () {
              bar.style.width = width;
            }, index * 150);
          });
        }
        ticking = false;
      }

      function onScroll() {
        if (!ticking) {
          requestAnimationFrame(function () {
            animateTrustIndicators();
            ticking = false;
          });
          ticking = true;
        }
      }

      window.addEventListener('scroll', onScroll, { passive: true });
      onDomReady(animateTrustIndicators);
    })();

    // Mobile Enhancements
    (function () {
      var isMobile = window.innerWidth <= 640;
      var pullRefresh = document.getElementById('pullRefresh');
      var startY = 0;
      var pullThreshold = 100;
      var isPulling = false;


      // Pull-to-refresh
      if (pullRefresh && isMobile) {
        var touchStartY = 0;
        var isAtTop = true;

        document.addEventListener('touchstart', function (e) {
          touchStartY = e.touches[0].clientY;
          isAtTop = window.scrollY === 0;
        }, { passive: true });

        document.addEventListener('touchmove', function (e) {
          if (!isAtTop) return;
          var currentY = e.touches[0].clientY;
          var diff = currentY - touchStartY;

          if (diff > 0 && diff < 200) {
            pullRefresh.style.height = Math.min(diff / 2, 60) + 'px';
            if (diff > pullThreshold) {
              pullRefresh.classList.add('active');
            } else {
              pullRefresh.classList.remove('active');
            }
          }
        }, { passive: true });

        document.addEventListener('touchend', function () {
          if (pullRefresh.classList.contains('active')) {
            window.location.reload();
          } else {
            pullRefresh.style.height = '0';
            pullRefresh.classList.remove('active');
          }
        });
      }

      // Swipe detection for galleries
      function addSwipeHint(container) {
        if (!container) return;
        var hint = container.querySelector('.swipe-hint');
        if (!hint) {
          hint = document.createElement('div');
          hint.className = 'swipe-hint';
          hint.innerHTML = '<span>↔</span><span>Swipe to see more</span>';
          container.appendChild(hint);
        }

        // Show hint after 2 seconds if user hasn't swiped
        var hintShown = false;
        var hintTimeout = setTimeout(function () {
          if (!hintShown) {
            hint.classList.add('visible');
            setTimeout(function () {
              hint.classList.remove('visible');
            }, 3000);
          }
        }, 2000);

        // Hide hint on swipe
        var startX = 0;
        container.addEventListener('touchstart', function (e) {
          startX = e.touches[0].clientX;
          hintShown = true;
          clearTimeout(hintTimeout);
        }, { passive: true });

        container.addEventListener('touchmove', function (e) {
          var diff = e.touches[0].clientX - startX;
          if (Math.abs(diff) > 50) {
            container.classList.add('swiping');
          }
        }, { passive: true });

        container.addEventListener('touchend', function () {
          container.classList.remove('swiping');
        }, { passive: true });
      }

      /* Only the slides gallery is still a swipeable strip; the proof section
         renders a single fixed player, so there is nothing to swipe there. */
      if (isMobile) {
        var slidesStage = document.querySelector('.slides-stage');
        if (slidesStage) {
          slidesStage.classList.add('gallery-swipe');
          addSwipeHint(slidesStage);
        }
      }

      // Touch-friendly button feedback
      var touchButtons = document.querySelectorAll('button, .button, a, .card');
      touchButtons.forEach(function (btn) {
        btn.addEventListener('touchstart', function () {
          this.style.transform = 'scale(0.97)';
        }, { passive: true });

        btn.addEventListener('touchend', function () {
          this.style.transform = '';
        }, { passive: true });
      });

      // Smooth page transitions on scroll (optimized)
      function initScrollAnimations() {
        var sections = document.querySelectorAll('.section');
        if (sections.length === 0) return;

        /* threshold 0, not a fraction: a section taller than roughly ten times
           the viewport can never be 10% visible at once, so on a phone the long
           sections stayed at opacity 0 for good and took their videos and photos
           with them. Any pixel showing is enough to reveal. */
        var observerOptions = {
          threshold: 0,
          rootMargin: '0px 0px -50px 0px'
        };

        if (!('IntersectionObserver' in window)) {
          /* No observer means .visible is never added, and every section would
             stay invisible. Show them all instead. */
          sections.forEach(function (section) { section.classList.add('visible'); });
          return;
        }

        var observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
              observer.unobserve(entry.target); // Stop observing once visible
            }
          });
        }, observerOptions);

        sections.forEach(function (section) {
          observer.observe(section);
        });
      }

      // Ripple effect on buttons
      function initRippleEffect() {
        var rippleButtons = document.querySelectorAll('.ripple');
        rippleButtons.forEach(function (button) {
          button.addEventListener('click', function (e) {
            var rect = this.getBoundingClientRect();
            var x = e.clientX - rect.left;
            var y = e.clientY - rect.top;

            var ripple = document.createElement('span');
            ripple.className = 'ripple-effect';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';

            this.appendChild(ripple);

            setTimeout(function () {
              ripple.remove();
            }, 600);
          });
        });
      }

      // Initialize effects
      onDomReady(function () {
        initScrollAnimations();
        initRippleEffect();
      });
    })();
  </script>
</body>
</html>