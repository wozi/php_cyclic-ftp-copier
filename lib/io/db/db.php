<?php
/**
 *	layer.db
 *	provides database access
 */
class dbLayer {

	/**
	 *	connect ()
	 *	Connect to the database.
	 *	This function has to be called each time before a query is made, just because the resource is not serialized!!
	 */
	public function connect ($params) {
		if (mysql_connect ($params [0], $params [1], $params [2]) != -1) 
			return true;
		else 
			return false;
	}

	/**
	 *	select_db ()
	 *	Select a database
	 *	@param {String} dbname : the db name to connect to
	 *	@return {Boolean} whether successful or not
	 */
	public function select_db ($dbname, $params) {
	//	if (mysql_select_db ($dbname, $this->con) != -1) {
		
		/**
		 *	Connect to the db
		 */
		self::connect ($params);
		
		mysql_select_db ($dbname, $this->con);
	//		return true;
	//	} else
	//		return false;
	}
	
	/**
	 *	insert ()
	 *	Insert an entry in a database
	 */
	public function insert ($table, $elements, $params) {
		/**
		 *	Connect to the db
		 */
		self::connect ($params);
		
		$query = "INSERT INTO ".$table." (";
		$query2 = "";
		
		foreach ($elements as $field_name=>$value) {
			$query .= $field_name.",";
			$query2 .= "'".$value."',";
		}
		$query = substr ($query, 0, strlen ($query) - 1);		//Remove the last ,
		$query2 = substr ($query2, 0, strlen ($query2) - 1);		//Remove the last ,
		
		$q = $query.") VALUES (".$query2.")";
		
		//TO DO : vérifier les insertions avec les valeurs retorunées et vérfieri avant insert par le security::checkDb ();
		$res = mysql_query ($q);
		
		return true;
	}
	
	/**
	 *	select_all ()
	 *	Do a select * on a table and return all the elements.
	 */
	public function select_all ($table, $params) {
		/**
		 *	Connect to the db
		 */
		self::connect ($params);
		
		$query = "SELECT * FROM ".$table;
		if (($res = mysql_query ($query)))
			return $res;
		else 
			return NULL;
	}
		
}
?>