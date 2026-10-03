<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/video-player.php';
$s = hpl_settings();

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
     */
    function hpl_embed_url(string $value): string
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

        /* Muted autoplay is the only kind browsers permit without a gesture,
           and a paused poster frame reads as a still photograph. Sound stays
           off because a cross-origin player cannot be unmuted from here -
           visitors use Bunny's own controls. */
        $query['autoplay'] = '1';
        $query['muted'] = '1';
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
            return '<div class="proof-embed" style="aspect-ratio:16 / 9; height:100%; width:100%;">'
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
    :root { --navy:#111a38; --navy-dark:#090f24; --gold:#d4a52c; --gold-light:#f4ca5b; --ink:#182038; --muted:#687083; --paper:#111a38; --wash:#111a38; }
    * { box-sizing:border-box; }
    html { scroll-behavior:smooth; }
    body { margin:0; color:#fff; background:var(--navy); font-family:'DM Sans',sans-serif; font-size:17px; line-height:1.55; overflow-x:hidden; }
    a { color:inherit; text-decoration:none; }
    .page { width:100%; margin:0 auto; background:var(--navy); }
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
    .button.hero-find-cta { align-items:center; background:var(--gold); border:6px solid #713400; border-radius:999px; box-shadow:0 0 24px rgba(244,202,91,.36); color:var(--navy-dark); display:inline-flex; font-size:17px; font-weight:700; justify-content:center; line-height:1.3; min-height:94px; padding:20px 38px; text-align:center; width:min(460px,100%); }
    .button.hero-find-cta:hover { background:var(--gold-light); }
    .section { color:#fff; padding:46px 34px; }
    .section.center { text-align:center; }
    .section h2 { color:#fff; font-family:'Space Grotesk',sans-serif; font-size:34px; letter-spacing:-.05em; line-height:1.05; margin:0 0 12px; }
    .section p { color:rgba(255,255,255,.78); font-size:16px; line-height:1.6; margin:0 auto; max-width:640px; }
    .wash { background:var(--navy); }
    .proof { background:var(--navy); text-align:center; }
    .proof h2 { color:#fff; margin-bottom:20px; }
    .proof-stage { background:transparent; display:flex; gap:18px; margin:0 auto; max-width:620px; overflow-x:auto; overflow-y:hidden; padding:8px 10px 16px; scroll-behavior:smooth; scroll-snap-type:x proximity; scrollbar-width:none; -ms-overflow-style:none; width:min(92vw, 620px); }
    .proof-stage::-webkit-scrollbar { display:none; }
    .proof-slide { aspect-ratio:16/9 !important; background:#000; border-radius:14px; box-shadow:0 12px 25px rgba(9,15,36,.16); flex:0 0 100%; height:min(42vw, 420px) !important; margin:0; max-height:420px; max-width:100%; opacity:1; overflow:hidden; position:relative; scroll-snap-align:center; transform:none; transition:opacity .25s ease, transform .3s ease; }
    .proof-slide.on { transform:none; z-index:2; }
    .proof-slide .proof-embed { aspect-ratio:16/9 !important; height:100% !important; position:relative; width:100% !important; }
    .proof-slide .proof-embed iframe { border:0; display:block; height:100% !important; inset:0; position:absolute; width:100% !important; }
    .proof-slide video { display:block; height:100%; object-fit:cover; width:100%; }
    .proof-slide figcaption { background:linear-gradient(to top,rgba(0,0,0,.82),rgba(0,0,0,0)); bottom:0; color:#fff; font-size:13px; font-weight:700; left:0; padding:28px 12px 10px; position:absolute; right:0; text-align:left; }
    .proof-cue { align-items:center; animation:nudge 1.8s ease-in-out infinite; background:rgba(17,26,56,.9); border:1px solid rgba(244,202,91,.7); border-radius:999px; bottom:18px; box-shadow:0 10px 20px rgba(9,15,36,.18); color:#fff; cursor:pointer; display:flex; gap:8px; font-size:12px; font-weight:700; justify-content:center; letter-spacing:.08em; padding:8px 14px 8px 12px; position:absolute; right:14px; text-transform:uppercase; transition:opacity .3s ease; z-index:4; }
    .proof-cue .proof-cue-arrow { font-size:22px; line-height:1; }
    .proof-cue .proof-cue-text { animation:blink 1.2s ease-in-out infinite; }
    .proof-stage-wrap.cue-dismissed .proof-cue { opacity:1; pointer-events:auto; }
    .proof-stage-wrap { margin:0 auto; max-width:620px; position:relative; }
    .proof-stage-wrap .ring-sound-btn { right:16px; top:16px; position:absolute; z-index:10; }
    @keyframes nudge { 0%,100% { transform:translateX(0); } 50% { transform:translateX(6px); } }
    @keyframes blink { 0%,100% { opacity:1; } 50% { opacity:.35; } }
    .proof-empty { background:var(--navy); color:#fff; font-size:14px; margin:0 auto; max-width:420px; padding:60px 20px; }
    .proof-hint { color:var(--muted); font-size:13px; font-weight:700; letter-spacing:.08em; margin:16px 0 0; text-transform:uppercase; }
    .proof-dots { display:flex; gap:8px; justify-content:center; margin:12px 0 0; }
    .proof-dots button { background:#cfd4dc; border:0; border-radius:50%; cursor:pointer; height:9px; padding:0; width:9px; }
    .proof-dots button.on { background:var(--gold); transform:scale(1.35); }
    /* circle of videos */
    .ring-wrap { margin:26px auto 0; max-width:100%; position:relative; touch-action:pan-y; }
    .ring { --ring-w:min(var(--ring-size,760px), 84vw); height:calc(var(--ring-w) / var(--ring-shape,0.5625) * 1.12 + 30px); margin:0 auto; max-width:100%; perspective:calc(var(--ring-r,520px) * 10); position:relative; width:calc(var(--ring-r,520px) * 2 + var(--ring-w) + 40px); }
    .ring-item { background:#0b1226; border-radius:14px; box-shadow:0 12px 30px rgba(9,15,36,.28); cursor:pointer; left:50%; margin:0; overflow:hidden; position:absolute; top:50%; transform:translate(-50%,-50%); transition:transform .3s cubic-bezier(.45,.05,.25,1), opacity .2s ease, filter .2s ease, box-shadow .2s ease; width:var(--ring-w); will-change:transform,opacity; }
    .ring-item video { aspect-ratio:var(--ring-shape,0.5625); display:block; height:auto; object-fit:cover; width:100%; }
    /* Keep Bunny embeds full-bleed inside the card so they feel like native
       video tiles, while preserving the original ring geometry and motion. */
    .proof-embed { align-items:center; aspect-ratio:var(--ring-shape,0.5625); background:#0b1226; border-radius:14px; display:flex; height:100%; justify-content:center; overflow:hidden; position:relative; width:100%; }
    /* No scale() here on purpose. Magnifying the frame cropped roughly 16% off
       every edge inside the overflow:hidden card, so a paused player read as a
       cropped photograph. Bunny's own player letterboxes inside the box, which
       is what a normal embedded video does. */
    .proof-embed iframe { border:0; display:block; height:100%; inset:0; max-height:100%; max-width:100%; object-fit:contain; position:absolute; width:100%; }
    .proof-embed iframe:not([data-on]) { visibility:hidden; }
    .ring-item figcaption { background:linear-gradient(to top,rgba(0,0,0,.86),rgba(0,0,0,0)); bottom:0; color:#fff; font-size:12px; font-weight:700; left:0; line-height:1.25; padding:30px 10px 10px; position:absolute; right:0; text-align:left; }
    .ring-item::after { border:2px solid transparent; border-radius:14px; content:''; inset:0; pointer-events:none; position:absolute; transition:border-color .35s ease; }
    .ring-item.front { box-shadow:0 20px 44px rgba(9,15,36,.4); z-index:5; }
    .ring-item.front::after { border-color:var(--gold); }
    .ring-item:not(.front) { filter:saturate(.82) brightness(.82); }
    .ring-item:hover:not(.front) { filter:none; }
    .ring-play { align-items:center; background:rgba(17,26,56,.55); border:0; border-radius:50%; color:#fff; cursor:pointer; display:flex; font-size:26px; height:62px; justify-content:center; left:50%; padding:0 0 0 4px; position:absolute; top:50%; transform:translate(-50%,-50%); transition:opacity .3s ease, transform .3s ease; width:62px; z-index:6; }
    .ring-play:focus-visible { outline:2px solid var(--gold); outline-offset:3px; }
    .ring-wrap.playing .ring-play { opacity:0; pointer-events:none; }
    .ring-item video { pointer-events:none; }
    .ring-nav { align-items:center; display:flex; gap:14px; justify-content:center; margin:18px 0 0; }
    .ring-sound-btn { align-items:center; background:rgba(17,26,56,.75); border:1px solid rgba(244,202,91,.5); border-radius:50%; color:var(--gold-light); cursor:pointer; display:flex; font-size:18px; height:44px; justify-content:center; right:14px; padding:0; position:absolute; top:14px; transition:background-color .2s ease, border-color .2s ease; width:44px; z-index:200; }
    .ring-sound-btn:hover { background:rgba(17,26,56,.9); border-color:var(--gold); }
    .ring-sound-btn:focus-visible { outline:2px solid var(--gold); outline-offset:2px; }
    .ring-sound-btn[aria-pressed="true"] { background:var(--gold); border-color:var(--gold); color:var(--navy-dark); }
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
.proof-coverflow .ring-item { border:1px solid rgba(255,255,255,.12); border-radius:28px; box-shadow:0 14px 34px rgba(0,0,0,.4); transform-style:preserve-3d; transition:transform .4s cubic-bezier(.22,1,.36,1), opacity .4s cubic-bezier(.22,1,.36,1), filter .4s cubic-bezier(.22,1,.36,1); width:var(--cf-w);  }
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

    /* Sound control for the coverflow, overlaid on the top right of the player in
       the same place and style as the promo's .mute-toggle on the hero panel, so
       the two read as one control. It sits inside .cf-stage, which is already
       position:relative and clips its overflow, so it needs a z-index above the
       cards - those are z-indexed 98..100 by the layout code. */
    .cf-audio { align-items:center; display:flex; gap:10px; position:absolute; right:14px; top:14px; z-index:200; }
    .cf-audio-btn { align-items:center; background:rgba(9,15,36,.82); border:1px solid rgba(244,202,91,.6); border-radius:6px; color:var(--gold-light); cursor:pointer; display:flex; font-size:12px; font-weight:700; gap:7px; letter-spacing:.1em; padding:9px 13px; text-transform:uppercase; transition:background-color .2s ease, border-color .2s ease; }
    /* These two set display themselves, which outranks the user agent's
       [hidden] { display:none }, so setting the hidden attribute left both
       sound buttons visible over a player embed - a control with nothing
       behind it, since a cross-origin player cannot be unmuted from here. */
    .cf-audio-btn[hidden], .ring-sound-btn[hidden] { display:none; }
    .cf-sound-btn { right:14px; top:14px; position:absolute; z-index:200; }
    .cf-audio-btn:hover { background:rgba(9,15,36,.95); border-color:var(--gold); }
    .cf-audio-btn:focus-visible { outline:2px solid var(--gold); outline-offset:2px; }
    .cf-audio-btn[aria-pressed="true"] { background:var(--gold); border-color:var(--gold); color:var(--navy-dark); }
    .cf-audio-ico { font-size:15px; line-height:1; }
    .cf-audio-txt { white-space:nowrap; }
    .cf-audio-vol { align-items:center; background:rgba(9,15,36,.82); border:1px solid rgba(244,202,91,.35); border-radius:6px; display:flex; gap:7px; padding:7px 11px; }
    .cf-audio-lbl { color:var(--gold-light); font-size:14px; line-height:1; }
    .cf-audio-vol input[type="range"] { accent-color:var(--gold); cursor:pointer; height:16px; width:88px; }
    .cf-audio-vol input[type="range"]:focus-visible { outline:2px solid var(--gold); outline-offset:2px; }

    /* On a phone the button text and the slider cannot both sit across the top of
       a 65vw card, so the label drops and the slider shortens. The speaker icon
       and the button icon still carry the meaning. */
    @media (max-width:640px) {
      .cf-audio { gap:6px; right:8px; top:8px; }
      .cf-audio-txt { display:none; }
      .cf-audio-btn { padding:8px 10px; }
      .cf-audio-vol { padding:5px 8px; }
      .cf-audio-vol input[type="range"] { width:62px; }
    }

    /* Tablet and phone cards are nearly as wide as the stage, so a control wide
       enough to hold a label would sit on top of the video it belongs to. Below
       this width it becomes a single icon button and the slider only appears on
       focus, which keeps the video face clear while leaving the control usable
       from the keyboard. */
    @media (max-width:900px) {
      .cf-audio-txt { display:none; }
      .cf-audio-vol { display:none; }
      .cf-audio-vol:focus-within { display:flex; }
      .cf-audio-btn { padding:9px 11px; }
    }
    /* At phone widths the stage is exactly as wide as the card, so there is no room
       beside the video for a control, and .cf-stage clips its overflow, so
       pushing the control above the stage would hide it. It stays on the video and
       becomes a single small icon, matching how the promo overlays its own panel.
       The focused card keeps its face visible because the control sits in the top
       corner where a testimonial has no text. */
    @media (max-width:480px) {
      .cf-audio { gap:5px; right:6px; top:6px; }
      .cf-audio-btn { padding:7px 8px; }
      .cf-audio-ico { font-size:14px; }
    }
.proof-coverflow .ring-play { backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); background:rgba(30,30,30,.55); height:64px; width:64px; }
.proof-coverflow .ring-play:hover { transform:translate(-50%,-50%) scale(1.08); }
.proof-coverflow .ring-nav button { background:rgba(255,255,255,.08); border-color:rgba(255,255,255,.18); color:#fff; }
    @media (max-width:1024px) { .proof-coverflow .cf-stage { --cf-gap:20px; --cf-w:230px; } }
    @media (max-width:640px) { .proof-coverflow .cf-stage { --cf-gap:14px; --cf-w:min(58vw, 250px); } .proof-coverflow .ring-item { border-radius:22px; } .proof-coverflow .ring-item::after { border-radius:22px; } .proof-coverflow .cf-arrow { height:40px; width:40px; font-size:21px; } }
    @media (max-width:640px) { .ring { --ring-r:120px; --ring-size:250px; } .ring-item { border-radius:11px; } .ring-item figcaption { font-size:10px; padding:22px 7px 7px; } .ring-play { font-size:22px; height:50px; width:50px; } }
    @media (max-width:400px) { .ring { --ring-r:95px; --ring-size:210px; } .ring-item figcaption { font-size:9px; padding:18px 6px 6px; } }
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
    .proof-visual-grid { display:grid; gap:18px; grid-template-columns:repeat(3,minmax(0,1fr)); margin:28px auto 0; max-width:1120px; }
    .proof-visual-grid figure { background:#fff; border:1px solid rgba(244,202,91,.28); border-radius:12px; display:flex; flex-direction:column; margin:0; overflow:hidden; }
    .proof-visual-grid img { aspect-ratio:4/5; display:block; height:auto; object-fit:cover; width:100%; }
    .review-screenshot-grid { align-items:start; display:grid; gap:18px; grid-template-columns:repeat(3,minmax(0,1fr)); margin:36px auto 0; max-width:1220px; }
    .review-screenshot-grid figure { background:#fff; border:1px solid rgba(244,202,91,.42); border-radius:10px; box-shadow:0 14px 32px rgba(0,0,0,.3); margin:0; overflow:hidden; padding:10px; }
    .review-screenshot-grid img { display:block; height:auto; max-height:520px; object-fit:contain; width:100%; }
    .review-screenshot-heading { color:var(--gold-light); font-family:'Space Grotesk',sans-serif; font-size:22px; margin:36px 0 0; }
    .proof-mix-grid { display:grid; gap:26px; grid-template-columns:1fr; margin:28px auto 0; max-width:1220px; }
    .proof-mix-story-link { color:inherit; display:block; text-decoration:none; }
    .proof-mix-card-link { color:inherit; display:block; height:100%; text-decoration:none; }
    .proof-mix-card { background:#fff; border:1px solid rgba(17,26,56,.08); border-radius:18px; box-shadow:0 18px 45px rgba(17,26,56,.08); display:flex; flex-direction:column; height:100%; overflow:hidden; transition:transform .25s ease, box-shadow .25s ease; }
    .proof-mix-card-link:hover .proof-mix-card,
    .proof-mix-card-link:focus-visible .proof-mix-card { box-shadow:0 28px 60px rgba(17,26,56,.12); transform:translateY(-3px); }
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
    .proof-mix-story-header h3 {
      color:#fff; font-family:'Space Grotesk',sans-serif; font-size:clamp(28px,4vw,42px); letter-spacing:-.04em; line-height:1.08; margin:0;
    }
    .proof-mix-story-body {
      align-items:stretch; display:grid; gap:28px; grid-template-columns:minmax(260px,.9fr) minmax(0,1.1fr); padding:26px;
    }
    .proof-mix-story-copy {
      align-self:center; grid-column:2; grid-row:1;
    }
    .proof-mix-story:nth-child(2) .proof-mix-story-copy { grid-column:1; }
    .proof-mix-story-copy h4 {
      color:var(--gold-light); font-family:'Space Grotesk',sans-serif; font-size:clamp(22px,3vw,32px); letter-spacing:-.03em; line-height:1.15; margin:0 0 14px;
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
    .proof-mix-body h3 { color:var(--navy); font-family:'Space Grotesk',sans-serif; font-size:22px; letter-spacing:-.04em; line-height:1.1; margin:0; }
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
    .proof-social-wrap { display:grid; gap:18px; grid-template-columns:repeat(3,minmax(0,1fr)); margin:28px auto 0; max-width:1220px; }
    .proof-social-card { background:#fff; border:1px solid rgba(17,26,56,.08); border-radius:18px; box-shadow:0 18px 45px rgba(17,26,56,.08); overflow:hidden; }
    .proof-social-top { align-items:center; background:#fff; display:flex; gap:9px; justify-content:space-between; padding:12px 14px; }
    .proof-social-brand { align-items:center; display:flex; gap:8px; }
    .proof-social-dot { background:linear-gradient(135deg,#2dd4bf,#14b8a6); border-radius:50%; display:block; height:10px; width:10px; }
    .proof-social-app { color:#111a38; font-size:10px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
    .proof-social-body { background:linear-gradient(180deg,#f9fafb,#eef2f7); padding:14px; }
    .proof-social-body p { color:#1f2937; font-size:13px; line-height:1.5; margin:0; }
    .proof-social-screenshot { background:#f3f6fb; border:1px solid rgba(17,26,56,.08); border-radius:12px; min-height:190px; overflow:hidden; position:relative; }
    .proof-social-screenshot.has-image { aspect-ratio:4/5; margin:0 auto; max-width:420px; min-height:0; }
    .proof-social-screenshot.has-image::before { display:none; }
    .proof-social-screenshot.has-image > img { display:block; height:100%; object-fit:contain; width:100%; }
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
      .proof-social-wrap { grid-template-columns:1fr; }
    }
    @media (max-width:640px) {
      .proof-mix-grid { grid-template-columns:1fr; }
      .proof-mix-body h3 { font-size:19px; }
      .review-screenshot-grid { gap:14px; grid-template-columns:1fr; max-width:460px; }
      .proof-visual-grid { grid-template-columns:1fr; max-width:460px; }
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
    .proof .proof-caption, .proof .pf-sound-hint, .proof-stage-wrap .ring-sound-btn { display:none !important; }
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
    .benefit h3 { color:#fff; font-family:'Space Grotesk',sans-serif; font-size:18px; margin:0 0 8px; }
    .benefit p { color:rgba(255,255,255,.78); font-size:14px; line-height:1.5; }
    .spaced-cta { padding:10px 0 46px; text-align:center; }
    .accordions { margin:0 auto; max-width:700px; text-align:left; }
    details { border-bottom:1px solid #d9dce2; }
    summary { align-items:center; background:#7e8292; color:#fff; cursor:pointer; display:flex; font-size:15px; font-weight:700; justify-content:space-between; list-style:none; margin-top:12px; padding:14px 16px; }
    summary::-webkit-details-marker { display:none; }
    summary::after { content:'+'; font-size:20px; font-weight:400; }
    details[open] summary::after { content:'×'; }
    details p { color:rgba(255,255,255,.78); font-size:14px; line-height:1.6; padding:0 16px 10px; }
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
    /* Two-step qualification funnel */
    .lead-form { max-width:560px; padding:26px 26px 28px; }
    .form-progress { margin:0 0 20px; }
    .form-progress-track { background:#e6e9ee; border-radius:40px; display:block; height:5px; overflow:hidden; }
    .form-progress-fill { background:linear-gradient(90deg,var(--gold),var(--gold-light)); border-radius:40px; display:block; height:100%; transition:width .35s ease; width:50%; }
    .form-progress-text { color:var(--muted); display:block; font-size:11.5px; font-weight:700; letter-spacing:.11em; margin-top:9px; text-transform:uppercase; }
    .form-step-head { border-bottom:1px solid #e6e9ee; margin:0 0 4px; padding-bottom:15px; }
    .form-step-head h3 { color:var(--navy); font-family:'Space Grotesk',sans-serif; font-size:21px; letter-spacing:-.01em; line-height:1.2; margin:0 0 5px; }
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
    .lead-form .card { align-items:flex-start; background:#fff; border:1px solid #dfe3ea; border-radius:10px; cursor:pointer; display:flex; gap:12px; margin:0; padding:13px 15px; transition:border-color .18s ease, background .18s ease, box-shadow .18s ease; }
    .lead-form .card:hover { border-color:var(--gold); }
    .lead-form .card input { height:1px; margin:0; opacity:0; position:absolute; width:1px; }
    .card-mark { border:2px solid #c3c9d4; border-radius:50%; flex:none; height:19px; margin-top:2px; position:relative; transition:border-color .18s ease; width:19px; }
    .card-mark::after { background:var(--navy-dark); border-radius:50%; content:''; height:9px; left:50%; opacity:0; position:absolute; top:50%; transform:translate(-50%,-50%) scale(.5); transition:opacity .18s ease, transform .18s ease; width:9px; }
    .lead-form .card input:checked ~ .card-mark { border-color:var(--gold); }
    .lead-form .card input:checked ~ .card-mark::after { opacity:1; transform:translate(-50%,-50%) scale(1); }
    .lead-form .card input:focus-visible ~ .card-mark { outline:2px solid var(--gold-light); outline-offset:2px; }
    .lead-form .card.is-on { background:#fffdf5; border-color:var(--gold); box-shadow:0 2px 10px rgba(212,165,44,.18); }
    .card-text { display:block; min-width:0; }
    .card-title { color:var(--navy); display:block; font-size:14.5px; font-weight:700; line-height:1.35; }
    .card-note { color:var(--muted); display:block; font-size:12.5px; font-weight:400; line-height:1.4; margin-top:2px; }
    .lead-form .advice-check { align-items:flex-start; background:#fffdf5; border:1px solid #f0e2bd; border-radius:10px; cursor:pointer; display:flex; gap:11px; margin-top:18px; padding:14px 15px; }
    .lead-form .advice-check input { accent-color:var(--gold); flex:none; height:17px; margin:1px 0 0; width:17px; }
    .lead-form .advice-check span { color:var(--navy); font-size:13.5px; font-weight:600; line-height:1.45; }
    .lead-form .button[disabled] { cursor:default; opacity:.65; }
    .lead-form .button.is-busy { pointer-events:none; }
    .final .lead-success { background:#fff; border-radius:14px; box-shadow:0 20px 48px rgba(4,10,28,.34); margin:26px auto 0; max-width:560px; padding:44px 34px; }
    .lead-success:focus { outline:none; }
    .lead-success-mark { align-items:center; background:linear-gradient(140deg,var(--gold),var(--gold-light)); border-radius:50%; color:var(--navy-dark); display:inline-flex; height:66px; justify-content:center; margin-bottom:18px; width:66px; }
    .lead-success-mark svg { height:34px; width:34px; }
    .final .lead-success h3 { color:var(--navy); font-family:'Space Grotesk',sans-serif; font-size:30px; letter-spacing:-.02em; margin:0 0 10px; }
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
    .thankyou h3 { color:var(--navy); font-family:'Space Grotesk',sans-serif; font-size:22px; margin:0 0 8px; }
    .thankyou p { color:var(--muted); font-size:15px; margin:0; }
    footer { background:var(--navy); color:#ffffff; font-size:12px; font-weight:300; line-height:1.3em; padding:35px 10px 25px; }
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
      box-shadow:0 20px 40px rgba(16,185,129,.44);
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
    .back-to-top {
      align-items:center; background:var(--gold); border:1px solid rgba(9,15,36,.28); border-radius:50%; bottom:94px; box-shadow:0 8px 20px rgba(9,15,36,.28); color:var(--navy-dark); cursor:pointer; display:flex; font-size:25px; font-weight:700; height:46px; justify-content:center; line-height:1; padding:0; position:fixed; right:24px; width:46px; z-index:990;
    }
    .back-to-top:hover { background:var(--gold-light); }
    @media (max-width:640px) {
  .cookie-banner { padding:16px 14px; }
  .cookie-content { flex-direction:column; align-items:flex-start; gap:12px; }
  .cookie-buttons { width:100%; justify-content:space-between; }
  .cookie-btn { flex:1; text-align:center; }
  .wa-float {
    bottom:18px;
    padding:10px 14px 10px 12px;
    right:14px;
  }
  .back-to-top { bottom:82px; right:16px; }
  .wa-float-label { letter-spacing:.06em; }
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
  .button.hero-find-cta { border-width:5px; font-size:15px; min-height:78px; padding:14px 22px; width:min(400px,90%); }
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
    .lead-form .card { gap:11px; padding:12px 13px; }
    .card-title { font-size:14px; }
    .card-note { font-size:12px; }
    .form-step-head h3 { font-size:19px; }
    .form-nav .button { padding:12px 12px; }
    .final .lead-success { padding:34px 20px; }
    .final .lead-success h3 { font-size:26px; }
    .field-row { grid-template-columns:1fr; }
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
    .slides-frame img, .proof-slide, .ring-item, .slides-cap { transition-duration:.01ms !important; }
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
  /* Sound hint. Hidden by JS the moment the existing toggle reports sound on,
      so it can never contradict the button state. Needs a specific selector
      to win against global .section p typography. */
  p.pf-sound-hint,
  .pf-sound-hint {
    align-items:center; color:rgba(255,255,255,.72); display:flex;
    font-size:11px; font-weight:700; gap:8px; justify-content:center;
    letter-spacing:.16em; margin:16px 0 0; text-transform:uppercase;
  }
  .pf-sound-hint[hidden] { display:none; }

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
  }
  </style>
</head>
<body>
  <div class="page">
    <div class="topline"><?= h($s['topline']) ?></div>
    <header><div class="nav"><a class="brand logo-chip" href="#top"><img class="header-logo" src="<?= hpl_img_url('hpllogo.jpeg') ?>" alt="HPL Gold Detectors"></a><nav class="nav-links"><a href="#what-you-get"><?= h($s['nav_1']) ?></a><a href="#faq"><?= h($s['nav_2']) ?></a></nav><a class="nav-cta" href="#book"><?= h($s['nav_cta']) ?></a></div></header>
    <main id="top">
      <section class="hero" id="hero">
        <span class="live-pill"><i></i><?= h($s['live_pill']) ?></span>
        <h1><?= h($s['hero_h1']) ?><span><?= h($s['hero_h1_span']) ?></span></h1>
        <p class="hero-sub"><?= h($s['hero_sub']) ?></p>
        <p class="hero-intro"><?= h($s['hero_intro']) ?></p>
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
              <a href="<?= h($bunnySrc) ?>" target="_blank" rel="noopener">Watch the HPL promo video</a>
            </div>
          </noscript>
        </div>
        <p class="hero-copy"><?= h($s['hero_caption']) ?></p>
        <a class="button hero-find-cta" href="#book" aria-disabled="true" data-ready-label="<?= h($s['cta_text']) ?>">WATCH THE VIDEO TO UNLOCK</a>
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
              $proofItems[] = ['kind' => 'embed', 'src' => $embed, 'caption' => (string)($s['proof_video_' . $i . '_caption'] ?? '')];
              continue;
          }
          $src = hpl_media_url($raw);
          if ($src === '') { continue; }
          $proofItems[] = ['kind' => 'video', 'src' => $src, 'caption' => (string)($s['proof_video_' . $i . '_caption'] ?? '')];
      }
      $layout = (string)($s['proof_layout'] ?? 'ring');
      if (!in_array($layout, ['ring', 'strip', 'coverflow'], true)) { $layout = 'ring'; }

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
          $proofGroups[] = ['key' => 'videos', 'label' => 'Videos', 'count' => count($proofItems)];
      }
      $photoCount = 0;
      for ($i = 1; $i <= 6; $i++) {
          if (file_exists(__DIR__ . '/img/slide-' . $i . '.jpg')) { $photoCount++; }
      }
      if ($photoCount > 0) {
          $proofGroups[] = ['key' => 'photos', 'label' => 'Field Photos', 'count' => $photoCount];
      }
      ?>
      <?php if (count($proofGroups) > 0): ?>
      <div class="pf-bar" id="pfBar" role="group" aria-label="Filter customer proof">
        <button type="button" class="pf-tab is-on" data-pf="all" aria-pressed="true">All</button>
        <?php foreach ($proofGroups as $g): ?>
        <button type="button" class="pf-tab" data-pf="<?= h($g['key']) ?>" aria-pressed="false"
                aria-label="<?= h($g['label'] . ', ' . (int)$g['count'] . ' items') ?>">
          <?= h($g['label']) ?><span class="pf-n" aria-hidden="true"><?= (int)$g['count'] ?></span>
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <section data-pf-group="videos" class="section center <?= $layout === 'coverflow' ? 'proof-dark' : 'wash' ?> proof"><div class="section-label"><?= h($s['social_label']) ?></div><h2><?= h($s['social_heading']) ?></h2><?php if (empty($proofItems)): ?><div class="proof-empty">Customer videos will appear here once added from the admin panel.</div><?php elseif ($layout === 'strip'): ?><div class="proof-stage-wrap" id="proofWrap"><div class="proof-stage" id="proofStage"><?php foreach ($proofItems as $item): ?><figure class="proof-slide" style="aspect-ratio:16 / 9; height:min(42vw, 420px); max-height:420px; width:100%;"><?= hpl_proof_media($item, 'loop data-proof-video') ?><?php if ($item['caption'] !== ''): ?><figcaption><?= h($item['caption']) ?></figcaption><?php endif; ?></figure><?php endforeach; ?></div><button class="proof-cue" id="proofCue" type="button" aria-label="More videos"><span class="proof-cue-text">More videos</span><span class="proof-cue-arrow" aria-hidden="true">&rsaquo;</span></button><button class="ring-sound-btn" id="ringSound" type="button" aria-label="Turn sound on" aria-pressed="false"><span aria-hidden="true">&#128263;</span></button></div><div class="proof-dots" id="proofDots"></div>
<?php elseif ($layout === 'coverflow'): ?>
<div class="ring-wrap proof-coverflow" id="ringWrap" style="--ring-shape:<?= h((string)max(0.2, min(4, (float)($s['proof_ring_shape'] ?? 0.5625)))) ?>;"><div class="cf-stage" id="ring"><?php foreach ($proofItems as $i => $item): ?><figure class="ring-item" data-ring-item role="button" tabindex="0" aria-label="Show story <?= (int)$i + 1 ?><?= $item['caption'] !== '' ? ': ' . h($item['caption']) : '' ?>"><?= hpl_proof_media($item, 'data-ring-video') ?><?php if ($item['caption'] !== ''): ?><figcaption><?= h($item['caption']) ?></figcaption><?php endif; ?></figure><?php endforeach; ?></div><button class="cf-arrow cf-prev" id="ringPrev" type="button" aria-label="Previous story"><span aria-hidden="true">&lsaquo;</span></button><button class="cf-arrow cf-next" id="ringNext" type="button" aria-label="Next story"><span aria-hidden="true">&rsaquo;</span></button><button class="cf-audio-btn cf-sound-btn" id="cfSound" type="button" aria-label="Turn sound on" aria-pressed="false"><span class="cf-audio-ico" aria-hidden="true">&#128263;</span><span class="cf-audio-txt">Sound off</span></button><button class="ring-play" id="ringPlay" type="button" aria-label="Play this story"><span aria-hidden="true">&#9654;</span></button></div><div class="cf-dots" id="cfDots"></div><?php else: ?><div class="ring-wrap" id="ringWrap" style="--ring-r:<?= h((string)(max(0, (float)($s['proof_ring_r'] ?? 300)))) ?>px;--ring-size:<?= h((string)(max(80, (float)($s['proof_ring_size'] ?? 300)))) ?>px;--ring-shape:<?= h((string)max(0.2, min(4, (float)($s['proof_ring_shape'] ?? 0.5625)))) ?>"><div class="ring" id="ring"><?php foreach ($proofItems as $i => $item): ?><figure class="ring-item" data-ring-item role="button" tabindex="0" aria-label="Show story <?= (int)$i + 1 ?><?= $item['caption'] !== '' ? ': ' . h($item['caption']) : '' ?>"><?= hpl_proof_media($item, 'loop data-ring-video') ?><?php if ($item['caption'] !== ''): ?><figcaption><?= h($item['caption']) ?></figcaption><?php endif; ?></figure><?php endforeach; ?></div><button class="ring-sound-btn" id="ringSound" type="button" aria-label="Turn sound on" aria-pressed="false"><span aria-hidden="true">&#128263;</span></button><button class="ring-play" id="ringPlay" type="button" aria-label="Play this story"><span aria-hidden="true">&#9654;</span></button></div><p class="proof-hint"><?= h($s['proof_hint']) ?></p><div class="ring-nav"><button type="button" id="ringPrev" aria-label="Previous story">&lsaquo;</button><button type="button" id="ringNext" aria-label="Next story">&rsaquo;</button></div><?php endif; ?><p class="proof-caption"><?= h($s['social_caption']) ?></p></section>

      <?php
        $proofVisuals = [];
        foreach ([
          'proof-1.jpg' => 'Detectorist holding a detector and recovered gold',
          'proof-2.jpg' => 'Gold detector standing in worked ground',
          'proof-3.jpg' => 'Gold detector shown across the field terrain',
        ] as $file => $alt) {
          if (file_exists(__DIR__ . '/img/' . $file)) { $proofVisuals[] = ['file' => $file, 'alt' => $alt]; }
        }
        $customerReviewFiles = array_values(array_filter(glob(__DIR__ . '/img/customer-review-*') ?: [], static function ($file) {
          return (bool)preg_match('/\.(jpe?g|png|webp|gif)$/i', $file);
        }));
        sort($customerReviewFiles, SORT_NATURAL | SORT_FLAG_CASE);
      ?>
      <?php if ($proofVisuals): ?>
      <section class="section proof-visuals">
        <div class="section-label">From the field</div>
        <h2>Real equipment. Real ground.</h2>
        <div class="proof-visual-grid">
          <?php foreach ($proofVisuals as $visual): ?>
          <figure><img src="<?= hpl_img_url($visual['file']) ?>" alt="<?= h($visual['alt']) ?>" loading="lazy"></figure>
          <?php endforeach; ?>
        </div>
        <?php if ($customerReviewFiles): ?>
        <h3 class="review-screenshot-heading">Customer reviews</h3>
        <div class="review-screenshot-grid">
          <?php foreach ($customerReviewFiles as $reviewFile): ?>
          <figure><img src="<?= hpl_img_url(basename($reviewFile)) ?>" alt="Customer review screenshot" loading="lazy"></figure>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </section>
      <?php endif; ?>

      <?php $slideItems = []; for ($i = 1; $i <= 6; $i++) { $f = 'slide-' . $i . '.jpg'; if (file_exists(__DIR__ . '/img/' . $f)) { $slideItems[] = ['file' => $f, 'caption' => (string)($s['slide_' . $i . '_caption'] ?? '')]; } } ?><section data-pf-group="photos" class="section center slides"><div class="section-label"><?= h($s['slides_label']) ?></div><h2><?= h($s['slides_heading']) ?></h2><?php if (empty($slideItems)): ?><div class="slides-empty">Field photos will appear here once added from the admin panel.</div><?php else: ?><div class="slides-stage"><div class="slides-frame" id="slidesFrame"><?php foreach ($slideItems as $si => $item): ?>                  <img class="<?= $si === 0 ? 'on' : '' ?>" src="<?= hpl_img_url($item['file']) ?>" alt="<?= h($item['caption'] !== '' ? $item['caption'] : 'Customer field photo') ?>" data-caption="<?= h($item['caption']) ?>" loading="<?= $si === 0 ? 'eager' : 'lazy' ?>"><?php endforeach; ?><p class="slides-cap" id="slidesCap"></p></div><div class="slides-dots" id="slidesDots"></div></div><?php endif; ?></section>

      <?php
        $fieldProofImage1 = file_exists(__DIR__ . '/img/field-proof-1.jpg') ? hpl_img_url('field-proof-1.jpg') : hpl_img_url('slide-1.jpg');
        $fieldProofImage2 = file_exists(__DIR__ . '/img/field-proof-2.jpg') ? hpl_img_url('field-proof-2.jpg') : hpl_img_url('slide-2.jpg');
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
        <div class="section-label">Customer voices</div>
        <h2>See what our customers are saying</h2>
        <div class="proof-social-wrap">
          <article class="proof-social-card">
            <div class="proof-social-top">
              <div class="proof-social-brand"><span class="proof-social-dot"></span><span class="proof-social-app">WhatsApp</span></div>
              <span class="proof-social-app">Today</span>
            </div>
            <div class="proof-social-body">
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
          </article>

          <article class="proof-social-card">
            <div class="proof-social-top">
              <div class="proof-social-brand"><span class="proof-social-dot" style="background:linear-gradient(135deg,#60a5fa,#3b82f6);"></span><span class="proof-social-app">Facebook</span></div>
              <span class="proof-social-app">Post</span>
            </div>
            <div class="proof-social-body">
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
          </article>

          <article class="proof-social-card">
            <div class="proof-social-top">
              <div class="proof-social-brand"><span class="proof-social-dot" style="background:linear-gradient(135deg,#f472b6,#a855f7);"></span><span class="proof-social-app">TikTok</span></div>
              <span class="proof-social-app">Video</span>
            </div>
            <div class="proof-social-body">
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
          </article>
        </div>
      </section>

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
<?php if ($leadSent): ?>
        <div class="lead-success" id="leadSuccess" role="status" tabindex="-1">
          <span class="lead-success-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
          <h3>Thank You!</h3>
          <p>Your enquiry has been received. A member of our HPL team will contact you shortly to discuss your requirements and recommend the right equipment.</p>
          <p class="lead-success-note">We aim to respond within one business day.</p>
          <div class="lead-success-actions">
            <a href="thank-you.php" class="lead-success-btn lead-success-btn-secondary">Go to Thank You Page</a>
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
              <h3>Let's start with your details</h3>
              <p>Please enter your information so our HPL team can contact you.</p>
            </header>

            <div class="field" data-field="lead_name">
              <label for="leadName">Full Name <span class="req">*</span></label>
              <input type="text" id="leadName" name="lead_name" value="<?= h($leadFields['lead_name']) ?>" autocomplete="name" required<?= lead_invalid($leadErrors, 'lead_name') ?>>
              <?= lead_field_error($leadErrors, 'lead_name') ?>
            </div>

            <div class="field" data-field="lead_phone">
              <label for="leadPhone">WhatsApp Number <span class="req">*</span></label>
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
              <label for="leadEmail">Email Address <span class="opt">optional</span></label>
              <input type="email" id="leadEmail" name="lead_email" value="<?= h($leadFields['lead_email']) ?>" placeholder="you@example.com" autocomplete="email"<?= lead_invalid($leadErrors, 'lead_email') ?>>
              <?= lead_field_error($leadErrors, 'lead_email') ?>
            </div>

            <div class="field-row">
              <div class="field" data-field="lead_country">
                <label for="leadCountry">Country <span class="req">*</span></label>
                <select id="leadCountry" name="lead_country" required<?= lead_invalid($leadErrors, 'lead_country') ?>>
                  <option value="">Select country</option>
<?php foreach (hpl_lead_country_names() as $countryName): ?>
                  <option value="<?= h($countryName) ?>"<?= $leadFields['lead_country'] === $countryName ? ' selected' : '' ?>><?= h($countryName) ?></option>
<?php endforeach; ?>
                </select>
                <?= lead_field_error($leadErrors, 'lead_country') ?>
              </div>

              <div class="field" data-field="lead_city">
                <label for="leadCity">City / Town <span class="req">*</span></label>
                <input type="text" id="leadCity" name="lead_city" value="<?= h($leadFields['lead_city']) ?>" autocomplete="address-level2" required<?= lead_invalid($leadErrors, 'lead_city') ?>>
                <?= lead_field_error($leadErrors, 'lead_city') ?>
              </div>
            </div>

            <div class="form-nav"><button class="button" type="button" id="leadNext">Continue</button></div>
          </div>

          <div class="form-step" data-step="2" hidden>
            <header class="form-step-head">
              <h3>Tell us about what you need</h3>
              <p>These answers help us send you the right equipment, not a generic price list.</p>
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
              <textarea id="leadMessage" name="lead_message" rows="4" placeholder="Example: I have a mining area in Kitwe and I am looking for a detector for deep gold."><?= h($leadFields['lead_message']) ?></textarea>
              <?= lead_field_error($leadErrors, 'lead_message') ?>
            </div>

            <label class="advice-check" for="leadAdvice">
              <input type="checkbox" id="leadAdvice" name="lead_needs_advice" value="1"<?= $leadNeedsAdvice ? ' checked' : '' ?>>
              <span>I'm not sure what equipment I need &mdash; please advise me.</span>
            </label>

            <div class="form-nav">
              <button class="btn-ghost" type="button" id="leadBack">Back</button>
              <button class="button" type="submit" id="leadSubmit">Submit &amp; Talk to HPL</button>
            </div>
          </div>
        </form>
<?php endif; ?>
      </section>
    </main>
    <footer role="contentinfo" aria-label="Site Footer"><div class="footer-inner">
      <div class="footer-top">
        <a class="footer-brand logo-chip" href="#top"><img class="footer-logo" src="<?= hpl_img_url('hpllogo.jpeg') ?>" alt="HPL Gold Detectors"></a>
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
    <?php /* Hidden outright when there is no usable number: an anchor with an empty
       href is focusable, looks clickable, and goes nowhere. */ if ($quickContactUrl !== '') { ?>
    <a class="wa-float" href="<?= h($quickContactUrl) ?>" target="_blank" rel="noopener" aria-label="<?= h('Message the HPL team on WhatsApp') ?>">
      <span class="wa-float-icon" aria-hidden="true">✆</span>
      <span class="wa-float-label"><?= h($quickContactLabel) ?></span>
    </a>
<?php } ?>
    <button class="back-to-top" id="backToTop" type="button" aria-label="Back to top" title="Back to top">&uarr;</button>
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

/* ---------- Bunny Stream promo: visitor-started playback ---------- */
      (function () {
        var frame = document.getElementById('promoPlayer');
        if (!frame) return;
        var cta = document.querySelector('.hero-find-cta');
        var player = null;

        function track(type, data) { if (window.hplTrack) window.hplTrack(type, data); }

        function unlockCta() {
          if (!cta || cta.getAttribute('aria-disabled') !== 'true') return;
          cta.textContent = cta.getAttribute('data-ready-label') || "I'M READY TO FIND GOLD";
          cta.setAttribute('aria-disabled', 'false');
        }

        if (cta) {
          cta.addEventListener('click', function (event) {
            if (cta.getAttribute('aria-disabled') === 'true') event.preventDefault();
          });
        }

        function bind() {
          player = new playerjs.Player(frame);

          player.on('ready', function () {
            player.getPaused(function (paused) { if (!paused) unlockCta(); });
          });

          player.on('play', function () {
            unlockCta();
            track('video_play', { video: 'promo' });
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
      var STEP_LABELS = ['Step 1 of 2 \u00b7 Your details', 'Step 2 of 2 \u00b7 Your requirements'];

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

        if (!name || !name.value.trim()) { setError('lead_name', 'Please enter your full name.'); return false; }
        if (!phone || phone.value.replace(/\D/g, '').length < 6) { setError('lead_phone', 'Please enter your WhatsApp number.'); return false; }
        if (email && email.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.value.trim())) {
          setError('lead_email', 'Please check that email address, or leave it blank.');
          return false;
        }
        if (!country || !country.value) { setError('lead_country', 'Please select your country.'); return false; }
        if (!city || !city.value.trim()) { setError('lead_city', 'Please enter your city or town.'); return false; }
        return true;
      }

      function validateStep2() {
        clearMsg();
        for (var i = 0; i < QUESTIONS.length; i++) {
          if (!form.querySelector('input[name="' + QUESTIONS[i] + '"]:checked')) {
            for (var j = 0; j < QUESTIONS.length; j++) clearError(QUESTIONS[j]);
            setError(QUESTIONS[i], 'Please choose an option to continue.');
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
      // The sound hint lives on the section, not on the carousel element, so it
      // is reached through the nearest .proof ancestor.
      var proofSec = ring.closest ? ring.closest('.proof') : null;
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

      function frontIsEmbed() { return !!items[idx].querySelector('.proof-embed iframe'); }

      /* A cross-origin player cannot be paused or muted from the parent page, so
         the only reliable way to stop one after a handover is to unload it. The
         src is also what triggers the player, so nothing is fetched until a
         card actually reaches the front - five players loading at once would
         cost far more than the one the visitor is looking at. */
      function setEmbed(item, on) {
        var f = item.querySelector('.proof-embed iframe');
        if (!f) return;
        if (on) {
          if (!f.hasAttribute('data-on')) {
            f.setAttribute('src', f.getAttribute('data-embed-src') || '');
            f.setAttribute('data-on', '1');
          }
        } else if (f.hasAttribute('data-on')) {
          f.removeAttribute('src');
          f.removeAttribute('data-on');
        }
      }

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
        // An embed is already running: the player was asked to autoplay muted
        // when it loaded. There is no handle on it to pause, so it counts as
        // playing and the play overlay stays out of the way.
        var playing = v ? !v.paused : frontIsEmbed();
        if (wrap) wrap.classList.toggle('playing', !!playing);
        /* The sound button is the site's own control and it drives <video>
           elements. With a player embed in front there is nothing for it to
           reach, so it is hidden rather than left as a control that does
           nothing - the player's own controls take over. */
        var embedFront = frontIsEmbed();
        if (soundBtn) soundBtn.hidden = embedFront;
        if (cfSound) cfSound.hidden = embedFront;
        var hint = proofSec ? proofSec.querySelector('.pf-sound-hint') : null;
        if (hint) hint.hidden = embedFront;
        if (toggleBtn) {
          toggleBtn.innerHTML = playing ? '<span aria-hidden="true">&#10073;&#10073;</span>' : '<span aria-hidden="true">&#9654;</span>';
          toggleBtn.setAttribute('aria-label', playing ? 'Pause videos' : 'Play videos');
        }
        if (playBtn) playBtn.style.display = playing ? 'none' : '';
      }

// Sound stays on by default, but the choice then sticks
        // across every handover instead of being re-muted on each new story.
        var soundOn = true;
        try { soundOn = localStorage.getItem('hpl_story_sound') !== '0'; } catch (e) {}
  
        // Volume is remembered separately so a visitor who dialled it down gets the
        // same level back, and so "sound on" and "audible" stay distinct states: a
        // slider at zero is silence and has to read as muted in the button.
        var vol = 0.8;
        try {
          var savedVol = parseFloat(localStorage.getItem('hpl_story_vol'));
          if (!isNaN(savedVol) && savedVol >= 0 && savedVol <= 1) vol = savedVol;
        } catch (e) {}
  
        var cfAudio = document.getElementById('cfAudio');
        var cfSound = document.getElementById('cfSound');
        var cfVolume = document.getElementById('cfVolume');
        var cfAudioTxt = cfSound ? cfSound.querySelector('.cf-audio-txt') : null;
  
        function audibleNow() { return soundOn && vol > 0; }
  
        function applySound() {
          // A visitor who has not asked for sound never gets it turned on for them
          // when the section scrolls past, because browsers block autoplay with
          // audio anyway - the attempt would fail silently and leave the button
          // out of step with what is playing.
          var audible = soundOn && vol > 0;
          items.forEach(function (el) {
            var v = el.querySelector('video');
            if (v) { v.muted = !audible; v.volume = vol; }
          });
          if (soundBtn) {
            soundBtn.innerHTML = soundOn
              ? '<span aria-hidden="true">&#128266;</span>'
              : '<span aria-hidden="true">&#128263;</span>';
            soundBtn.setAttribute('aria-label', soundOn ? 'Turn sound off' : 'Turn sound on');
            soundBtn.setAttribute('aria-pressed', soundOn ? 'true' : 'false');
          }
          if (cfSound) {
            var ico = cfSound.querySelector('.cf-audio-ico');
            if (ico) ico.innerHTML = audible
              ? '&#128266;'
              : (soundOn ? '&#128263;' : '&#128263;');
            cfSound.setAttribute('aria-pressed', soundOn ? 'true' : 'false');
            cfSound.setAttribute('aria-label', soundOn ? 'Turn sound off' : 'Turn sound on');
            if (cfAudioTxt) cfAudioTxt.textContent = audible ? 'Sound on' : 'Sound off';
          }
          if (cfAudio) cfAudio.setAttribute('data-effective', audible ? 'on' : 'off');
          if (cfVolume) cfVolume.value = String(Math.round(vol * 100));
        }

      function toggleSound() {
        soundOn = !soundOn;
        try { localStorage.setItem('hpl_story_sound', soundOn ? '1' : '0'); } catch (e) {}
        applySound();
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
        setEmbed(items[idx], false);
        idx = ((i % n) + n) % n;
        setEmbed(items[idx], true);
        layout(true);
        syncBtn();
        if (autoplay === true) playFront();
        if (wrap) wrap.classList.add('moved');
      }

      function next() { show(idx + 1, false); }
      function prev() { show(idx - 1, false); }

      items.forEach(function (el, i) {
        el.addEventListener('click', function () {
          if (i === idx) { togglePlay(); } else { show(i, false); }
        });
        el.addEventListener('keydown', function (e) {
          if (e.key !== 'Enter' && e.key !== ' ') return;
          e.preventDefault();
          if (i === idx) { togglePlay(); } else { show(i, false); }
        });
        var v = el.querySelector('video');
        if (v) v.addEventListener('play', syncBtn);
        if (v) v.addEventListener('pause', syncBtn);
        // coverflow only: once a story has played out, do not autoplay next
        if (cf && v) v.addEventListener('ended', function () {
          syncBtn();
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
        // The new top right control drives the same soundOn flag as the existing
        // bottom button rather than keeping its own, so the two can never disagree.
        if (cfSound) {
          cfSound.addEventListener('click', function () {
            if (!soundOn && vol <= 0) vol = 0.8;
            toggleSound();
          });
        }
        if (cfVolume) {
          cfVolume.addEventListener('input', function () {
            vol = Math.max(0, Math.min(1, (parseFloat(cfVolume.value) || 0) / 100));
            try { localStorage.setItem('hpl_story_vol', String(vol)); } catch (e) {}
            applySound();
            // Nudging the slider up is the visitor asking to hear it, so make sure
            // something is actually playing rather than only changing the level.
            var fv = frontVideo();
            // volume change does not autoplay
          });
        }
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
        document.addEventListener('visibilitychange', function () {
          if (document.hidden) pauseAll();
        });
      }

      layout(false);
      /* The scroll observer below owns when an embed loads: nothing is fetched
         while the section is off screen, and the observer brings the front one
         back when it returns. Exposed here because the two pieces of script are
         otherwise independent. */
      window.__hplProofFront = function () { setEmbed(items[idx], true); };
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
      if (!sec) { return; }
      if (!('IntersectionObserver' in window)) {
        // Nothing to gate on without the observer, so show the player at once
        // rather than leaving the card permanently blank.
        if (typeof window.__hplProofFront === 'function') { window.__hplProofFront(); }
        return;
      }
      new IntersectionObserver(function (entries) {
        var vis = entries[0].isIntersecting;
        window.__hplInView = vis;
        if (!vis) {
          sec.querySelectorAll('video').forEach(function (v) { if (v && !v.paused) { v.pause(); } });
          // A player embed has no pause handle from here, so scrolling away
          // unloads it rather than leaving it talking over the page behind.
          sec.querySelectorAll('.proof-embed iframe[data-on]').forEach(function (f) {
            f.removeAttribute('src');
            f.removeAttribute('data-on');
          });
        } else if (typeof window.__hplProofFront === 'function') {
          // Coming back into view has to restore the player that was unloaded
          // on the way out, otherwise the front card stays blank.
          window.__hplProofFront();
        }
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
      var proofSection = stage.closest('.proof');
      var idx = 0;
      var reachedLast = false;

      function proofNearViewport() {
        if (!proofSection) return false;
        var rect = proofSection.getBoundingClientRect();
        return rect.bottom > -300 && rect.top < window.innerHeight + 300;
      }

      function dismissCue() {
        if (wrap) wrap.classList.add('cue-dismissed');
      }

      function updateCueJourney() {
        if (idx === slides.length - 1) reachedLast = true;
        if (reachedLast && idx === 0) dismissCue();
      }

      function setSlideEmbed(slide, on) {
        var iframe = slide && slide.querySelector('.proof-embed iframe');
        if (!iframe) return;
        if (on && !iframe.hasAttribute('data-on')) {
          iframe.setAttribute('src', iframe.getAttribute('data-embed-src') || '');
          iframe.setAttribute('data-on', '1');
        } else if (!on && iframe.hasAttribute('data-on')) {
          iframe.removeAttribute('src');
          iframe.removeAttribute('data-on');
        }
      }

      function bindProofEmbed(iframe) {
        var focused = false;
        var embedUrl = iframe.getAttribute('data-embed-src') || iframe.src;
        if (!/\.mediadelivery\.net(?:\/|$)/i.test(embedUrl)) return;

        iframe.addEventListener('focus', function () { focused = true; });
        function bindPlayer() {
          if (!window.playerjs || !iframe.hasAttribute('src') || iframe.dataset.cuePlayerBound) return;
          try {
            var player = new window.playerjs.Player(iframe);
            player.on('play', function () {
              if (focused) dismissCue();
            });
            iframe.dataset.cuePlayerBound = '1';
          } catch (e) {}
        }

        iframe.addEventListener('load', bindPlayer);
        var playerScript = document.querySelector('script[src*="playerjs-latest.min.js"]');
        if (window.playerjs) bindPlayer();
        else if (playerScript) playerScript.addEventListener('load', bindPlayer, { once: true });
      }

      slides.forEach(function (s) {
        s.style.aspectRatio = '16 / 9';
        s.style.height = 'min(42vw, 420px)';
        s.style.maxHeight = '420px';
        s.style.width = 'min(68vw, 700px)';
        var embed = s.querySelector('.proof-embed');
        if (embed) {
          embed.style.aspectRatio = '16 / 9';
          embed.style.height = '100%';
          embed.style.width = '100%';
        }
        var iframe = s.querySelector('.proof-embed iframe');
        if (iframe) {
          iframe.style.height = '100%';
          iframe.style.width = '100%';
          iframe.style.position = 'absolute';
          iframe.style.inset = '0';
        }
        var video = s.querySelector('video');
        if (video) video.addEventListener('play', dismissCue);
        var embedFrame = s.querySelector('.proof-embed iframe');
        if (embedFrame) bindProofEmbed(embedFrame);
      });

      var dots = slides.map(function (s, i) {
        var d = document.createElement('button');
        d.type = 'button';
        d.setAttribute('aria-label', 'Go to video ' + (i + 1));
        d.addEventListener('click', function () { show(i); });
        if (dotsWrap) dotsWrap.appendChild(d);
        return d;
      });

      function syncDots() {
        dots.forEach(function (d, n) { d.classList.toggle('on', n === idx); });
      }

      function scrollToIndex(i) {
        i = Math.max(0, Math.min(slides.length - 1, i));
        var target = slides[i];
        if (!target) return;
        if (i !== idx) setSlideEmbed(slides[idx], false);
        stage.scrollTo({
          left: target.offsetLeft - (stage.clientWidth - target.offsetWidth) / 2,
          behavior: 'smooth'
        });
        idx = i;
        syncDots();
        updateCueJourney();
        if (proofNearViewport()) setSlideEmbed(slides[idx], true);
      }

      window.__hplProofFront = function () { setSlideEmbed(slides[idx], true); };
      window.__hplProofFront();

      function show(i) {
        if (i === idx) return;
        var prev = slides[idx];
        if (prev) {
          prev.classList.remove('on');
          var pv = prev.querySelector('video');
          if (pv) { pv.pause(); pv.currentTime = 0; }
        }
        scrollToIndex(i);
        var cur = slides[idx];
        if (cur) cur.classList.add('on');
      }

      function next() { show((idx + 1) % slides.length); }
      function prev() { show((idx - 1 + slides.length) % slides.length); }

      slides.forEach(function (s, i) {
        s.classList.toggle('on', i === 0);
      });
      dots[0].classList.add('on');

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

      stage.addEventListener('scroll', function () {
        var centre = stage.scrollLeft + stage.clientWidth / 2;
        var nearest = 0;
        var nearestDistance = Number.POSITIVE_INFINITY;
        slides.forEach(function (s, i) {
          var mid = s.offsetLeft + s.offsetWidth / 2;
          var distance = Math.abs(mid - centre);
          if (distance < nearestDistance) {
            nearestDistance = distance;
            nearest = i;
          }
        });
        if (nearest !== idx) {
          idx = nearest;
          syncDots();
          updateCueJourney();
        }
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

    /* ---------- sound affordance ----------
       The testimonial area already has a working sound toggle with an
       aria-pressed state. All this does is make the option discoverable while
       sound is still off, then get out of the way.

       It deliberately never calls play(), mute() or unmute(): browsers only
       allow audible autoplay after a real interaction, and forcing or
       re-prompting is exactly what visitors dislike. */
    (function () {
      'use strict';
      var proof = document.querySelector('[data-pf-group="videos"]');
      if (!proof) return;
      /* The toggle is named differently per layout; all of them carry the same
         aria-pressed contract. */
      var btn = proof.querySelector('#cfSound, #ringSound');
      if (!btn) return;
      if (proof.querySelector('.pf-sound-hint')) return;

      var hint = document.createElement('p');
      hint.className = 'pf-sound-hint';
      hint.innerHTML = '<span aria-hidden="true">&#128266;</span> Tap to hear the story';

      var anchor = proof.querySelector('.proof-caption') || proof.querySelector('.ring-nav') || proof.querySelector('.ring-wrap');
      if (anchor && anchor.parentNode) { anchor.parentNode.insertBefore(hint, anchor); }
      else { proof.appendChild(hint); }

      function sync() {
        var on = btn.getAttribute('aria-pressed') === 'true';
        hint.hidden = on;
      }
      btn.addEventListener('click', function () { window.setTimeout(sync, 0); });
      sync();
    })();
  </script>
</body>
</html>