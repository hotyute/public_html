<?php
require_once 'C:/htmlpurifier/library/HTMLPurifier.auto.php'; // Adjust the path based on where you extracted HTMLPurifier

function sanitize_html($content) {
    $config = HTMLPurifier_Config::createDefault();
    // Configure HTMLPurifier to allow certain tags and attributes
    $config->set('HTML.Allowed', 'p,b,a[href],i,em,strong,ul,ol,li,br,span[style],div[style],img[src|alt|width|height]');

    $purifier = new HTMLPurifier($config);
    return $purifier->purify($content);
}
