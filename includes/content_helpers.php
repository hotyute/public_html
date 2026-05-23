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

function app_can_edit_posts(): bool
{
    return isset($_SESSION['user_id']) && in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'], true);
}

function app_post_image_src(?string $thumbnail): string
{
    $thumbnail = trim((string)$thumbnail);
    return $thumbnail !== '' ? $thumbnail : '/images/thumbnail.png';
}
