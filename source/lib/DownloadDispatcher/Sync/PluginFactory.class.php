<?php

class DownloadDispatcher_Sync_PluginFactory extends DownloadDispatcher_PluginFactory {
    
    protected static $plugin_prefix    = 'DownloadDispatcher_Sync_Plugin_';
    protected static $plugin_interface = 'DownloadDispatcher_Sync_IPlugin';
    protected static $plugin_dir       = array(
    	DownloadDispatcher_Lib  => 'DownloadDispatcher/Sync/Plugin/',
	);
    
    
    public static function init() {
        
    }
    
    public static function create($plugin, SihnonFramework_Config $config, SihnonFramework_Log $log, $instance) {
        self::ensureScanned();
    
        if (! self::isValidPlugin($plugin)) {
            throw new Sihnon_Exception_InvalidPluginName($plugin);
        }
    
        $classname = self::classname($plugin);
    
        return call_user_func(array($classname, 'create'), $config, $log, $instance);
    }
    
}

?>