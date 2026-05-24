<?php

function app_current_issue_label(): string
{
    $month = (int)date('n');
    $year = date('Y');

    if ($month <= 2) {
        return "January-February $year";
    }
    if ($month <= 4) {
        return "March-April $year";
    }
    if ($month <= 6) {
        return "May-June $year";
    }
    if ($month <= 8) {
        return "July-August $year";
    }
    if ($month <= 10) {
        return "September-October $year";
    }

    return "November-December $year";
}

function app_user_role_class(?string $role): string
{
    switch ($role) {
        case 'admin':
        case 'owner':
            return 'admin-owner';
        case 'editor':
            return 'editor-user';
        default:
            return 'regular-user';
    }
}

function app_plain_excerpt(?string $content, int $limit = 180): string
{
    $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string)$content)));
    if ($plain === '') {
        return '';
    }

    $length = function_exists('mb_strlen') ? mb_strlen($plain) : strlen($plain);
    if ($length <= $limit) {
        return $plain;
    }

    $slice = function_exists('mb_substr') ? mb_substr($plain, 0, $limit) : substr($plain, 0, $limit);
    return rtrim($slice, " \t\n\r\0\x0B.,;:") . '...';
}

function app_plain_excerpt_data(?string $content, int $limit = 180): array
{
    $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string)$content)));
    if ($plain === '') {
        return [
            'text' => '',
            'is_truncated' => false,
        ];
    }

    $length = function_exists('mb_strlen') ? mb_strlen($plain) : strlen($plain);
    if ($length <= $limit) {
        return [
            'text' => $plain,
            'is_truncated' => false,
        ];
    }

    $slice = function_exists('mb_substr') ? mb_substr($plain, 0, $limit) : substr($plain, 0, $limit);
    return [
        'text' => rtrim($slice),
        'is_truncated' => true,
    ];
}

function app_can_edit_posts(): bool
{
    return isset($_SESSION['user_id']) && in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'], true);
}

function app_can_manage_magazines(): bool
{
    return isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin';
}

function app_post_image_src(?string $thumbnail): string
{
    $thumbnail = trim((string)$thumbnail);
    if ($thumbnail === '') {
        return app_post_placeholder_image_src();
    }

    $normalizedThumbnail = str_replace('\\', '/', $thumbnail);
    $normalizedThumbnail = ltrim($normalizedThumbnail, '/');
    while (strpos($normalizedThumbnail, '../') === 0) {
        $normalizedThumbnail = substr($normalizedThumbnail, 3);
    }
    if (strcasecmp($normalizedThumbnail, 'images/thumbnail.png') === 0) {
        return app_post_placeholder_image_src();
    }

    if (strpos($thumbnail, '../') === 0) {
        return '/' . ltrim(substr($thumbnail, 3), '/');
    }
    if (strpos($thumbnail, 'images/') === 0) {
        return '/' . $thumbnail;
    }

    return $thumbnail;
}

function app_post_placeholder_image_src(): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 960 640" role="img" aria-label="No thumbnail selected"><defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f7f4ef"/><stop offset="1" stop-color="#e8f0f7"/></linearGradient></defs><rect width="960" height="640" rx="36" fill="url(#bg)"/><path d="M160 454h640" stroke="#d7a83b" stroke-width="8" stroke-linecap="round"/><path d="M250 378l130-142 96 106 74-78 160 114" fill="none" stroke="#0e3a5d" stroke-width="22" stroke-linecap="round" stroke-linejoin="round" opacity=".75"/><circle cx="642" cy="218" r="44" fill="#e7b84c" opacity=".85"/><text x="480" y="508" fill="#0e3a5d" font-family="Nunito, Arial, sans-serif" font-size="42" font-weight="800" text-anchor="middle">No Thumbnail Selected</text></svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function app_safe_image_style(?string $style): string
{
    $style = trim((string)$style);
    if ($style === '') {
        return '';
    }

    $allowed = [
        'width' => true,
        'height' => true,
        'max-width' => true,
        'object-fit' => true,
        'object-position' => true,
        'aspect-ratio' => true,
    ];
    $safe = [];

    foreach (explode(';', $style) as $declaration) {
        $parts = explode(':', $declaration, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $property = strtolower(trim($parts[0]));
        $value = trim($parts[1]);
        if (!isset($allowed[$property]) || $value === '') {
            continue;
        }
        if (stripos($value, 'url') !== false || !preg_match('/^[a-z0-9 .,%()\/-]+$/i', $value)) {
            continue;
        }

        $safe[] = $property . ': ' . $value;
    }

    return implode('; ', $safe);
}

function app_video_embed_url(?string $url): string
{
    $url = trim((string)$url);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }

    $parts = parse_url($url);
    $host = strtolower((string)($parts['host'] ?? ''));
    $path = trim((string)($parts['path'] ?? ''), '/');
    $query = [];
    parse_str((string)($parts['query'] ?? ''), $query);

    if (strpos($host, 'youtu.be') !== false) {
        $id = strtok($path, '/');
        return $id ? 'https://www.youtube.com/embed/' . rawurlencode($id) : '';
    }

    if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtube-nocookie.com') !== false) {
        if (isset($query['v']) && preg_match('/^[A-Za-z0-9_-]{6,}$/', (string)$query['v'])) {
            return 'https://www.youtube.com/embed/' . rawurlencode((string)$query['v']);
        }
        if (preg_match('#^(embed|shorts|live)/([A-Za-z0-9_-]{6,})#', $path, $matches)) {
            return 'https://www.youtube.com/embed/' . rawurlencode($matches[2]);
        }
    }

    if (strpos($host, 'vimeo.com') !== false) {
        if (preg_match('/(\d{6,})/', $path, $matches)) {
            return 'https://player.vimeo.com/video/' . rawurlencode($matches[1]);
        }
    }

    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    return in_array($scheme, ['http', 'https'], true) ? $url : '';
}

function app_video_preview_image(?string $url): string
{
    $embed = app_video_embed_url($url);
    if ($embed === '') {
        return '';
    }

    $parts = parse_url($embed);
    $host = strtolower((string)($parts['host'] ?? ''));
    $path = trim((string)($parts['path'] ?? ''), '/');

    if (strpos($host, 'youtube.com') !== false && preg_match('#^embed/([A-Za-z0-9_-]{6,})#', $path, $matches)) {
        return 'https://img.youtube.com/vi/' . rawurlencode($matches[1]) . '/hqdefault.jpg';
    }

    return '';
}

function app_featured_video_file(): string
{
    return __DIR__ . '/featured_video.txt';
}

function app_featured_video_meta_file(): string
{
    return __DIR__ . '/featured_video_meta.json';
}

function app_featured_video_data(): array
{
    $urlFile = app_featured_video_file();
    $metaFile = app_featured_video_meta_file();
    $url = file_exists($urlFile) ? trim((string)file_get_contents($urlFile)) : '';
    $meta = [];

    if (file_exists($metaFile)) {
        $decoded = json_decode((string)file_get_contents($metaFile), true);
        if (is_array($decoded)) {
            $meta = $decoded;
        }
    }

    $style = app_safe_image_style(is_scalar($meta['container_style'] ?? null) ? (string)$meta['container_style'] : '');

    return [
        'url' => $url,
        'embed' => app_video_embed_url($url),
        'preview' => app_video_preview_image($url),
        'container_style' => $style,
    ];
}

function app_featured_video_save(string $url, string $containerStyle = ''): array
{
    $embed = app_video_embed_url($url);
    if ($embed === '') {
        throw new InvalidArgumentException('Please enter a valid YouTube, Vimeo, or embeddable video link.');
    }

    file_put_contents(app_featured_video_file(), $embed);
    $style = app_safe_image_style($containerStyle);
    file_put_contents(
        app_featured_video_meta_file(),
        json_encode([
            'container_style' => $style,
            'updated_at' => date(DATE_ATOM),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    return [
        'url' => $embed,
        'embed' => $embed,
        'preview' => app_video_preview_image($embed),
        'container_style' => $style,
    ];
}
