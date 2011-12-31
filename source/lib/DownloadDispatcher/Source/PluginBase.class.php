<?php

class DownloadDispatcher_Source_PluginBase extends DownloadDispatcher_PluginBase {
    
    static protected $cache;
    
    static protected $source_cache = null;
    static protected $source_cache_file = 'source_cache';
    static protected $cache_lifetime = 86400;
    
    protected function initSourceCache() {
        if ( ! static::$cache) {
            static::$cache = DownloadDispatcher_Main::instance()->cache();
        }
        
        if (is_null(static::$source_cache)) {
            try {
                static::$source_cache = static::$cache->fetch(static::$source_cache_file, static::$cache_lifetime);
            } catch (SihnonFramework_Exception_CacheObjectNotFound $e) {
                static::$source_cache = array();
            }
        }
        
        if ( ! array_key_exists(get_called_class(), static::$source_cache)) {
            static::$source_cache[get_called_class()] = array();
        } 
    }
    
    protected function markProcessed($file) {
        $this->initSourceCache();
        
        if ( ! in_array($file, static::$source_cache[get_called_class()])) {
            static::$source_cache[get_called_class()][] = $file;
        }
        
        static::$cache->store(static::$source_cache_file, static::$source_cache);
    }
    
    protected function checkProcessed($file) {
        $this->initSourceCache();
        
        return in_array($file, static::$source_cache[get_called_class()]);
    }
    
}

?>