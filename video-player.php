<?php
/**
 * Reusable custom video player.
 *
 * One component serves every video on the site, so a new promo is a call rather
 * than a second implementation:
 *
 *     hpl_video_player(['src' => 'uploads/promo.mp4', 'poster' => 'uploads/promo.jpg']);
 *
 * hpl_video_player_assets() prints the stylesheet and script once, however many
 * players the page renders.
 */

if (!function_exists('hpl_media_url')) {
    /**
     * Fallback for config.php builds that predate the media helpers.
     *
     * config.php holds the database credentials and is deliberately never
     * uploaded, so the copy already on the server can be older than this file.
     * index.php requires config.php first, so when the server does define these
     * its versions win and these are simply skipped. Without the guard a
     * missing helper would be a fatal error taking the whole page down.
     */
    function hpl_media_url(?string $value): string
    {
        $v = trim((string)$value);
        if ($v === '' || $v === 'YOUR_DRIVE_FILE_ID') {
            return '';
        }

        // already a direct URL for some other host (CDN, S3, local upload, ...)
        if (preg_match('~^https?://(?!drive\.google|driveusercontent|drive\.googleusercontent)~i', $v)) {
            return $v;
        }

        // same-site relative path, e.g. uploads/promo.mp4
        if (!preg_match('~^[a-z][a-z0-9+.-]*:~i', $v)
            && strpos($v, '..') === false
            && preg_match('~^[A-Za-z0-9._/-]+$~', $v)
            && preg_match('~\.(mp4|webm|m4v|mov|ogv|jpe?g|png|gif|webp)$~i', $v)) {
            return $v;
        }

        if (preg_match('~/d/([A-Za-z0-9_-]{10,})~', $v, $m)
            || preg_match('~[?&]id=([A-Za-z0-9_-]{10,})~', $v, $m)) {
            $id = $m[1];
        } elseif (preg_match('~^[A-Za-z0-9_-]{25,}$~', $v)) {
            $id = $v;
        } else {
            return '';
        }

        return 'https://drive.usercontent.google.com/download?id=' . rawurlencode($id) . '&export=download&confirm=t';
    }
}

if (!function_exists('hpl_media_type')) {
    function hpl_media_type(string $url, string $default = 'video/mp4'): string
    {
        $ext = strtolower((string)pathinfo((string)(parse_url($url, PHP_URL_PATH) ?? ''), PATHINFO_EXTENSION));

        switch ($ext) {
            case 'mp4':
            case 'm4v':
                return 'video/mp4';
            case 'webm':
                return 'video/webm';
            case 'ogv':
                return 'video/ogg';
            case 'mov':
                return 'video/quicktime';
            default:
                return $default;
        }
    }
}

if (!function_exists('hpl_video_player')) {
    /**
     * Render a video player.
     *
     * @param array $o {
     *   @type string $src       Video URL. Anything hpl_media_url() understands.
     *   @type string $poster    Poster image URL.
     *   @type string $title     Accessible name for the player.
     *   @type string $captions  URL of a WebVTT file, if there is one.
     *   @type string $id        id for the <video>, so existing code can find it.
     *   @type string $kicker    Small label shown over the top-left corner.
     *   @type bool   $loop      Restart at the end. Default true for a promo.
     *   @type bool   $autoplay  Try to start on its own. Default true.
     *   @type bool   $startMuted Start muted. Default true - see note in the JS.
     * }
     */
    function hpl_video_player(array $o = []): string
    {
        $src     = trim((string)($o['src'] ?? ''));
        $poster  = trim((string)($o['poster'] ?? ''));
        $title   = trim((string)($o['title'] ?? 'Video'));
        $caps    = trim((string)($o['captions'] ?? ''));
        $id      = trim((string)($o['id'] ?? 'pv' . substr(md5($src . $title), 0, 6)));
        $kicker  = trim((string)($o['kicker'] ?? ''));
        $loop    = !array_key_exists('loop', $o) || (bool)$o['loop'];
        $autoplay = !array_key_exists('autoplay', $o) || (bool)$o['autoplay'];
        // The markup keeps muted+autoplay as a floor for when JavaScript never
        // runs. The script tries audible playback first and steps down from there.
        $startMuted = !array_key_exists('startMuted', $o) || (bool)$o['startMuted'];

        $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $attrs = ' data-hpl-player'
            . ' data-title="' . $e($title) . '"'
            . ' data-loop="' . ($loop ? '1' : '0') . '"'
            . ' data-autoplay="' . ($autoplay ? '1' : '0') . '"';

        $h = '<div class="hpl-player"' . $attrs . '>';

        $h .= '<video class="hpl-player-video" id="' . $e($id) . '"'
            . ' playsinline preload="none"'
            . ($poster !== '' ? ' poster="' . $e($poster) . '"' : '')
            . ($loop ? ' loop' : '')
            . ($autoplay ? ' autoplay' : '')
            . ($startMuted ? ' muted' : '')
            . ' crossorigin="anonymous">';
        if ($src !== '') {
            $h .= '<source src="' . $e($src) . '" type="' . $e(hpl_media_type($src)) . '">';
        }
        if ($caps !== '') {
            $h .= '<track kind="captions" src="' . $e($caps) . '" srclang="en" label="English" default>';
        }
        // Kept: existing copy already links out when a video cannot play.
        $h .= '<div class="video-fallback">Your browser can\'t play this video.'
            . ($src !== '' ? ' <a href="' . $e($src) . '" style="text-decoration:underline">Open the video</a>.' : '')
            . '</div></video>';

        if ($kicker !== '') {
            $h .= '<span class="panel-kicker">' . $e($kicker) . '</span>';
        }

        // Centre play, shown whenever playback is not running.
        $h .= '<button class="hpl-center-play" type="button" aria-label="Play video">'
            . '<span aria-hidden="true"></span></button>';

        // Shown only when the browser forced muted autoplay.
        $h .= '<button class="hpl-unmute" type="button" hidden>'
            . 'Your video is playing. Click to unmute</button>';

        // Live caption surface, above the bar.
        $h .= '<div class="hpl-cc" aria-live="polite"></div>';

        $h .= '<div class="hpl-controls">'
            . '<div class="hpl-ctrls-left">'
            . '<button class="hpl-btn hpl-playpause" type="button" aria-label="Play">'
            . '<span class="hpl-ico hpl-ico-play" aria-hidden="true"></span></button>'
            . '<button class="hpl-btn hpl-rew" type="button" aria-label="Rewind 10 seconds">'
            . '<span class="hpl-ico hpl-ico-rew" aria-hidden="true"></span></button>'
            . '<button class="hpl-btn hpl-vol" type="button" aria-label="Mute" aria-pressed="false">'
            . '<span class="hpl-ico hpl-ico-vol" aria-hidden="true"></span></button>'
            . '<button class="hpl-btn hpl-vol-slider-btn" type="button" aria-label="Volume" aria-expanded="false">'
            . '<span class="hpl-ico hpl-ico-volwave" aria-hidden="true"></span></button>'
            . '<div class="hpl-vol-pop" hidden><label class="hpl-sr" for="' . $e($id) . '-vol">Volume</label>'
            . '<input class="hpl-vol-range" type="range" id="' . $e($id) . '-vol" min="0" max="100" step="5" value="100"></div>'
            . '</div>'
            . '<div class="hpl-ctrls-mid">'
            . '<span class="hpl-time hpl-time-cur">0:00</span>'
            . '<div class="hpl-progress" role="slider" tabindex="0" aria-label="Seek" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">'
            . '<div class="hpl-progress-track"><div class="hpl-progress-buffer"></div><div class="hpl-progress-played"></div></div>'
            . '<div class="hpl-progress-knob"></div></div>'
            . '<span class="hpl-time hpl-time-dur">0:00</span>'
            . '</div>'
            . '<div class="hpl-ctrls-right">'
            . ($caps !== '' ? '<button class="hpl-btn hpl-ccbtn" type="button" aria-label="Captions" aria-pressed="false"><span class="hpl-ico hpl-ico-cc" aria-hidden="true">CC</span></button>' : '')
            . '<button class="hpl-btn hpl-settings-btn" type="button" aria-label="Settings" aria-expanded="false">'
            . '<span class="hpl-ico hpl-ico-gear" aria-hidden="true"></span></button>'
            . '<div class="hpl-menu" hidden role="menu" aria-label="Playback settings"><div class="hpl-menu-title">Speed</div>'
            . '<div class="hpl-speeds" role="group" aria-label="Playback speed">'
            . '<button type="button" data-rate="0.5" role="menuitemradio" aria-checked="false">0.5x</button>'
            . '<button type="button" data-rate="0.75" role="menuitemradio" aria-checked="false">0.75x</button>'
            . '<button type="button" data-rate="1" role="menuitemradio" aria-checked="true">1x</button>'
            . '<button type="button" data-rate="1.25" role="menuitemradio" aria-checked="false">1.25x</button>'
            . '<button type="button" data-rate="1.5" role="menuitemradio" aria-checked="false">1.5x</button>'
            . '<button type="button" data-rate="2" role="menuitemradio" aria-checked="false">2x</button>'
            . '</div></div>'
            . '</div>'
            . '</div>';

        $h .= '</div>';

        return $h;
    }
}

if (!function_exists('hpl_video_player_assets')) {
    /**
     * Print the player stylesheet and script exactly once per page.
     */
    function hpl_video_player_assets(): string
    {
        static $done = false;
        if ($done) {
            return '';
        }
        $done = true;
        return '<link rel="stylesheet" href="video-player.css">'
            . '<script src="video-player.js" defer></script>';
    }
}
