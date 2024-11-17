<?php
require_once 'session.php';
require_once 'C:/htmlpurifier/library/HTMLPurifier.auto.php'; // Adjust the path based on where you extracted HTMLPurifier
require_once 'HTMLPurifier_Filter_PageBreak.php';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function sanitize_html($content) {
    $config = HTMLPurifier_Config::createDefault();
    
    // Allow specific tags and attributes
    $config->set('HTML.Allowed', 'p,b,a[href],i,em,strong,ul,ol,li,br,h1,h2,h3,span[style],div[style],img[src|alt|width|height]');
    
    // Add the custom filter for pagebreak
    $config->set('Filter.Custom', array(new HTMLPurifier_Filter_PageBreak()));
    
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($content);
}

function sanitize_html2($content) {
    $config = HTMLPurifier_Config::createDefault();
    
    // Allow specific tags and attributes
    $config->set('HTML.Allowed', 'p,b,a[href],i,em,strong,ul,ol,li,br,h1,h2,h3,span[style],div[style|class],img[src|alt|width|height|style|class]');
    
    // Add the custom filter for pagebreak
    $config->set('Filter.Custom', array(new HTMLPurifier_Filter_PageBreak()));
    
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($content);
}

// Function to apply nl2br while skipping <li> elements
function nl2br_skip($content) {
    // Only apply nl2br to content that is not inside <li>...</li> tags
    return preg_replace_callback(
        // Match everything except lines enclosed in <li>...</li>
        '/(?!<li>)(.*?)(?<!<\/li>)/s',
        function ($matches) {
            return nl2br($matches[0]); // Apply nl2br to the matched content
        },
        $content
    );
}

