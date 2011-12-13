<?php

class DownloadDispatcher_Source_Plugin_TV extends DownloadDispatcher_Source_PluginBase implements DownloadDispatcher_Source_IPlugin {
    
    /**
     * Name of this plugin
     *
     * @var string
     */
    const PLUGIN_NAME = "TV";
    
    protected $config;
    protected $log;
    
    public static function create($config, $log) {
        return new self($config, $log);
    }
    
    protected function __construct($config, $log) {
        $this->config = $config;
        $this->log = $log;
    }
    
    public function run() {
        DownloadDispatcher_LogEntry::debug($this->log, 'Running TV dispatcher');
        
        // Iterate over source directories, and move matched files to the output directory
        $source_dirs = $this->config->get('sources.TV.input-directories');
        foreach ($source_dirs as $dir) {
            if (is_dir($dir) && is_readable($dir)) {
                $iterator = new DownloadDispatcher_Utility_MediaFilesIterator(new DownloadDispatcher_Utility_VisibleFilesIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir))));
                foreach ($iterator as /** @var SplFileInfo */ $file) {
                    $this->process_matched_file($file->getPath(), $file->getFilename());
                }
            } else {
                DownloadDispatcher_LogEntry::warning($this->log, "TV input directory '{$dir}' does not exist or cannot be read.");
            }
        }
    }
        
    protected function process_matched_file($dir, $file) {
        // TODO - Handle movement of the matched file to the correct output directory
        //      Handle direct media files, and also RAR archives
        DownloadDispatcher_LogEntry::debug($this->log, "Media file: {$file}");
                
        // Check to see if this file has been handled previously
        if ($this->check_processed($dir . '/' . $file)) {
            DownloadDispatcher_LogEntry::debug($this->log, "Skipping previously seen file");
            return;
        }
        
    }
    
    protected function identify_output_dir($dir, $file) {
        // TODO - Generate the correct output directory, apply any special case mappings, and ensure the destination exists
    }
    
    protected function identify_duplicate($dir, $file) {
        // TODO - Verify that the file we've found hasn't already been processed
        //        Use the cache to reduce processing overhead
        // TODO - Upstream caching
    }
    
    protected function rename_output($dir, $file) {
        // TODO - use tvrenamer to update the filenames
    }
    
}

?>