<?php // $Id: extra.lib.php 14427 2013-04-22 13:34:49Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Light Object-Oriented Database Layer for Claroline :
 * Advanced API
 *
 * @version     $Revision: 14427 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.database
 */

require_once dirname(__FILE__) . '/database.lib.php';

class Database_Multiple_Query
{
    protected $sqlQueryArray = array();
    protected $onErrorCallback = null;
    
    public function __construct( $sqlQueryString )
    {
        $this->sqlQueryArray = $this->parse( $sqlQueryString );
    }
    
    public function setErrorCallback( $callback )
    {
        $this->onErrorCallback = $callback;
    }
    
    public function exec()
    {
        $this->executeQueries( $this->sqlQueryArray );
    }
    
    protected function parse( $sql )
    {
        $ret = array();
        
        $sql          = rtrim($sql, "\n\r");
        $sql_len      = strlen($sql);
        $char         = '';
        $string_start = '';
        $in_string    = FALSE;
        $nothing      = TRUE;
        
        for ($i = 0; $i < $sql_len; ++$i)
        {
            $char = $sql[$i];
            // We are in a string, check for not escaped end of strings except for
            // backquotes that can't be escaped
            if ($in_string)
            {
                for (;;)
                {
                    $i         = strpos($sql, $string_start, $i);
                    // No end of string found -> add the current substring to the
                    // returned array
                    if (!$i)
                    {
                        $ret[] = array('query' => $sql, 'empty' => $nothing);
                        return $ret;
                    }
                    // Backquotes or no backslashes before quotes: it's indeed the
                    // end of the string -> exit the loop
                    else if ($string_start == '`' || $sql[$i-1] != '\\')
                    {
                        $string_start      = '';
                        $in_string         = FALSE;
                        break;
                    }
                    // one or more Backslashes before the presumed end of string...
                    else
                    {
                        // ... first checks for escaped backslashes
                        $j                     = 2;
                        $escaped_backslash     = FALSE;
                        while ($i-$j > 0 && $sql[$i-$j] == '\\') {
                            $escaped_backslash = !$escaped_backslash;
                            $j++;
                        }
                        // ... if escaped backslashes: it's really the end of the
                        // string -> exit the loop
                        if ($escaped_backslash)
                        {
                            $string_start  = '';
                            $in_string     = FALSE;
                            break;
                        }
                        // ... else loop
                        else
                        {
                            $i++;
                        }
                    } // end if...elseif...else
                } // end for
            } // end if (in string)
            
            // lets skip comments (/*, -- and #)
            else if (($char == '-' && $sql_len > $i + 2 && $sql[$i + 1] == '-' && $sql[$i + 2] <= ' ')
                || $char == '#' || ($char == '/' && $sql_len > $i + 1 && $sql[$i + 1] == '*'))
            {
                $i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
                // didn't we hit end of string?
                if ($i === FALSE)
                {
                    break;
                }
                if ($char == '/') $i++;
            }
            
            // We are not in a string, first check for delimiter...
            else if ($char == ';')
            {
                // if delimiter found, add the parsed part to the returned array
                $ret[]      = array('query' => substr($sql, 0, $i), 'empty' => $nothing);
                $nothing    = TRUE;
                $sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
                $sql_len    = strlen($sql);
                if ($sql_len)
                {
                    $i      = -1;
                }
                else
                {
                    // The submited statement(s) end(s) here
                    return $ret;
                }
            } // end else if (is delimiter)
    
            // ... then check for start of a string,...
            else if (($char == '"') || ($char == '\'') || ($char == '`'))
            {
                $in_string    = TRUE;
                $nothing      = FALSE;
                $string_start = $char;
            } // end else if (is start of string)
            elseif ($nothing)
            {
                $nothing = FALSE;
            }
        } // end for
    
        // add any rest to the returned array
        if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql))
        {
            $ret[] = array('query' => $sql, 'empty' => $nothing);
        }
    
        return $ret;
    }
    
    protected function executeQueries()
    {
        foreach ( $this->sqlQueryArray as $query )
        {
            $sql = $query['query'];
            
            try
            {    
                Claroline::getDatabase()->exec( $sql );
            }
            catch( Exception $e )
            {
                if ( ! is_null( $this->onErrorCallback )
                    && is_callable( $this->onErrorCallback )
                    && call_user_func( $this->onErrorCallback, $sql, $e ) )
                {
                    continue;
                }
                else
                {
                    throw $e;
                }
            }
        }
    }
}

class Database_PreparedStatement
{
    protected $dbConn;
    protected $sql;
    protected $mode;
    
    const MODE_KEY = 1;
    const MODE_QUEST = 2;
    const MODE_NONE = 3;
    
    public function __construct( $database )
    {
        $this->dbConn = $database;
    }
    
    public function prepare( $sql )
    {
        $this->sql = $sql;
        $this->parse( $sql );
    }
    
    public function query( $params = array() )
    {
        return $this->dbConn->query( $this->transformQuery( $this->sql, $params ) );
    }
    
    public function exec( $params = array() )
    {
        return $this->dbConn->exec( $this->transformQuery( $this->sql, $params ) );
    }
    
    protected function transformQuery( $sql, $params )
    {
        switch( $this->mode )
        {
            case self::MODE_KEY:
                if ( empty( $params ) )
                {
                    throw new Database_Connection_Exception('Empty parameters passed to query');
                }
                
                foreach ( $params as $key => $value )
                {
                    preg_replace( "/:{$key}\W/", $this->dbConn->quote($value), $sql );
                }
                
                return $sql;
            
            case self::MODE_QUEST:
                if ( empty( $params ) )
                {
                    throw new Database_Connection_Exception('Empty parameters passed to query');
                }
                
                $sqlArr = explode( '?', $sql );
                
                if ( count( $sqlArr ) - count( $params ) != 1 )
                {
                    throw new Database_Connection_Exception('Wrong number of parameters passed to query');
                }
                
                $sql = '';
                
                foreach ( $sqlArr as $part )
                {
                    $sql .= $part . ' ';
                    
                    if ( !empty($params) )
                    {
                        $sql .= $this->dbConn->quote( array_shift( $params ) ) . ' ';
                    }
                }
                
                $sql = rtrim( $sql );
                
                return $sql;
            
            case self::MODE_NONE:
                return $sql;
            
            default:
                throw new Database_Connection_Exception('Invalid mode');
        }
    }
    
    protected function parse( $sql )
    {
        // if :key
        if ( preg_match( '/\:\w+/', $sql ) )
        {
            $this->mode = self::MODE_KEY;
        }
        // else if ?
        elseif ( preg_match('/\?\W/', $sql ) )
        {
            $this->mode = self::MODE_QUEST;
        }
        else
        {
            $this->mode = self::MODE_NONE;
        }
    }
}

class Claroline_PreparedStatement extends Database_PreparedStatement
{
    public function __construct()
    {
        parent::__construct( Claroline::getDatabase() );
    }
}
