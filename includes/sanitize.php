<?php
require_once 'session.php';
require_once 'C:/htmlpurifier/library/HTMLPurifier.auto.php'; // Adjust the path based on where you extracted HTMLPurifier
require_once '../htmlpurifier/HTMLPurifier_Filter_PageBreak.php';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function sanitize_html($content) {
    $config = HTMLPurifier_Config::createDefault();
    
    // Allow specific tags and attributes
    $config->set('HTML.Allowed', 'p,b,a[href],i,em,strong,ul,ol,li,br,span[style],div[style],img[src|alt|width|height]');
    
    // Add the custom filter for pagebreak
    $config->set('Filter.Custom', array(new HTMLPurifier_Filter_PageBreak()));
    
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($content);
}

