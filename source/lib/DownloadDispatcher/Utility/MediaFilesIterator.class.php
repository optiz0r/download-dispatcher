<?php                                                                                                                                                                             
                                                                                                                                                                                  
class DownloadDispatcher_Utility_MediaFilesIterator extends FilterIterator {                                                                                                            
    public function accept() {                                                                                                                                                    
        return preg_match('/(?<!\.sample)\.(?:avi|ogm|m4v|mkv|mov|mp4|mpg|srt|rar)$/i', $this->current()->getFilename());                                                                          
    }                                                                                                                                                                             
}                                                                                                                                                                                 
                                                                                                                                                                                  
?>