<?php
ob_clean();
$elPath = rtrim(getConfig('elfinder_path'), '/') . '/';
parse_str(parse_url($_GET['page'], PHP_URL_QUERY), $queryParams);
$file = $queryParams['file'] ?? null;
$mime = $queryParams['mime'] ?? null;

if ($file === null) {
    return;
}
$filePath = $elPath . $file;
if (file_exists($filePath)) {
    $mime = $mime ?? mime_content_type($filePath);

    if (!$mime) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            "css"  => "text/css",
            "js"   => "application/javascript",
            "json" => "application/json",
            "png"  => "image/png",
            "jpg"  => "image/jpeg",
            "jpeg" => "image/jpeg",
            "gif"  => "image/gif",
            "svg"  => "image/svg+xml",
            "woff" => "font/woff",
            "woff2"=> "font/woff2",
            "ttf"  => "font/ttf",
            "eot"  => "application/vnd.ms-fontobject",
            "otf"  => "font/otf"
        ];
        $mime = $mimeTypes[$extension] ?? "application/octet-stream";
    }

    header("Content-Type: $mime");
    header("Cache-Control: public, max-age=86400");
    readfile($filePath);
    exit;
}

