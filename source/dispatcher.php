<?php

define('DD_File', 'dispatcher');

$options = array();
if (isset($_SERVER['argv'])) {
    $options = getopt('c:', array('config:'));
}

if (isset($options['config'])) {
    require_once $options['config'];
} else {
    require_once '/etc/download-dispatcher/config.php';
}

require_once(SihnonFramework_Lib . 'SihnonFramework/Main.class.php');

SihnonFramework_Main::registerAutoloadClasses('SihnonFramework', SihnonFramework_Lib,
												'DownloadDispatcher', SihnonFramework_Main::makeAbsolutePath(DownloadDispatcher_Lib));


try {

    set_time_limit(0);

    $main = DownloadDispatcher_Main::instance();
    DownloadDispatcher_LogEntry::setLocalProgname('download-dispatcher');

    // Download Dispatcher entry point
    DownloadDispatcher_Processor::run();

} catch (DownloadDispatcher_Exception $e) {
    die("Uncaught Exception: " . $e->getMessage());
}


?>