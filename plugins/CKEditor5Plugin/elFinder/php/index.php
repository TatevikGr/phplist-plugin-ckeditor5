<?php

error_reporting(0);

is_readable('./vendor/autoload.php') && require './vendor/autoload.php';

require './autoload.php';

elFinder::$netDrivers['ftp'] = 'FTP';

include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderConnector.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinder.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeDriver.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeLocalFileSystem.class.php';

/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from '.' (dot)
 *
 * @param  string    $attr    attribute name (read|write|locked|hidden)
 * @param  string    $path    absolute file path
 * @param  string    $data    value of volume option `accessControlData`
 * @param  object    $volume  elFinder volume driver object
 * @param  bool|null $isDir   path is directory (true: directory, false: file, null: unknown)
 * @param  string    $relpath file path relative to volume root directory started with directory separator
 * @return bool|null
 **/
function access($attr, $path, $data, $volume, $isDir, $relpath) {
    $basename = basename($path);
    return $basename[0] === '.'
    && strlen($relpath) !== 1
        ? !($attr == 'read' || $attr == 'write')
        :  null;
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);


if (session_status() === PHP_SESSION_NONE && !empty($_COOKIE['phpListSession'])) {
    session_id($_COOKIE['phpListSession']);
}
session_start();

$imagePath = realpath($_SERVER['DOCUMENT_ROOT'] . '/images/') . '/';
$user_directory = $imagePath;

if (!empty($_SESSION['logindetails']['id'])) {
    $userId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_SESSION['logindetails']['id']); // just in case
    $user_directory = $imagePath . $userId . '/';

    if (!is_dir($user_directory)) {
        if (!mkdir($user_directory, 0755, true)) {
            die("Failed to create user directory: $user_directory");
        }
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$publicURL = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/images/' . ($userId ?? '');

$opts = array(
    'debug' => false,
    'roots' => array(
        array(
            'driver'        => 'LocalFileSystem',
            'path'          => $user_directory,
            'URL'           => dirname($_SERVER['PHP_SELF']) . DIRECTORY_SEPARATOR . $user_directory,
            'trashHash'     => 't1_Lw',
            'winHashFix'    => DIRECTORY_SEPARATOR !== '/',
            'uploadDeny'    => array('all'),
            'uploadAllow'   => array(
                'image/x-ms-bmp',  // BMP
                'image/gif',       // GIF
                'image/jpeg',      // JPEG
                'image/png',       // PNG
                'image/x-icon',    // ICO
                'text/plain',      // TXT
                'application/pdf', // PDF
                'application/msword',                      // DOC (Microsoft Word)
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // DOCX
                'application/vnd.ms-excel',                // XLS (Microsoft Excel)
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // XLSX
                'application/vnd.ms-powerpoint',           // PPT (Microsoft PowerPoint)
                'application/vnd.openxmlformats-officedocument.presentationml.presentation', // PPTX
                'application/zip',                          // ZIP Archive
                'application/x-rar-compressed',             // RAR Archive
                'application/x-7z-compressed',              // 7z Archive
                'application/x-tar',                        // TAR Archive
                'application/json',                         // JSON
                'text/csv',                                 // CSV
                'text/html',                                // HTML
                'application/javascript',                   // JavaScript files
                'application/xml',                          // XML
                'application/x-shockwave-flash',            // SWF
                'application/octet-stream',                 // Binary files
                'audio/mpeg',                               // MP3
                'audio/wav',                                // WAV
                'video/mp4',                                // MP4
                'video/x-msvideo',                          // AVI
                'video/x-matroska',                         // MKV
                'video/webm'
            ),
            'uploadOrder'   => array('deny', 'allow'),
            'accessControl' => 'access'
        ),
    )
);

$connector = new elFinderConnector(new elFinder($opts));
$connector->run();
