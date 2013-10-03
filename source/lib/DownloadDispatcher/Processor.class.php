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


        if (! $config->get('sync.skip', false)) { 
            // Find the list of available Sync plugins
            $sync_plugins = $config->get('sync');
            foreach ($sync_plugins as $plugin_name) {
                // Get a list of all the instances of this plugin to be used
                $instances = $config->get("sync.{$plugin_name}");
                foreach ($instances as $instance) {
                    try {
                        $plugin = DownloadDispatcher_Sync_PluginFactory::create($plugin_name, $config, $log, $instance);
                        $plugin->run();
                    
                    } catch(SihnonFramework_Exception_PluginException $e) {
                        SihnonFramework_LogEntry::warning($log, $e->getMessage());
                    }
                }
            }
        }

        // Find the list of available source plugins
        DownloadDispatcher_Source_PluginFactory::scan();
        $source_plugins = $config->get('sources');
        foreach ($source_plugins as $plugin_name) {
            try {
                $plugin = DownloadDispatcher_Source_PluginFactory::create($plugin_name, $config, $log);
                $plugin->run();
                
            } catch(DownloadDispatcher_Exception_PluginException $e) {
                SihnonFramework_LogEntry::warning($log, $e->getMessage());
            }
        }
    }
    
}

?>
