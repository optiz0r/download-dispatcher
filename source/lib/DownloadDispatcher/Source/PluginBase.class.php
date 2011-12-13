<?php

class DownloadDispatcher_Source_PluginBase extends DownloadDispatcher_PluginBase {
    
    static protected $cache;
    
    static protected $source_cache = null;
    static protected $cache_file = 'source_cache';
    static protected $cache_lifetime = 86400;
    
    protected function init_cache() {
        if ( ! static::$cache) {
            static::$cache = DownloadDispatcher_Main::instance()->cache();
        }
        
        if (is_null(static::$source_cache)) {
            try {
                static::$source_cache = static::$cache->fetch(static::$cache_file, static::$cache_lifetime);
            } catch (SihnonFramework_Exception_CacheObjectNotFound $e) {
                static::$source_cache = array();
            }
        }
        
        if ( ! array_key_exists(get_called_class(), static::$source_cache)) {
            static::$source_cache[get_called_class()] = array();
        } 
    }
    
    protected function mark_processed($file) {
        $this->init_cache();
        
        if ( ! in_array($file, static::$source_cache[get_called_class()])) {
            static::$source_cache[get_called_class()][] = $file;
        }
        
        static::$cache->store($cache_file, static::$source_cache);
    }
    
    protected function check_processed($file) {
        $this->init_cache();
        
        return in_array($file, static::$source_cache[get_called_class()]);
    }
    
}

?>