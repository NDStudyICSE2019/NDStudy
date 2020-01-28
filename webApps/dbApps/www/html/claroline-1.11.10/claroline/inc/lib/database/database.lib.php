<?php // $Id: database.lib.php 14500 2013-08-01 06:51:01Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Light-weight and extensible Object-Oriented Database Layer for Claroline
 * the main goal is to have some of the advantages of mysqli or pdo
 * and being compatible to the old Claroline kernel database connection.
 *
 * This library provides the following interfaces :
 *
 * 1. Database_Connection interface provided to allow implementation of other
 * database connections
 * 2. Database_ResultSet interface provided to allow implementation of other
 * database result sets
 *
 * This library provides the following classes :
 *
 * 1. Claroline_Database_Connection is an adapter provided by the Claroline core
 *  class through Claroline::getDatabase() static method call
 * 2. Mysql_Database_connection is an adapater build upon the mysql extension
 *  provided to connect to other databases
 * 3. Mysql_ResultSet implementation of Database_ResultSet to store and access
 *  database query result based on mysql extension and used by both
 *  Mysql_Database_Connection and Claroline_Database_Connection
 * 4. Database_Connection_Exception exception class specific to database
 *  connections
 *
 * @version     $Revision: 14500 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.database
 */

require_once dirname(__FILE__) . '/../utils/iterators.lib.php';

/**
 * Database Specific Exception
 */
class Database_Connection_Exception extends Exception{};

/**
 * Database_Connection generic interface
 */
interface Database_Connection
{
    /**
     * Connect to the database
     * @throws  Database_Connection_Exception
     */
    public function connect();
    
    /**
     * Select a database
     * @param   string $database database name
     * @throws  Database_Connection_Exception on failure
     */
    public function selectDatabase( $database );
    
    /**
     * Execute a query and returns the number of affected rows
     * @return  int
     * @throws  Database_Connection_Exception
     */
    public function exec( $sql );
    
    /**
     * Execute a query and returns the result set
     * @return  Database_ResultSet
     * @throws  Database_Connection_Exception
     */
    public function query( $sql );
    
    /**
     * Returns the number of rows affected by the last query
     * @return  int
     * @throws  Database_Connection_Exception
     */
    public function affectedRows();
    
    /**
     * Get the ID generated from the previous INSERT operation
     * @return  int
     * @throws  Database_Connection_Exception
     */
    public function insertId();
    
    /**
     * Escape dangerous characters in the given string
     * @param   string $str
     * @return  string
     */
    public function escape( $str );
    
    /**
     * Escape dangerous characters and enquote the given string
     * @param   string $str
     * @return  string
     */
    public function quote( $str );
    
    /**
     * Set connexion charset 
     * @param string $charset 
     */
    public function setCharset( $charset );
    
    /**
     * Get connexion charset 
     * @return string connexion charset
     */
    public function getCharset();
}

/**
 * Mysql specific Database_Connection
 */
class Mysql_Database_Connection implements Database_Connection
{
    protected $host, $username, $password, $database;
    protected $dbLink;
    
    /**
     * Create a new Mysql_Database_Connection instance
     * @param   string $host database host
     * @param   string $username database user name
     * @param   string $password database user password
     * @param   string $database name of the database to select (optional)
     */
    public function __construct( $host, $username, $password, $database = null )
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->dbLink = false;
    }
    
    protected function isConnected()
    {
        return !empty($this->dbLink);
    }
    
    /**
     * @see Database_Connection
     */
    public function connect()
    {
        if ( $this->isConnected() )
        {
            throw new Database_Connection_Exception("Already to database server {$this->username}@{$this->host}");
        }
        
        $this->dbLink = @mysql_connect( $this->host, $this->username, $this->password );
        
        if ( ! $this->dbLink )
        {
            throw new Database_Connection_Exception("Cannot connect to database server {$this->username}@{$this->host}");
        }
        
        if ( !empty( $this->database ) )
        {
            $this->selectDatabase( $this->database );
        }
    }
    
    /**
     * @see Database_Connection
     */
    public function selectDatabase( $database )
    {
        if ( ! $this->isConnected() )
        {
            throw new Database_Connection_Exception("No connection found to database server, please connect first");
        }
        
        if ( ! @mysql_select_db( $database, $this->dbLink ) )
        {
            throw new Database_Connection_Exception("Cannot select database {$database} on {$this->username}@{$this->host}");
        }
        
        $this->database = $database;
    }
    
    /**
     * @see Database_Connection
     */
    public function affectedRows()
    {
        if ( ! $this->isConnected() )
        {
            throw new Database_Connection_Exception("No connection found to database server, please connect first");
        }
        
        return @mysql_affected_rows( $this->dbLink );
    }
    
    /**
     * @see Database_Connection
     */
    public function insertId()
    {
        if ( ! $this->isConnected() )
        {
            throw new Database_Connection_Exception("No connection found to database server, please connect first");
        }
        
        return @mysql_insert_id( $this->dbLink );
    }
    
    /**
     * @see Database_Connection
     */
    public function exec( $sql )
    {
        if ( ! $this->isConnected() )
        {
            throw new Database_Connection_Exception("No connection found to database server, please connect first");
        }
        
        if ( false === @mysql_query( $sql, $this->dbLink ) )
        {
            throw new Database_Connection_Exception( "Error in {$sql} : ".@mysql_error($this->dbLink), @mysql_errno($this->dbLink) );
        }
        
        return $this->affectedRows();
    }
    
    /**
     * @see Database_Connection
     */
    public function query( $sql )
    {
        if ( ! $this->isConnected() )
        {
            throw new Database_Connection_Exception("No connection found to database server, please connect first");
        }
        
        if ( false === ( $result = @mysql_query( $sql, $this->dbLink ) ) )
        {
            throw new Database_Connection_Exception( "Error in {$sql} : ".@mysql_error($this->dbLink), @mysql_errno($this->dbLink) );
        }
        
        $tmp = new Mysql_ResultSet( $result );
        
        return $tmp;
    }
    
    /**
     * @see Database_Connection
     */
    public function escape( $str )
    {
        return mysql_real_escape_string( $str, $this->dbLink );
    }
    
    /**
     * @see Database_Connection
     */
    public function quote( $str )
    {
        return "'".$this->escape($str)."'";
    }
    
    public function setCharset( $charset )
    {
        if ( function_exists( 'mysql_set_charset' ) )
        {
            @mysql_set_charset( $charset, $this->dbLink );
        }
        else
        {
            @mysql_query( "SET NAMES '{$charset}'", $this->dbLink );
        }
    }
    
    public function getCharset()
    {
        return mysql_client_encoding( $this->dbLink );
    }
}

/**
 * Provides a MYsql_Database_Connection adapted to the Claroline database
 * with extra logging capability that mimics the old sql.lib.php functions
 * @todo move to database/claroline.lib.php to split generic database layer from
 * Claroline specific database layer
 */
class
    Claroline_Database_Connection
extends
    Mysql_Database_Connection
{
    protected $mysqlConnection;
    protected $queryCounter = 0;

    public function __construct()
    {
        $this->mysqlConnection = parent::__construct(
            get_conf('dbHost'),
            get_conf('dbLogin'),
            get_conf('dbPass'),
            get_conf('mainDbName')
        );
    }

    /**
     * Connect to the database
     * @see Database_Connection
     */
    public function connect()
    {
        if ( $this->isConnected() )
        {
            throw new Database_Connection_Exception("Already to database server {$this->username}@{$this->host}");
        }

        if ( ! defined('CLIENT_FOUND_ROWS') )
        {
            define('CLIENT_FOUND_ROWS', 2);
        }

        $this->dbLink = @mysql_connect(
            $this->host,
            $this->username,
            $this->password,
            false,
            CLIENT_FOUND_ROWS
        );

        if ( ! $this->dbLink )
        {
            throw new Database_Connection_Exception("Cannot connect to database server {$this->username}@{$this->host}");
        }

        if ( !empty( $this->database ) )
        {
            $this->selectDatabase( $this->database );
        }
    }

    /**
     * @see Database_Connection
     */
    public function exec( $sql )
    {
        if ( claro_debug_mode() && get_conf('CLARO_PROFILE_SQL',false) )
        {
            $start = microtime();
        }

        try
        {
            parent::exec( self::prepareQueryForExecution( $sql ) );

            if ( claro_debug_mode() && get_conf('CLARO_PROFILE_SQL',false) )
            {
                $duration = microtime() - $start;
                $info = 'execution time : ' . ($duration > 0.001 ? '<b>' . round($duration,4) . '</b>':'&lt;0.001')  . '&#181;s'  ;
                $info .= ': affected rows :' . $this->affectedRows();

                pushClaroMessage( '<br />Query counter : <b>' . $this->queryCounter++ . '</b> : ' . $info . '<br />'
                    . '<code><span class="sqlcode">' . nl2br($sql) . '</span></code>'
                    , 'sqlinfo' );

            }
            
            return $this->affectedRows();
        }
        catch ( Database_Exception $e )
        {
            if ( claro_debug_mode() )
            {
                $duration = microtime() - $start;
                $info = 'execution time : ' . ($duration > 0.001 ? '<b>' . round($duration,4) . '</b>':'&lt;0.001')  . '&#181;s'  ;
                $info .= ': affected rows :' . $this->affectedRows();

                pushClaroMessage( '<br />Query counter : <b>' . $this->queryCounter++ . '</b> : ' . $info . '<br />'
                    . '<code><span class="sqlcode">' . nl2br($sql) . '</span></code>'
                    , 'error' );
            }

            throw $e;
        }
    }

    /**t
     * @see Database_Connection
     */
    public function query( $sql )
    {
        if ( claro_debug_mode() && get_conf('CLARO_PROFILE_SQL',false) )
        {
            $start = microtime();
        }

        try
        {
            $result =  parent::query( self::prepareQueryForExecution( $sql ) );

            if ( claro_debug_mode() && get_conf('CLARO_PROFILE_SQL',false) )
            {
                $duration = microtime() - $start;
                $info = 'execution time : ' . ($duration > 0.001 ? '<b>' . round($duration,4) . '</b>':'&lt;0.001')  . '&#181;s'  ;
                $info .= ': affected rows :' . $this->affectedRows();

                pushClaroMessage( '<br />Query counter : <b>' . $this->queryCounter++ . '</b> : ' . $info . '<br />'
                    . '<code><span class="sqlcode">' . nl2br($sql) . '</span></code>'
                    , 'sqlinfo' );
            }

            return $result;
        }
        catch ( Database_Exception $e )
        {
            if ( claro_debug_mode() )
            {
                $duration = microtime() - $start;
                $info = 'execution time : ' . ($duration > 0.001 ? '<b>' . round($duration,4) . '</b>':'&lt;0.001')  . '&#181;s'  ;
                $info .= ': affected rows :' . $this->affectedRows();

                pushClaroMessage( '<br />Query counter : <b>' . $this->queryCounter++ . '</b> : ' . $info . '<br />'
                    . '<code><span class="sqlcode">' . nl2br($sql) . '</span></code>'
                    , 'error' );
            }

            throw $e;
        }
    }

    /**
     * This function is available only for backward compatibility in sql.lib.php
     * DO NOT USE IT IN YOUR SCRIPTS !
     * @since Claroline 1.10
     * @deprecated since Claroline 1.10
     * @return resource
     */
    public function getDbLink()
    {
        return $this->dbLink;
    }

    /**
     * This function is available only for bacward compatibility in sql.lib.php.
     * DO NOT USE IT IN YOUR SCRIPTS !
     * @since Claroline 1.10
     * @deprecated since Claroline 1.10
     */
    public function getQueryCounter()
    {
        return $this->queryCounter;
    }

    /**
     * This function is available only for bacward compatibility in sql.lib.php.
     * DO NOT USE IT IN YOUR SCRIPTS !
     * @since Claroline 1.10
     * @deprecated since Claroline 1.10
     * @return $this
     */
    public function incrementQueryCounter()
    {
        $this->queryCounter++;
        return $this;
    }

    /**
     * Replace the Claroline SQL place holders __CL_MAIN__ and __CL_COURSE__ by
     * the corresponding value in the given SQL query
     * @param string $sql
     * @return string
     * @since Claroline 1.10
     */
    protected static function prepareQueryForExecution( $sql )
    {
        $sql = str_replace ('__CL_MAIN__',get_conf('mainTblPrefix'), $sql);

        if ( claro_is_in_a_course() )
        {
            $currentCourseDbNameGlu = claro_get_course_data(
                claro_get_current_course_id(), 'dbNameGlu');

            $sql = str_replace('__CL_COURSE__', $currentCourseDbNameGlu['dbNameGlu'], $sql );
        }
        else
        {
            if ( preg_match( '/__CL_COURSE__/', $sql ) )
            {
                throw new Exception( "Trying to execute course SQL query while not in a course contexte" );
            }
        }

        return $sql;
    }
}

/**
 * Database Object usable by Database_ResultSet in FETCH_CLASS mode
 * @since Claroline 1.9.5
 */
interface Database_Object
{
    /**
     *
     * @param array $data
     */
    public static function getInstance( $data );
}

/**
 * Database_ResultSet generic interface
 */
interface Database_ResultSet extends CountableSeekableIterator// SeekableIterator, Countable
{
    /**
     * Associative array fetch mode constant, default mode if no one specified
     */
    const FETCH_ASSOC = 'FETCH_ASSOC';
    
    /**
     * Numeric index array fetch mode constant
     */
    const FETCH_NUM = 'FETCH_NUM';
    
    /**
     * Associative and numeric array fetch mode constant
     */
    const FETCH_BOTH = 'FETCH_BOTH';
    
    /**
     * Object fetch mode constant
     */
    const FETCH_OBJECT = 'FETCH_OBJECT';
    
    /**
     * Fetch the value of the first column  of the first row of the result set
     */
    const FETCH_VALUE = 'FETCH_VALUE';
    
    /**
     * Fetch the value of the first column of each row of the result set
     */
    const FETCH_COLUMN = 'FETCH_COLUMN';

    /**
     * Fetch the next rows as a new instance of the class name specified as the
     * second argument of Database_ResultSet::setFetchMode
     *
     * This class must implement the magic static __set_state method
     * @since Claroline 1.9.5
     */
    const FETCH_CLASS = 'FETCH_CLASS';
    
    /**
     * Set fetch mode. If not set, the default mode is FETCH_ASSOC
     * @param   string $mode fetch mode
     * @param   string class name used for the FETCH_CLASS mode, this class
     *  need to implement all the method in the Database_Object interface
     * @return  $this for chaining
     */
    public function setFetchMode( $mode, $className = null );
    
    /**
     * Get the next row in the Result Set
     * @param   string $mode fetch mode (optional, use internal fetch mode :
     *      FETCH_ASSOC by default or set by setFetchMode())
     * @param   string class name used for the FETCH_CLASS mode, this class
     *  need to implement all the method in the Database_Object interface
     * @return  mixed result row, returned data type depends on fetch mode :
     *      FETCH_ASSOC, FETCH_NUM or FETCH_BOTH : array
     *      FETCH_OBJECT : object representation of the current row
     *      FETCH_CLASS : instance of the class name given
     *      FETCH_VALUE : value of the first field in the current row
     * @throws Claroline_Database_Exception
     */
    public function fetch( $mode = null, $className = null );
    
    /**
     * Get the number of rows in the result set
     * @return  int
     */
    public function numRows();
    
    /**
     * Check if the result set is empty
     * @return  boolean
     */
    public function isEmpty();

    /**
     * Set the name of the resultset key to be used as the row id in the
     * iterator if not, the rank of the row in the result set will be used
     * WARNING This will work only with FETCH_ASSOC, FETCH_OBJECT, FETCH_CLASS
     * and FETCH_BOTH. In FETCH_NUM, FETCH_VALUE and FETCH_ROW mode, the id key
     * name will be ignored and the return key will be the rank of the row in
     * the result set not the value of the key $idKeyName !
     * @param string $idKeyName
     * @return $this for chaining
     */
    public function useId( $idKeyName );
}

/**
 * Mysql _Database_Connection Result Set class
 * implements iterator and countable interfaces for
 * array-like behaviour.
 */
class Mysql_ResultSet implements Database_ResultSet
{
    protected
        $mode,
        $className,
        $idx,
        $valid,
        $numrows,
        $resultSet,
        $idKeyName,
        $idKeyValue;
    
    /**
     * @param   resource $result Mysql native resultset
     */
    public function __construct( $result )
    {
        if ( $result )
        {
            $this->resultSet = $result;
            $this->mode = self::FETCH_ASSOC;
            
            // set to 0 if false;
            $this->numrows = (int) @mysql_num_rows( $this->resultSet );
            $this->idx = 0;
            $this->idKeyName = null;
            $this->idKeyValue = null;
        }
        else
        {
            throw new Database_Connection_Exception("Invalid SQL result passed to " . __CLASS__);
        }
    }
    
    public function __destruct()
    {
        if ( $this->resultSet )
        {
            @mysql_free_result($this->resultSet);
        }
        
        unset( $this->resultSet );
        unset( $this->numrows );
        unset( $this->mode );
        unset( $this->valid );
        unset( $this->idx );
        unset( $this->idKeyName );
        unset( $this->idKeyValue );
    }
    
    // --- Database_ResultSet  ---
    
    /**
     * Set fetch mode
     * @param   string $mode fetch mode
     * @param   string class name used for the FETCH_CLASS mode, this class
     *  need to implement all the method in the Database_Object interface
     * @see     Database_ResultSet
     * @return $this
     */
    public function setFetchMode( $mode, $className = null )
    {
        $this->mode = $mode;

        if ( $this->mode == self::FETCH_CLASS )
        {
            $this->className = $className;
        }

        return $this;
    }
    
    /**
     * Set the name of the resultset key to be used as the row id in the
     * iterator if not, the rank of the row in the result set will be used
     * @param string $idKeyName
     * @return $this for chaining
     * @see Database_ResultSet
     * @since Claroline 1.9.7
     */
    public function useId( $idKeyName )
    {
        $this->idKeyName = $idKeyName;
    }
    
    /**
     * Get the number of rows in the result set
     * @see     Database_ResultSet
     * @return  int
     */
    public function numRows()
    {
        return $this->numrows;
    }
    
    /**
     * Check if the result set is empty
     * @see     Database_ResultSet
     * @return  boolean
     */
    public function isEmpty()
    {
        return (0 == $this->numRows());
    }
    
    /**
     * Get the next row in the Result Set
     * @see     Database_ResultSet
     * @param   string $mode fetch mode (optional, use internal fetch mode :
     *      FETCH_ASSOC by default or set by setFetchMode())
     * @param   string class name used for the FETCH_CLASS mode, this class
     *  need to implement all the method in the Database_Object interface
     * @return  mixed result row, returned data type depends on fetch mode :
     *      FETCH_ASSOC, FETCH_NUM or FETCH_BOTH : array
     *      FETCH_OBJECT : object representation of the current row
     *      FETCH_CLASS : instance of the class name given
     *      FETCH_VALUE : value of the first field in the current row
     * @throws Claroline_Database_Exception
     */
    public function fetch( $mode = null, $className = null )
    {
        $mode = empty( $mode ) ? $this->mode : $mode;
        $className = empty( $className ) ? $this->className : $className;
        
        if ( $mode == self::FETCH_CLASS )
        {
            if ( empty ( $className )
                || ! class_exists( $className )
                || ! is_callable ( array( $className, 'getInstance' ) ) )
            {
                throw new Claroline_Database_Exception( "Cannot instanciate class {$className}" );
            }

            $row = @mysql_fetch_array( $this->resultSet, MYSQL_ASSOC );

            $this->setIdFromKeyValue( $mode, $row );

            $obj = call_user_func( array($className, 'getInstance' ), $row );

            return $obj;
        }
        elseif ( $mode == self::FETCH_OBJECT )
        {
            $row = @mysql_fetch_object( $this->resultSet );
            $this->setIdFromKeyValue( $mode, $row );

            return $row;
        }
        // FIXME : FETCH_VALUE should not be called twice !
        elseif ( $mode == self::FETCH_VALUE || $mode == self::FETCH_COLUMN )
        {
            $res = @mysql_fetch_array( $this->resultSet, MYSQL_NUM );
            
            // use side effect of the [] operator : will return null if !$res
            return $res[0];
        }
        else
        {
            $row = @mysql_fetch_array( $this->resultSet, $this->mysqlFetchMode( $mode ) );
            $this->setIdFromKeyValue( $mode, $row );

            return $row;
        }
    }

    /**
     *
     * @param <type> $fetchmode
     * @param <type> $row
     */
    protected function setIdFromKeyValue( $mode, $row )
    {
        if ( is_null($this->idKeyName) )
        {
            $this->idKeyValue = null;
        }
        elseif ( $mode == self::FETCH_ASSOC
            || $mode == self::FETCH_BOTH
            || $mode == self::FETCH_CLASS
            || $mode == self::FETCH_OBJECT )
        {
            if ( $mode == self::FETCH_OBJECT )
            {
                $data = (array)$row;
            }
            else
            {
                $data = $row;
            }

            if ( ! array_key_exists( $this->idKeyName, $data) )
            {
                throw new Database_Connection_Exception("Id key {$this->idKeyName} not found in the current row");
            }

            $this->idKeyValue = $data[$this->idKeyName];
        }
        else
        {
            $this->idKeyValue = null;
        }
    }

    protected function mysqlFetchMode( $mode )
    {
        switch ( $mode )
        {
            case self::FETCH_ASSOC:
                return MYSQL_ASSOC;

            case self::FETCH_NUM:
                return MYSQL_NUM;
            
            case self::FETCH_BOTH:
            default:
                return MYSQL_BOTH;
        }
    }
    
    // --- Countable  ---
    
    /**
     * Count the number of rows in the result set
     * Usage :
     *      $size = count( $resultSet );
     *
     * @see     Countable
     * @return  int size of the result set (ie number of rows)
     */
    public function count()
    {
        return $this->numRows();
    }
    
    // --- Iterator ---
    
    /**
     * Check if the current position in the result set is valid
     * @see     Iterator
     * @return  boolean
     */
    public function valid()
    {
        return $this->valid;
    }
    
    /**
     * Return the current row
     * @see     Iterator
     * @see     Database_ResultSet::fetch() for return value data type
     * @return  mixed, current row
     */
    public function current()
    {
        // Go to the correct data
        $this->seek( $this->idx );
        
        return $this->fetch( $this->mode );
    }
    
    /**
     * Advance to the next row in the result set
     * @see     Iterator
     */
    public function next()
    {
        $this->idx++;
        $this->valid = $this->idx < $this->numRows();
    }
    
    /**
     * Rewind to the first row
     * @see     Iterator
     */
    public function rewind()
    {
        $this->idx = 0;
        $this->idKeyValue = null;
        
        if ( $this->numRows() )
        {
            $this->valid = @mysql_data_seek( $this->resultSet, 0 );
        }
        else
        {
            $this->valid = false;
        }
    }
    
    /**
     * Return the index of the current row
     * @see     Iterator
     * @return  int
     */
    public function key()
    {
        if ( is_null($this->idKeyValue) )
        {
            return $this->idx;
        }
        else
        {
            return $this->idKeyValue;
        }
    }
    
    // --- SeekableIterator ---
    
    /**
     * Usage :
     *      $resultSet->seek( 5 );
     *      $r = $resultSet->fetch();
     *
     * @see     SeekableIterator
     * @param   int $idx
     * @return  void
     * @throws  OutOfBoundsException if invalid index
     */
    public function seek( $idx )
    {
        if ( $idx < $this->numRows()
            && $idx >= 0
            && ! $this->isEmpty()
            && $this->valid() )
        {
            $this->idx = $idx;
            @mysql_data_seek( $this->resultSet, $this->idx );
        }
        else
        {
            throw new OutOfBoundsException('Invalid seek position');
        }
    }
}
