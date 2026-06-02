<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

atlas_send_security_headers('media');
Auth::init();

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    exit('Authentication required');
}

$videoId = (int) ($_GET['video_id'] ?? 0);
$user = Auth::user();
$video = atlasGetVideoMediaRecord($videoId);

if (!$video) {
    http_response_code(404);
    exit('Video not found');
}

if (!atlasCanAccessVideo($user, $videoId)) {
    http_response_code(403);
    exit('Forbidden');
}

$filePath = atlasResolveVideoFilePath($video['storage_path'] ?? null);

if (!$filePath || !is_file($filePath)) {
    http_response_code(404);
    exit('Video file not found');
}

$fileSize = filesize($filePath);
$mimeType = trim((string) ($video['mime_type'] ?? ''));
$downloadName = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($video['original_name'] ?? ('video-' . $videoId . '.mp4')));

if ($mimeType === '' && function_exists('mime_content_type')) {
    $mimeType = (string) mime_content_type($filePath);
}

if ($mimeType === '') {
    $mimeType = 'video/mp4';
}

$start = 0;
$end = max(0, $fileSize - 1);
$statusCode = 200;

header('Content-Type: ' . $mimeType);
header('Accept-Ranges: bytes');
header('Content-Disposition: inline; filename="' . $downloadName . '"');
header('Cache-Control: private, max-age=300');

$range = (string) ($_SERVER['HTTP_RANGE'] ?? '');

if ($range !== '' && preg_match('/bytes=(\d*)-(\d*)/', $range, $matches) === 1) {
    $rangeStart = $matches[1] !== '' ? (int) $matches[1] : null;
    $rangeEnd = $matches[2] !== '' ? (int) $matches[2] : null;

    if ($rangeStart === null && $rangeEnd !== null) {
        $rangeStart = max(0, $fileSize - $rangeEnd);
        $rangeEnd = $fileSize - 1;
    }

    if ($rangeStart !== null) {
        $start = max(0, min($rangeStart, $end));
    }

    if ($rangeEnd !== null) {
        $end = max($start, min($rangeEnd, $end));
    }

    $statusCode = 206;
    http_response_code($statusCode);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
} else {
    http_response_code($statusCode);
}

$length = ($end - $start) + 1;
header('Content-Length: ' . $length);

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'HEAD') {
    exit;
}

$handle = fopen($filePath, 'rb');

if ($handle === false) {
    http_response_code(500);
    exit('Unable to read video');
}

fseek($handle, $start);
$remaining = $length;
$chunkSize = 8192;

while ($remaining > 0 && !feof($handle)) {
    $buffer = fread($handle, min($chunkSize, $remaining));

    if ($buffer === false) {
        break;
    }

    echo $buffer;
    flush();
    $remaining -= strlen($buffer);
}

fclose($handle);
