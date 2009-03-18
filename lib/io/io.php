<?php
/**
 *	Class io
 *	Abstraction layer for IO accesses (local or distant)
 *	Provides read/write accesses, + cache and versionning capabilities
 *	@layer
 *	@version 0.1.0.20 - 28/12/2008
 *	Takes care of all IO actions
 */

require_once ('File.php');
require_once ('FileInfo.php');
require_once ('Folder.php');
 
/**
 *	CACHE_DIR
 *	Must be set to your CACHE directory. Make sure it is writeable.
 */
define ("CACHE_DIR", "cache/");

/**
 *	DISTANT_CACHE_EXCEED
 *	Set how many minutes we keep a cache for a distant access
 */
define ("DISTANT_CACHE_EXCEED", 30);

class IO {
	
	/**
	 *	read ()
	 *	Read a file or a folder
	 */
	public function read ($path) {
		if (function_exists ("resolvePath")) $path = resolvePath ($path);

		/**
		 *	Check if $path is an URL, in which case we will use readDistant instead.
		 */
		if (preg_match ('@^(?:http://)@i', $path))
			return Net::get ($path);
		
		// if it's a folder we'll read each files within it and return an array of files
		if (is_dir ($path)) {
			return self::readFolder ($path);
		
		} else if (file_exists ($path)) { 	// a changer par un File retourné !!
			$fr = fopen ($path, 'r');
			$data = fread ($fr, filesize ($path));
			fclose ($fr);
		
			return $data;
		} else
			return NULL;
	}
	
	/**
	 *	get ()
	 *	@alias read ()
	 */
	public function get ($path) {
		return self::read ($path);
	}
	
	/**
	 *	readFolder ()
	 *	Read a folder and all its sub folders
	 *	@param {String} path
	 *	@param {Int} max_deep : the number of max sub folders we go in. If null no limit.
	 *	@param {Int} i : the current folder's index
	 *	@param {Array} collection : the collection of pages. All new subfolders will be added to the collection with their names:
	 *	array (
			
	 *	);
	 *
	 *	@return {Folder} 
	 *	@recursive
	 */
	public function readFolder ($path) {
		// start a new folder
		$currentFolder = new Folder ($path);
		
		if (is_dir ($path)) {
			$res = opendir ($path);
			
			// browse each files
			while (($file = readdir ($res)) !== false) {
				// only takes files
				if ($file != '.' && $file != '..') {
					$fullPath = $path."\\".$file;
					
					// directories
					if (is_dir ($fullPath)) {
						// gotta browse deeper : create a new folder (will be made by the call)
						$currentFolder->add (self::readFolder ($fullPath));
					// files
					} else {
						$currentFolder->add (new File ($fullPath));
					}
				}
	        }
	        closedir ($res);
		}
		return $currentFolder;
	}
	
	
	/**
	 *	write ()
	 *	Write a file
	 */
	public function write ($filename, $content) {
	//	if (function_exists ("resolvePath")) $filename = resolvePath ($filename);
	
		if (($fw = fopen ($filename, "w+")) === FALSE) {
			return false;
		}
		fwrite ($fw, $content, strlen ($content));
		fclose ($fw);
		
		return true;
	}
	
	/**
	 *	move ()
	 *	Move a file/folder to a new path
	 *	
	 *	@param {Folder/File} folder : a File or a Folder
	 *	@param {String} target_path : the target path
	 *	@param {String} from_path : it's used to specify from where we need to keep the path (ex: d:\test\yo\file.txt, if = d:\test\ we'll keep yo\test.php)
	 *
	 *	@return {Int} the number of files moved
	 *	@recursive
	 */
	public function move ($folder, $target_path, $from_path=NULL) {
		// move each files / folders
		$moved = 0;
		
		foreach ($folder->tree as $file_folder) {
			// File
			if (get_class ($file_folder) == 'File') {
				// remove the path we don't need
				if ($from_path)
					$dest = $target_path.preg_replace ("/^".addslashes ($from_path)."/i", "", $file_folder->path);
				else
					$dest = $target_path."\\".$file_folder->path;
				
				echo "Move ".$file_folder->path." to $dest; ".dirname ($dest)."<br>";
				
				// create the folders if don't already exist
				if (!is_dir (dirname ($dest))) {
					$newdir = dirname (preg_replace ("/^".addslashes ($from_path)."/i", "", $file_folder->path));
			//		echo "Creating new dir is $newdir<br>";
					
					// we may be in a subdir that doesn't even exist, so we'll check them all!
					$dirs = explode ("\\", $newdir);
					$previous_dir = "";
					foreach ($dirs as $dir) {
						$dir_path = $target_path."\\".$previous_dir.$dir;
						
						if (!is_dir ($dir_path)) {
							echo "Create $dir_path<br>";
							mkdir ($dir_path);
						}
						$previous_dir .= $dir."\\";
					}
				}
				
				// chmod ($target_name, '777');
				if (@copy ($file_folder->path, $dest)) {
					// remove the file @note WE SHOULD ALSO REMOVE THE FOLDERS!!!
					unlink ($file_folder->path);
					$moved++;
				}
			
			// Folder: recursive
			} else if (get_class ($file_folder) == 'Folder') {
				// go browse that new folder and copy the files that are within it
				$moved += self::move ($file_folder, $target_path, $from_path);
			}
		}
		return $moved;
	}
	
	/**
	 *	mod_time ()
	 *	Get the last mod time of a file
	 *	@param {String} path
	 *	@return {String} the date, NULL if the file is not found
	 */
	public function mod_time ($path) {
		if (function_exists ("resolvePath")) $path = resolvePath ($path);

		if (file_exists ($path))
			return filemtime ($path);
		else
			return NULL;
	}
	
	/**
	 *	creation_time ()
	 *	Get the creation time of a file
	 *	@param {String} path
	 *	@return {String} the date, NULL if the file is not found
	 */
	public function creation_time ($path) {
		if (function_exists ("resolvePath")) $path = resolvePath ($path);

		if (file_exists ($path))
			// PAS BON !!!!!!! return la date de derniere modif !!!
			return filectime ($path);
		else
			return NULL;
	}
	
	/**
	 *	getPermissions ()
	 */
	public function getPermissions ($path) {
		if (function_exists ("resolvePath")) $path = resolvePath ($path);

		if (file_exists ($path))
			return fileperms ($path);
		else
			return NULL;
	}
	
	/**
 	 *	copy ()
 	 *	Copy a file to another location
 	 *	@param {String} source : the source path
 	 *	@param {String} dest : the dest path. If this is a dir, we'll use the same filename.
 	 *	@return {Boolean} if success or failed
 	 */
	public function copy ($source, $dest) {
		if (function_exists ("resolvePath")) $source = resolvePath ($source);
		if (function_exists ("resolvePath")) $dest = resolvePath ($dest);
		if (!file_exists ($source)) return false;
		
		/**
		 *	Get the file's content
		 */
		if (($file_content = self::read ($source)) === NULL) {
			echo "Copy: Failed reading source file content<br>";
			return false;
		}
		
		/**
 		 *	Now get the type of copy. If we have a filename for dest file, we'll use it. If not, we'll use the same name
 		 */
 		if (is_dir ($dest)) {
 			$dest .= basename ($source);
 		}
 		
 		/**
 		 *	Now write the file to this folder
 		 */
 		if (!self::write ($dest, $file_content)) return false;
 		
 		return true;
	}
	
	/**
	 *	CACHE *****************************************************
	 */
	
	/**
	 *	cache ()
	 *	Write a file into the cache.
	 *	It can be use to cache a file by doing a copy, or to cache some content so a new file will be created
	 *	@param {String} path : the path of the file to be cached
	 *	@param {String} file_content [] : if set, this content will be written into a file named with the given path
	 *	@return {Boolean} true if success, false if failed caching
	 *	
	 *	@ToDo : on devrait checker si la version en cache (si il y a) n'est pas déjà la dernière version
	 */
	public function cache ($path, $file_content=NULL) {
		if (function_exists ("resolvePath")) $path = resolvePath ($path);
		
		/**
		 *	Create the cache filename
		 */
		$cached_path = self::createCachedPath ($path);			
	
		/**
		 *	If file_content is not set, we'll make an exact copy of the given file in cache
		 */
		if (!$file_content) {
			if (!file_exists ($path)) return false;
			
			/**
			 *	Copy the file
			 */
			return self::copy ($path, $cached_path);
		
		/**
 		 *	If this is a content copy, we'll create a new file
 		 */
		} else {
			return self::write ($cached_path, $file_content);
		}
	}	
	
	/**
	 *	createCachedPath ()
	 *	Create a valid cache path
	 */
	public function createCachedPath ($path) {
		return CACHE_DIR.basename ($path).".cache";
	}
	
	/**
	 *	cacheUpToDate ()
	 *	Check if a file is up to date in the cache.
	 *	This will check the mod_time of the original file and the mod_time of the file we have
	 *	in our db
	 */	
	public function cacheUpToDate ($path) {
		if (function_exists ("resolvePath")) $path = resolvePath ($path);
		if (!file_exists ($path)) return false;
		
		if (self::mod_time ($path) < self::getCacheTime ($path)) return true;
		else return false;
	}
	
	/**
	 *	getCachePath ()
	 *	get the path in cache of a file that have been cached, from its original path
	 */
	public function getCachePath ($path) {
		return self::createCachedPath ($path);
	}
	
	/**
	 *	readCache ()
	 *	Get the content of a file that has been cached, from its original filename
	 */
	public function readCache ($path) {
		return self::read (self::createCachedPath ($path)); 
	}
	
	/**
	 *	getCacheTime ()
	 *	Get the time when a file has been cached, from its original path
	 */
	public function getCacheTime ($path) {
		return self::mod_time (CACHE_DIR.basename ($path).".cache");
	}
	
	/**
	 *	Filename Utils ********************************
	 */
	
	/**
	 *	createFileName ()
	 *	Create a valid filename
	 *	@param {String} filename : the filename to be converted. Can be a complete path.
	 *	@param {String} folder : the complete path to the file for access
	 *
	 *	@alpha
	 */
	private function createFileName ($filename, $folder) {
		//Control caracters
		$filename = fsLayer::sanitize ($filename);
		
		/**
		 *	Don't overwrite files. If filename already exists, we'll rename it
		 */
		if (file_exists ($folder.$filename)) $filename = self::createAlternativeFilename ($folder, $filename);
		
		return $filename;
	}
	
	/**
 	 *	sanitize ()
 	 *	Sanitize a filename
 	 *	@param {String} filename : the filename to sanitize
 	 */
	public function sanitize ($filename) {
		$filename = preg_replace ('/[,| ]/', '_', $filename);		//Replace , et espaces par _
		/** 
		 *	Attention! Le check sur les accents doit etre fait avant, sinon probleme d'encodage
		 */
		$filename = preg_replace ('/[é|è|ê|ë|Ã©]/', 'e', $filename);		//Replace les accents
		$filename = preg_replace ('/[à|â|ä]/', 'a', $filename);
		$filename = preg_replace ('/[ù|û]/', 'u', $filename);
		
		/**
		 *	Remove any . in the filename except for the extension
		 */
		$filename = preg_replace ('/^.[.]+\.'.self::getFileExtension ($filename).'$/', '_', $filename);
		
		return $filename;
	}
	
	/**
	 *	getFileExtension ()
	 *	Get the extention of a file
	 */
	public function getFileExtension ($filename) {
		$extension = array ();
		preg_match ('/^.+?\.([a-zA-Z0-9]+)$/', $filename, $extension);
		return $extension [1];
	}
	
	/**
	 *	createAlternativeFilename ()
	 *	Create an alternative filename, in case the file already exists
	  *
	 *	@alpha
	 */
	private function createAlternativeFilename ($folder, $filename, $i=1) {
		/**
		 *	The nalternative filename will be something like filename [1].jpg
		 *	@note : at this point, we should not have any . in the filename, except for the extension
		 */
		$file_exp = explode ('.', $filename);
		$alt_filename = $file_exp [0].' ['.$i.'].'.$file_exp [1];
		
		if (file_exists ($folder.$alt_filename)) return self::createAlternativeFilename ($folder, $filename, $i++);
		return $alt_filename;
	}
	
	/**
	 *	Mime Type Utils ********************************
	 */
	
	/**
	 *	getFileType ()
	 *	Get the file type of a file by its path
	 *	Could be improved a lot
	 *	@param {String} file_path : the file path to analyze
	 */
	public function getFileTypeByExtension ($file_path) {
		if (is_dir ($file_path)) return "Folder";
	
		/*
		if (eregi (".jpg", $file_path)) {				//TO IMPROVE WITH GOOD REGEX for other img extensions
				//Check the image flag in the image header to make sure we have a valid image
				$res = fopen ($file_path, 'r');
				$header .= fgets ($res, 16);
				fclose ($res);
				//if (substr ($header, 4, 8) != "JFIF")
				//	return "Unvalid Image (".substr ($header,4, 8).")";
				return $key;
		*/
		if (eregi ('(.jpg|.jpeg|.gif|.png|.bmp)$', $file_path)) return "Image";		//A changer en regex PERL et preg_match
		else if (eregi ('(.zip|.rar|.ace|.tar|.gz|.7zip)$', $file_path)) return "Archive";
		else if (eregi ('(.doc|.xls|.ppt)$', $file_path)) return "Office Document";
		else if (eregi ('(.pdf)$', $file_path)) return "PDF Document";
		else if (eregi ('(.htm|.html)$', $file_path)) return "Web Page"; 
		else if (eregi ('(.php|.php4|.php5)$', $file_path)) return "PHP Script";
		else if (eregi ('(.flv)$', $file_path)) return "FLV Video";
		else if (eregi ('(.mov)$', $file_path)) return "QuickTime Video";
		else if (eregi ('(.avi|.mpg|.mpeg|.xvid|.wmv)$', $file_path)) return "Video";
		else if (eregi ('(.exe)$', $file_path)) return "Windows Application";
		else if (eregi ('(.jar)$', $file_path)) return "Java Archive";
		else if (eregi ('(.conf|.yml)$', $file_path)) return "Config File";
		else if (eregi ('(.pgp)$', $file_path)) return "Crypt Key";
		else if (eregi ('(readme)$', $file_path)) return "Readme File";
		else if (eregi ('(.cpp)$', $file_path)) return "C++ Source File";
		else if (eregi ('(.h)$', $file_path)) return "Header Source File";
		else if (eregi ('(.css)$', $file_path)) return "Stylesheet";
		else if (eregi ('(.js)$', $file_path)) return "JavaScript Source File";
		else if (eregi ('(.xml)$', $file_path)) return "XML File";
		else if (eregi ('(.txt|.rtf)$', $file_path)) return "Text Document";
		else if (eregi ('(.mp2|.mp3|.ogg|.wav)$', $file_path)) return "Audio File";
		else if (eregi ('(.mid)$', $file_path)) return "Audio File";
		else if (eregi ('(.nfo)$', $file_path)) return "Information File";
		else if (eregi ('(.srt|.sub)$', $file_path)) return "Subtitles";

		return "Unknown";
	}
	
	/**
	 *	getFileType ()
	 *	Get the file type of a file
	 *	@param {String} file : the file to analyze
	 */
	public function getFileType ($file) {
		return self::getFileTypeByExtension ($file);
	
		if (eregi ('(.jpg|.jpeg|.gif|.png|.bmp)$', $file)) return "Image";		//A changer en regex PERL et preg_match
		else if (eregi ('(.zip|.rar|.ace|.tar|.gz)$', $file)) return "Archive";
		else if (eregi ('(.doc|.xls|.ppt)$', $file)) return "Office Document";
		else return "Unknown";
	}
	
	/**
	 *	getImageType ()
	 *	Get the type of an image.
	 *	We'll try first with exif if the library is loaded and with the extension if no exif found.
	 *	@param {String} file_path
	 *	@return {String} mimetype : something like image/jpeg
	 */
	public function getImageType ($file_path) {
		/**
		 *	Firt try with EXIF
		 */
		if (function_exists ('exif_imagetype')) {
			return image_type_to_mime_type (exif_imagetype ($file_path));
		} else {
			/**
 			 *	Try with the extension
 			 */
 			if (eregi ('(.jpg|.jpeg)$', $file_path)) return "image/jpeg";
 			else if (eregi ('(.gif)$', $file_path)) return "image/gif";
 			else if (eregi ('(.png)$', $file_path)) return "image/png";
 			else if (eregi ('(.swf|.swc)$', $file_path)) return "application/x-shockwave-flash";
 			else if (eregi ('(.psd)$', $file_path)) return "image/psd";
			else if (eregi ('(.bmp)$', $file_path)) return "image/bmp";
			else if (eregi ('(.tiff|tif)$', $file_path)) return "image/tiff";
			else if (eregi ('(.jpc)$', $file_path)) return "application/octet-stream";
			else if (eregi ('(.jp2)$', $file_path)) return "image/jp2";
			else if (eregi ('(.jpx)$', $file_path)) return "application/octet-stream";
			else if (eregi ('(.jb2)$', $file_path)) return "application/octet-stream";
			else if (eregi ('(.iff)$', $file_path)) return "image/iff";
			else if (eregi ('(.wbmp)$', $file_path)) return "image/vnd.wap.wbmp";
			else if (eregi ('(.xbm)$', $file_path)) return "image/xbm";
			else if (eregi ('(.ico)$', $file_path)) return "image/vnd.microsoft.icon";
			else return "unknown";
		}
	}
	
	/**
	 *	getMimeType ()
	 *	Get the mime type of a file
	 *	@param {String} file : the file to analyze
	 */
	public function getMimeType ($file) {
		//TO DO
		return "x/unknown";
	}
	
	/**
	 *	getHumanFileSize ()
	 *	Get a file size that can be displayed and understood
	 *	@param {String} file_path 
	 */
	public function getHumanFileSize ($file_path) {
		$size = self::getFileSize ($file_path);
		if ($size == 0) return 0;		
		
		$kb = round ($size / 1024, 2);
		if ($kb < 1000)
			return $kb." kb";
		else if ($kb < 1000000)
			return round ($kb / 1024, 2)." mb";
		else
			return round ($kb / 1000000, 2)." gb"; 
	}
	
	/**
 	 *	getFileSize ()
 	 *	Get the size of a file by its path
 	 *	@param {String} file_path
 	 *	@return {Int}
 	 */
	public function getFileSize ($file_path) {
		if (is_dir ($file_path)) return 0;
		return filesize ($file_path);
	}
	
	/**
	 *	file_size ()
	 *	@alias getFileSize ()
	 */
	public function file_size ($file_path) {
		return self::getFileSize ($file_path);
	}
}
?>