<?php
/**
 *	Class Config
 *	Takes care of the configuration of Kobaia. It is accessible by the controllers, processes and libs.
 *	Based on a YAML config file.
 *	Takes care of caching the file (ioLayer is needed to enable this feature).
 *
 *	@version 0.2 // 14112008 ++ 07/01/2009
 *
 *	@Supports:
 *		- load simple YAML file as array
 *		- apply conditions (if x in y)
 *
 *	@todo:
 *		- Implements other conditions + finish If
 */
//include ('lib/spyc/class.spyc.php');
//include ('layer/layer.io.php');

define ("YAML_CACHE_DIR", "../cache/config");		//A CHANGER !!!!

class Config {

	/**
	 *	yaml
	*	Our YAML array, containing the configuration information
	 */
	var $yaml = NULL;

	/**
	 *	tree
	 *	If the tree mode is enabled, this variable will contain the whole configuration as a DOM tree.
	 */	
	var $tree = NULL;
	
	
	/**
	 *	Constructor
	 *	Load a new config from the specified file
	 *	@param {String} configPath : the path to the YAML config file
	 */
	function __construct ($configPath=NULL) {
		if ($configPath) $this->load ($configPath);
	}
	
	/**
	 *	load ()
	 *	Load a config file
	 *
	 *	@param {String} configPath : the path to the YAML config file
	 *	@param {Boolean} createTree : if set to true, a tree will be created and any value will be accessible using the ->tree property
	 *	@return {Boolean} true/false whether succeed or failed
	 *
	 *	@todo Implement the tree feature
	 */
	public function load ($configPath, $createTree=false) {
		if (!class_exists ("Spyc")) return false;
			
		/** 
		 *	Cache the file if the ioLayer is loaded
		 */
		if (class_exists ('ioLayer')) {
			if (file_exists ($configPath)) {
				if (($conf = $this->cacheYaml ($configPath)) != FALSE) {
					$this->yaml = $conf;
					return true;
				} else
					$this->yaml = $this->applyConditions (Spyc::YAMLLoad ($configPath));		//If caching failed
					return true;
				}
		} else {
			if (file_exists ($configPath)) {
				$this->yaml = $this->applyConditions (Spyc::YAMLLoad ($configPath));
				return true;
			} 
		}
		return false;
	}	
	
	/**
	 *	applyConditions ()
	 *	Apply conditions to the YAML data
	 *	@param {Array} data
	 *	@return {Array} compiled data, so conditions not matched will not be there
	 *
	 *	@note This function only applies conditions on level 1!! TO DO recursive for all levels!
	 *	@dev
	 */
	private function applyConditions ($data) {
		$result = array ();
		$if = false;
		$in_if = false;
		foreach ($data as $key=>$value) {
			/**
			 *	Apply the IF
			 */
			if ($key == 'if') {
				$in_if = true;
				
				// apply the condition
				if ($this->testCondition ($value)) {
					echo "Conditions are true<br>";
					$if = true;	
				}
				
				// add the if to the array for other use
				$result [$key] = $value;
			}
			
			/**
			 *	Apply the End If
			 */
			if ($key == 'end') {
				if ($value == 'if') {
					$if = false;
					$in_if = false;
				}
			}
			
			/**
			 *	if we can go, let's assign the value to the result
			 */
			if ($in_if && $if) $result [$key] = $value;
			else if (!$in_if) $result [$key] = $value;
		}

		return $result;
	}
	
	/**
	 *	testCondition ()
	 *	Test a condition and return the result
	 */
	private function testCondition ($cond) {
		/**
		 *	First, let's look what type of operator we have
		 */
		if (preg_match ("/( [!]?in )/i", $cond)) {		//Condition: IN
			return $this->in ($cond);
		}
		
		return true;
	}
	
	/**
	 *	Condition:IN ()
	 * 	Test a x IN y condition
	 $	@param {String} cond
	 */
	private function in ($cond) {
		/**
		 *	Split the 2 parts of the operator
		 */
		preg_match_all ("/(.+)( [!]?in )(.+)/i", $cond, $matches);

		/**
		 *	Now, we may have special callers on the conditions. We'll convert them then!
		 */
		$left = $this->convertSpecialEncapsulation ($matches [1] [0]);
		$right = $this->convertSpecialEncapsulation ($matches [3] [0]);
			
		echo "Test $left with $right<br>";
			
		$res = preg_match ("/".$left."/i", $right);
	/*	if ($res == 0) $res = false;
		else $res = true;*/
		
		/**
		 *	Now, if we have a !in or a in we'll return the right value
		 */
		return (preg_match ("/( !in )/i", $cond)) ? !$res : $res;
	}
	
	/**
	 *	convertSpecialEncapsulation ()
	 *	Convert special encapsulations such as [ ... ] which means that the stuff inside is a PHP call
	 */
	private function convertSpecialEncapsulation ($value) {
		/**
		 *	Look for PHP encapsulation, put between brackets [ ... ]
		 */
		if (preg_match_all ("/\[ (.+?) \]/i", $value, $matches)) {
			eval ("\$result = ".$matches [1] [0].";");
			return $result;
		}
		return $value;
	}
	
	/** 
	 *	cacheYaml ()
	 *	Write a yaml file into a file as an array for cache.
	 *	We serialize the yaml array, and we just write it to a file.
	 *	@param {String} config_file : the path of the config file
	 *	@return {Array} the yaml array
	 */
	public function cacheYaml ($config_file) {
		/**
		 *	Create the cache name
		 */
		$cache_name = preg_replace ("/\//", "-", $config_file);
		$cache_name = preg_replace ("/[..\/]/", "", $config_file);
		$cached_file = YAML_CACHE_DIR.basename ($cache_name).".cache";
		
		/** 
		 *	if the file is out dated, we'll recache it
		 */
		if (ioLayer::mod_time ($cached_file) < ioLayer::mod_time ($config_file)) {
			/**
			 *	Serialize the data for write
			 */
			$yaml = Spyc::YAMLLoad ($config_file);
			$content = serialize ($yaml);
			
			/** 
			 *	Now let's write it with the cache option
			 */
			if (ioLayer::write ($cached_file, $content) === FALSE) {
				return false;
			}
		}
		
		/** 
		 *	Now read it and deserialize it.
		 */
		return $this->applyConditions (unserialize (ioLayer::read ($cached_file)));
	}
	
	/**
	 *	get ()
	 *	Get a value from a key
	 *	@param {String} key : the key to the value
	 *	@param {String} in : optionnal, take the value from another key element (inside/embed element)
	 *	@return {String} the value or NULL if not found
	 */
	function get ($key, $in=NULL) {
	//print_r ($this->yaml);
		foreach ($this->yaml as $ykey=>$yvalue) {
			/**
			 *	We may want to loonk for a specific value into that
			 */
			if ($in) {
		//	echo 'check '.$in.' with '.$ykey.' for search '.$key.' in '.$in.'<br>';
				if ($ykey === $in) {
			//	echo 'ok for '.$ykey.' with '.$in.'<br>';
			//	print_r ($yvalue);
					/**
					 *	We've found the right key, now we'll look for a value into that
					 */
					foreach ($yvalue as $yykey=>$yyvalue) {
				//	echo 'check value '.$yykey.' with '.$key.'<br>';
						if ($yykey == $key) {
				//			echo '<b>Got it!! '.$yyvalue.'</b><br>';
							return $yyvalue;		//Got it!
						}
					}
				}
			}
			else {
				if ($ykey == $key) return $yvalue;
			}
		}
		return NULL;
	}
	
	/**
	 *	getAll ()
	 *	Get the complete config tree
	 *	@return {Array} 
	 */
	public function getAll () {
		return $this->yaml;
	}
	
	/**
	 *	getParent ()
	 *	Get the parent value of a value
	 *	@param {String} key : the key to the value
	 *	@return {String} the value or NULL if not found
	 */
	public function getParent ($key) {
	//print_r ($this->yaml);
		foreach ($this->yaml as $ykey=>$value) {
			if (is_array ($value)) {
				foreach ($value as $invalue) {
					if ($invalue == $key) {
					//echo 'got the parent: '.$ykey.'<br>';
						return $ykey;		//Gotcha -> return the parent key
					}
				}
			} else {
				if ($value == $key) return $value;		//Nothing higher -> return this value
			}
		}
	}
}
?>