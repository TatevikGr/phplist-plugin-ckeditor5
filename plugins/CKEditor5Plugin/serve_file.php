<?php
ob_clean();
header("Access-Control-Allow-Origin: *");

$pluginRoot = getConfig('elFinder_path');

$file = $_GET['file'] ?? null;

if (!$file || strpos($file, '..') !== false) {
    header("HTTP/1.1 400 Bad Request");
    exit("Invalid file request.");
}

$filePath = realpath($pluginRoot . $file);

if (!$filePath || strpos($filePath, realpath($pluginRoot)) !== 0 || !file_exists($filePath)) {
    header("HTTP/1.1 404 Not Found");
    exit("File not found.");
}

$mime = mime_content_type($filePath) ?: "application/octet-stream";
header("Content-Type: $mime");
header("Cache-Control: public, max-age=86400");

readfile($filePath);
exit;
