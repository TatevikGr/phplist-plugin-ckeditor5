<?php
ob_clean();
error_reporting(0);

is_readable('./elFinder/vendor/autoload.php');

require 'elFinder/vendor/autoload.php';
elFinder::$netDrivers['ftp'] = 'FTP';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinder/php/elFinderConnector.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinder/php/elFinder.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinder/php/elFinderVolumeDriver.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinder/php/elFinderVolumeLocalFileSystem.class.php';

function access($attr, $path, $data, $volume, $isDir, $relpath) {
    $basename = basename($path);
    return $basename[0] === '.'
    && strlen($relpath) !== 1
        ? !($attr == 'read' || $attr == 'write')
        :  null;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";

$uploadDir = UPLOADIMAGES_DIR;
$uploadPath = realpath($_SERVER['DOCUMENT_ROOT'] . "/$uploadDir/") . '/';
$userDirectory = $uploadPath;
$publicURL = $protocol . '://' . $_SERVER['SERVER_NAME'] . "/$uploadDir/";

if (defined('IMAGE_DIR_PER_ADMIN') && IMAGE_DIR_PER_ADMIN) {
    $userId = $_SESSION['logindetails']['id'];
    $userDirectory = $uploadPath . $userId . '/';
    $publicURL .= $userId;
}
$imagePath = $uploadPath . '/' . getConfig('elfinder_image_directory');
$filePath = $uploadPath . '/' . getConfig('elfinder_files_directory');

if (!is_dir($imagePath)) {
    if (!mkdir($imagePath, 0755, true)) {
        die("Failed to create user directory: $imagePath");
    }
}
if (!is_dir($filePath)) {
    if (!mkdir($filePath, 0755, true)) {
        die("Failed to create user directory: $filePath");
    }
}
if (!is_dir($userDirectory . '/.trash/')) {
    if (!mkdir($userDirectory . '/.trash/', 0755, true)) {
        die("Failed to create user directory: $userDirectory");
    }
}

$allowedUploadImageTypes = array(
    'image/x-ms-bmp', 'image/gif', 'image/jpeg', 'image/png', 'image/x-icon',
);

$opts = array(
    'debug' => false,
    'roots' => array(
        array(
            'id'            => 't1_Lw',
            'driver'        => 'Trash',
            'path'          => $userDirectory . '/.trash/',
            'winHashFix'    => DIRECTORY_SEPARATOR !== '/',
            'uploadDeny'    => array('all'),
            'uploadAllow'   => array('all'),
            'uploadOrder'   => array('deny', 'allow'),
            'accessControl' => 'access',
        ),
        array(
            'driver'        => 'LocalFileSystem',
            'path'          => $imagePath,
            'URL'           => $publicURL . getConfig('elfinder_image_directory') . '/',
            'trashHash'     => 't1_Lw',
            'winHashFix'    => DIRECTORY_SEPARATOR !== '/',
            'uploadDeny'    => array('all'),
            'uploadAllow'   => array('all'),
            'uploadOrder'   => array('deny', 'allow'),
            'accessControl' => 'access'
        ),
        array(
            'driver'        => 'LocalFileSystem',
            'path'          => $filePath,
            'URL'           => $publicURL . getConfig('elfinder_files_directory') . '/',
            'uploadAllow'   => array('all'),
            'uploadDeny'    => array('all'),
            'uploadOrder'   => array('deny', 'allow'),
            'accessControl' => 'access',
        ),
    )
);

$connector = new elFinderConnector(new elFinder($opts));
$connector->run();
