<?php
// Copyright 2018 SQLFusion LLC, Author: Philippe Lewicki           info@sqlfusion.com
// For licensing, reuse, modification and distribution see license.txt
  /**
    *  Abstract Database query in PAS for SQLite.
    *
    *  It is used to abstract query  and other Database access.
    *  Curently PostgreSQL, MySQL, ODBC, SQLite are supported.
    *  For all the PAS application there is basic requirement in the
    *  table structure. All the table must have an integer not null with autoincrement and primary key with
    *  the following name id<tableName> and type.
    *  To keep the compatibility with postgreSQL the insert queries must include the list of fields prior to the values.
    *  Exemple : insert into table1 (field1, field2, field3) values ( 'valuefield1', 'valuefield2', valuefield3')
    *  Avoid using : insert into table1 ('', 'valuefield1', ''. valuedield3), the '' are not inserting the default value in postgreSQL
    *  but an empty string.
    *
    * @author Philippe Lewicki  <phil@sqlfusion.com>
    * @version 4.0.0
    * @package RadriaCoreMySQL
    * @access public
    */

#namespace radriacore;

Class sqlQuery extends BaseObject {
  /**  Name of the table used in the query, it can be an array for multiple tables.
   * @var mixte $table in generale a string with a table name, but can be an array.
   */
  var $table = "";

  /**  SQL query string  that is going to be executed.
   * @var String $sql_query
   */
  var $sql_query ;

  /**  Order sequence of the SQL Query, exemple.
   * @var string $sql_order
   */
  var $sql_order ;

  /**  Position of the record, for the limit sequence of the SQL Query.
   * @var integer $pos
   */
  var $pos = 0 ;

  /**  Number maximum of rows to display, for the limit sequence of the SQL Query.
   * @var string $max_rows
   */
  var $max_rows ;

  /**  Number of rows return by the execution of the SQL Query.
   * @var integer $num_rows
   */
  var $num_rows;

  /**  Server number, deprecate var was use for compatibility with phpmyadmin .
   * @var String $server
   * @deprecated
   */
  var $server ="0";

   /**  Result set of the executed SQL Query.
   * @var ResultSet $result
   * @private
   */
  var $result ;

   /**  Unique id of the last inserted record.
   * @var integer $insert_id
   * @private
   */
  var $insert_id ;

   /**  Database connexion object used to execute the query.
   * @var sqlConnect $dbCon
   */
  var $dbCon ;        

   /**  Position of the record in the result set.
   * @var integer $cursor
   */
  var $cursor ;

   /**  MetaData about the SQL Query, table name, fields.
   * @var array $metadata
   * @access public
   */
  var $metadata ;

   /**  Data from the restult of a fetch or fetchArray
   * @var mixte $data
   * @access public
   */
  var $data ;

   /**  connexion ressource used for the executed query
   * @var resource $query_connexion
   * @access public
   */
  var $query_connexion ;
  
  /** Data query array of variables for the pdo query 
      @var array data_query
      @access private
      */
   //private $data_query = Array(); 

  /**
   * Constructor, create a new instance of a sqlQuery with database connection
   * and query string.
   * @param object sqlConnect $dbCon
   * @param string $sql   String with the SQL Query.
   * @access public
   */
  function __construct($dbCon=0, $sql="") {
    //$this->setDisplayErrors(false) ; 
    parent::__construct();
    if (defined("RADRIA_LOG_RUN_SQLQUERY")) {
        $this->setLogRun(RADRIA_LOG_RUN_SQLQUERY);
    } else { $this->setLogRun(false) ; }
    
    if (is_object($dbCon)) {
      $this->dbCon = $dbCon ;
    }
    if (!$this->dbCon->id) {  
      $this->setError("Query Error: No open or valid connexion has been provide to execute the query. (construct) ") ;
      return false;
    }
    $this->query_connexion = $this->dbCon->id;
    if (strlen($sql)>0) {
      $this->sql_query = $sql ;
    }
//	$this->setLogRun(true);
    return true;
  }

    /**
   * Return the numbers of row for the executed SQLQuery.
   * @param ResultSet $result
   * @return integer num_rows
   * @access public
   */
  function getNumRows($result = 0) {

        if (!$result) {
	        $this->num_rows = 0 ;
		} else {
         	$this->num_rows = 0; 
         	//$result->numRows();
         	///mysqli_num_rows($result) ;
         	while ($row = $result->fetchArray()) { $this->num_rows++; }
        } 
		if(!$this->result && $this->num_rows == 0) {
 					$this->num_rows = 0 ;
        } else {
            $this->num_rows = 0;
            while ($row = $this->result->fetchArray()) { $this->num_rows++; }
        }

    return $this->num_rows ;
  }

  /**
   * Execute an sqlQuery.
   *
   * Execute a query the query string and database connexion object need
   * to be passe has parameters or be previously define.
   *
   * @param string $sql   String with the SQL Query.
   * @param object sqlConnect $dbCon   Connexion object if not previously define in the contructor.
   * @return ResultSet $rquery
   * @access public
   */
  function query($sql = "", $dbcon = null ) {
    
    if ($dbcon != null) {
     $this->dbCon = $dbCon;
    }
    if (strlen($sql)>0) {
      $this->sql_query = trim($sql) ;
    }
    if (!$this->dbCon->id) {
      $this->setError("Query Error: No open or valid connexion has been provide to execute the query: ".$this->sql_query) ;
      return false;
    }

    if (empty($this->sql_query)) {
         $this->setLog(" query(): No query to execute.");
        return false;
    }
    if ($this->max_rows) {
      if (!$this->pos) { $this->pos = 0 ; }
      $qpos = " limit ".$this->pos.", ".$this->max_rows ;
    } else {
      if (!$this->pos) { $this->pos = "" ; }
      $qpos = $this->pos;
    }

     if ($this->dbCon->getUseCluster()) {
           if (preg_match("/^select/i", $this->sql_query)) {
               $this->query_connexion  =  $this->dbCon->id;
           } else {
                $this->query_connexion  = $this->dbCon->wid;

           }
     } else {
        $this->query_connexion = $this->dbCon->id;
    }
    if (preg_match("/^select/i", $this->sql_query)) { 
    	try {	
            $rquery = $this->query_connexion->query($this->sql_query." ".$this->sql_order." ".$qpos);           
        } catch(Exception $e) {
            $this->setError("<b>SQL Query Error :</b>". $e->getMessage());
        }
				                       
    		
		//$rquery = mysqli_query($this->query_connexion, $this->sql_query." ".$this->sql_order." ".$qpos);
		
		$this->setLog($this->sql_query." ".$this->sql_order." ".$qpos);
	} else {
	    try {
	    
		    $rquery = null;
		    if ($this->query_connexion->exec($this->sql_query)) {
		        $this->setLog("\n\nWrite Query executed fine: ".$this->sql_query);
		    }
            
        } catch(Exception $e) {
            $this->setError("<b>SQL Query Error :</b>". $e->getMessage());
        }
		
		$this->setLog($this->sql_query);
	}
	
    $sqlerror = "";
    /** Double check if needed:
    if (!$this->isValidResource($rquery)) {
        $sqlerror = mysqli_error($this->query_connexion) ;
        if (!empty($sqlerror)) {
          $this->setError("<b>SQL Query Error :</b>".mysqli_errno($this->query_connexion)." - ".$sqlerror." (".$this->sql_query.")") ;
        }
    }*/
    
    if (!$this->max_rows) {
    // Do we need this ???
    //  $this->num_rows = $this->getNumRows($rquery) ;
    }
    //$this->insert_id = mysql_insert_id() ;
    $this->insert_id = 0;
    $this->result = $rquery ;
    $this->cursor = 0 ;
    
    if ($this->dbCon->getBackupSync()) {
        if (preg_match("/^alter/i", $this->sql_query)
         || preg_match("/^create/i", $this->sql_query)
         || preg_match("/^drop/i", $this->sql_query)) {
            if ($this->dbCon->getUseDatabase()) {
                $qInsSync = "insert into ".$this->dbCon->getTableBackupSync()." ( actiontime, sqlstatement, dbname) values ( '".time()."', '".addslashes($this->sql_query)."', '".$this->dbCon->db."') " ;
                
                // TODO
                //$rquery = mysqli_query($this->dbCon->id, $qInsSync);
                
            } else {
                $file = $this->dbCon->getProjectDirectory()."/".$this->dbCon->getTableBackupSync().".struct.sql" ;
                $fp = fopen($file, "a") ;
                $syncquery = $this->sql_query.";\n" ;
                fwrite($fp, $syncquery, strlen($syncquery)) ;
                fclose($fp) ;
            }
          }
        if (preg_match("/^insert/i", $this->sql_query)
         || preg_match("/^update/i", $this->sql_query)
         || preg_match("/^delete/i", $this->sql_query)) {
            if ($this->dbCon->getUseDatabase()) {
                $qInsSync = "insert into ".$this->dbCon->getTableBackupSync()." ( actiontime, sqlstatement, dbname) values ( '".time()."', '".addslashes($this->sql_query)."', '".$this->dbCon->db."') " ;
                
                // TODO
                //$rquery = mysqli_query($this->dbCon->id, $qInsSync);
                
            } else {
                $file = $this->dbCon->getProjectDirectory()."/".$this->dbCon->getTableBackupSync().".data.sql" ;
                $fp = fopen($file, "a") ;
                $syncquery = $this->sql_query.";\n" ;
                fwrite($fp, $syncquery, strlen($syncquery)) ;
                fclose($fp) ;
            }
          }
    }
    return $rquery ;
  }

  /**
   * Return uniq id from the last insert,
   *
   * The param table and field are not used here but are
   * required if you want to make your application compatible
   * with postgreSQL
   * PhL 20021120 - Add a check if not greater then zero then run the mysql_insert_id() function. This in case
   * the getinsertid of the object is run multiple time.
   *
   * @param string $table  name of the table with the sequence
   * @param string $field name of the primary key used for the sequence
   *
   * @return integer insert_id
   */
  function getInsertId($table="", $field="") {
    if (!($this->insert_id > 0)) {
    
        // TODO
        $this->insert_id = $this->query_connexion->lastInsertRowID() ;
    }
    return $this->insert_id ;
  }

  /**
   * Catch and return an error string from the last Error.
   * @return string Error description
   * @access public
   */
  function getError() {
    // TODO
    //return mysqli_errno($this->query_connexion) . ": " .mysqli_error($this->query_connexion) ;
    return $this->query_connexion->lastErrorCode();
  }

  /**
   *  return the content data of a record from a result set.
   *
   *  Return the data of a record in the form of an object where all fields are vars.
   *  It use the result set of a previously executed query.
   *  It move the cursor of the ResultSet to the next record
   *
   * @param ResultSet $result
   * @return object $rowobject
   * @see fetchArray()
   */
  function fetch($result = 0) {  
      // TODO  Figure out an object option
      //$rowobject = mysqli_fetch_object($result) ;
      return $this->fetchArray($result);
  }

  /**
   *  Return the content data of a record from a result set.
   *
   *  Return the data of a record in the form of an Array where all fields are keys.
   *  It use the result set of a previously executed query.
   *  It move the cursor of the ResultSet to the next record
   *
   * @param ResultSet $result
   * @return array $rowarray
   * @see fetch()
   */
  function fetchArray($result = 0) {
    if ($result>0) {
    
      //$rowarray = mysqli_fetch_array($result) ;
      $rowarray = $result->fetchArray() ;
      
    } elseif ($this->result>0) {
    
      //$rowarray = mysqli_fetch_array($this->result) ;
      $rowarray = $this->result->fetchArray();
      
    }
    $this->cursor++ ;
    $this->data = $rowarray ;
    return $rowarray ;
  }

  /**
   *  Return all the fields of a table.
   *
   * It also populate the metadata array attribute will all the informations about the
   * table fields. Type, Key, Extra, Null
   *
   * @param string $table Name of the Table
   * @return array $field  All the fields name
   */
  function getTableField($table="") {
    if (is_array($table)) {
      $atable = $table ;
    } elseif (strlen($table) > 0) {
      $atable = $table ;
    } else {
      $atable = $this->table ;
    }
    if (is_array($atable)) {
      // TODO re-implement array's of table fields
      return false;      
    } else {

      $table_def = $this->query_connexion->query("select * from ".$atable);
      $field = $this->getQueryField($table_def);
      
    }
    if (is_array($field)) {
        reset($field) ;
    }
    return $field ;
  }

 /**
   *  Return all the fields from a query
   *
   * @param string $result Name of the Table
   * @return array $field  All the fields name and false if no result set is found.
   */
  function getQueryField($result="") {
    if ($result == "") {
      $result = $this->getResultSet() ;
      //$result = $this->query_connexion;
    }
    //if (is_resource($result)) {
    if ($this->isValidResource($result)) {
    
        //$numfield = mysqli_num_fields($result) ;
        
        $numfield = $result->numColumns();
        
        for ($i=0; $i < $numfield; $i++) {
        
            // TODO get meta data from a table field
            //$meta = mysqli_fetch_field($result, $i);          
            
            $fieldname = $result->columnName($i);
            $field[$i] = $fieldname;
            $this->metadata[$fieldname]["Type"] = $result->columnType($i);
            //$this->metadata[$fieldname]["Null"] = $meta->not_null;
            //$this->metadata[$fieldname]["Key"] = $meta->primary_key;
            //$this->metadata[$fieldname]["Default"] = $meta->def;     
        }
    } else {
        $this->setError("Couldn't find a valid resource from the query to fetch Field names and meta informations, make sure your query is executed and worked");
        $field = false;
    }
    return $field ;
  }
  /**
   * Return a ResultSet with all the table names from the database of the query.
   *
   * @param object sqlConnect $dbc
   * @return ResultSet $result  ResultSet with all the tables names
   * @access public
   * @see fetchTableName()
   */
  function getTables($dbc=0) {
    if ($dbc == 0) {
      $dbc = $this->getDbCon() ;
    }
	//$result = mysqli_list_tables ($dbc->id, $dbc->db);
	// TODO get the list of all the tables
	//$result = mysqli_query($dbc->id, "SHOW TABLES");
	
    $this->result = $result ;
    $this->cursor = 0 ;
    //$this->num_rows = mysqli_num_rows($result) ;
    return $result ;
  }

  /**
   * Return a ResultSet with all the databases from the connexion.
   *
   * @param object sqlConnect $dbc
   * @return ResultSet $result  ResultSet with all the tables names
   * @access public
   * @see fetchTableName()
   */
  function getDatabases($dbc=0) {
    if ($dbc == 0) {
      $dbc = $this->getDbCon() ;
    }
    // TODO get a list of all the database
    //$result = mysqli_list_dbs($dbc->id);
    
    $this->result = $result ;
    $this->cursor = 0 ;
    //$this->num_rows = mysqli_num_rows($result) ;
    return $result ;
  }
  /**
   * Try to create a new database (DEPRECATE ??)
   *
   * @param string name new database name
   * @return true if succed false if not
   * @access public
   */
  function createDatabase($name) {
    //$b_success = mysql_create_db($name);
    $q = new sqlQuery($this->getDbCon());
    if ($q->query("CREATE DATABASE ".$name)) {
        if (strlen($q->getError()) < 5) {
            return true;
        } else {
            return false;
        }
    } else { return false; }
    #return $b_success;
  }

  /**
   *  Fetch a table name from the result set created by getTables.
   *
   *  Fetch the ResultSet from getTables and increment the cursor to the
   * next record
   *
   *  @param ResultSet $tableList
   *  @return string $tablename
   *  @access public
   *  @see getTables()
   */
  function fetchTableName($tableList=0) {
    if ($this->cursor >= $this->num_rows) {
      return 0 ;
    } else {
      if($tableList==0) {
        // TODO PDO sqlite get the name of table from query statement
        //$tablename = mysqli_tablename ($this->result, $this->cursor);
      } else {
        // TODO PDO sqlite get the name of table from a tablelist ??
        
        //$tablename = mysqli_tablename ($tableList, $this->cursor);
      }
      $this->cursor++ ;
      return $tablename ;
    }
  }
  
  /** 
   * Set the database connexion object (sqlConnect).
   *
   * @param sqlConnect $dbConid database connexion.
   */  
  function setDbCon($dbConid) {
    $this->dbCon = $dbConid ;
  }
  
  /** 
   * Return the database connexion object (sqlConnect).
   *
   * @return sqlConnect database connexion
   */  
  function getDbCon() {
    return $this->dbCon ;
  }
  
  /**
   * Set the Data Query Array
   * Each query with data variables needs a associated mapping array
   * @param array $data_query
   */
   //function setDataQuery($data_query) {
   //     $this->data_query = $data_query;
   //}
   
  /**
   * Return the Data Query array
   * @return array 
   */
   //function getDataQuery() {
   // return $this->data_query;
   //}
  
  /**
   * Return the name of the Table from the executed SQL Query.
   *
   * @param ResultSet $result
   * @return string $table Table name
   * @access public
   */
  function getTableName($result=0) {
    //if (is_resource($this->result)) {
    if ($this->isValidResource($this->result)) {
        // TODO PDO sqlite3 reflexion
        //$table = mysqli_field_table($this->result,0);
    } else {
        $this->setError("Can't get the table name the result set is not a ressource") ;
    }
    return $table ;
  }
  
  /**
   * Set the default table name for the query.
   * Some object needs the table name of query reruning the query parser is 
   * very cpu intensive so we store the table name in the table attribute.
   * To set multiple tables separate them with comas.
   * 
   * @param mixte $table string with table name separated with comas or array of table names. 
   * @see getTable()
   */
  
  function setTable($table) {
    if (strrpos($table, ",")) {
		$table = explode(",",$table);
	}
    $this->table = $table;
  }
  
  /** 
   * Return the table(s) of the query.
   * The table return can be a string with the uniq table name or 
   * an array or table names.
   * 
   * @return mixte array of tables or string with table name.
   */
  function getTable() {
    return $this->table ;
  }
  
  /**
   * Return the sql statement of the query.
   * 
   * @return string with the sql statement of the query.
   */
  function getSqlQuery() {
    return $this->sql_query ;
  }

  /** 
   * Set the SQL statement for the query.
   * @param string $query with SQL statement. 
   * @see query()
   */
  function setSqlQuery($query) {
    $this->sql_query = $query ;
  }

  /** 
   * Return the result ressourse of the query
   * When query() is called the query is executed and the result ressource can 
   * be returned.
   * The fetch() and getData() method used the internal ressource attribute.
   *
   * @return ressource of the query ressource.
   * @see query()
   */
  function getResultSet() {
    return $this->result ;
  }
  /** 
   * setResultSet
   * Set the result of a query the this object.
   *
   * @param resource ResultSet of a query
   * @see query(), getResultSet()
   */
  function setResultSet($resultSet) {
      //if (is_resource($resultSet)) {
      if ($this->isValidResource($resultSet)) {
          $this->result = $resultSet;
      }
  }
  
  /**
   * Set the maximum number of rows for a query.
   * This will add a limit clause to the SQL Statement on its excecution.
   * 
   * @param integer $rows maximum number of rows to display.
   */
  function setMaxRows($rows) {
    $this->max_rows = $rows;
  }
  
  /**
   * Set the limit part of the SQL statement
   *
   * @param string sql statement part
   */
   function setSqlLimit($pos) {
      $this->pos = $pos;
   }

  /**
   * Set the order part of the SQL statement
   *
   * @param string sql statement part
   */
   function setSqlOrder($order) {
        $this->sql_order = $order;
   }


  /**
   * setCursor set the position of the cursor in the current result set.
   * Once the query is executed, the $pos will move the cursor to that position.
   * @param integer $pos position of the next row to seek.
   **/
  function setCursor($pos) {
    $this->cursor = $pos ;
    // TODO sqlite3 doesn't have a seek anymore
    //mysqli_data_seek($this->getResultSet(),$pos);
    if ($this->cursor == 0) { $this->result->reset(); }
  }

  /**
   * getCursor return the current position of the cursor.
   **/
  function getCursor() {
      return $this->cursor;
  }

  /**
   * Return the value of a field.
   * From an executed sql Query and fetch row this will return the 
   * value of a field from the current read row.
   * query() and fetch() method need to be executed before data can be returned.
   * @param string $fieldname name of the field to get the query value from.
   * @see query(), fetch(), getD()
   */
  function getData($fieldname) {
    return $this->getD($fieldname) ;
  }
  
  /**
   * Return the value of a field.
   * Shorter version of getData() method.
   *
   * @param string $fieldname name of the field to get the query value from.
   * @see getData()
   */  
  function getD($fieldname) {
    if (is_object($this->data)) {
        return $this->data->{$fieldname} ;
    } elseif (is_array($this->data)) {
        return $this->data[$fieldname] ;
    } else {
        return false ;
    }
  }

  /**
   * Escape a string from bad injections.
   * Need to be apply to all values comming from POST/GET.
   *
   * @param string string to escape
   * @return string escaped.
   */
    function escapeString($value) {

        if (get_magic_quotes_gpc()) {
                $value = stripslashes($value);
        }

        return SQLite3::escapeString($value);

    }
    /**
     * quote
     * alias to escapeString using new naming convention
     * similare to other framework
     * @param string to escape and quote
     * @return string escaped and quote.
     */
    function quote($value) {
        return $this->escapeString($value);
    }
  /**
   *  Destructor, clear the ResultSet and  other related attributes.
   *
   * @param ResultSet $result
   * @access public
   */
  function free($result = 0) {
    parent::free();
    if ($result>0) {
      $result->finalize();
    } elseif ($this->isValidResource($this->result)) {
      $this->result->finalize();     
    }
    $this->sql_query ="";
    $this->sql_order ="" ;
    $this->pos = 0 ;
    $this->max_rows = 0 ;
    $this->num_rows = 0;
  }

/*
 * Checks if the resource exists.
 *
 * @return boolean
 */
function isValidResource($data) {
	if ($data) {
		return true;
	} else {
		return false;
	}
}

} /* End class sqlQuery */
?>
