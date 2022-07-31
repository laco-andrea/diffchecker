<?php
set_time_limit(3600);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'FtpApplication.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'SourceLogger.php';

//LIST OF PARAMETERS FOR FTP CONNECTION
const HOST = '';
const USER = '';
const PSW = '';
const DIRECTORIES_TO_DOWNLOAD = array(
    'classes', 'controllers', 'js', 'app', 'config'
);
const FORCE_REFRESH = true; //IF TRUE, THIS WILL REMOVE ALL DIRECTORIES LISTED PREVIOUSLY BEFORE DOWNLOAD THEM

$ftpConnect = new FtpApplication(HOST, USER, PSW);
$sourceLogger = new SourceLogger();
if (isset($_GET['ftp_sync']) && (int)$_GET['ftp_sync'] == 1) {
    try {
        $result = $ftpConnect->downloadResources($sourceLogger, DIRECTORIES_TO_DOWNLOAD, FORCE_REFRESH);
    } catch (Exception $e) {
        die($e->getMessage());
    }
}
try {
    $sourceLogger->logState();
    die('EXECUTION TERMINATED');
} catch (Exception $e) {
    die($e->getMessage());
}
