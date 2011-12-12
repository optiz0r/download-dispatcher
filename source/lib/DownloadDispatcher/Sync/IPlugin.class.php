<?php

interface DownloadDispatcher_Sync_IPlugin extends DownloadDispatcher_IPlugin {
    
    /**
     * Process files recognised by this plugin
     * 
     * 
     */
    public function run();
    
    public static function create($config, $log, $instance);
    
}

?>