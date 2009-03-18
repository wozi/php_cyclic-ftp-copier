<?php

class FileInfo {

	/**
	 *	getMimeType ()
	 *	Get a file's mime type
	 */
	public function getMimeType ($path) {
		/**
		*	If the file's local, we'll read it and search the mime type within it.
		*	If the file's distant, we'll assume a type for it.
		 */
		if (eregi ('^http://', $path)) {
			if (eregi ('(.jpg|.jpeg)$', $path)) return "image/jpeg";
			else if (eregi ('(.gif)$', $path)) return "image/gif";
			else if (eregi ('(.bmp)$', $path)) return "image/bitmap";
			else if (eregi ('(.png)$', $path)) return "image/png";
		} else {
			// @todo
			return 'todo';
		}
		//if (eregi ('(.jpg|.jpeg|.gif|.png|.bmp)$', $file)) return "Image";		//A changer en regex PERL et preg_match
	}
}
?>