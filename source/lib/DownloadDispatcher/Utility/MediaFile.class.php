<?php

class DownloadDispatcher_Utility_MediaFile {

    public static function isMediaFile($filename) {
        return preg_match('/(?<!(?:\.|-)sample)\.(?:avi|ogm|m4v|mkv|mov|mp4|mpg|srt|smi|rar)$/i', $filename);
    }

    public static function isVideoFile($filename) {
        return preg_match('/(?<!(?:\.|-)sample)\.(?:avi|ogm|m4v|mkv|mov|mp4|mpg)$/i', $filename);
    }

    public static function isMetadataFile($filename) {
        return preg_match('/(?<!(?:\.|-)sample)\.(srt|smi)$/i', $filename);
    }

    public static function isArchiveFile($filename) {
        return preg_match('/(?<!(?:\.|-)sample)\.(rar)$/i', $filename);
    }

}


?>
