<?php // $Id: installer.class.php 13348 2011-07-18 13:58:28Z abourguignon $

if ( count( get_included_files() ) == 1 ) die( basename(__FILE__) );

/**
 * CLAROLINE
 *
 * Claroline installer class
 * Moved from install.lib.inc.php to avoid fatal error in PHP4 before checking
 * requirements.
 *
 * @version     $Revision: 13348 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Install
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 * @author      Sebastien Piraux <seb@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @package     INSTALL
 */

/**
 * Installer class
 */
class ClaroInstaller
{
    protected $mainTblPrefix, $statsTblPrefix;
    
    public function __construct( $mainTblPrefix, $statsTblPrefix )
    {
        $this->mainTblPrefix = $mainTblPrefix;
        $this->statsTblPrefix = $statsTblPrefix;
    }
    
    public function executeSqlScript( $sqlStr, $onErrorCallback = false )
    {
        $queries = $this->pmaParse( $sqlStr );
        
        if ( ! $onErrorCallback )
        {
            $onErrorCallback = array( $this, 'onErrorCallback' );
        }
        
        foreach ( $queries as $query )
        {
            if ( ! mysql_query( $this->toClaroQuery( $query['query'] ) ) )
            {
                
                if ( call_user_func( $onErrorCallback,
                        $query, mysql_error(), mysql_errno() ) )
                {
                    continue;
                }
                else
                {
                    throw new Exception( mysql_error(), mysql_errno() );
                }
            }
        }
    }
    
    public function onErrorCallback( $query, $error, $errno )
    {
        return false;
    }
    
    public function toClaroQuery( $sql )
    {
        // replace __CL_MAIN__ with main database prefix
        $sql = str_replace ( '__CL_MAIN__', $this->mainTblPrefix, $sql );
        // replace __CL_MAIN__ with main database prefix
        $sql = str_replace ( '__CL_STATS__', $this->statsTblPrefix, $sql );
        
        return $sql;
    }
    
    protected function pmaParse( $sql )
    {
        $ret = array();
        
        $sql          = rtrim($sql, "\n\r");
        $sql_len      = strlen($sql);
        $char         = '';
        $string_start = '';
        $in_string    = false;
        $nothing      = true;
        
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
                        $in_string         = false;
                        break;
                    }
                    // one or more Backslashes before the presumed end of string...
                    else
                    {
                        // ... first checks for escaped backslashes
                        $j                     = 2;
                        $escaped_backslash     = false;
                        while ($i-$j > 0 && $sql[$i-$j] == '\\') {
                            $escaped_backslash = !$escaped_backslash;
                            $j++;
                        }
                        // ... if escaped backslashes: it's really the end of the
                        // string -> exit the loop
                        if ($escaped_backslash)
                        {
                            $string_start  = '';
                            $in_string     = false;
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
                if ($i === false)
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
                $nothing    = true;
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
                $in_string    = true;
                $nothing      = false;
                $string_start = $char;
            } // end else if (is start of string)
            elseif ($nothing)
            {
                $nothing = false;
            }
        } // end for
    
        // add any rest to the returned array
        if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql))
        {
            $ret[] = array('query' => $sql, 'empty' => $nothing);
        }
        
        return $ret;
    }
}