<?php

class DownloadDispatcher_Main extends SihnonFramework_Main {

    protected $daemon;
    
    public function __construct() {
        parent::__construct();
    }
    
    public function init() {
        parent::init();
        
        try {
            $this->daemon = new DownloadDispatcher_Daemon($this->config);
            
        } catch (SihnonFramework_Exception_AlreadyRunning $e) {
            DownloadDispatcher_LogEntry::error($this->log, "Another instance is already running, exiting this process now.");
            exit(0);
        }
    }
    
}

?>