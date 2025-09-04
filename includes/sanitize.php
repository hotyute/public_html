<?php
require_once 'session.php';

// Prefer Composer vendor path; fallback to env variable; last resort: hardcoded path
$purifierVendor = __DIR__ . '/../../vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
if (file_exists($purifierVendor)) {
    require_once $purifierVendor;
} else {
    $fallback = getenv('HTMLPURIFIER_PATH');
    if ($fallback && file_exists($fallback)) {
        require_once $fallback;
    } else {
        // Adjust as needed for your environment
        require_once 'C:/htmlpurifier/library/HTMLPurifier.auto.php';
    }
}

require_once 'HTMLPurifier_Filter_PageBreak.php';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function purifier_config_base() {
    $config = HTMLPurifier_Config::createDefault();

    // Keep commonly used formatting and attributes for a Word-like editor
    $config->set('HTML.Allowed',
        'p[style|class],b,strong,i,em,u,mark,sub,sup,blockquote,code,pre,' .
        'a[href|target|rel],br,hr[class],' .
        'ul[style|class],ol[style|class],li[style|class],' .
        'h1[style|class],h2[style|class],h3[style|class],h4[style|class],h5[style|class],h6[style|class],' .
        'span[style|class],div[style|class],' .
        'img[src|alt|width|height|style|class],' .
        'table[style|class],thead,tbody,tr,td[style|colspan|rowspan|class],th[style|colspan|rowspan|class],' .
        'audio[controls],source[src]'
    );

    // Allow safe CSS properties used by the editor
    $config->set('CSS.AllowedProperties', [
        'color','background-color','text-decoration','font-weight','font-style','font-size','font-family',
        'text-align','line-height','margin','margin-left','margin-right','margin-top','margin-bottom',
        'padding','padding-left','padding-right','padding-top','padding-bottom',
        'border','border-top','border-right','border-bottom','border-left','border-radius',
        'display','float','width','height','max-width'
    ]);

    // Allow opening links in new tab safely
    $config->set('Attr.AllowedFrameTargets', ['_blank']);
    $config->set('Attr.AllowedRel', ['noopener','noreferrer','nofollow']);

    // Keep the custom pagebreak filter
    $config->set('Filter.Custom', array(new HTMLPurifier_Filter_PageBreak()));

    return $config;
}

function sanitize_html($content) {
    $config = purifier_config_base();
    // For titles / short text, simpler output typically
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($content);
}

function sanitize_html2($content) {
    $config = purifier_config_base();
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($content);
}

// nl2br while skipping list tags
function nl2br_skip($content) {
    $lines = explode("\n", $content);
    foreach ($lines as &$line) {
        $trimmedLine = trim($line);
        if (
            (preg_match('/^<li>/', $trimmedLine) && preg_match('/<\/li>$/', $trimmedLine)) ||
            preg_match('/^<ul>/', $trimmedLine) ||
            preg_match('/<\/ul>$/', $trimmedLine)
        ) {
            continue;
        }
        $line = nl2br($line);
    }
    return implode("\n", $lines);
}