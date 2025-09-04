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

function sanitize_html($content) {
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.Allowed', 'p[style],b,a[href],i,em,strong,ul,ol,li,br,h1,h2,h3,span[style],div[style],img[src|alt|width|height]');
    $config->set('Filter.Custom', array(new HTMLPurifier_Filter_PageBreak()));
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($content);
}

function sanitize_html2($content) {
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.Allowed', 'p[style],b,a[href],i,em,strong,ul,ol,li,br,h1,h2,h3,span[style],div[style|class],img[src|alt|width|height|style|class],audio[controls],source[src]');
    $config->set('Filter.Custom', array(new HTMLPurifier_Filter_PageBreak()));
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