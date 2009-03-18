<?php
/**
 *	Package Net
 *	Provide with basic Net functions
 */
 
 // we can define if we want to test that the proxy's ok before using it. If not, we'll try without it. 
define ('TEST_PROXY_BEFORE_USE', true);

class Net {

	/**
	 *	read ()
	 *	@alias get ()
	 */
	public function read ($path, $options=NULL) {	
		return self::get ($path, $options);
	}
	
	/**
	 *	get ()
	 *	Get the content of a distant file. Proxy is implemented.
	 *	@note Never use directly this function within a controller, lib, etc but use getContent () instead.
	 *	@param {String} path : the path to the file.
	 *	@param {Array} options : some options like proxy info etc
	 */
	public function get ($path, $options=NULL) {	
		// set the Net config from global configuration
		$options ['net'] = base::getConfig ()->get ('net');
		
		// Look into a specific file at root and overwrite global config. Disabled, uncomment for activation!
		/*if (file_exists ("app.yml")) {
			$conf = new Config ('app.yml');
			$options ['net'] = $conf->get ('net');
		}*/
	
		// use Curl if available
		if (function_exists ('curl_init')) {
			$ch = curl_init ($path);
			
			// proxy
			if (!empty ($options ['net'] ['proxy_address'])) {
				if (TEST_PROXY_BEFORE_USE) {
					if (self::ping ($options ['net'] ['proxy_address'], $options ['net'] ['proxy_port']) > 0) {
						curl_setopt ($ch, CURLOPT_PROXY, $options ['net'] ['proxy_address']);
						curl_setopt ($ch, CURLOPT_PROXYPORT, $options ['net'] ['proxy_port']);
						curl_setopt ($ch, CURLOPT_PROXYAUTH, $options ['net'] ['proxy_username']);
						curl_setopt ($ch, CURLOPT_PROXYUSERPWD, $options ['net'] ['proxy_password']);		
					} else
						base::getLogger ()->debug ("Proxy does not respond, going without it!");
						// we should send an alert, asking weither we should disable it to avoid bad performance for next requests 
				}
						
			}
			curl_setopt ($ch, CURLOPT_HEADER, 0);
			
			// options
			if ($options) {
				// post variables are in ['post'] as 'x=v&y=v'
				if (isset ($options ['post'])) {
					curl_setopt ($ch, CURLOPT_POST, 1);
					curl_setopt ($ch, CURLOPT_POSTFIELDS, $options ['post']);
				}
				
				// timeout
				if (isset ($options ['net'] ['timeout'])) curl_setopt ($ch, CURLOPT_TIMEOUT, $options ['net'] ['timeout']); 
			}
			curl_setopt ($ch, CURLOPT_FOLLOWLOCATION  ,1);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER  ,1); 
			
			// exec
			$output = curl_exec ($ch);
			curl_close ($ch);
	//		echo "output: $output<br>";
			return $output;
		
		} else {
			/**
			 *	If no Curl, use a socket connection
			 */
			return Net::getSocket ($path, &$options);
		}
	}
	
	/**
	 *	getSocket ()
	*	Distant connection using a socket.
	*	This function should not be called directly, use read () instead, so Curl will be used if installed.
	*
	*	@param {String} path
	*	@param {Array} options
	 */
	public function getSocket ($path, $options=NULL) {
	echo "getSocket $path<br>";
	
		// set the Net config if a config file is found
		if (file_exists ("app.yml")) {
			$conf = new Config ('app.yml');
			$options ['net'] = $conf->get ('net');
		}
	
		preg_match ('@^(?:http://)?([^/]+)@i', $path, $matches);
		$host = $matches [1];

		// Check if we use a proxy within the config
		
		if (isset ($options ['net'] ['proxy_address']) && TEST_PROXY_BEFORE_USE
					&& self::ping ($options ['net'] ['proxy_address'], $options ['net'] ['proxy_port']) > 0) {
			$fp = fsockopen ($options ['net'] ['proxy_address'], $options ['net'] ['proxy_port'], 
									$errno, $errstr, $options ['net'] ['timeout']);
			if (!$fp) return false;
			$get = $path;
					
			$out = "GET ".$get." HTTP/1.0\r\n";
			$out .= "Host: ".$options ['net'] ['proxy_address']."\r\n";
			if (!empty ($options ['net'] ['proxy_username']) && !empty ($options ['net'] ['proxy_pass']))		//Gere l'autentification
				$out .= "Proxy-Authorization: Basic ". base64_encode ($options ['net'] ['proxy_username'].":".$options ['net'] ['proxy_pass'])."\r\n"; 
					
			if (function_exists ('gzinflate')) 
				$out .= "Accept-Encoding: gzip,deflate\r\n";
			$out .= "Connection: Close\r\n\r\n";
				
		/**
 		 *	No proxy
 		 */
		} else {	 
			/**
			 *	get the file's name
			 */
			$uri = strtr (strval ($path), 
							array ("http://" => "", "https://" => "ssl://", "ssl://" => "ssl://", "\\" => "/", "//" => "/"));
        	if (($protocol = stripos ($uri, "://")) !== FALSE) {
	         	if (($domain_pos = stripos ($uri, "/", ($protocol + 3))) !== FALSE)
	              	$file = substr ($uri, $domain_pos);
	         	else
	              	$file = "/";
	         } else {
	         	if (($domain_pos = stripos ($uri, "/")) !== FALSE)
	              	$file = substr ($uri, $domain_pos);
	          	else 
	              	$file = "/";
	       	}
			$fp = fsockopen ($host, 80, $errno, $errstr, $options ['net'] ['timeout']);
			if (!$fp) return false;
				
			$out = "GET ".$file." HTTP/1.0\r\n";
			$out .= "Host: ".$host."\r\n";

			//if (function_exists ('gzinflate')) 
			//	$out .= "Accept-Encoding: gzip,deflate\r\n";
			$out .= "Connection: Close\r\n\r\n";
		}
		
		fwrite ($fp, $out);	
		$response = "";
		$info = stream_get_meta_data ($fp);
			
	    while (!feof ($fp)) {
	      	$response .= fgets ($fp, 128);
	      	$info = stream_get_meta_data ($fp);
	    }
	    fclose ($fp);	
			
		/**
		 *	data
		 *	Will contain the data we're interested in, without the HTTP headers
		 */
		$data = '';
		if (!$info ['timed_out']) {
	//	echo "response is ".$response."<br>";			
			
			if (stripos ($response, "\r\n\r\n") !== FALSE) {
				$hc = explode ("\r\n\r\n", $response);
	         	 
				/**
				*	Do we need to uncompress the data if it has been zipped?
				*	Maybe there is a header that says this is compressed:
				*	if (stripos (hc [0], 'gzip')) {
				*		if (substr ($data, 0, 3) == "\x1f\x8b\x08")		//Check seen on http://fr3.php.net/gzinflate
				*			$data = gzinflate ($data);
				*	}
				*/
				$data = '';
				for ($i = 1; $i < sizeof ($hc); $i++)
					$data .= $hc [$i];
	            
			} else if (stripos ($response, "\r\n") !== FALSE) {
				$hc = explode ("\r\n",  $response);
				$data = $hc [sizeof ($hc)-1];
			} else
				$data = $response;
		}
		return $data;
	}
	
	/**
	 *	ping ()
	 *	@param {String} addr : an IP address to test
	 *	@param {Int} port
	 *	@return {Int} the time of ping. -1 if no answer
	 */
	public function ping ($addr, $port=80) {
		if (!$addr) return NULL;
		
		// set the max time to wait for answer
		$ping_timeout = 0.5;
		
		// should start timer! 
		$time_spent = 1;
		
		// go open the connection 
		$fp = @fsockopen ($addr, $port, $errno, $errstr, $ping_timeout);
									
		// no answer
		if (!$fp) return -1;
		// stop timer
		else {
			fclose ($fp);	
			return $time_spent;
		}
	}
	
	// -------- NET ----------
	
	/**
	 *	openFTP ()
	 *	Open an FTP connection
	 *	@return {FTPConnection}
	 */
	public function openFTP ($addr, $user, $password) {
		return FTP::open ($addr, $user, $password);
	}
	
	/**
	 *	closeFTP ()
	 *	Close an FTP connection
	 */
	public function closeFTP (&$con) {
		return FTP::close ($con);
	}
	
	/**
	 *	copyFTP ()
	 */
	public function copyFTP ($source_file, $destination_file, &$con) {
		return FTP::copy ($source_file, $destination_file, $con);
	}
}
?>