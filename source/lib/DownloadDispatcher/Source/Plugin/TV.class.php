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
    
    protected $output_dir_cache;
    
    protected $input_dirs;
    protected $output_dir;
    
    public static function create($config, $log) {
        return new self($config, $log);
    }
    
    protected function __construct($config, $log) {
        $this->config = $config;
        $this->log = $log;
        
        $this->input_dirs = $this->config->get('sources.TV.input');
        $this->output_dir = $this->config->get('sources.TV.output');
    }
    
    public function run() {
        DownloadDispatcher_LogEntry::debug($this->log, 'Running TV dispatcher');
        
        // Iterate over source directories, and move matched files to the output directory
        foreach ($this->input_dirs as $dir) {
            if (is_dir($dir) && is_readable($dir)) {
                $iterator = new DownloadDispatcher_Utility_MediaFilesIterator(new DownloadDispatcher_Utility_VisibleFilesIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir))));
                foreach ($iterator as /** @var SplFileInfo */ $file) {
                    $this->processMatchedFile($file->getPath(), $file->getFilename(), $file->getExtension());
                }
            } else {
                DownloadDispatcher_LogEntry::warning($this->log, "TV input directory '{$dir}' does not exist or cannot be read.");
            }
        }
    }
        
    protected function processMatchedFile($dir, $file, $type) {
        // TODO - Handle movement of the matched file to the correct output directory
        //      Handle direct media files, and also RAR archives
        DownloadDispatcher_LogEntry::debug($this->log, "Media file: {$file}");
                
        // Check to see if this file has been handled previously
        if ($this->checkProcessed($dir . '/' . $file)) {
            DownloadDispatcher_LogEntry::debug($this->log, "Skipping previously seen file");
            return;
        }
        
        $full_output_dir = $this->identifyOutputDir($dir, $file);
        if ($full_output_dir) {
            if ($this->noDuplicates($full_output_dir, $file)) {
                if ($this->copyOutput($type, $dir, $file, $full_output_dir)) {
                    $this->renameOutput($full_output_dir);
                }
            }
        }
        
    }
    
    protected function identifyOutputDir($dir, $file) {
        // TODO - Generate the correct output directory, apply any special case mappings, and ensure the destination exists
        if (is_null($this->output_dir_cache)) {
            $this->scanOutputDir();
        }
        
        $normalised_file = $this->normalise($file);
        if (array_key_exists($normalised_file, $this->output_dir_cache)) {
            $season = $this->season($file);
            
            $full_output_dir = "{$this->output_dir}/{$this->output_dir_cache[$normalised_file]}/Season {$season}";
            
            if (is_dir($full_output_dir)) {
                return $full_output_dir;
            }
        }
        
        DownloadDispatcher_LogEntry::warning($this->log, "TV output directory for '{$file}' could not be identified; you may need to create one.");
        return null;
    }
    
    protected function scanOutputDir() {
        // Get a list of the series and season directories available in normalised form
        DownloadDispatcher_LogEntry::debug($this->log, "Scanning TV output directory ({$this->output_dir})");
        $this->output_dir_cache = array();
        
        $series_iterator = new DownloadDispatcher_Utility_VisibleFilesIterator(new DirectoryIterator($this->output_dir));
        foreach ($series_iterator as $series) {
            $series_name = $series->getBasename();
            $normalised_series = $this->normalise($series_name);
            $this->output_dir_cache[$normalised_series] = $series_name;
        }
        
    }
    
    protected function normalise($name) {
        if (preg_match('/(.*?)([\s\.]us)?([\s\.]+(19|20)\d{2})?[\s\.]+(\d+x\d+|s\d+e\d+|\d{3}).*/i', $name, $matches)) {
            $name = $matches[1];
        }
        
        $name = preg_replace('/[^a-zA-Z0-9]/', ' ', $name);
        $name = preg_replace('/ +/', ' ', $name);
        $name = strtolower($name);
        $name = trim($name);
        
        return $name;
    }
    
    protected function season($name) {
        $set_season = function($a) {
            for ($i = 1, $l = count($a); $i < $l; ++$i) {
                if ($a[$i]) {
                    return trim($a[$i], '0');
                }
            }
            return null;
        };
        
        if (preg_match('/(\d+)x\d+|s(\d+)e\d+|(?:(?:19|20)\d{2}[\s\.]+)?(\d+)\d{2}/i', $name, $matches)) {
            return $set_season($matches);
        } else {
            return 0;
        }
    }
    
    protected function episode($name) {
        $set_episode = function($a) {
            for ($i = 1, $l = count($a); $i < $l; ++$i) {
                if ($a[$i]) {
                    return trim($a[$i], '0');
                }
            }
            return null;
        };
        
        if (preg_match('/\d+x(\d+)|s\d+e(\d+)|(?:(?:19|20)\d{2}[\s\.]+)?\d+(\d{2})/i', $name, $matches)) {
            return $set_episode($matches);
        } else {
            return 0;
        }
    }
    
    protected function noDuplicates($dir, $file) {
        $episode = $this->episode($file);
        
        $iterator = new DownloadDispatcher_Utility_MediaFilesIterator(new DownloadDispatcher_Utility_VisibleFilesIterator(new DirectoryIterator($dir)));
        foreach ($iterator as /** @var SplFileInfo */ $existing_file) {
            $existing_episode = $this->episode($existing_file->getFilename());
            if ($existing_episode == $episode) {
                return false;
            }
        }
        
        return true;
    }
    
    protected function copyOutput($type, $source_dir, $source_file, $destination_dir) {
        switch (strtolower($type)) {
            case 'rar': {
                DownloadDispatcher_LogEntry::info($this->log, "Unrarring '{$source_file}' into '{$destination_dir}'.");
                
                $command = "/usr/bin/unrar e -p- -sm8192 -y {$source_dir}/{$source_file}";
                DownloadDispatcher_LogEntry::debug($this->log, "Unrarring '{$source_file}' with command: {$command}");
                
                list($code, $output, $error) = DownloadDispatcher_ForegroundTask::execute($command, $destination_dir);
            } break;
            
            case 'avi': {
                // Verify that the file isn't a fake
                $safe_source_file = escapeshellarg($source_file);
                $command = "file {$safe_source_file}";
                DownloadDispatcher_LogEntry::debug($this->log, "Verifying '{$source_file}' contents with command: {$command}");
                list($code, $output, $error) = DownloadDispatcher_ForegroundTask::execute($command, $source_dir);
                var_dump($code, $output, $error);
                
                if (preg_match('/Microsoft ASF/', $output)) {
                    DownloadDispatcher_LogEntry::warning($this->log, "Skipping '{$source_dir}/{$source_file}' due to dubious contents.");
                    return false;
                }
                
            } // continue into the next case
            default: {
                DownloadDispatcher_LogEntry::info($this->log, "Copying '{$source_file}' to '{$destination_dir}'.");
                copy("{$source_dir}/${source_file}", "{$destination_dir}/{$source_file}");
            }
        }
        
        return true;
    }
    
    protected function renameOutput($dir) {
        $cwd = getcwd();
        
        $command = <<<EOSH
        {$cwd}/3rdparty/tvrenamer/tvrenamer.pl \
            --include_series \
            --nogroup \
            --pad=2 \
            --scheme=XxYY \
            --preproc='s/x264//;' \
            --postproc='s/(?:-+img|-+a).*(\.[a-zA-Z0-9]+$)/\1/;' \
            --unattended \
            --dubious \
            --cache
EOSH;

        DownloadDispatcher_LogEntry::debug($this->log, "Executing tvrenamer command in '{$dir}': {$command}");
        list($code, $output, $error) = DownloadDispatcher_ForegroundTask::execute($command, $dir);
        var_dump($code, $output, $error);
    }
    
}

?>