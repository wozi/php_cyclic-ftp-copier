<?php
/**
 *	Class Folder
 *	Create a Folder structure
 *	Folder can contain other folders and files
 */
class Folder {

	/**
	 *	properties
	 */
	var $path = NULL;
	
	/**
	 *	name
	 *	The name of the folder
	 */
	var $name = NULL;

	/**
	 *	tree
	 *	The complete tree of this folder
	 */
	var $tree = array ();

	/**
	 *	Constructor ()
	 *	Create a new file structure
	 */
	public function __construct ($path) {
//	echo "new Folder $path<br>";
		$this->path = $path;
		
		$this->name = dirname ($path);	// buggy in windows !
	}
	
	/**
	 *	add ()
	 *	Add a new file or Folder to the folder colelction
	 *	@param {File/Folder} file : a File/Folder object
	 */
	public function add ($file) {
		array_push ($this->tree, $file);
	}
	
	/**
	 *	getJSON ()
	 *	Convert a return the structure as a JSON structure
	 *	@return {String}
	 */
	public function getJSON () {
		return "{ path: '".$this->path."' }";
	}
}
?>