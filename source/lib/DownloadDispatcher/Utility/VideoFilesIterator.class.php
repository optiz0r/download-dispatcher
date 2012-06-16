<?php                                                                                                                                                                             
                                                                                                                                                                                  
class DownloadDispatcher_Utility_VideoFilesIterator extends FilterIterator {                                                                                                            
    public function accept() {
        $filename = $this->current()->getFilename();                              
        if (preg_match('/^sample/', $filename)) {
            return false;
        }

        return DownloadDispatcher_Utility_MediaFile::isVideoFile($filename);
    }                                                                                                                                                                             
}                                                                                                                                                                                 
                                                                                                                                                                                  
?>
