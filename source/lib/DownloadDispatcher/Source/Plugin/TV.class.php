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
        DownloadDispatcher_LogEntry::debug($this->log, "Media file: '{$file}'.");
        
        try {
                
            // Check to see if this file has been handled previously
            if ($this->checkProcessed($dir . '/' . $file)) {
                throw new DownloadDispatcher_Exception_PreviouslySeenContent($file);
            }
            
            $full_output_dir = $this->identifyOutputDir($dir, $file);
            
            $this->checkDuplicates($full_output_dir, $file);
            
            $this->copyOutput($type, $dir, $file, $full_output_dir);
            
            $this->renameOutput($full_output_dir);
        
            // This file has been dealt with, so no need to look at it in subsequent operations
            $this->markProcessed($dir . '/' . $file);
            
        } catch (DownloadDispatcher_Exception_PreviouslySeenContent $e) {
            DownloadDispatcher_LogEntry::debug($this->log, "Skipping previously seen file '{$e->getMessage()}'.");
                        
        } catch (DownloadDispatcher_Exception_UnidentifiedContent $e) {
            DownloadDispatcher_LogEntry::warning($this->log, "TV output directory for '{$e->getMessage()}' could not be identified; you may need to create one.");
            
        } catch (DownloadDispatcher_Exception_UnacceptableContent $e) {
            DownloadDispatcher_LogEntry::warning($this->log, "Skipping '{$e->getMessage()}' due to dubious contents.");
            
            // Forget the download upstream so a new copy can be fetched
            $file = $e->getMessage();
            $this->forgetDownload($this->normalise($file), $this->season($file), $this->episode($file));
            
            // Mark this file as processed so that its not rechecked on every invocation
            $this->markProcessed($dir . '/' . $file);
            
        } catch (DownloadDispatcher_Exception_DuplicateContent $e) {
            DownloadDispatcher_LogEntry::info($this->log, "Skipping duplicate file '{$e->getMessage()}'.");
            
        } catch (DownloadDispatcher_Exception_UnprocesseableContent $e) {
            DownloadDispatcher_LogEntry::warning($this->log, "Failed to copy '{$e->getMessage()}' to the destination directory.");
            
        }
    }
    
    protected function identifyOutputDir($dir, $file, $try_parent = true) {
        if (is_null($this->output_dir_cache)) {
            $this->scanOutputDir();
        }
        
        $normalised_file = $this->normalise($file);
        if (array_key_exists($normalised_file, $this->output_dir_cache)) {
            $season = $this->season($file);
            if (!$season) {
                $season = $this->season($dir);
            }
            
            $full_output_dir = "{$this->output_dir}/{$this->output_dir_cache[$normalised_file]}/Season {$season}";
            
            if (is_dir($full_output_dir)) {
                return $full_output_dir;
            }
        }
        
        // Filename not recognised, try the parent directory name instead
        if ($try_parent) {
            try {
                return $this->identifyOutputDir(dirname($dir), basename($dir), false);
            } catch (DownloadDispatcher_Exception_UnidentifiedContent $e) {
                // Rethrow the exception for the original file, not its parent directory
                throw new DownloadDispatcher_Exception_UnidentifiedContent($file);
            }
        }
        
        throw new DownloadDispatcher_Exception_UnidentifiedContent($file);
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
        
        // Post-process in any manual directory mappings
        $this->output_dir_cache = array_merge($this->output_dir_cache, $this->config->get('sources.TV.output_series_mappings'), array());
    }
    
    protected function normalise($name) {
        $normalised_name = $name;
        if (preg_match('/(?:\[ www.[a-zA-Z0-9.]+ \] - )?(.*?)([\s.]+us)?([\s\.](19|20)\d{2})?[\s\.](\d+x\d+|s(?:eason ?)?\d+[.-_ ]?e(?:pisode ?)?\d+|\d{3,4}).*/i', $normalised_name, $matches)) {
            $normalised_name = $matches[1];
        }
        
        $normalised_name = preg_replace('/[^a-zA-Z0-9]/', ' ', $normalised_name);
        $normalised_name = preg_replace('/ +/', ' ', $normalised_name);
        $normalised_name = strtolower($normalised_name);
        $normalised_name = preg_replace('/season \d+( complete)?/', '', $normalised_name);
        $normalised_name = trim($normalised_name);

        DownloadDispatcher_LogEntry::debug($this->log, "Normalised '{$name}' to '{$normalised_name}'");
        return $normalised_name;
    }
    
    protected function season($name) {
        $set_season = function($a) {
            for ($i = 1, $l = count($a); $i < $l; ++$i) {
                if ($a[$i]) {
                    return ltrim($a[$i], '0');
                }
            }
            return null;
        };
        
        if (preg_match('/(\d+)\d{2}(?!\d|[\s\.](?:\d+x\d+|s\d+[._-]?ep?\d+))|(\d+)x\d+|s(?:season ?)?(\d+)e(?:pisode ?)?\d+|season (\d+)/i', $name, $matches)) {
            return $set_season($matches);
        } else {
            return 0;
        }
    }
    
    protected function episode($name) {
        $set_episode = function($a) {
            for ($i = 1, $l = count($a); $i < $l; ++$i) {
                if ($a[$i]) {
                    return ltrim($a[$i], '0');
                }
            }
            return null;
        };
        
        if (preg_match('/\d+(\d{2})(?!\d|[\s\.](?:\d+x\d+|s\d[._-]?+ep?\d+))|\d+x(\d+)|s(?:eason ?)?\d+e(?:pisode ?)?(\d+)|^(\d{1,2})/i', $name, $matches)) {
            return $set_episode($matches);
        } else {
            return 0;
        }
    }

    protected function filetype($file) {
        if (preg_match('/\.([^.]*)$/', $file, $matches)) {
            return $matches[1];
        }

        return null;
    }
    
    protected function checkDuplicates($dir, $file) {
        $episode = $this->episode($file);

        $iterator = new DownloadDispatcher_Utility_VideoFilesIterator(new DownloadDispatcher_Utility_VisibleFilesIterator(new DirectoryIterator($dir)));
        foreach ($iterator as /** @var SplFileInfo */ $existing_file) {
            $existing_episode = $this->episode($existing_file->getFilename());
            if ($existing_episode == $episode) {
                // Only reject duplicates for media files with the same extension, so we can keep meta data or high/low def copies
                if (DownloadDispatcher_Utility_MediaFile::isArchivefile($file) || $this->filetype($file) == $this->filetype($existing_file->getFilename())) {
                    throw new DownloadDispatcher_Exception_DuplicateContent($file);
                }
            }
        }
    }
    
    protected function copyOutput($type, $source_dir, $source_file, $destination_dir) {
        switch (strtolower($type)) {
            case 'rar': {
                DownloadDispatcher_LogEntry::info($this->log, "Unrarring '{$source_file}' into '{$destination_dir}'.");
                
                $safe_source_file = escapeshellarg("{$source_dir}/{$source_file}");
                
                $command = "/usr/bin/unrar lb -p- -sm8192 -y {$safe_source_file}";
                DownloadDispatcher_LogEntry::debug($this->log, "Checking archive contents of '{$source_file}' with command: {$command}");
                list ($code,$output,$error) = DownloadDispatcher_ForegroundTask::execute($command, $destination_dir);
                $files = explode("\n", $output);
                if (empty($files)) {
                    DownloadDispatcher_LogEntry::warning($this->log, "Unacceptable content inside rar archive: {$source_dir}/{$source_file} ({$file}) (no files >8k)");
                    throw new DownloadDispatcher_Exception_UnacceptableContent($source_file);
                }
                foreach ($files as $file) {
                    if (preg_match('/\.(rar|wmv|avi)$/', $file)) {
                        DownloadDispatcher_LogEntry::warning($this->log, "Unacceptable contents inside rar archive: {$source_dir}/{$source_file} ({$file})");
                        throw new DownloadDispatcher_Exception_UnacceptableContent($source_file);
                    }
                }

                $command = "/usr/bin/unrar e -p- -sm8192 -y {$safe_source_file}";
                DownloadDispatcher_LogEntry::debug($this->log, "Unrarring '{$source_file}' with command: {$command}");
                list ($code,$output,$error) = DownloadDispatcher_ForegroundTask::execute($command, $destination_dir);
                if ($code == 3) {
                    DownloadDispatcher_LogEntry::warning($this->log, "Rejecting password-protected rar archive: {$source_dir}/{$source_file}");
                    throw new DownloadDispatcher_Exception_UnacceptableContent($source_file);
                } else if ($code != 0) {
                    DownloadDispatcher_LogEntry::warning($this->log, "Failed to unrar '{$source_dir}/{$source_file}'.");
                    throw new DownloadDispatcher_Exception_UnprocesseableContent($source_file);
                }
            } break;
            
            case 'avi': {
                // Verify that the file isn't a fake
                $safe_source_file = escapeshellarg($source_file);
                $command = "file {$safe_source_file}";
                DownloadDispatcher_LogEntry::debug($this->log, "Verifying '{$source_file}' contents with command: {$command}");
                list($code, $output, $error) = DownloadDispatcher_ForegroundTask::execute($command, $source_dir);
                if ($code != 0) {
                    DownloadDispatcher_LogEntry::warning($this->log, "Failed to determine contents of '{$source_dir}/{$source_file}'.");
                    throw new DownloadDispatcher_Exception_UnprocesseableContent($source_file);
                }
                
                if (preg_match('/Microsoft ASF/', $output)) {
                    throw new DownloadDispatcher_Exception_UnacceptableContent($source_file);
                }
                
            } // continue into the next case
            default: {
                DownloadDispatcher_LogEntry::info($this->log, "Copying '{$source_file}' to '{$destination_dir}'.");
                $result = copy("{$source_dir}/{$source_file}", "{$destination_dir}/{$source_file}");
                if ( ! $result) {
                    DownloadDispatcher_LogEntry::warning($this->log, "Failed to copy '{$source_dir}/{$source_file}' to output directory '{$destination_dir}'.");
                    throw new DownloadDispatcher_Exception_UnprocesseableContent($source_file);
                }
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
        DownloadDispatcher_ForegroundTask::execute($command, $dir);
    }
    
    protected function forgetDownload($series, $season, $episode) {
        $base_url  = $this->config->get('sources.TV.flexget-url');
        $username = $this->config->get('sources.TV.flexget-username');
        $password = $this->config->get('sources.TV.flexget-password');
        
        // Pad series and episode numbers with leading zeroes for flexget
        $season = str_pad($season, 2, '0', STR_PAD_LEFT);
        $episode = str_pad($episode, 2, '0', STR_PAD_LEFT);

        $url = "{$base_url}execute/";
        $data = array(
            'options' => "--series-forget '{$series}' 's{$season}e{$episode}'",
            'submit' => 'Start Execution',
        );
        
        DownloadDispatcher_LogEntry::debug($this->log, "Sending flexget series-forget command to {$url} with options '{$data['options']}'.");
        
        $request = new HttpRequest($url, HTTP_METH_POST, array(
            'httpauth' => "{$username}:{$password}",
            'httpauthtype' => HTTP_AUTH_BASIC,
        ));
        $request->setPostFields($data);
        
        $response = $request->send();
        DownloadDispatcher_LogEntry::debug($this->log, "Response code: {$response->getResponseCode()}.");
        
        if ($response->getResponseCode() == 200) {
            $response_body = $response->getBody();
            if (preg_match('/Removed episode .* from series .*/', $response_body)) {
                DownloadDispatcher_LogEntry::info($this->log, "Successfully made flexget forget about {$series} s{$season}e{$episode}.");
            } else {
                DownloadDispatcher_LogEntry::warning($this->log, "Failed to make flexget forget about {$series} s{$season}e{$episode}.");
            }
        } else {
            DownloadDispatcher_LogEntry::warning($this->log, "Failed to communicate with flexget webui.");
        }
    }
    
}

?>
