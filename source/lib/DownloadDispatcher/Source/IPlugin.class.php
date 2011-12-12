<?php

interface DownloadDispatcher_Source_IPlugin extends DownloadDispatcher_IPlugin {
    
    /**
     * Process files recognised by this plugin
     * 
     * 
     */
    public function run();
    
    public static function create($config, $log);
    
}

?>