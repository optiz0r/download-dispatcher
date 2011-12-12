<?php

class DownloadDispatcher_Processor {
    
    /**
     * Entry point for the Download Dispatcher
     * 
	 * Iterates over all enabled plugins and moves supported files to the proper destinations
     */
    public static function run() {
        
        $main = DownloadDispatcher_Main::instance();
        $config = $main->config();
        $log    = $main->log();
        
        // Find the list of available plugins
        DownloadDispatcher_Source_PluginFactory::scan();
        $plugins = DownloadDispatcher_Source_PluginFactory::getValidPlugins();
        
        $enabled_plugins = $config->get('sources');
        foreach ($plugins as $plugin_name) {
            if (in_array($plugin_name, $enabled_plugins)) {
                $plugin = DownloadDispatcher_Source_PluginFactory::create($plugin_name, $config, $log);
                $plugin->run();
            }
        }
    }
    
}

?>