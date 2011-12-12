<?php

class DownloadDispatcher_Source_PluginFactory extends DownloadDispatcher_PluginFactory {
    
    protected static $plugin_prefix    = 'DownloadDispatcher_Source_Plugin_';
    protected static $plugin_interface = 'DownloadDispatcher_Source_IPlugin';
    protected static $plugin_dir       = array(
    	DownloadDispatcher_Lib  => 'DownloadDispatcher/Source/Plugin/',
	);
    
    
    public static function init() {
        
    }
    
    public static function create($plugin, SihnonFramework_Config $config, SihnonFramework_Log $log) {
        self::ensureScanned();
    
        if (! self::isValidPlugin($plugin)) {
            throw new Sihnon_Exception_InvalidPluginName($plugin);
        }
    
        $classname = self::classname($plugin);
    
        return call_user_func(array($classname, 'create'), $config, $log);
    }
    
}

?>