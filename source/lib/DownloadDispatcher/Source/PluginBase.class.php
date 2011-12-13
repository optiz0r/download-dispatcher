<?php

class DownloadDispatcher_Source_PluginBase extends DownloadDispatcher_PluginBase {
    
    static protected $source_cache = array();
    
    protected function init_cache() {
        if ( ! array_key_exists(get_called_class(), static::$source_cache)) {
            // TODO - attempt to load data from persistent storage
            static::$source_cache[get_called_class()] = array();
        } 
    }
    
    protected function mark_processed($file) {
        $this->init_cache();
        
        if ( ! in_array($file, static::$source_cache[get_called_class()])) {
            static::$source_cache[get_called_class()][] = $file;
        }
        
        // TODO - flush cache to persistent storage
    }
    
    protected function check_processed($file) {
        $this->init_cache();
        
        return in_array($file, static::$source_cache[get_called_class()]);
    }
    
}

?>