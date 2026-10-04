<?php
/**
 * Shared runtime bootstrap: response compression and photo de-duplication.
 *
 * This lives in its own tracked file rather than in config.php for a specific
 * reason. config.php holds the database credentials, so it is gitignored and is
 * deliberately excluded from the deployment archive. Anything that only the
 * local config.php defined would therefore be missing on the server, which is
 * the same trap already noted in admin/index.php. Shipping it as a normal
 * tracked file means it deploys with everything else.
 *
 * It must be required before the page produces any output, since both the
 * output buffer and the header checks below depend on that.
 */

/* ------------------------------------------------------------------ *
 * Response compression
 * ------------------------------------------------------------------ *
 *
 * The homepage is a single document with all of its CSS and JS inline, which
 * makes it roughly 198KB before compression and about 44KB after. It is the
 * heaviest first-party thing the site serves.
 *
 * Only one mechanism may ever run. If Apache has mod_deflate loaded, the
 * .htaccess rules compress the response and this must stay out of the way,
 * because compressing an already-compressed body produces output the browser
 * cannot decode. Where mod_deflate is present but mod_filter is not - which is
 * the case on the local XAMPP build - the .htaccess rules cannot apply at all,
 * since AddOutputFilterByType is a mod_filter directive, so the compression is
 * performed here instead.
 *
 * .user.ini is not an option: it is only read when PHP runs as CGI or FastCGI
 * and is ignored outright under mod_php.
 */
if (PHP_SAPI !== 'cli'
    && !headers_sent()
    && stripos((string)($_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''), 'gzip') !== false) {
    $apacheModules = function_exists('apache_get_modules') ? apache_get_modules() : [];
    if (!in_array('deflate_module', $apacheModules, true) && function_exists('ob_gzhandler')) {
        ob_start('ob_gzhandler');
    }
}

/* ------------------------------------------------------------------ *
 * Duplicate photograph collapse
 * ------------------------------------------------------------------ *
 *
 * The photo set is eighteen filenames holding only five unique images. The
 * proof grid, the testimonial carousel, the benefit cards and the thank-you page
 * each request their own filename, so the browser downloaded the same
 * photograph up to six times over - about 2MB of images for one page view, most
 * of it repeats of a file already on screen.
 *
 * The filenames are stored in the database and edited through the admin media
 * map, so renaming the files would have required a production database change
 * and would drift again the next time anybody saved the settings. Collapsing at
 * URL level needs no database edit and no change to any existing setting.
 *
 * Each entry records the byte size the duplicate had when this map was built. If
 * a photo is later replaced through the admin panel the size stops matching and
 * the real file is served again, so the map releases itself rather than pinning
 * the wrong image in place.
 *
 * The canonical file for every group is one that git tracks. img/slide-*.jpg is
 * gitignored, so pointing the map at those would leave the images the site
 * actually serves absent from the repository.
 */
if (!function_exists('hpl_photo_aliases')) {
    function hpl_photo_aliases(): array
    {
        return [
            'slide-1.jpg'   => ['file' => 'proof-1.jpg', 'size' => 115477],
            'ty-1.jpg'      => ['file' => 'proof-1.jpg', 'size' => 115477],
            'slide-2.jpg'   => ['file' => 'proof-2.jpg', 'size' => 107336],
            'ty-2.jpg'      => ['file' => 'proof-2.jpg', 'size' => 107336],
            'slide-3.jpg'   => ['file' => 'proof-3.jpg', 'size' => 142016],
            'ty-3.jpg'      => ['file' => 'proof-3.jpg', 'size' => 142016],
            'slide-4.jpg'   => ['file' => 'benefit-1.jpg', 'size' => 135040],
            'ty-4.jpg'      => ['file' => 'benefit-1.jpg', 'size' => 135040],
            'slide-5.jpg'   => ['file' => 'benefit-2.jpg', 'size' => 168689],
            'slide-6.jpg'   => ['file' => 'benefit-2.jpg', 'size' => 168689],
            'benefit-3.jpg' => ['file' => 'benefit-2.jpg', 'size' => 168689],
            'ty-5.jpg'      => ['file' => 'benefit-2.jpg', 'size' => 168689],
            'ty-6.jpg'      => ['file' => 'benefit-2.jpg', 'size' => 168689],
        ];
    }
}

if (!function_exists('hpl_canonical_photo')) {
    function hpl_canonical_photo(string $file): string
    {
        $alias = hpl_photo_aliases()[$file] ?? null;
        if ($alias === null) {
            return $file;
        }

        $requested = __DIR__ . '/img/' . $file;
        $canonical = __DIR__ . '/img/' . $alias['file'];
        if (!is_file($requested) || !is_file($canonical)) {
            return $file;
        }
        if (filesize($requested) !== $alias['size']) {
            return $file;
        }

return $alias['file'];
      }
  }

/**
 * Selectable font families.
 *
 * A fixed catalog rather than free text, because the front page builds its
 * Google Fonts request from these entries: keeping the list closed means the
 * URL can never carry anything unexpected and a bad value falls back rather
 * than silently dropping the webfont. Weights are the ones actually referenced
 * by the stylesheet, so the request stays small.
 *
 * Lives here rather than in config.php because the admin panel needs the same
 * list to build its dropdowns and config.php is excluded from deployment.
 *
 * @return array<string, array{weights: string, stack: string}>
 */
if (!function_exists('hpl_font_catalog')) {
    function hpl_font_catalog(): array
    {
        return [
            'Anton'            => ['weights' => '400',                 'stack' => "'Anton',sans-serif"],
            'Bebas Neue'       => ['weights' => '400',                 'stack' => "'Bebas Neue',sans-serif"],
            'Archivo Black'    => ['weights' => '400',                 'stack' => "'Archivo Black',sans-serif"],
            'Oswald'           => ['weights' => '400;500;600;700',     'stack' => "'Oswald',sans-serif"],
            'Barlow Condensed' => ['weights' => '400;500;600;700',     'stack' => "'Barlow Condensed',sans-serif"],
            'Montserrat'       => ['weights' => '400;500;600;700;800', 'stack' => "'Montserrat',sans-serif"],
            'Poppins'          => ['weights' => '400;500;600;700;800', 'stack' => "'Poppins',sans-serif"],
            'Raleway'          => ['weights' => '400;500;600;700;800', 'stack' => "'Raleway',sans-serif"],
            'Playfair Display' => ['weights' => '400;500;600;700;800', 'stack' => "'Playfair Display',serif"],
            'Lobster'          => ['weights' => '400',                 'stack' => "'Lobster',cursive"],
            'Manrope'          => ['weights' => '400;500;600;700;800', 'stack' => "'Manrope','Plus Jakarta Sans',sans-serif"],
            'Inter'            => ['weights' => '400;500;600;700;800', 'stack' => "'Inter',sans-serif"],
            'DM Sans'          => ['weights' => '400;500;700',         'stack' => "'DM Sans',sans-serif"],
            'Work Sans'        => ['weights' => '400;500;600;700',     'stack' => "'Work Sans',sans-serif"],
            'Source Sans 3'    => ['weights' => '400;500;600;700',     'stack' => "'Source Sans 3',sans-serif"],
            'Nunito'           => ['weights' => '400;600;700;800',     'stack' => "'Nunito',sans-serif"],
            'Karla'            => ['weights' => '400;500;700;800',     'stack' => "'Karla',sans-serif"],
            'Rubik'            => ['weights' => '400;500;600;700',     'stack' => "'Rubik',sans-serif"],
            'Lato'             => ['weights' => '400;700',             'stack' => "'Lato',sans-serif"],
        ];
    }
}