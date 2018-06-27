<?php 
// Copyright 2018 SQLFusion LLC, Author: Philippe Lewicki           info@sqlfusion.com
// For licensing, reuse, modification and distribution see license.txt
   /**
    *   Abstract connexion to a SQLite Database.
    *
    *  This on is for SQLite database
    *  Create a connexion object that can be reuse in the PHP script
    *  The connexion object is used in all the Radria Classes.
    *  The backup synchronisation feature is used to make a backup off all the
    *  change in the database. (insert, update, alter, create....). Its very uselfull if
    *  you want to synchronise with a main server after working in local.
    *
    *  Since mysql_db_query is deprecate and suposly slow the connexion object use now the mysql_select_db function. This is
    *  now a problem is you want to use the sqlconnect object to query to different databases. You need to make sure that
    *  the sqlconnect for each database uses different username and password.
    *  Otherwise you can turn on or allways_select_db flag that will do a mysql_db_select before each query.
    *
    * @copyright  SQLFusion LLC 2018
    * @author Philippe Lewicki  <phil@sqlfusion.com>
    * @version 3.0.0
    * @package RadriaCoreMySQL
    * @access public
    */

#namespace radriacore;

Class sqlConnect extends BaseObject {

  /**  Hostname where the read database is hosted
   * @var String $hostname
   * @access private
   */
  private $hostname = "default.db"  ;

  /**  Login to acces the read database
   * @var String $login
   * @access private
   */
  private $login = "" ;

  /**  Password to acces theread database
   * @var String $password
   * @access private
   */
  private $password = "" ;

  /**  Hostname where the write database is hosted
   * @var String $hostname
   * @access private
   */
  private $whostname = "localhost"  ;

  /**  Login to acces the write database
   * @var String $login
   * @access private
   */
  private $wlogin = "" ;

  /**  Password to acces the write database
   * @var String $password
   * @access private
   */
  private $wpassword = "" ;

  /**  Set read write database connexion
   * @var boolean use_readwrite
   * @access private
   */
  private $use_readwrite = false ;

   /**  Database Name / database file
   * @var String $db
   */
  var $db = "";
  
  private $database_file = "default.db";

  /**  Table Name
   * @var String $table
   */
  var $table = "";

  /**  Database connexion identifier
  * @var databaseidentifier $id
  * @access private
  */
  var $id;

  /**  Database write db connexion identifier
  * @var databaseidentifier $id
  * @access private
  */
  var $wid;

   /** Backup all queries for synchronisation
    * @var bool $backupSync
    * @access private
    */
   var $backupSync = false ;

    /** Table backup Sync
    * @var String $tbl_backupSync
    * @access public
    */
   var $tbl_backupSync = "backupsync" ;

   /** Use Database or text files for backupsync
    * @var boolean $useDatabase
    * @access private
    */
   var $useDatabase = false ;

   /** Base directory for files
   * @var String baseDir
   * @default "." curent directory
   * @access public
   */
   var $baseDir  ="../mydb2/" ;

   /** Project Directory for files
   * @var String baseDir
   * @default "." curent directory
   * @access public
   */
   var $projectDir  ="./" ;
   
   /** AllwaysSelectDb flag to tel sqlQuery if he need to
   * select the database before each query.
   * @var booloean
   * @default "." curent directory
   * @access public
   */
   var $allwaysselectdb = false;

  /**
   *    Constructor sqlConnect
   *
   *    If provide with parameters set the login and password
   *
   *  @param string login $login    Username to access the database
   *  @param  string password $password    Password to access the database
   */
  function sqlConnect($login="", $password="") {
    $this->login = $login ;
    $this->password = $password ;
  }

  /**
   *  Method start()
   *
   *  Its use to connect to a MySQL database.
   *  This is the prefered methode compare to startp().
   *  The login and password need to be set before executing the method.
   *
   *  @param string login $login    Username to access the database
   *  @param  string password $password    Password to access the database
   *  @return connextionid $linkidentifier
   * @see startp()
   */
  function start($database_file="")  {
    if (strlen($database_file) > 0) {
        $this->database_file = $database_file;
        $this->db = $this->database_file;
    }
    if (strlen($this->database_file) > 0) {
    
        try {
            $linkidentifier = new SQLite3($this->db, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            //$linkidentifier->open($this->db, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        } catch(Exception $e) {
            $this->setError("<b>Database Connect Error</b> :". $e->getMessage());
        }  
            
        if (!$linkidentifier) {
				$this->setError("<b>Database Connect Error</b> : Couldn't connect to the database") ;
		} else {
            $this->id = $linkidentifier ;
        }

    } 
    return $linkidentifier ;
  }

  /**
	* 	Method stop
   *  Close Database connexion
   */
  function stop() {
     $this->close();
  }
  /**
   *  Set the hostname for the connexion
   */
  function setHostname($hostname) {
    $this->hostname = $hostname ;
  }
  /**
   *  return the hostname of the connexion
   */
  function getHostname() {
    return $this->hostname ;
  }
  /**
   *  Set the database name of the connexion
   */
  function setDatabase($db='') {
    $this->db = $db ;
    $this->database_file = $this->db;
    if (!empty($this->database_file)) { 
        return true;
    } else {
        return false;
    }
  }
  /**
   *  Return the database name of the connexion
   */
  function getDatabase() {
    if (!empty($this->db)) {
        return $this->db ;
    } else {
        return $this->database_file; 
    }
  }

  /**
   *  Return connexion ressource id
   */
  function getDbConId() {
    return $this->id ;
  }
  /**
   *  Set to true of false the Backup synchronisation.
		* 	 If set to true, all the queries using this connexion will be saved in 2 files:
		* 
		* backupsync.struct.sql for all queries that generate structural changes to the database. (CREATE, DROP, ALTER)
		* backupsync.data.sql for all the queries that modify the data content of the 
		* database.
		* @param boolean bool 
   */
  function setBackupSync($bool = true) {
    $this->backupSync = $bool ;
  }
  /**
   *  Return the value of the backupSync flag

   * @return bool value of the backupsync flag
   */
  function getBackupSync() {
    return $this->backupSync  ;
  }
  /**
   *  Return the name of the table use for the backupsync 
* 	 (Deprecate)
* 	 @return string name of the table used for backupsync
   */
  function getTableBackupSync() {
    return $this->tbl_backupSync ;
  }
  /**
   *  Return true the value of the backupSync flag
* 	 @return boolean true if there is connexion to the database, false otherwise.
   */
  function is_connected() {
	  $sqlQ = new sqlQuery() ;

    //if (is_resource($this->id)) {
    if ($sqlQ->isValidResource($this->id)) {
      return true ;
    } else {
      return false ; 
    }
  }
  /**
   *  return if the database instead of xml file is used or not.
* 	 @return boolean true if the database is used, false otherwise
   */
  function getUseDatabase(){
    return $this->useDatabase ;
  }
	  /**
   *  Set the use of the database to true or false.
* 	 By default mydb elements like : reports, forms, registry are stored using xml files.
* For speed and sclalable reason database tables can be used instead.
*
   */
  function setUseDatabase($bool= true) {
    $this->useDatabase = $bool ;
  }
  /**
   *  Return the path of the directory where the main MyDB library is stored.
* 	 @return string with path of the MyDB library
   */
  function getBaseDirectory() {
    return $this->baseDir ;
  }
  /**
   *  Set the path where the MyDB library is stored.
   */
  function setBaseDirectory($dirname) {
    if(preg_match("/$/", $dirname)) {
     $this->baseDir = $dirname ;
    } else {
     $this->baseDir = $dirname."/" ;
    }
  }
  /**
   *  Return the path of the project using this connexion is stored.
* 	 @return string with path of project using this connexion
   */
  function getProjectDirectory() {
    return $this->projectDir ;
  }
  /**
   *  Set the path of the project using this connexion is stored
   */
  function setProjectDirectory($dirname) {
    if(preg_match("/$/", $dirname)) {
      $this->projectDir = $dirname ;
    } else {
      $this->projectDir = $dirname."/" ;
    }
  }
  /**
   *  allways_select_db, is a flag for the sqlQuery object
   *  To select the database before each query
   *  This flag fix a weid behavior from mysql/php.
   *  When you use multiple sqlConnect objects to access different
   *  databases and use the same user name, then the mysql_select_db
   *  function dosen't work properly anymore. The select database is
   *  apply to all the connexion with the same username.
   *
   *  So for that case this flag will tel sqlConnect to do a mysql_select_db
   *  before each query
   **/

   function setAllwaysSelectDb($bool) {
     $this->allwaysselectdb = $bool;
   }
   function getAllwaysSelectDb() {
     return $this->allwaysselectdb ;
   }

  /**
   *  For db cluster that requires a different read and write connexions
   */

   function setUseCluster($usereadwrite=true) {
       $this->use_readwrite = $usereadwrite;
   }
   function getUseCluster() {
      return  $this->use_readwrite;
   }
   function setWriteConnection($hostname, $username, $password) {
          $this->whostname = $hostname;
          $this->wlogin = $username;
          $this->wpassword = $password;
          $this->use_readwrite = true;
  }

} /** End class sqlConnect */
?>
