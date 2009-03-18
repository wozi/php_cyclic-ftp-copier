<?php
/**
 *	Class FTP
 *	Manage FTP transfers
 *	@author Nicolas Wozniak
 *	@version 1.0.0.1 - 14/02/2009
 */
class FTP {
	
	/**
	 *	open ()
	 *	Open an FTP connection
	 *	@return {FTPConnection}
	 */
	public function open ($addr, $user, $password, $port=21) {
		echo "FTP open on $addr:$user@$password on $port";
		
		$con = new FTPConnection ($addr, $user, $password, $port);
		return $con;
	}
	
	/**
	 *	copy ()
	 *	Copy a file to an FTP connection.
	 *	@not eThe Connection has to be established in prior of copying files!
	 *	@note Copy will copy the file within the current folder! You need to change it before calling copy if necessary!
	 */
	public function copy ($source_file, $destination_file, &$con) {
		// we handle a standard vera File object
		if (get_class ($source_file) == 'File') {
			echo "FTP: Copying a File instance: ".$source_file->path."<br>";
			
			$con->put ($destination_file, $source_file->path);
		// String assumed
		} else {
			$con->put ($destination_file, $source_file);
		}
	}
	
	/**
	 *	close ()
	 *	Close the DTP connection
	 */
	public function close (&$con) {
		$con->close ();
	}
}

/**
 *	Class FTPConnection
 *	@version 1.0.0.1 -- 14/02/2009
 *	@author Nicolas Wozniak
 */
class FTPConnection {

	// our FTP resource
	var $conn_id = NULL;
	
	// login informations
	var $ftp_server = NULL;
	var $user = NULL;
	var $password = NULL;
	var $port = NULL;
	
	// the curren tdirectory
	var $current_dir = NULL;
	
	// flag to know if we are in or not
	var $connected = false;
	
	/**
	 *	Constructor ()
	 */
	public function __construct ($ftp_server, $user, $password, $port=21) {
		$this->ftp_server = $ftp_server;
		$this->user = $user;
		$this->password = $password;
		$this->port = $port;
		
		// connect
		if ($this->connect ()) $this->connected = true;
	}
	
	/**
	 *	connect ()
	 */
	public function connect () {
		// open the connection
		if (!($this->conn_id = ftp_connect ($this->ftp_server))) return false;
		
		// login
		if (!(ftp_login ($this->conn_id, $this->user, $this->password))) return false;
		
		// set the flag on fire!
		$this->connected = true;
		return true;
	}
	
	/**
	 *	close ()
	 */
	public function close () {
		if ($this->conn_id && $this->connected) {
			ftp_close ($this->conn_id);
			$this->connected = false;
		}
	}
	
	/**
	 *	put ()
	 *	Upload a file to the FTP server
	 */
	public function put ($destination_file, $source_file) {
	echo "Put $destination_file from $source_file...<br>";
	
		if ($this->conn_id && $this->connected) {
			// check if the folder exists or create it!
			$dir = dirname ($destination_file);
			if (!$this->changeDir ($dir)) {
				$this->makeDir ($dir);
			}
		
			if (ftp_put ($this->conn_id, $destination_file, $source_file, FTP_BINARY)) {
				echo " successful!<br>";
				return true;
			} else
				return false;
		}
	}
	
	/**
	 *	get ()
	 *	Download a file from the FTP server
	 *	This function writes the downloaded content to a file, it doens't return it!
	 */
	public function get ($local_file, $server_file) {
		if ($this->conn_id && $this->connected) {
			if (ftp_get ($this->conn_id, $local_file, $server_file, FTP_BINARY)) {
				return true;
			} else
				return false;
		}
	}
	
	/**
	 *	delete ()
	 *	Delete a file
	 *	@param {String} file : the file's path (test/truc.txt)
	 */
	public function delete ($file) {
		if ($this->conn_id && $this->connected) {
			return ftp_delete ($this->conn_id, $file);
		}
		return NULL;
	}
	
	/**
	 *	getCurrentDir ()
	 */
	public function getCurrentDir () {
		if ($this->conn_id && $this->connected) {
			return ftp_pwd ($this->conn_id);
		}
		return NULL;
	}
	
	/**
	 *	changeDir ()
	 *	Modify the current directory's name
	 */
	public function changeDir ($new_dirname) {
		if ($this->conn_id && $this->connected) {
			//echo "Changing dir to $new_dirname<br>";
			return @ftp_chdir ($this->conn_id, $new_dirname);
		}
		return NULL;
	}
	
	/**
	 *	makeDir ()
	 *	Create a new directory
	 */
	public function makeDir ($dir) {
		if ($this->conn_id && $this->connected) {
			// browse into all dirs from top to bottom and create each one of them if necessary
			$dirs = explode ("/", $dir);
			$path = '';
			foreach ($dirs as $single_dir) {
				// add the dir to the new path for next turn
				$path .= $single_dir."/";
					
				// check if the dir exists, if not create it
				if (!$this->changeDir ($path)) {
					echo "Creating new dir $path<br>";
					if (!ftp_mkdir ($this->conn_id, $path)) return false;
				}
			}
		}
		return true;
	}
}
?>