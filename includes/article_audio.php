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

function article_audio_manifest_needs_refresh(int $postId): bool
{
    $path = article_audio_manifest_path($postId);
    if (!file_exists($path)) {
        return true;
    }

    $manifest = json_decode((string)file_get_contents($path), true);
    if (!is_array($manifest) || empty($manifest['pages']) || !is_array($manifest['pages'])) {
        return true;
    }

    foreach ($manifest['pages'] as $page) {
        if (empty($page['cues']) || !is_array($page['cues'])) {
            return true;
        }
    }

    return false;
}

function article_audio_manifest_exists(int $postId): bool
{
    return is_file(article_audio_manifest_path($postId));
}

function article_audio_base_url(int $postId): string
{
    return '/audio/' . article_audio_base_name($postId) . '_p1.mp3';
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

function article_audio_words(string $text): array
{
    preg_match_all('/\S+/', $text, $matches);
    return $matches[0] ?? [];
}

function article_audio_word_duration(string $word): float
{
    $duration = 60 / 165;
    $length = function_exists('mb_strlen') ? mb_strlen($word) : strlen($word);

    if ($length > 9) {
        $duration += 0.08;
    }
    if (preg_match('/[,;:]$/', $word)) {
        $duration += 0.12;
    }
    if (preg_match('/[.!?]$/', $word)) {
        $duration += 0.28;
    }

    return $duration;
}

function article_audio_cues(string $text): array
{
    $words = article_audio_words($text);
    $wordCount = count($words);
    if ($wordCount === 0) {
        return [];
    }

    $cues = [];
    $cursor = 0;
    $time = 0.0;

    while ($cursor < $wordCount) {
        $startWord = $cursor;
        $start = $time;
        $chunk = [];

        while ($cursor < $wordCount) {
            $word = $words[$cursor];
            $chunk[] = $word;
            $time += article_audio_word_duration($word);
            $cursor++;

            $chunkSize = count($chunk);
            if ($chunkSize >= 5 && preg_match('/[.!?]$/', $word)) {
                break;
            }
            if ($chunkSize >= 7 && preg_match('/[,;:]$/', $word)) {
                break;
            }
            if ($chunkSize >= 10) {
                break;
            }
        }

        $cues[] = [
            'start' => round($start, 3),
            'end' => round(max($start + 0.16, $time), 3),
            'start_word' => $startWord,
            'end_word' => $cursor - 1,
            'text' => implode(' ', $chunk),
        ];
    }

    return $cues;
}

function article_audio_vtt_from_cues(array $cues): string
{
    $vtt = "WEBVTT\n\n";

    foreach ($cues as $index => $cue) {
        $vtt .= ($index + 1) . "\n";
        $vtt .= article_audio_timestamp((float)$cue['start']) . ' --> ' . article_audio_timestamp((float)$cue['end']) . "\n";
        $vtt .= $cue['text'] . "\n\n";
    }

    return $vtt;
}

function article_audio_vtt(string $text): string
{
    return article_audio_vtt_from_cues(article_audio_cues($text));
}

function article_audio_add_diagnostic(array &$diagnostics, string $engine, string $message, array $context = []): void
{
    $entry = [
        'engine' => $engine,
        'message' => $message,
    ];
    if ($context !== []) {
        $entry['context'] = $context;
    }
    $diagnostics[] = $entry;
    error_log('Article audio ' . $engine . ': ' . $message . ($context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : ''));
}

function article_audio_output_excerpt(array $output): string
{
    $text = trim(implode("\n", $output));
    if ($text === '') {
        return '';
    }

    return function_exists('mb_substr') ? mb_substr($text, 0, 1200) : substr($text, 0, 1200);
}

function article_audio_path_state(?string $path): array
{
    if ($path === null) {
        return ['found' => false];
    }

    return [
        'path' => $path,
        'exists' => file_exists($path),
        'readable' => is_readable($path),
        'executable' => is_executable($path),
    ];
}

function article_audio_shell_command_exists(string $command): ?string
{
    if (PHP_OS_FAMILY === 'Windows' || !function_exists('shell_exec')) {
        return null;
    }

    $path = trim((string)shell_exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null'));
    return $path !== '' ? $path : null;
}

function article_audio_find_first_file(array $patterns): ?string
{
    foreach ($patterns as $pattern) {
        foreach (glob($pattern) ?: [] as $file) {
            if (is_file($file) && is_readable($file)) {
                return $file;
            }
        }
    }

    return null;
}

function article_audio_piper_binary(): ?string
{
    $env = getenv('PIPER_TTS_BIN');
    if ($env && is_file($env) && is_executable($env)) {
        return $env;
    }

    $home = getenv('HOME') ?: '';
    $candidates = [];
    if ($home !== '') {
        $candidates[] = $home . '/piper-tts/.venv/bin/piper';
    }
    $candidates[] = '/home/*/piper-tts/.venv/bin/piper';
    $candidates[] = '/opt/piper-tts/.venv/bin/piper';

    foreach ($candidates as $candidate) {
        foreach (glob($candidate) ?: [] as $file) {
            if (is_file($file) && is_executable($file)) {
                return $file;
            }
        }
    }

    return article_audio_shell_command_exists('piper');
}

function article_audio_piper_model(): ?string
{
    $env = getenv('PIPER_TTS_MODEL');
    if ($env && is_file($env) && is_readable($env)) {
        return $env;
    }

    $home = getenv('HOME') ?: '';
    $patterns = [];
    if ($home !== '') {
        $patterns[] = $home . '/piper-tts/voices/en_GB-alan-medium/en_GB-alan-medium.onnx';
    }
    $patterns[] = '/home/*/piper-tts/voices/en_GB-alan-medium/en_GB-alan-medium.onnx';
    $patterns[] = '/opt/piper-tts/voices/en_GB-alan-medium/en_GB-alan-medium.onnx';

    return article_audio_find_first_file($patterns);
}

function article_audio_ffmpeg_binary(): ?string
{
    $env = getenv('FFMPEG_BIN');
    if ($env && is_file($env) && is_executable($env)) {
        return $env;
    }

    return article_audio_shell_command_exists('ffmpeg');
}

function article_audio_espeak_command(): ?string
{
    return article_audio_shell_command_exists('espeak-ng') ?: article_audio_shell_command_exists('espeak');
}

function article_audio_generate_with_piper(string $textFile, string $mp3File, array &$diagnostics): bool
{
    if (PHP_OS_FAMILY === 'Windows' || !function_exists('exec')) {
        article_audio_add_diagnostic($diagnostics, 'piper', 'Piper cannot run because exec is disabled or the host is Windows.');
        return false;
    }

    $piper = article_audio_piper_binary();
    $model = article_audio_piper_model();
    $ffmpeg = article_audio_ffmpeg_binary();
    if ($piper === null || $model === null || $ffmpeg === null) {
        article_audio_add_diagnostic($diagnostics, 'piper', 'Required Piper dependencies were not found.', [
            'piper' => article_audio_path_state($piper),
            'model' => article_audio_path_state($model),
            'ffmpeg' => article_audio_path_state($ffmpeg),
        ]);
        return false;
    }

    if (!is_readable($textFile)) {
        article_audio_add_diagnostic($diagnostics, 'piper', 'Input text file is not readable.', ['text_file' => $textFile]);
        return false;
    }

    $audioDir = dirname($mp3File);
    if (!is_dir($audioDir) || !is_writable($audioDir)) {
        article_audio_add_diagnostic($diagnostics, 'piper', 'Audio directory is not writable.', [
            'audio_dir' => $audioDir,
            'exists' => is_dir($audioDir),
            'writable' => is_writable($audioDir),
        ]);
        return false;
    }

    $wavFile = preg_replace('/\.mp3$/', '.wav', $mp3File) ?: ($mp3File . '.wav');
    $piperCmd = escapeshellarg($piper)
        . ' --model ' . escapeshellarg($model)
        . ' --output_file ' . escapeshellarg($wavFile)
        . ' < ' . escapeshellarg($textFile)
        . ' 2>&1';
    exec($piperCmd, $piperOutput, $piperStatus);
    if ($piperStatus !== 0 || !file_exists($wavFile) || filesize($wavFile) <= 0) {
        article_audio_add_diagnostic($diagnostics, 'piper', 'Piper failed to create a WAV file.', [
            'status' => $piperStatus,
            'output' => article_audio_output_excerpt($piperOutput),
            'wav_file' => $wavFile,
            'wav_exists' => file_exists($wavFile),
            'wav_size' => file_exists($wavFile) ? filesize($wavFile) : 0,
        ]);
        return false;
    }

    $ffmpegCmd = escapeshellarg($ffmpeg)
        . ' -y -loglevel error -i ' . escapeshellarg($wavFile)
        . ' -codec:a libmp3lame -q:a 4 ' . escapeshellarg($mp3File)
        . ' 2>&1';
    exec($ffmpegCmd, $ffmpegOutput, $ffmpegStatus);

    $success = $ffmpegStatus === 0 && file_exists($mp3File) && filesize($mp3File) > 0;
    if (!$success) {
        article_audio_add_diagnostic($diagnostics, 'piper', 'ffmpeg failed to convert Piper WAV output to MP3.', [
            'status' => $ffmpegStatus,
            'output' => article_audio_output_excerpt($ffmpegOutput),
            'mp3_file' => $mp3File,
            'mp3_exists' => file_exists($mp3File),
            'mp3_size' => file_exists($mp3File) ? filesize($mp3File) : 0,
        ]);
        return false;
    }

    @unlink($wavFile);
    return true;
}

function article_audio_generate_with_espeak(string $textFile, string $audioFile, array &$diagnostics): bool
{
    $command = article_audio_espeak_command();
    if ($command === null || !function_exists('exec')) {
        article_audio_add_diagnostic($diagnostics, 'espeak', 'espeak fallback is not available.');
        return false;
    }

    $cmd = escapeshellarg($command) . ' -w ' . escapeshellarg($audioFile) . ' -f ' . escapeshellarg($textFile) . ' 2>&1';
    exec($cmd, $output, $status);
    $success = $status === 0 && file_exists($audioFile) && filesize($audioFile) > 0;
    if (!$success) {
        article_audio_add_diagnostic($diagnostics, 'espeak', 'espeak failed to generate fallback audio.', [
            'status' => $status,
            'output' => article_audio_output_excerpt($output),
        ]);
    }
    return $success;
}

function article_audio_generate_audio_file(string $textFile, string $fileStem, array &$diagnostics = []): array
{
    $audioDir = article_audio_dir();
    $mp3Path = $audioDir . '/' . $fileStem . '.mp3';
    if (article_audio_generate_with_piper($textFile, $mp3Path, $diagnostics)) {
        return ['engine' => 'piper', 'path' => $mp3Path, 'url' => '/audio/' . $fileStem . '.mp3'];
    }

    $wavPath = $audioDir . '/' . $fileStem . '.wav';
    if (article_audio_generate_with_espeak($textFile, $wavPath, $diagnostics)) {
        return ['engine' => 'espeak', 'path' => $wavPath, 'url' => '/audio/' . $fileStem . '.wav'];
    }

    return ['engine' => 'browser-speech', 'path' => null, 'url' => null];
}

function article_audio_failure_result(bool $generateAudio, array $diagnostics, string $message): array
{
    return [
        'success' => false,
        'audio_generated' => false,
        'realistic_audio_requested' => $generateAudio,
        'engine' => 'browser-speech',
        'message' => $message,
        'diagnostics' => $diagnostics,
        'manifest_url' => null,
        'page_count' => 0,
    ];
}

function article_audio_generate_for_post(PDO $pdo, int $postId, string $content, bool $generateAudio = true): array
{
    $audioDir = article_audio_dir();
    $diagnostics = [];
    $fail = function (string $message) use (&$diagnostics, $generateAudio, $pdo, $postId): array {
        $stmt = $pdo->prepare('UPDATE posts SET voiceover_url = NULL WHERE id = ?');
        $stmt->execute([$postId]);
        return article_audio_failure_result($generateAudio, $diagnostics, $message);
    };

    if (!is_dir($audioDir) && !@mkdir($audioDir, 0755, true)) {
        article_audio_add_diagnostic($diagnostics, 'storage', 'Audio directory could not be created.', ['audio_dir' => $audioDir]);
        return $fail('Audio directory could not be created.');
    }

    if (!is_writable($audioDir)) {
        article_audio_add_diagnostic($diagnostics, 'storage', 'Audio directory is not writable by PHP.', ['audio_dir' => $audioDir]);
        return $fail('Audio directory is not writable by PHP.');
    }

    $base = article_audio_base_name($postId);
    $pages = article_audio_pages($content);
    $manifestPages = [];
    $generatedAudio = false;
    $engine = 'browser-speech';

    foreach ($pages as $index => $text) {
        $pageNumber = $index + 1;
        $fileStem = $base . '_p' . $pageNumber;
        $textPath = $audioDir . '/' . $fileStem . '.txt';
        $vttPath = $audioDir . '/' . $fileStem . '.vtt';

        if (@file_put_contents($textPath, $text) === false) {
            article_audio_add_diagnostic($diagnostics, 'storage', 'Failed to write reader text file.', ['path' => $textPath]);
            return $fail('Failed to write reader text file.');
        }
        $cues = article_audio_cues($text);
        if (@file_put_contents($vttPath, article_audio_vtt_from_cues($cues)) === false) {
            article_audio_add_diagnostic($diagnostics, 'storage', 'Failed to write reader cue file.', ['path' => $vttPath]);
            return $fail('Failed to write reader cue file.');
        }

        $audioResult = $generateAudio
            ? article_audio_generate_audio_file($textPath, $fileStem, $diagnostics)
            : ['engine' => 'browser-speech', 'path' => null, 'url' => null];
        $hasAudio = $audioResult['url'] !== null;
        $generatedAudio = $generatedAudio || $hasAudio;
        if ($hasAudio && $engine === 'browser-speech') {
            $engine = $audioResult['engine'];
        }

        $wordCount = count(article_audio_words($text));
        $estimatedSeconds = $cues !== [] ? max(array_column($cues, 'end')) : max(1, $wordCount * (60 / 165));

        $manifestPages[] = [
            'page' => $pageNumber,
            'text' => $text,
            'text_url' => '/audio/' . $fileStem . '.txt',
            'vtt_url' => '/audio/' . $fileStem . '.vtt',
            'audio_url' => $audioResult['url'],
            'cues' => $cues,
            'estimated_seconds' => max(1, round((float)$estimatedSeconds, 2))
        ];
    }

    $manifest = json_encode([
            'post_id' => $postId,
            'generated_at' => date(DATE_ATOM),
            'engine' => $generatedAudio ? $engine : 'browser-speech',
            'realistic_audio_requested' => $generateAudio,
            'diagnostics' => $diagnostics,
            'pages' => $manifestPages
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($manifest === false || @file_put_contents(article_audio_manifest_path($postId), $manifest) === false) {
        article_audio_add_diagnostic($diagnostics, 'storage', 'Failed to write reader manifest.', ['path' => article_audio_manifest_path($postId)]);
        return $fail('Failed to write reader manifest.');
    }

    $stmt = $pdo->prepare('UPDATE posts SET voiceover_url = ? WHERE id = ?');
    $firstAudioUrl = $manifestPages[0]['audio_url'] ?? null;
    $stmt->execute([$firstAudioUrl, $postId]);

    return [
        'success' => true,
        'audio_generated' => $generatedAudio,
        'realistic_audio_requested' => $generateAudio,
        'engine' => $engine,
        'message' => !$generatedAudio && $generateAudio && $diagnostics !== []
            ? ($diagnostics[0]['message'] ?? 'Realistic audio could not be generated.')
            : null,
        'diagnostics' => $diagnostics,
        'manifest_url' => article_audio_manifest_url($postId),
        'page_count' => count($manifestPages)
    ];
}

function article_audio_preserved_result(int $postId): array
{
    return [
        'success' => true,
        'audio_generated' => false,
        'audio_preserved' => article_audio_manifest_exists($postId),
        'realistic_audio_requested' => false,
        'engine' => 'existing',
        'manifest_url' => article_audio_manifest_exists($postId) ? article_audio_manifest_url($postId) : null,
        'page_count' => null
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
