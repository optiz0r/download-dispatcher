<?php

class DownloadDispatcher_Sync_Plugin_Rsync extends DownloadDispatcher_PluginBase implements DownloadDispatcher_Sync_IPlugin {
    
    /**
     * Name of this plugin
     *
     * @var string
     */
    const PLUGIN_NAME = 'Rsync';
    
    protected $config;
    protected $log;
    
    protected $instance;
    protected $options;
    protected $source;
    protected $destination;
    
    public static function create($config, $log, $instance) {
        return new self($config, $log, $instance);
    }
    
    protected function __construct($config, $log, $instance) {
        $this->config = $config;
        $this->log = $log;
        $this->instance = $instance;
        
        $this->options = $this->config->get("sync.Rsync.{$this->instance}.options");
        $this->source = $this->config->get("sync.Rsync.{$this->instance}.source");
        $this->destination = $this->config->get("sync.Rsync.{$this->instance}.destination");
    }
    
    public function run() {
        DownloadDispatcher_LogEntry::debug($this->log, "Running Rsync synchroniser: '{$this->instance}'");
        
        $command = "/usr/bin/rsync {$this->options} '{$this->source}' '{$this->destination}'";
        DownloadDispatcher_LogEntry::debug($this->log, "Running foreground task: {$command}");
        
        DownloadDispatcher_ForegroundTask::execute($command, null, null, null, array($this, 'output'), null, $this->instance);
    }
    
    public function output($identifier, $data) {
        DownloadDispatcher_LogEntry::debug($this->log, "{$identifier}: {$data}");
    }
}

?>