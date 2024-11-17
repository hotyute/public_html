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
    // Use DOMDocument for parsing
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // XPath to select all text nodes NOT inside <li> tags
    $xpath = new DOMXPath($dom);

    // Select all text nodes outside of <li>
    foreach ($xpath->query('//text()[not(ancestor::li)]') as $textNode) {
        // Apply nl2br to these text nodes only
        $newContent = nl2br($textNode->nodeValue);
        $newFragment = $dom->createDocumentFragment();
        $newFragment->appendXML($newContent);
        $textNode->parentNode->replaceChild($newFragment, $textNode);
    }

    // Return the modified HTML
    return $dom->saveHTML();
}

