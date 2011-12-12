<?php

class DownloadDispatcher_Source_Plugin_RouterboardFirmware extends DownloadDispatcher_PluginBase implements DownloadDispatcher_Source_IPlugin {
    
    /**
     * Name of this plugin
     *
     * @var string
     */
    const PLUGIN_NAME = "RouterboardFirmware";
    
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
        DownloadDispatcher_LogEntry::debug($this->log, 'Running RouterboardFirmware dispatcher');
        
        // Iterate over source directories, and move matched files to the output directory
        $source_dirs = $this->config->get('sources.RouterboardFirmware.input-directories');
        foreach ($source_dirs as $dir) {
            if (is_dir($dir) && is_readable($dir)) {
                $this->process_dir($dir);
            } else {
                DownloadDispatcher_LogEntry::warning($this->log, "RouterboardFirmware input directory '{$dir}' does not exist or cannot be read.");
            }
        }
    }
    
    protected function process_dir($dir) {
        // TODO - Iterate over the contents of the directory, process files and recurse deeper
    }
    
    protected function process_matched_file($dir, $file) {
        // TODO - Handle movement of the matched file to the correct output directory
        //      Handle direct media files, and also RAR archives
    }
    
    protected function identify_output_dir($dir, $file) {
        // TODO - Generate the correct output directory, apply any special case mappings, and ensure the destination exists
    }
    
    protected function identify_duplicate($dir, $file) {
        // TODO - Verify that the file we've found hasn't already been processed
        //        Use the cache to reduce processing overhead
    }
    
    protected function rename_output($dir, $file) {
        // TODO - use tvrenamer to update the filenames
    }

    
}

?>