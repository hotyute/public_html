<?php

function app_upload_image_file(array $file, string $prefix = 'image_'): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No image was uploaded.');
    }

    if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
        throw new RuntimeException('Image is too large. Please use an image under 8 MB.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    $mime = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    } elseif (function_exists('mime_content_type')) {
        $mime = mime_content_type($file['tmp_name']);
    } else {
        $mime = $file['type'] ?? null;
    }

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Please choose a JPG, PNG, GIF, or WebP image.');
    }

    $uploadDir = dirname(__DIR__) . '/images/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Unable to prepare the upload folder.');
    }

    $filename = uniqid($prefix, true) . '.' . $allowed[$mime];
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Unable to save the uploaded image.');
    }

    return [
        'path' => '/images/uploads/' . $filename,
        'file' => $target,
        'mime' => $mime,
    ];
}
