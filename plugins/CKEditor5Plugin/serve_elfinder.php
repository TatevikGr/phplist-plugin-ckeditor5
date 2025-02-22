<?php
ob_clean();
header("Access-Control-Allow-Origin: *");

$elFinderPath = __DIR__ . '/elfinder.html';

if (!file_exists($elFinderPath)) {
    exit();
}

header("Content-Type: text/html");
readfile($elFinderPath);
exit;
