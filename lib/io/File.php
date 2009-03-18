<?php
/**
 *	Class File
 *	Create a file structure
 */
class File {

	/**
	 *	properties
	 */
	var $path = NULL;
	var $name = NULL;
	var $type = NULL;
	var $mimetype = NULL;
	var $size = NULL;
	var $perms = NULL;
	var $properties = NULL;
	var $creationdate = NULL;
	var $lastmoddate = NULL;
	var $extension = NULL;
	var $dimensions = NULL;
	var $thumbnail = NULL;
	

	/**
	 *	Constructor ()
	 *	Create a new file structure
	 */
	public function __construct ($path, $tmb=NULL) {
//	echo "new File $path<br>";
	
		$this->path = $path;
		$this->name = basename ($path);
		
		$this->type = IO::getFileType ($path);
		$this->mimetype = IO::getMimeType ($path);
		$this->size = IO::getFileSize ($path);
		$this->perms = IO::getPermissions ($path);
	//	$this->properties = FileUtils::getProperties ($path);
		$this->creationdate = IO::creation_time ($path);
		$this->lastmoddate = IO::mod_time ($path);
		$this->extension = IO::getFileExtension ($path);
		
		$this->thumbnail = $tmb;
	}
	
	/**
	 *	getJSON ()
	 *	Convert a return the structure as a JSON structure
	 *	@return {String}
	 */
	public function getJSON () {
		return "{ name: '".$this->name."', type: '".$this->type."', size: '".$this->size."', perms: '".$this->perms."', properties: '".
						$this->properties."', creatime: '".$this->creationdate."', lastmod: '".$this->lastmoddate.
						"', thumbnail: '".$this->thumbnail."' }";
	}
}
?>