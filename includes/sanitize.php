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
function nl2br_skip_li_lines($content) {
    // Split the content into individual lines
    $lines = explode("\n", $content);

    // Process each line
    foreach ($lines as &$line) {
        // Trim whitespace for accurate checks
        $trimmedLine = trim($line);

        // Check if the line meets any of the skip conditions
        if ((preg_match('/^<li>/', $trimmedLine) && preg_match('/<\/li>$/', $trimmedLine)) || // Starts AND ends with <li>...</li>
            preg_match('/^<ul>/', $trimmedLine) ||                                            // Starts with <ul>
            preg_match('/<\/ul>$/', $trimmedLine)                                            // Ends with </ul>
        ) {
            // Skip nl2br for this line
            continue;
        }

        // Apply nl2br to all other lines
        $line = nl2br($line);
    }

    // Reassemble the content
    return implode("\n", $lines);
}

