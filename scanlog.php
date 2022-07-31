<?php
set_time_limit(3600);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'FtpApplication.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'SourceLogger.php';

//LIST OF PARAMETERS FOR FTP CONNECTION
const SOURCE_HOST = '';
const SOURCE_USER = '';
const SOURCE_PSW = '';

const TARGET_HOST = '';
const TARGET_USER = '';
const TARGET_PSW = '';

const DIRECTORIES_TO_DOWNLOAD = array(
    'classes', 'controllers', 'js', 'app', 'config'
);
const FORCE_REFRESH = true; //IF TRUE, THIS WILL REMOVE ALL DIRECTORIES LISTED PREVIOUSLY BEFORE DOWNLOAD THEM

$ftpConnect_source = new FtpApplication(SOURCE_HOST, SOURCE_USER, SOURCE_PSW);
$ftpConnect_target = new FtpApplication(TARGET_HOST, TARGET_USER, TARGET_PSW);
$sourceLogger = new SourceLogger();
if (isset($_GET['ftp_sync']) && (int)$_GET['ftp_sync'] == 1) {
    try {
        $result_source = $ftpConnect_source->downloadResources($sourceLogger, DIRECTORIES_TO_DOWNLOAD, FORCE_REFRESH, false);
        $result_target = $ftpConnect_target->downloadResources($sourceLogger, DIRECTORIES_TO_DOWNLOAD, FORCE_REFRESH, true);
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
