<?php
// Converts legacy <font color|face|size> to <span style="...">
// so downstream sanitization can keep semantics with CSS.
class HTMLPurifier_Filter_FontToSpan extends HTMLPurifier_Filter
{
    public $name = 'FontToSpan';

    public function preFilter($html, $config, $context) {
        // Map HTML font "size" (1..7, +n/-n) to px
        $mapSize = function($size) {
            $base = 3;
            // Handle relative values like +1, -2
            if (preg_match('/^[+-]\d+$/', $size)) {
                $val = $base + (int)$size;
            } elseif (preg_match('/^\d+$/', $size)) {
                $val = (int)$size;
            } else {
                $val = $base; // default
            }
            if ($val < 1) $val = 1;
            if ($val > 7) $val = 7;
            // Classic mapping
            $pxMap = [1=>10, 2=>13, 3=>16, 4=>18, 5=>24, 6=>32, 7=>48];
            return $pxMap[$val];
        };

        $html = preg_replace_callback('/<font\b([^>]*)>/i', function($m) use ($mapSize) {
            $attrs = $m[1];

            $styleParts = [];

            // color="..." or color=...
            if (preg_match('/\bcolor\s*=\s*(["\']?)([^"\'>\s]+)\1/i', $attrs, $c)) {
                $color = trim($c[2]);
                // Minimal scrub; full CSS sanitization happens later in HTMLPurifier
                $styleParts[] = 'color:' . htmlspecialchars($color, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            // face="Arial,Helvetica" or face=Arial
            if (preg_match('/\bface\s*=\s*(["\']?)([^"\'>]+)\1/i', $attrs, $f)) {
                $face = trim($f[2]);
                // strip dangerous chars; quoting handled by browser; Purifier will cleanup
                $face = preg_replace('/[;"\']+/', '', $face);
                $styleParts[] = 'font-family:' . htmlspecialchars($face, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            // size="3" or size=+1
            if (preg_match('/\bsize\s*=\s*(["\']?)([^"\'>\s]+)\1/i', $attrs, $s)) {
                $size = trim($s[2]);
                $px = $mapSize($size);
                $styleParts[] = 'font-size:' . $px . 'px';
            }

            $style = implode(';', $styleParts);
            if ($style !== '') $style .= ';';

            return '<span' . ($style ? ' style="' . $style . '"' : '') . '>';
        }, $html);

        // Close tags
        $html = preg_replace('/<\/font\s*>/i', '</span>', $html);

        return $html;
    }

    public function postFilter($html, $config, $context) {
        return $html;
    }
}