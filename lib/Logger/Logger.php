<?php
/**
 *	Class Logger
 *	Logger manages all log entry from the system. A log is something written into a file, so if you want to trace things out
 *	use the Trace instead!
 *
 *	The object can be accessed from all controllers from $this->_logger->info () / debug ()
 *	Or from anywhere using base::getLogger ()->info () / debug ()
 *	The difference is that the classname won't be valid if using base so the controller object should be used as possible.
 *
 *	@note We don't use the FS here but use our own file access functions to create no dependancy!
 */
 /* New line depends of your OS :
	- Windows : \r\n
	- UNIX : 	\n
	- Mac :	\r
*/
// if (Utils::getOS () == 'unix'))
define ("_NEWLINE", "\r\n");

class Logger {

	// our log files' path
	var $logPath = "E:\\FileCopier\\logs\\";		//"../log/";

	// max filesize for Info file, in bytes
	var $maxInfoFileLarge = 1000;
	
	// the name of the Info log file
	var $infoFileName = "info.log";

	// max filesize for Debug file, in bytes
	var $maxDebugFileLarge = 10000;
	
	// the name of the Debug log file
	var $debugFileName = "debug.log";
	
	// max filesize of log files
	var $max_log_file_size = 10000;


	/**
	 *	Constructor ()
	 */
	public function __construct () {
		// delete the log files (start from clean) if logs filesize is over the max file size
		// A AMELIORER PR GARDER L'ANCIEN ET RESTER SUR LE MAX !
		if (file_exists ($this->logPath.$this->infoFileName)) {
			if (filesize ($this->logPath.$this->infoFileName) > $this->max_log_file_size)
				unlink ($this->logPath.$this->infoFileName);
		}
		if (file_exists ($this->logPath.$this->debugFileName)) {
			if (filesize ($this->logPath.$this->infoFileName) > $this->max_log_file_size)
				unlink ($this->logPath.$this->debugFileName);
		}
	}
	
	/**
	 *	log ()
	 *	Log something into a specific log file
	 */
	public function log ($txt, $filename) {
		// create the info file's path
		$path = $this->logPath.$filename;
		
		// get the existing data first
		$data = $this->readData ($path);
		
		// add the new line
		// Add some information about the time (a sortir ds uen fction)
		$data = $data."[".date (DATE_RFC822)."] ".$txt._NEWLINE;
		
		// now write the data
		$this->writeData ($path, $data);
	}
	
	/**
	 *	info ()
	 */
	public function info ($txt) {
		// create the info file's path
		$path = $this->logPath.$this->infoFileName;
		
		// get the existing data first
		$data = $this->readData ($path);
		
		// add the new line
		// Add some information about the time (a sortir ds uen fction)
		$data = $data."[".date (DATE_RFC822)."] ".$txt._NEWLINE;
		
		// now write the data
		$this->writeData ($path, $data);
	}
	
	/**
	 *	addInfo ()
	 *	@alias info ()
	 */
	public function addInfo ($txt) {
		return $this->info ($txt);
	}
	
	/**
	 *	debug ()
	 */
	public function debug ($txt) {
		// create the info file's path
		$path = $this->logPath.$this->debugFileName;
		
		// get the existing data first
		$data = $this->readData ($path);
		
		// add the new line
		// Add some information about the time (a sortir ds uen fction)
		$data = $data."[".date (DATE_RFC822)."] ".$txt._NEWLINE;
		
		// now write the data
		$this->writeData ($path, $data);
	}
	
	/**
	 *	addDebug ()
	 *	@alias debug ()
	 */
	public function addDebug ($txt) {
		return $this->debug ($txt);
	}
	
	/**
	 *	readData ()
	 */
	public function readData ($path) {
		if (!file_exists ($path)) return '';	
		
		$fr = fopen ($path, 'r');
		
		// get each line
		$data = '';
		while (!feof ($fr)) {
			if (($data .= fread ($fr, 1024)) === FALSE) {  
				fclose ($fr);
				return ''; 
			}
		}
		
		return $data;
	}
	
	/**
	 *	writeData ()
	 */
	public function writeData ($path, $data) {
		if (($fw = fopen ($path, "w+")) === FALSE) {
			return false;
		}
		fwrite ($fw, $data);
		fclose ($fw);
	}
}
?>