<?php                                                                                                                                                                             
                                                                                                                                                                                  
class DownloadDispatcher_Utility_MediaFilesIterator extends FilterIterator {                                                                                                            
    public function accept() {
        $filename = $this->current()->getFilename();                              
        if (preg_match('/^sample/', $filename)) {
            return false;
        }

        return DownloadDispatcher_Utility_MediaFile::isMediaFile($filename);
    }
}                                                                                                                                                                                 
                                                                                                                                                                                  
?>
