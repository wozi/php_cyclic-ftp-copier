<?php
/**
 *	Class FileCopier
 *	Manage the copy of files.
 *	It takes files from somewhere, copy them by FTP somewhere, and put them another folder.
 */

// path to the config file
define ('CONFIG_PATH', 'E:\FileCopier\config\jobs.yml');

class FileCopier {

	// our logger instance
	var $logger = NULL;

	public function __construct () {
		// create a logger instance
		$this->logger = new Logger ();
	}
	
	public function FileCopier () {
		return $this->__construct ();
	}

	/**
	 *	startProcess ()
	 *	Start a process
	 *
	 *	@param {String} the name of process, defined within the config file
	 */
	public function startProcess($process) {
		echo "Starting inventory for the process $process...";

		// get the config file
		if (($conf = $this->getConfig ()) === NULL) {
			echo "Can't load the config file for process $process, aborting!";
			
			// add a log entry
			$this->logger->info ("Can't load the config file for process $process, aborting!");
			return false;
		}
		
		// get the parameters
		if (($processConf = $conf->get ($process, 'jobs')) === NULL) {
			echo "Can't find the config entry for process $process, aborting!";
			$this->logger->info ("Can't find the config entry for process $process, aborting!");
			return false;
		}
		
		// get the list of files that need to be copied & moved
		$folder = $this->createInventory ($processConf ['from']);
		
		// copy the files by FTP
		if ($this->copyFTP ($processConf ['ftp_folder'], $folder,
									$processConf ['to'], $processConf ['user'], $processConf ['password'], $processConf ['from'])) {
			echo "Can't copy the files for process $process, aborting";
			$this->logger->info ("Can't copy the files for process $process, aborting!");
			return false;
		}
		
		// now move the files to the new folder
		if ($this->move ($folder, $processConf ['onfinished'], $processConf ['from'])) {
			echo "Can't move the files for process $process, aborting!";
			$this->logger->info ("Can't move the files for process $process, aborting!");
			return false;
		}
	}
	
	/**
	 *	createInventory ()
	 *	@param {String} from : a folder we need to monitor
	 *	@return {Array}
	 */
	public function createInventory ($from) {
		// get an array of files : [  File, File, File, ...]
		$filesSet = IO::read ($from);
		return $filesSet;
	}
	
	/**
	 *	copyFTP ()
	 *	Copy the stuff via FTP
	 *	@recursive
	 *	@note pour moi : la fction dirname ne marche qu'ne mode Unix avec des / ms ne fonctionne pas avec des \
	 */
	public function copyFTP ($ftp_folder, &$folder, $dest, $user, $password, $from_path, &$con=NULL, $recursive=false) {
		// open the connection
		if (!$con)  $con = Net::openFTP ($dest, $user, $password);		// = FTP::open ()
		
		// copy the files
		$success = 0;
		foreach ($folder->tree as $file_folder) {
			if (get_class ($file_folder) == 'File') {
				// remove the path we don't need
				$destination_file = $ftp_folder.preg_replace ("/^".addslashes ($from_path)."/i", "", $file_folder->path);
				// replace the window \ by /
				$destination_file = preg_replace ("/\\\/i", "/", $destination_file);
				
				// change the dir to root
			//	$con->changeDir ($ftp_folder);
			//	echo "copyFTP ".$file_folder->path." to $destination_file<br>";
				
				if (Net::copyFTP ($file_folder->path, $destination_file, $con))		// &, on donen un File car copyFTP sait les gérer
					$success++;
					
				// we log the file copied
				$this->logger->info ("File ".$file_folder->path." successfully copied to $destination_file");
				
			} else if (get_class ($file_folder) == 'Folder') {
				// go browse that new folder and copy the files that are within it
				$success += $this->copyFTP ($ftp_folder, $file_folder, $dest, $user, $password, $from_path, $con, true);
			}
		}
		
		// close the connection if last call
		if (!$recursive) Net::closeFTP ($con);
		
		// return the list of files in success or false if none copied	@to improve the success
		return $success;
	}
	
	/**
	 *	move ()
	 */
	public function move ($folder, $newdir, $from_path) {
		echo "Now moving files to the new dir $newdir<br>";
		
		// nothing special to here, IO can do it for us!
		$moved = IO::move ($folder, $newdir, $from_path);
		
		// delete the folder if empty (ou le faire apres move () pr supprimer ts les dirs du level 1)
		
		echo "$moved files moved!<br>";
	}
	
	/**
	 *	getConfig ()
	 */
	private function getConfig () {
		$conf = new Config (CONFIG_PATH);
		if ($conf->yaml) return $conf;
		else return NULL;
	}
}
?>