<?php

function article_audio_base_name(int $postId): string
{
    return 'post_' . $postId;
}

function article_audio_dir(): string
{
    return dirname(__DIR__) . '/audio';
}

function article_audio_manifest_path(int $postId): string
{
    return article_audio_dir() . '/' . article_audio_base_name($postId) . '.json';
}

function article_audio_manifest_url(int $postId): string
{
    return '/audio/' . article_audio_base_name($postId) . '.json';
}

function article_audio_base_url(int $postId): string
{
    return '/audio/' . article_audio_base_name($postId) . '.wav';
}

function article_audio_plain_text(string $html): string
{
    $html = preg_replace('/<!--\s*pagebreak\s*-->/i', "\n\n", $html);
    $html = preg_replace('/<\s*br\s*\/?>/i', "\n", $html);
    $html = preg_replace('/<\/(p|div|h[1-6]|li|blockquote|tr)>/i', "\n", $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\s*\n\s*/', "\n", $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}

function article_audio_pages(string $html): array
{
    $parts = preg_split('/<!--\s*pagebreak\s*-->/i', $html) ?: [$html];
    $pages = [];

    foreach ($parts as $part) {
        $text = article_audio_plain_text($part);
        if ($text !== '') {
            $pages[] = $text;
        }
    }

    return $pages !== [] ? $pages : [article_audio_plain_text($html)];
}

function article_audio_timestamp(float $seconds): string
{
    $seconds = max(0, $seconds);
    $hours = (int)floor($seconds / 3600);
    $minutes = (int)floor(($seconds % 3600) / 60);
    $wholeSeconds = (int)floor($seconds % 60);
    $milliseconds = (int)floor(($seconds - floor($seconds)) * 1000);

    return sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $wholeSeconds, $milliseconds);
}

function article_audio_vtt(string $text): string
{
    preg_match_all('/\S+/', $text, $matches);
    $words = $matches[0] ?? [];
    $wordCount = max(1, count($words));
    $secondsPerWord = 60 / 155;
    $cueWords = 9;
    $cursor = 0;
    $cue = 1;
    $vtt = "WEBVTT\n\n";

    while ($cursor < $wordCount) {
        $chunk = array_slice($words, $cursor, $cueWords);
        $start = $cursor * $secondsPerWord;
        $end = min($wordCount * $secondsPerWord, ($cursor + count($chunk)) * $secondsPerWord + 0.12);
        $vtt .= $cue . "\n";
        $vtt .= article_audio_timestamp($start) . ' --> ' . article_audio_timestamp($end) . "\n";
        $vtt .= implode(' ', $chunk) . "\n\n";
        $cursor += count($chunk);
        $cue++;
    }

    return $vtt;
}

function article_audio_tts_command(): ?string
{
    if (PHP_OS_FAMILY === 'Windows' || !function_exists('shell_exec')) {
        return null;
    }

    $candidates = ['espeak-ng', 'espeak'];
    foreach ($candidates as $candidate) {
        $path = trim((string)shell_exec('command -v ' . escapeshellarg($candidate) . ' 2>/dev/null'));
        if ($path !== '') {
            return $path;
        }
    }

    return null;
}

function article_audio_generate_wav(string $textFile, string $audioFile): bool
{
    $command = article_audio_tts_command();
    if ($command === null || !function_exists('exec')) {
        return false;
    }

    $cmd = escapeshellarg($command) . ' -w ' . escapeshellarg($audioFile) . ' -f ' . escapeshellarg($textFile) . ' 2>&1';
    exec($cmd, $output, $status);
    return $status === 0 && file_exists($audioFile) && filesize($audioFile) > 0;
}

function article_audio_generate_for_post(PDO $pdo, int $postId, string $content): array
{
    $audioDir = article_audio_dir();
    if (!is_dir($audioDir)) {
        @mkdir($audioDir, 0755, true);
    }

    $base = article_audio_base_name($postId);
    $pages = article_audio_pages($content);
    $manifestPages = [];
    $generatedAudio = false;

    foreach ($pages as $index => $text) {
        $pageNumber = $index + 1;
        $fileStem = $base . '_p' . $pageNumber;
        $textPath = $audioDir . '/' . $fileStem . '.txt';
        $vttPath = $audioDir . '/' . $fileStem . '.vtt';
        $audioPath = $audioDir . '/' . $fileStem . '.wav';

        file_put_contents($textPath, $text);
        file_put_contents($vttPath, article_audio_vtt($text));

        $hasAudio = article_audio_generate_wav($textPath, $audioPath);
        $generatedAudio = $generatedAudio || $hasAudio;

        preg_match_all('/\S+/', $text, $wordMatches);
        $wordCount = count($wordMatches[0] ?? []);

        $manifestPages[] = [
            'page' => $pageNumber,
            'text' => $text,
            'text_url' => '/audio/' . $fileStem . '.txt',
            'vtt_url' => '/audio/' . $fileStem . '.vtt',
            'audio_url' => $hasAudio ? '/audio/' . $fileStem . '.wav' : null,
            'estimated_seconds' => max(1, round($wordCount * (60 / 155), 2))
        ];
    }

    file_put_contents(
        article_audio_manifest_path($postId),
        json_encode([
            'post_id' => $postId,
            'generated_at' => date(DATE_ATOM),
            'engine' => $generatedAudio ? 'espeak' : 'browser-speech',
            'pages' => $manifestPages
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );

    $stmt = $pdo->prepare('UPDATE posts SET voiceover_url = ? WHERE id = ?');
    $stmt->execute([article_audio_base_url($postId), $postId]);

    return [
        'success' => true,
        'audio_generated' => $generatedAudio,
        'manifest_url' => article_audio_manifest_url($postId),
        'page_count' => count($manifestPages)
    ];
}

function article_audio_delete_for_post(int $postId): void
{
    $audioDir = article_audio_dir();
    $base = article_audio_base_name($postId);
    foreach (glob($audioDir . '/' . $base . '*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
