<?php
require_once 'session.php';

// Prefer Composer vendor path; fallback via env; last resort local path
$purifierVendor = __DIR__ . '/../../vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
if (file_exists($purifierVendor)) {
    require_once $purifierVendor;
} else {
    $fallback = getenv('HTMLPURIFIER_PATH');
    if ($fallback && file_exists($fallback)) {
        require_once $fallback;
    } else {
        // Adjust for your environment if needed (Windows path example)
        require_once 'C:/htmlpurifier/library/HTMLPurifier.auto.php';
    }
}

require_once 'HTMLPurifier_Filter_PageBreak.php';
require_once 'HTMLPurifier_Filter_FontToSpan.php';

// CSRF token generation (used elsewhere in app too)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/**
 * Base config for HTMLPurifier that:
 * - Supports Summernote features (formatting, colors, fonts, sizes, alignment, lists)
 * - Supports tables, links with target/rel
 * - Supports images by URL (no data: URIs)
 * - Allows hr[class] to carry pagebreak markers while editing
 * - Preserves <!-- pagebreak --> via custom filter
 * - Converts legacy <font> to <span style="..."> via custom filter
 */
function purifier_config_base(): HTMLPurifier_Config {
    $config = HTMLPurifier_Config::createDefault();

    // Optional performance cache
    // $config->set('Cache.SerializerPath', __DIR__ . '/cache');

    $config->set('HTML.Allowed',
        // Blocks, headings, text and emphasis
        'p[style|class],div[style|class|id],span[style|class],br,hr[class],' .
        'h1[style|class|id],h2[style|class|id],h3[style|class|id],h4[style|class|id],h5[style|class|id],h6[style|class|id],' .
        'strong,b,em,i,u,s,del,ins,mark,sub,sup,blockquote,code,pre[class|style],' .
        // Lists
        'ul[style|class],ol[style|class],li[style|class],' .
        // Links
        'a[href|title|target|rel|class|style],' .
        // Images by URL
        'img[src|alt|width|height|style|class|srcset|sizes],' .
        // Tables
        'table[style|class],thead,tbody,tfoot,caption[style|class],' .
        'tr,th[style|class|colspan|rowspan|scope|align|valign],td[style|class|colspan|rowspan|align|valign],' .
        'colgroup,col[span|style|class],' .
        // Media you already use elsewhere
        'audio[controls|preload],source[src|type]'
    );

    // Safe CSS properties frequently used by the editor
    $config->set('CSS.AllowedProperties', [
        // text/typography
        'color','background-color','text-decoration','font-weight','font-style','font-size','font-family',
        'text-align','line-height','letter-spacing','text-indent','white-space','vertical-align',
        // box model
        'margin','margin-left','margin-right','margin-top','margin-bottom',
        'padding','padding-left','padding-right','padding-top','padding-bottom',
        'border','border-top','border-right','border-bottom','border-left','border-color','border-style','border-width','border-radius',
        // layout/sizing
        'display','float','width','height','max-width','min-width',
        // lists and tables
        'list-style-type','border-collapse','border-spacing','table-layout','caption-side'
    ]);

    // Links and targets
    $config->set('Attr.AllowedFrameTargets', ['_blank','_self','_parent','_top']);
    $config->set('Attr.AllowedRel', ['noopener','noreferrer','nofollow']);

    // Allowed URL schemes (disallow data: to avoid base64 payloads in content)
    $config->set('URI.AllowedSchemes', [
        'http'   => true,
        'https'  => true,
        'mailto' => true,
        'ftp'    => true,
        'tel'    => true,
        // 'data' => false (not listed => disallowed)
    ]);

    // Keep author structure; don’t auto-wrap
    $config->set('AutoFormat.AutoParagraph', false);
    $config->set('AutoFormat.RemoveEmpty', false);
    $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', false);

    // Preserve <!-- pagebreak --> and convert legacy <font> to <span>
    $config->set('Filter.Custom', [
        new HTMLPurifier_Filter_PageBreak(),
        new HTMLPurifier_Filter_FontToSpan()
    ]);

    return $config;
}

function sanitize_html(string $content): string {
    $config = purifier_config_base();
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($content);
}

function sanitize_html2(string $content): string {
    $config = purifier_config_base();
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($content);
}

// nl2br while skipping list structures
function nl2br_skip($content) {
    $lines = explode("\n", $content);
    foreach ($lines as &$line) {
        $t = trim($line);
        if (
            (preg_match('/^<li>/', $t) && preg_match('/<\/li>$/', $t)) ||
            preg_match('/^<ul>/', $t) ||
            preg_match('/<\/ul>$/', $t) ||
            preg_match('/^<ol>/', $t) ||
            preg_match('/<\/ol>$/', $t)
        ) {
            continue;
        }
        $line = nl2br($line);
    }
    return implode("\n", $lines);
}