<?php

class DownloadDispatcher_Exception_SourcePluginException     extends DownloadDispatcher_Exception {};
class DownloadDispatcher_Exception_PreviouslySeenContent     extends DownloadDispatcher_Exception_SourcePluginException {};
class DownloadDispatcher_Exception_UnidentifiedContent       extends DownloadDispatcher_Exception_SourcePluginException {};
class DownloadDispatcher_Exception_UnacceptableContent       extends DownloadDispatcher_Exception_SourcePluginException {};
class DownloadDispatcher_Exception_DuplicateContent          extends DownloadDispatcher_Exception_SourcePluginException {};
class DownloadDispatcher_Exception_UnprocesseableContent     extends DownloadDispatcher_Exception_SourcePluginException {}; 

?>