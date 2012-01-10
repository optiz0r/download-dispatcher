<?php                                                                                                                                                                             
                                                                                                                                                                                  
class DownloadDispatcher_Utility_MediaFilesIterator extends FilterIterator {                                                                                                            
    public function accept() {
        $filename = $this->current()->getFilename();                              
        if (preg_match('/^sample/', $filename)) {
            return false;
        }
        if (preg_match('/(?<!(?:\.|-)sample)\.(?:avi|ogm|m4v|mkv|mov|mp4|mpg|srt|rar)$/i', $filename)) {
            return true;
        }
    }                                                                                                                                                                             
}                                                                                                                                                                                 
                                                                                                                                                                                  
?>